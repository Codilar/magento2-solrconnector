<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Model\Eav;


use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Attribute
{
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * Attribute constructor.
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository
    )
    {
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @param array $attribute
     * @return array
     */
    public function formatAttribute($attribute) {
        return [
            'name' => $attribute['name'],
            'indexed' => $attribute['indexed'],
            'stored' => $attribute['stored'],
            'type'   => $this->getAttributeType($attribute['name']),
            'multiValued' => $this->isMultivalued($attribute['name'])
        ];
    }

    public function getAttributeType($attributeCode) {
        try {
            if ($attributeCode === "product_id" || $attributeCode === "category_id" || $attributeCode === "product_rating") {
                return "pints";
            } else if($attributeCode === "is_in_stock") {
                return "boolean";
            } else if($attributeCode === "qty") {
                return "pints";
            } else {
                $attributeType = $this->getAttribute($attributeCode)->getBackendType();
            }
        } catch (LocalizedException $localizedException) {
            $attributeType = "varchar";
        }

        switch ($attributeType) {
            case "decimal":
                return "pdouble";
            default:
                return "text_general";
        }
    }

    /**
     * @param string $attributeCode
     * @return bool
     */
    public function isMultivalued($attributeCode) {
        try {
            if ($attributeCode === "category_ids" || $attributeCode === "path") {
                return true;
            }
            /* @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
            $attribute = $this->getAttribute($attributeCode);
            return $attribute->usesSource();
        } catch (LocalizedException $localizedException) {
            return false;
        }
    }

    /**
     * @param string $attributeCode
     * @return \Magento\Eav\Api\Data\AttributeInterface
     * @throws NoSuchEntityException
     */
    public function getAttribute($attributeCode) {
        return $this->attributeRepository->get(\Magento\Catalog\Model\Product::ENTITY, $attributeCode);
    }
}