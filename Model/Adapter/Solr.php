<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Model\Adapter;

use Codilar\SolrConnector\Model\Api;
use Codilar\SolrConnector\Model\Config;
use Codilar\SolrConnector\Model\Data\ToolbarParametersFactory;
use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\Registry;
use Magento\Framework\Search\Adapter\Mysql\Mapper;
use Magento\Framework\Search\Adapter\Mysql\ResponseFactory;
use Magento\Framework\Search\Adapter\Mysql\TemporaryStorageFactory;
use Magento\Framework\Search\AdapterInterface;
use Magento\Framework\Search\RequestInterface;
use Magento\Framework\Search\Response\QueryResponse;
use Magento\Framework\Search\Adapter\Mysql\Aggregation\Builder as AggregationBuilder;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogSearch\Helper\Data as CatalogSearchHelper;

class Solr implements AdapterInterface
{

    const TOOLBAR_PARAMETERS_REGISTRY_KEY = "_codilar_solrconnector_toolbar_parameters";

    /**
     * @var ResponseFactory
     */
    private $responseFactory;
    /**
     * @var AggregationBuilder
     */
    private $aggregationBuilder;
    /**
     * @var Mapper
     */
    private $mapper;
    /**
     * @var TemporaryStorageFactory
     */
    private $temporaryStorageFactory;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var CatalogSearchHelper
     */
    private $catalogSearchHelper;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var Monolog
     */
    private $monolog;
    /**
     * @var Toolbar
     */
    private $toolbar;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var ToolbarParametersFactory
     */
    private $toolbarParametersFactory;

    /**
     * Solr constructor.
     * @param ResponseFactory $responseFactory
     * @param AggregationBuilder $aggregationBuilder
     * @param Mapper $mapper
     * @param TemporaryStorageFactory $temporaryStorageFactory
     * @param CatalogSearchHelper $catalogSearchHelper
     * @param Config $config
     * @param StoreManagerInterface $storeManager
     * @param Api $api
     * @param Json $json
     * @param Monolog $monolog
     * @param Toolbar $toolbar
     * @param Registry $registry
     * @param ToolbarParametersFactory $toolbarParametersFactory
     */
    public function __construct(
        ResponseFactory $responseFactory,
        AggregationBuilder $aggregationBuilder,
        Mapper $mapper,
        TemporaryStorageFactory $temporaryStorageFactory,
        CatalogSearchHelper $catalogSearchHelper,
        Config $config,
        StoreManagerInterface $storeManager,
        Api $api,
        Json $json,
        Monolog $monolog,
        Toolbar $toolbar,
        Registry $registry,
        ToolbarParametersFactory $toolbarParametersFactory
    )
    {
        $this->responseFactory = $responseFactory;
        $this->aggregationBuilder = $aggregationBuilder;
        $this->mapper = $mapper;
        $this->temporaryStorageFactory = $temporaryStorageFactory;
        $this->api = $api;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->catalogSearchHelper = $catalogSearchHelper;
        $this->json = $json;
        $this->monolog = $monolog;
        $this->toolbar = $toolbar;
        $this->registry = $registry;
        $this->toolbarParametersFactory = $toolbarParametersFactory;
    }

    /**
     * Process Search Request
     *
     * @param RequestInterface $request
     * @return QueryResponse
     */
    public function query(RequestInterface $request)
    {
        $query = $this->mapper->buildQuery($request);
        $temporaryStorage = $this->temporaryStorageFactory->create();
        $table = $temporaryStorage->storeDocumentsFromSelect($query);

        $documents = $this->getDocuments($table);

        $aggregations = $this->aggregationBuilder->build($request, $table, $documents);
        $response = [
            'documents' => $documents,
            'aggregations' => $aggregations,
        ];
        return $this->responseFactory->create($response);
    }

    /**
     * @param \Magento\Framework\DB\Ddl\Table $table
     * @return array
     */
    protected function getDocuments($table) {
        $collection = $this->config->getCollectionName(
            $this->storeManager->getStore()->getCode(),
            Config::COLLECTION_TYPE_PRODUCT
        );
        $query = $this->catalogSearchHelper->getEscapedQueryText();
        $pageSize = $this->toolbar->getLimit();
        $curPage = $this->toolbar->getCurrentPage();
        try {
            $searchableAttributes = [];
            foreach ($this->config->getProductSchema() as $item) {
                if ($item['indexed']) {
                    $searchableAttributes[] = $item['name'];
                }
            }
            $curl = $this->api->search(
                $collection,
                $query,
                $searchableAttributes,
                $pageSize,
                $curPage
            );
            $response = $this->json->unserialize($curl->getBody());
            if (!$response['responseHeader']['status'] === 0) {
                throw new LocalizedException(__("Solr server error occurred"));
            }
            $documents = array_map([$this, 'buildDocument'], $response['response']['docs']);
            $this->calculateToolbarParameters($response);
        } catch (LocalizedException $localizedException) {
            $this->monolog->error("SOLR: ".$localizedException->getMessage());
            $documents = [];
        } catch (\InvalidArgumentException $invalidArgumentException) {
            $this->monolog->error("SOLR: ".$invalidArgumentException->getMessage());
            $documents = [];
        }
        return $documents;
    }

    protected function buildDocument($product) {
        return [
            'entity_id' => $product['id'],
            'score'     => 0
        ];
    }

    protected function calculateToolbarParameters($response) {
        $totalResults = $response['response']['numFound'];
        $pageSize = $this->toolbar->getLimit();
        $curPage = $this->toolbar->getCurrentPage();
        /* @var \Codilar\SolrConnector\Model\Data\ToolbarParameters $toolbarParameters */
        $toolbarParameters = $this->toolbarParametersFactory->create();
        $toolbarParameters
            ->setFirstNum($pageSize * ($curPage - 1) + 1)
            ->setLastNum($pageSize * ($curPage - 1) + count($response['response']['docs']))
            ->setTotalNum($totalResults)
            ->setLastPageNum(ceil($totalResults / $pageSize))
            ->setCurrentPage($curPage);
        $this->registry->register(self::TOOLBAR_PARAMETERS_REGISTRY_KEY, $toolbarParameters);
    }
}