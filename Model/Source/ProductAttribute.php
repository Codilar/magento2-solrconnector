<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 13/3/18
 * Time: 3:58 PM
 */

namespace Codilar\SolrConnector\Model\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;

class ProductAttribute extends AbstractTable
{
    /**
     * @var ProductAttributeCollectionFactory
     */
    private $productAttributeCollectionFactory;

    /**
     * ProductAttribute constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param ProductAttributeCollectionFactory $productAttributeCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        ProductAttributeCollectionFactory $productAttributeCollectionFactory,
        array $data = []
    )
    {
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function getProductAttributes()
    {
        $attributeCollection = $this->productAttributeCollectionFactory->create();
        $response = [
            [
                'label' =>  __("ID"),
                'value' =>  "product_id"
            ],
            [
                'label' =>  __("type"),
                'value' =>  "type_id"
            ],
            [
                'label' =>  __("Product Url"),
                'value' =>  "product_url"
            ],
            [
                'label' =>  __("Is In Stock"),
                'value' =>  "is_in_stock"
            ],
            [
                'label' =>  __("Quantity"),
                'value' =>  "qty"
            ],
            [
                'label' =>  __("Product Rating"),
                'value' =>  "product_rating"
            ]
        ];

        foreach ($attributeCollection as $attribute) {
            /* @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
            $response[] = [
                'label' =>  $attribute->getAttributeCode(),
                'value' =>  $attribute->getAttributeCode()
            ];
        }

        /* Sort alphabetically */
        usort($response, function ($first, $second) {
            return $first <=> $second;
        });

        return $response;
    }

    protected function getTableData()
    {
        return [
            'name' =>  [
                'label' =>  __("Attribute"),
                'values'=>  $this->getProductAttributes()
            ],
            'indexed'   =>  [
                'label' =>  __("Is Searchable"),
                'values'=>  $this->getYesNoArray()
            ],
            'stored'    =>  [
                'label' =>  __("Is Retrievable"),
                'values'=>  $this->getYesNoArray()
            ]
        ];
    }
}