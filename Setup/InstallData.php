<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Setup;


use Codilar\SolrConnector\Model\Config;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeList;
use Magento\Framework\App\Cache\Frontend\Pool as CacheFrontendPool;

class InstallData implements InstallDataInterface
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var Reader
     */
    private $reader;
    /**
     * @var CacheTypeList
     */
    private $cacheTypeList;
    /**
     * @var CacheFrontendPool
     */
    private $cacheFrontendPool;

    /**
     * InstallData constructor.
     * @param Config $config
     * @param UrlInterface $url
     * @param Reader $reader
     * @param StoreManagerInterface $storeManager
     * @param CacheTypeList $cacheTypeList
     * @param CacheFrontendPool $cacheFrontendPool
     */
    public function __construct(
        Config $config,
        UrlInterface $url,
        Reader $reader,
        StoreManagerInterface $storeManager,
        CacheTypeList $cacheTypeList,
        CacheFrontendPool $cacheFrontendPool
    )
    {
        $this->config = $config;
        $this->url = $url;
        $this->reader = $reader;
        $this->storeManager = $storeManager;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
    }

    protected $defaultProductAttributes = [
        [
            'name' => 'product_id',
            'indexed' => '0',
            'stored' => '1',
            'type'   => 'pints',
            'multiValued' => false
        ],
        [
            'name' => 'product_url',
            'indexed' => '0',
            'stored' => '1',
            'type'   => 'text_general',
            'multiValued' => false
        ],
        [
            'name' => 'name',
            'indexed' => '1',
            'stored' => '1',
            'type'   => 'text_general',
            'multiValued' => false
        ],
        [
            'name' => 'sku',
            'indexed' => '1',
            'stored' => '1',
            'type'   => 'text_general',
            'multiValued' => false
        ],
        [
            'name' => 'price',
            'indexed' => '0',
            'stored' => '1',
            'type'   => 'pdouble',
            'multiValued' => false
        ],
        [
            'name' => 'special_price',
            'indexed' => '0',
            'stored' => '1',
            'type'   => 'pdouble',
            'multiValued' => false
        ],
        [
            'name' => 'category_ids',
            'indexed' => '0',
            'stored' => '1',
            'type'   => 'pints',
            'multiValued' => true
        ],
        [
            'name' => 'type_id',
            'indexed' => '0',
            'stored' => '1',
            'type'   => 'text_general',
            'multiValued' => true
        ],
        [
            'name' => 'thumbnail',
            'indexed' => '0',
            'stored' => '1',
            'type'  => "text_general",
            "multiValued" => false
        ],
        [
            'name' => 'is_in_stock',
            'indexed' => '0',
            'stored' => '1',
            'type'  => "boolean",
            "multiValued" => false
        ],
        [
            'name' => 'qty',
            'indexed' => '0',
            'stored' => '1',
            'type'  => "pints",
            "multiValued" => false
        ],
        [
            'name' => 'product_rating',
            'indexed' => '0',
            'stored' => '1',
            'type'  => "pints",
            "multiValued" => false
        ]
    ];


    protected $defaultCategoryAttributes = [
        [
            'name' => 'category_id',
            'indexed' => '0',
            'stored' => '1',
            'type'   => 'pints',
            'multiValued' => false
        ],
        [
            'name' => 'path',
            'indexed' => '0',
            'stored' => '1',
            'type'   => 'text_general',
            'multiValued' => true
        ],
        [
            'name' => 'name',
            'indexed' => '1',
            'stored' => '1',
            'type'   => 'text_general',
            'multiValued' => false
        ],
        [
            'name' => 'category_url',
            'indexed' => '1',
            'stored' => '1',
            'type'   => 'text_general',
            'multiValued' => false
        ]
    ];

    /**
     * Installs data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        try {
            $this->setSolrUrl();
        } catch (\Exception $exception) {}

        try {
            $this->setAutocompleteTemplate();
        } catch (\Exception $exception) {}

        try {
            $this->setDefaultProductAttributeSchema();
        } catch (\Exception $exception) {}

        try {
            $this->setDefaultCategoryAttributeSchema();
        } catch (\Exception $exception) {}

        $this->flushCache();
    }

    protected function setSolrUrl() {
        $url = $this->url->getBaseUrl();
        if (substr($url, -1) === "/") {
            $url = substr($url, 0, -1);
        }
        $url .= ":8983/";
        $this->config->saveConfig(Config::SOLR_URL_XML_KEY, $url, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    protected function setAutocompleteTemplate() {
        $templatePath = $this->reader->getModuleDir(
            \Magento\Framework\Module\Dir::MODULE_VIEW_DIR,
            "Codilar_SolrConnector").'/frontend/web/template/autocomplete.html';
        $template = \file_get_contents($templatePath);
        $this->config->saveConfig(Config::AUTOCOMPLETE_TEMPLATE_XML_KEY, $template, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }

    protected function setDefaultProductAttributeSchema() {
        $this->config->setProductSchema($this->defaultProductAttributes);
    }

    protected function setDefaultCategoryAttributeSchema() {
        $this->config->setCategorySchema($this->defaultCategoryAttributes);
    }

    protected function flushCache() {
        $types = ['config','full_page'];
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }
}