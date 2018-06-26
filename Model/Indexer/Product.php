<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Model\Indexer;

use Codilar\SolrConnector\Model\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Codilar\SolrConnector\Model\Indexer\Data\Product as ProductData;
use Magento\Store\Model\StoreManagerInterface;

class Product implements ActionInterface, MviewActionInterface
{

    private $data = [];

    /**
     * @var Collection
     */
    private $collection;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var ProductData
     */
    private $productData;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Status
     */
    private $status;
    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * Product constructor.
     * @param ProductData $productData
     * @param Collection $collection
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param Status $status
     * @param Visibility $visibility
     */
    public function __construct(
        ProductData $productData,
        Collection $collection,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        Status $status,
        Visibility $visibility
    )
    {
        $this->collection = $collection;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productData = $productData;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->status = $status;
        $this->visibility = $visibility;
    }

    /**
     * Execute materialization on ids entities
     *
     * @param int[] $ids
     * @return void
     * @api
     */
    public function execute($ids)
    {
        foreach ($ids as $id) {
            $this->executeRow($id, false);
        }
        $this->send();
    }

    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull()
    {

        $products = $this->productRepository->getList($this->getSearchCriteria()->create())->getItems();
        foreach ($products as $product) {
            $this->executeRow($product->getId(), false);
        }
        $this->send();
    }

    /**
     * Execute partial indexation by ID list
     *
     * @param int[] $ids
     * @return void
     */
    public function executeList(array $ids)
    {
        foreach ($ids as $id) {
            $this->executeRow($id, false);
        }
        $this->send();
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @param bool $sendData
     * @return void
     */
    public function executeRow($id, $sendData = true)
    {
        if (!$this->collection->getConfig()->isEnabled()) {
            return;
        }
        $schema = $this->collection->getProductSchema();
        foreach ($this->collection->getStoreManager()->getStores() as $store) {
            $collectionName = $this->collection->getConfig()->getCollectionName($store->getCode(), Config::COLLECTION_TYPE_PRODUCT);
            $productData = $this->productData->loadById($id, $store->getId())->getData($schema);
            if ($productData) {
                $this->data[$collectionName][] = $productData;
            }
        }
        if ($sendData) {
            $this->send();
        }
    }

    public function getSearchCriteria() {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', $this->status->getVisibleStatusIds(),"in")
            ->addFilter('visibility', $this->visibility->getVisibleInSearchIds(), "in");
        return $searchCriteria;
    }

    protected function send() {
        foreach ($this->data as $collection => $object) {
            try {
                $eventParameters = new DataObject([
                    'collection' => $collection,
                    'object'     => $object
                ]);
                $this->collection->getEventManager()->dispatch('solr_product_sync_send_before', ['data' => $eventParameters]);
                $this->collection->getApi()->addObjects($eventParameters->getData('collection'), $eventParameters->getData('object'));
            } catch (LocalizedException $localizedException) {
                $this->collection->getLogger()->info("SOLR CONNECTOR: ".$localizedException->getMessage());
            }
        }
    }
}