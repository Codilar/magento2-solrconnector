<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Observer;


use Codilar\SolrConnector\Model\Config;
use Codilar\SolrConnector\Model\Eav\Attribute;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeList;
use Magento\Framework\App\Cache\Frontend\Pool as CacheFrontendPool;

class ConfigSaveAfter implements ObserverInterface
{
    /**
     * @var MessageManager
     */
    private $messageManager;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Attribute
     */
    private $attribute;
    /**
     * @var CacheTypeList
     */
    private $cacheTypeList;
    /**
     * @var CacheFrontendPool
     */
    private $cacheFrontendPool;

    /**
     * ConfigSaveAfter constructor.
     * @param MessageManager $messageManager
     * @param Config $config
     * @param Attribute $attribute
     * @param CacheTypeList $cacheTypeList
     * @param CacheFrontendPool $cacheFrontendPool
     */
    public function __construct(
        MessageManager $messageManager,
        Config $config,
        Attribute $attribute,
        CacheTypeList $cacheTypeList,
        CacheFrontendPool $cacheFrontendPool
    )
    {
        $this->messageManager = $messageManager;
        $this->config = $config;
        $this->attribute = $attribute;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $productAttributes = $this->config->getProductSchema();
        foreach ($productAttributes as $index => $productAttribute) {
            $productAttributes[$index] = $this->attribute->formatAttribute($productAttribute);
        }
        $this->config->setProductSchema($productAttributes);

        $categoryAttributes = $this->config->getCategorySchema();
        foreach ($categoryAttributes as $index => $categoryAttribute) {
            $categoryAttributes[$index] = $this->attribute->formatAttribute($categoryAttribute);
        }
        $this->config->setCategorySchema($categoryAttributes);

        $this->flushCache();
        $this->messageManager->addNoticeMessage(__("CODILAR SOLR CONNECTOR: You will need to reindex for the changes to take effect"));
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