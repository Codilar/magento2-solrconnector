<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Model\Indexer\Data;


use Codilar\SolrConnector\Model\Eav\Attribute;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Review\Model\ReviewFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\App\Emulation;

class Product
{

    private $currentStoreId = null;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    private $product;
    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var ImageFactory
     */
    private $imageFactory;
    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;
    /**
     * @var Attribute
     */
    private $attribute;
    /**
     * @var ReviewFactory
     */
    private $reviewFactory;

    /**
     * Product constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param Emulation $emulation
     * @param ImageFactory $imageFactory
     * @param StockRegistryInterface $stockRegistry
     * @param Attribute $attribute
     * @param ReviewFactory $reviewFactory
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Emulation $emulation,
        ImageFactory $imageFactory,
        StockRegistryInterface $stockRegistry,
        Attribute $attribute,
        ReviewFactory $reviewFactory
    )
    {
        $this->productRepository = $productRepository;
        $this->emulation = $emulation;
        $this->imageFactory = $imageFactory;
        $this->stockRegistry = $stockRegistry;
        $this->attribute = $attribute;
        $this->reviewFactory = $reviewFactory;
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function loadById($productId, $storeId) {
        $this->currentStoreId = $storeId;
        $this->product = $this->productRepository->getById($productId, false, $this->currentStoreId);
        return $this;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct() {
        return $this->product;
    }

    /**
     * @param null|array $schema
     * @return array
     * @throws LocalizedException
     */
    public function getData($schema = null) {
        $this->emulation->startEnvironmentEmulation($this->currentStoreId, Area::AREA_FRONTEND, true);
        $product = $this->getProduct();
        if (!$this->isAllowed($product)) {
            return null;
        }
        if (!$product instanceof \Magento\Catalog\Model\Product) {
            throw new LocalizedException(__("Product not yet loaded"));
        }
        $productData = [
            'id' => (int)$product->getId()
        ];
        if ($schema) {
            $stock = $this->stockRegistry->getStockItem($product->getId());
            foreach ($schema as $item) {
                $value = $product->getData($item['name']) ?: "";
                switch ($item['name']) {
                    case "product_id":
                        $value = (int)$product->getId();
                        break;
                    case "type_id":
                        $value = $product->getTypeId();
                        break;
                    case "price":
                        $value = (double)($product->getTypeId() === "configurable" ? $product->getFinalPrice() : $product->getPrice());
                        break;
                    case "special_price":
                        $value = (double)$product->getFinalPrice();
                        break;
                    case "thumbnail":
                        $value = (string)$this->getProductImage($product);
                        break;
                    case "product_url":
                        $value = (string)$product->getProductUrl();
                        break;
                    case "is_in_stock":
                        $value = $stock->getIsInStock();
                        break;
                    case "qty":
                        $value = $stock->getQty();
                        break;
                    case "product_rating":
                        $value = $this->getProductRating($product);
                        break;
                }

                /* @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
                try {
                    $attribute = $this->attribute->getAttribute($item['name']);
                    if ($attribute->usesSource()) {
                        $value = $attribute->getSource()->getOptionText($value);
                    }
                } catch (\Exception $exception) {}
                $productData[$item['name']] = $value;

            }

            /* Append configurable attribute option labels to product data */
            if ($product->getTypeId() == "configurable") {
                /* @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $type */
                $type = $product->getTypeInstance();
                $data = $type->getConfigurableOptions($product);
                $options = [];
                foreach ($data as $attr) {
                    foreach ($attr as $p) {
                        $options[$p['attribute_code']][] = $p['option_title'];
                    }
                }
                $productData = array_merge($productData, $options);
            }
        } else {
            $productData = $product->getData();
        }
        $this->emulation->stopEnvironmentEmulation();
        return $productData;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    protected function getProductImage(\Magento\Catalog\Model\Product $product) {
        $this->emulation->startEnvironmentEmulation($this->currentStoreId, Area::AREA_FRONTEND, true);
        $url = $this->imageFactory->create()->init($product, "product_thumbnail_image")->getUrl();
        $this->emulation->stopEnvironmentEmulation();
        return $url;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return bool
     */
    public function isAllowed($product) {
        $allowedVisibilities = [
            Visibility::VISIBILITY_IN_SEARCH,
            Visibility::VISIBILITY_BOTH
        ];
        return (bool)(
            in_array($product->getVisibility(), $allowedVisibilities)
        );
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return int
     */
    protected function getProductRating($product) {
        $this->reviewFactory->create()->getEntitySummary($product, $this->currentStoreId);
        return (int)$product->getData('rating_summary')->getData('rating_summary');
    }
}