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
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\ActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Codilar\SolrConnector\Model\Indexer\Data\Category as CategoryData;
use Magento\Store\Model\StoreManagerInterface;

class Category implements ActionInterface, MviewActionInterface
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
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var CategoryCollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var CategoryData
     */
    private $categoryData;

    /**
     * Category constructor.
     * @param Collection $collection
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StoreManagerInterface $storeManager
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryData $categoryData
     */
    public function __construct(
        Collection $collection,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryData $categoryData
    )
    {
        $this->collection = $collection;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryData = $categoryData;
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

        $categories = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('entity_id', ['neq' => \Magento\Catalog\Model\Category::TREE_ROOT_ID]);
        foreach ($categories as $category) {
            /* @var \Magento\Catalog\Model\Category $category */
            $this->executeRow($category->getId(), false);
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
        $schema = $this->collection->getCategorySchema();
        foreach ($this->collection->getStoreManager()->getStores() as $store) {
            $collectionName = $this->collection->getConfig()->getCollectionName($store->getCode(), Config::COLLECTION_TYPE_CATEGORY);
            $categoryData = $this->categoryData->loadById($id, $store->getId())->getData($schema);
            if ($categoryData) {
                $this->data[$collectionName][] = $categoryData;
            }
        }
        if ($sendData) {
            $this->send();
        }
    }

    protected function send() {
        foreach ($this->data as $collection => $object) {
            try {
                $eventParameters = new DataObject([
                    'collection' => $collection,
                    'object'     => $object
                ]);
                $this->collection->getEventManager()->dispatch('solr_category_sync_send_before', ['data' => $eventParameters]);
                $this->collection->getApi()->addObjects($eventParameters->getData('collection'), $eventParameters->getData('object'));
            } catch (LocalizedException $localizedException) {
                $this->collection->getLogger()->info("SOLR CONNECTOR: ".$localizedException->getMessage());
            }
        }
    }
}