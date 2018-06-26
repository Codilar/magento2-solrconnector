<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Block;

use Codilar\SolrConnector\Model\Config;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Search\Helper\Data as SearchHelper;

class Solr extends Template
{
    /**
     * @var SearchHelper
     */
    private $searchHelper;

    /**
     * @var Json
     */
    private $json;
    /**
     * @var Config
     */
    private $config;

    /**
     * Solr constructor.
     * @param Template\Context $context
     * @param SearchHelper $searchHelper
     * @param Json $json
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        SearchHelper $searchHelper,
        Json $json,
        Config $config,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->searchHelper = $searchHelper;
        $this->json = $json;
        $this->config = $config;
    }

    protected function _prepareLayout()
    {
        $this->setTemplate("Codilar_SolrConnector::autocomplete.phtml");
        return parent::_prepareLayout();
    }

    /**
     * @return SearchHelper
     */
    public function getSearchHelper() {
        return $this->searchHelper;
    }

    public function getConfigJson() {
        return $this->json->serialize([
            'currency_code'         =>  $this->getStore()->getCurrentCurrency()->getCurrencySymbol(),
            'useUserDefinedTemplate'=>  $this->config->isAutocompleteUserDefined(),
            'template'              =>  $this->config->isAutocompleteUserDefined() ? $this->config->getAutocompleteTemplate() : "Codilar_SolrConnector/template/autocomplete.html",
            'dataChangedEvent'      =>  $this->getJsAutocompleteDataChangedEventName(),
            'sections'              =>  $this->getSections()
        ]);
    }

    /**
     * @return array
     */
    public function getSections() {
        $productCollection = $this->config->getCollectionName($this->getStore()->getCode(), Config::COLLECTION_TYPE_PRODUCT);
        $categoryCollection = $this->config->getCollectionName($this->getStore()->getCode(), Config::COLLECTION_TYPE_CATEGORY);

        $productSearchableAttributes = [];
        foreach ($this->config->getProductSchema() as $item) {
            if ($item['indexed']) {
                $productSearchableAttributes[] = $item['name'];
            }
        }

        $categorySearchableAttributes = [];
        foreach ($this->config->getCategorySchema() as $item) {
            if ($item['indexed']) {
                $categorySearchableAttributes[] = $item['name'];
            }
        }

        return [
            "products"      => [
                "url"                       =>  $this->config->getSearchUrl($productCollection),
                "index"                     =>  $productCollection,
                "searchable_attributes"     =>  $productSearchableAttributes,
                "count"                     =>  $this->config->getAutocompleteProductCount()
            ],
            "categories"    => [
                "url"                       =>  $this->config->getSearchUrl($categoryCollection),
                "index"                     =>  $categoryCollection,
                "searchable_attributes"     =>  $categorySearchableAttributes,
                "count"                     =>  $this->config->getAutocompleteCategoryCount()
            ]
        ];
    }

    public function getJsAutocompleteDataChangedEventName() {
        return "_codilar_solrconnector_autocomplete_data_changed";
    }

    /**
     * @return \Magento\Store\Model\Store
     */
    public function getStore() {
        /* @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore();
        return $store;
    }
}