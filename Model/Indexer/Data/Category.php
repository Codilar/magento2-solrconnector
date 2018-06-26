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
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Helper\ImageFactory;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\App\Emulation;

class Category
{

    private $currentStoreId = null;

    /**
     * @var \Magento\Catalog\Model\Category
     */
    private $category;
    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var ImageFactory
     */
    private $imageFactory;
    /**
     * @var Attribute
     */
    private $attribute;
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * Category constructor.
     * @param Emulation $emulation
     * @param ImageFactory $imageFactory
     * @param Attribute $attribute
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        Emulation $emulation,
        ImageFactory $imageFactory,
        Attribute $attribute,
        CategoryRepositoryInterface $categoryRepository
    )
    {
        $this->emulation = $emulation;
        $this->imageFactory = $imageFactory;
        $this->attribute = $attribute;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param int $categoryId
     * @param int $storeId
     * @return $this
     */
    public function loadById($categoryId, $storeId) {
        $this->currentStoreId = $storeId;
        $this->category = $this->categoryRepository->get($categoryId, $this->currentStoreId);
        return $this;
    }

    /**
     * @return \Magento\Catalog\Model\Category
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * @param null|array $schema
     * @return array
     * @throws LocalizedException
     */
    public function getData($schema = null) {
        $category = $this->getCategory();
        if (!$this->isAllowed($category)) {
            return null;
        }
        if (!$category instanceof \Magento\Catalog\Model\Category) {
            throw new LocalizedException(__("Category not yet loaded"));
        }
        $categoryData = [
            'id' => (int)$category->getId()
        ];
        if ($schema) {
            foreach ($schema as $item) {
                $value = $category->getData($item['name']) ?: "";
                switch ($item['name']) {
                    case "category_id":
                        $value = (int)$category->getId();
                        break;
                    case "path":
                        $value = $category->getPathIds();
                        break;
                    case "category_url":
                        $this->emulation->startEnvironmentEmulation($this->currentStoreId, Area::AREA_FRONTEND, true);
                        $value = $category->getUrl();
                        $this->emulation->stopEnvironmentEmulation();
                        break;
                }
                try {
                    /* @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
                    $attribute = $this->attribute->getAttribute($item['name']);
                    if ($attribute->usesSource()) {
                        $value = $attribute->getSource()->getOptionText($value);
                    }
                } catch (\Exception $exception) {}
                $categoryData[$item['name']] = $value;

            }
        } else {
            $categoryData = $category->getData();
        }
        return $categoryData;
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return bool
     */
    public function isAllowed($category) {
        if ($category->getId() === $category::TREE_ROOT_ID) {
            return false;
        }
        return true;
    }
}