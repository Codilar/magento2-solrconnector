<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Observer;


use Codilar\SolrConnector\Model\Indexer\Category;
use Codilar\SolrConnector\Model\Indexer\Collection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CategorySync implements ObserverInterface
{
    /**
     * @var Category
     */
    private $category;
    /**
     * @var Collection
     */
    private $collection;

    /**
     * CategorySync constructor.
     * @param Collection $collection
     * @param Category $category
     */
    public function __construct(
        Collection $collection,
        Category $category
    )
    {
        $this->category = $category;
        $this->collection = $collection;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->collection->getConfig()->isEnabled()) {
            return;
        }
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $observer->getEvent()->getData('category');
        $this->category->execute([$category->getId()]);
    }
}