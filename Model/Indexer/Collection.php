<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Model\Indexer;


use Codilar\SolrConnector\Model\Api;
use Codilar\SolrConnector\Model\Config;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Event\Manager as EventManager;

class Collection implements ActionInterface, MviewActionInterface
{

    /**
     * @var Config
     */
    private $config;
    /**
     * @var Api
     */
    private $api;
    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;
    /**
     * @var Monolog
     */
    private $monolog;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * Collection constructor.
     * @param Config $config
     * @param Api $api
     * @param StoreRepositoryInterface $storeRepository
     * @param Monolog $monolog
     * @param StoreManagerInterface $storeManager
     * @param EventManager $eventManager
     */
    public function __construct(
        Config $config,
        Api $api,
        StoreRepositoryInterface $storeRepository,
        Monolog $monolog,
        StoreManagerInterface $storeManager,
        EventManager $eventManager
    )
    {
        $this->config = $config;
        $this->api = $api;
        $this->storeRepository = $storeRepository;
        $this->monolog = $monolog;
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;
    }

    /**
     * @return Config
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @return Monolog
     */
    public function getLogger() {
        return $this->monolog;
    }

    /**
     * @return Api
     */
    public function getApi() {
        return $this->api;
    }

    /**
     * @return StoreManagerInterface
     */
    public function getStoreManager() {
        return $this->storeManager;
    }

    /**
     * @return StoreRepositoryInterface
     */
    public function getStoreRepository() {
        return $this->storeRepository;
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
            $this->executeRow($id);
        }
    }

    /**
     * Execute full indexation
     *
     * @return void
     */
    public function executeFull()
    {
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $this->executeRow($store->getId());
        }
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
            $this->executeRow($id);
        }
    }

    /**
     * Execute partial indexation by ID
     *
     * @param int $id
     * @return void
     */
    public function executeRow($id)
    {
        if (!$this->config->isEnabled()) {
            echo "\nCODILAR SOLR CONNECTOR is disabled\n";
            return;
        }
        $store = $this->storeRepository->getById($id)->getCode();
        $productCollectionName = $this->getConfig()->getCollectionName($store, Config::COLLECTION_TYPE_PRODUCT);

        $this->indexProductCollection($productCollectionName);

        $categoryCollectionName = $this->getConfig()->getCollectionName($store, Config::COLLECTION_TYPE_CATEGORY);

        $this->indexCategoryCollection($categoryCollectionName);

    }

    protected function indexProductCollection($productCollectionName) {
        /* Delete the collection if it already exists */
        try {
            $this->api->deleteCollection($productCollectionName);
        } catch (LocalizedException $localizedException) {
            $this->monolog->info("SOLR CONNECTOR: ".$localizedException->getMessage());
        }
        /* Create collection */
        try {
            $eventParameters = new DataObject(['collection' => $productCollectionName]);
            $this->getEventManager()->dispatch("solr_product_collection_create_before", ['data' => $eventParameters]);
            $this->api->createCollection($eventParameters->getData('collection'));
        } catch (LocalizedException $localizedException) {
            $this->monolog->info("SOLR CONNECTOR: ".$localizedException->getMessage());
        }

        /* Define schema for collection */
        try {
            $schema = $this->getProductSchema();
            $eventParameters = new DataObject([
                'collection' => $productCollectionName,
                'schema'     => $schema
            ]);
            $this->getEventManager()->dispatch("solr_product_collection_schema_add_before", ['data' => $eventParameters]);
            $this->api->addSchema($eventParameters->getData('collection'), $eventParameters->getData('schema'), Api::TYPE_SCHEMA_ADD);
        } catch (LocalizedException $localizedException) {
            $this->monolog->info("SOLR CONNECTOR: ".$localizedException->getMessage());
        }
    }

    protected function indexCategoryCollection($categoryCollectionName) {
        /* Delete the collection if it already exists */
        try {
            $this->api->deleteCollection($categoryCollectionName);
        } catch (LocalizedException $localizedException) {
            $this->monolog->info("SOLR CONNECTOR: ".$localizedException->getMessage());
        }
        /* Create collection */
        try {
            $eventParameters = new DataObject(['collection' => $categoryCollectionName]);
            $this->getEventManager()->dispatch("solr_category_collection_create_before", ['data' => $eventParameters]);
            $this->api->createCollection($eventParameters->getData('collection'));
        } catch (LocalizedException $localizedException) {
            $this->monolog->info("SOLR CONNECTOR: ".$localizedException->getMessage());
        }

        /* Define schema for collection */
        try {
            $schema = $this->getCategorySchema();
            $eventParameters = new DataObject([
                'collection' => $categoryCollectionName,
                'schema'     => $schema
            ]);
            $this->getEventManager()->dispatch("solr_category_collection_schema_add_before", ['data' => $eventParameters]);
            $this->api->addSchema($eventParameters->getData('collection'), $eventParameters->getData('schema'), Api::TYPE_SCHEMA_ADD);
        } catch (LocalizedException $localizedException) {
            $this->monolog->info("SOLR CONNECTOR: ".$localizedException->getMessage());
        }
    }

    public function getProductSchema() {
        return $this->config->getProductSchema();
    }

    public function getCategorySchema() {
        return $this->config->getCategorySchema();
    }


    /**
     * @return EventManager
     */
    public function getEventManager(): EventManager
    {
        return $this->eventManager;
    }
}