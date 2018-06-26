<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;

class Config extends DataObject
{
    const IS_ENABLED_XML_KEY = "codilar_solrconnector/general/is_enabled";
    const SOLR_URL_XML_KEY = "codilar_solrconnector/general/solr_url";
    const INDEX_NAME_XML_KEY = "codilar_solrconnector/general/index_name";
    const AUTOCOMPLETE_PRODUCT_ROWS_XML_KEY = "codilar_solrconnector/autocomplete/product_rows";
    const AUTOCOMPLETE_CATEGORY_ROWS_XML_KEY = "codilar_solrconnector/autocomplete/category_rows";
    const AUTOCOMPLETE_IS_USER_DEFINED_XML_KEY = "codilar_solrconnector/autocomplete/autocomplete_is_user_defined";
    const AUTOCOMPLETE_TEMPLATE_XML_KEY = "codilar_solrconnector/autocomplete/autocomplete_template";
    const PRODUCT_SCHEMA_XML_KEY = "codilar_solrconnector/product_schema/attributes";
    const CATEGORY_SCHEMA_XML_KEY = "codilar_solrconnector/category_schema/attributes";

    const CATALOG_SEARCH_ENGINE_XML_KEY = "catalog/search/engine";

    const COLLECTION_TYPE_PRODUCT = "product";
    const COLLECTION_TYPE_CATEGORY = "category";

    /**
     * @var Json
     */
    private $json;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * Config constructor.
     * @param Json $json
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigWriter $configWriter
     * @param array $data
     */
    public function __construct(
        Json $json,
        ScopeConfigInterface $scopeConfig,
        ConfigWriter $configWriter,
        array $data = []
    )
    {
        $this->json = $json;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        parent::__construct($data);
    }

    /**
     * @return bool
     */
    public function isEnabled() {
        return (bool)$this->getValue(self::IS_ENABLED_XML_KEY);
    }

    public function getUrl() {
        return (string)$this->getValue(self::SOLR_URL_XML_KEY);
    }

    /**
     * @param string $collection
     * @return string
     */
    public function getSearchUrl($collection) {
        return $this->getUrl()."solr/$collection/select";
    }

    /**
     * @return string
     */
    public function getIndexName() {
        return (string)$this->getValue(self::INDEX_NAME_XML_KEY);
    }

    /**
     * @return int
     */
    public function getAutocompleteProductCount() {
        return (int)$this->getValue(self::AUTOCOMPLETE_PRODUCT_ROWS_XML_KEY);
    }

    /**
     * @return int
     */
    public function getAutocompleteCategoryCount() {
        return (int)$this->getValue(self::AUTOCOMPLETE_CATEGORY_ROWS_XML_KEY);
    }


    /**
     * @return bool
     */
    public function isAutocompleteUserDefined() {
        return (bool)$this->getValue(self::AUTOCOMPLETE_IS_USER_DEFINED_XML_KEY);
    }

    /**
     * @return string
     */
    public function getAutocompleteTemplate() {
        return (string)$this->getValue(self::AUTOCOMPLETE_TEMPLATE_XML_KEY);
    }

    /**
     * @return array
     */
    public function getProductSchema() {
        try {
            if (!$this->getData("productSchema")) {
                $productSchema = $this->json->unserialize($this->getValue(self::PRODUCT_SCHEMA_XML_KEY));
                foreach ($productSchema as $key => $value) {
                    $value['indexed'] = (bool)$value['indexed'];
                    $value['stored'] = (bool)$value['stored'];
                    $productSchema[$key] = $value;
                }
                $this->setData("productSchema", $productSchema);
            }
            return $this->getData('productSchema');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            return [];
        }
    }

    /**
     * @return array
     */
    public function getCategorySchema() {
        try {
            if (!$this->getData("categorySchema")) {
                $productSchema = $this->json->unserialize($this->getValue(self::CATEGORY_SCHEMA_XML_KEY));
                foreach ($productSchema as $key => $value) {
                    $value['indexed'] = (bool)$value['indexed'];
                    $value['stored'] = (bool)$value['stored'];
                    $productSchema[$key] = $value;
                }
                $this->setData("categorySchema", $productSchema);
            }
            return $this->getData('categorySchema');
        } catch (\InvalidArgumentException $invalidArgumentException) {
            return [];
        }
    }

    /**
     * @param array $schema
     * @return $this
     */
    public function setProductSchema($schema) {
        $this->saveConfig(
            self::PRODUCT_SCHEMA_XML_KEY,
            $this->json->serialize($schema),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
        return $this;
    }

    /**
     * @param array $schema
     * @return $this
     */
    public function setCategorySchema($schema) {
        $this->saveConfig(
            self::CATEGORY_SCHEMA_XML_KEY,
            $this->json->serialize($schema),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
        return $this;
    }

    /**
     * @param string $store
     * @param string $type
     * @return string
     */
    public function getCollectionName($store, $type) {
        $index = $this->getIndexName();
        return $index."_".$store."_".$type;
    }

    /**
     * @return string
     */
    public function getCurrentSearchEngine() {
        return (string)$this->getValue(self::CATALOG_SEARCH_ENGINE_XML_KEY);
    }

    /**
     * @param string $path
     * @param string $value
     * @param string $scope
     * @return $this
     */
    public function saveConfig($path, $value, $scope) {

        $this->configWriter->save($path, $value, $scope);
        return $this;
    }

    /**
     * @param $path
     * @return string
     */
    public function getValue($path) {
        $cacheKey = "_codilar_solrconnector_config_value_".str_replace("/", "_", $path);
        if (!$this->getData($cacheKey)) {
            $this->setData(
                $cacheKey,
                $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE)
            );
        }
        return $this->getData($cacheKey);
    }

}