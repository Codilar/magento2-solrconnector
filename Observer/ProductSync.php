<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Observer;


use Codilar\SolrConnector\Model\Indexer\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductSync implements ObserverInterface
{
    /**
     * @var Product
     */
    private $product;

    /**
     * ProductSaveAfter constructor.
     * @param Product $product
     */
    public function __construct(
        Product $product
    )
    {
        $this->product = $product;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $productIds = [];
        switch ($observer->getEvent()->getName()) {
            case "catalog_product_save_after":
                $productIds = $this->productSaveAfter($observer);
                break;
            case "cataloginventory_stock_item_save_commit_after":
                $productIds = $this->stockItemSaveAfter($observer);
                break;
            case "sales_order_place_after":
            case "order_cancel_after":
                $productIds = $this->orderChangeAfter($observer);
                break;
        }
        $this->product->execute($productIds);
    }

    /**
     * @param Observer $observer
     * @return array
     */
    public function productSaveAfter(Observer $observer) {
        /* @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getData('product');
        return [$product->getId()];
    }

    /**
     * @param Observer $observer
     * @return array
     */
    public function stockItemSaveAfter(Observer $observer)
    {
        /* @var \Magento\CatalogInventory\Model\Stock\Item $item */
        $item = $observer->getEvent()->getData('item');
        return [$item->getProductId()];
    }

    /**
     * @param Observer $observer
     * @return array
     */
    public function orderChangeAfter(Observer $observer)
    {
        /* @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getData('order');
        $productIds = [];
        foreach ($order->getAllItems() as $orderItem) {
            $productIds[] = $orderItem->getProductId();
        }
        return $productIds;
    }
}