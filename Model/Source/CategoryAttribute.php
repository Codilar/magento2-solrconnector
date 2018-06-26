<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 13/3/18
 * Time: 3:58 PM
 */

namespace Codilar\SolrConnector\Model\Source;

use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory as CategoryAttributeCollectionFactory;

class CategoryAttribute extends AbstractTable
{
    /**
     * @var CategoryAttributeCollectionFactory
     */
    private $categoryAttributeCollectionFactory;

    /**
     * ProductAttribute constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param CategoryAttributeCollectionFactory $categoryAttributeCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        CategoryAttributeCollectionFactory $categoryAttributeCollectionFactory,
        array $data = []
    )
    {
        $this->categoryAttributeCollectionFactory = $categoryAttributeCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function getCategoryAttributes()
    {
        $attributeCollection = $this->categoryAttributeCollectionFactory->create();
        $response = [
            [
                'label' =>  __("ID"),
                'value' =>  "category_id"
            ],
            [
                'label' =>  __("Path"),
                'value' =>  "path"
            ],
            [
                'label' =>  __("Category URL"),
                'value' =>  "category_url"
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
                'values'=>  $this->getCategoryAttributes()
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