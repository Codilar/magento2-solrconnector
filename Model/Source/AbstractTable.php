<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 13/3/18
 * Time: 3:58 PM
 */

namespace Codilar\SolrConnector\Model\Source;

use Codilar\SolrConnector\Block\System\Form\Field\Select;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

abstract class AbstractTable extends AbstractFieldArray
{
    protected $selectFields = [];
    protected $productHelper;
    protected $categoryHelper;
    protected $config;

    abstract protected function getTableData();

    protected function getRenderer($columnId, $columnData)
    {
        if (!array_key_exists($columnId, $this->selectFields) || !$this->selectFields[$columnId]) {

            /* @var Select $select */
            $select = $this->getLayout()->createBlock(Select::class, '', ['data' => ['is_render_to_js_template' => true]]);

            $options = $columnData['values'];

            if (is_callable($options)) {
                $options = $options();
            }

            $extraParams = $columnId === 'attribute' ? 'style="width:160px;"' : 'style="width:100px;"';
            $select->setExtraParams($extraParams);
            $select->setOptions($options);

            $this->selectFields[$columnId] = $select;
        }

        return $this->selectFields[$columnId];
    }

    protected function _construct()
    {
        $data = $this->getTableData();

        foreach (array_keys($data) as $columnId) {
            $columnData = $data[$columnId];

            $column = [
                'label' => __($columnData['label']),
            ];

            if (isset($columnData['values'])) {
                $column['renderer'] = $this->getRenderer($columnId, $columnData);
            }

            if (isset($columnData['class'])) {
                $column['class'] = $columnData['class'];
            }

            if (isset($columnData['style'])) {
                $column['style'] = $columnData['style'];
            }

            $this->addColumn($columnId, $column);
        }

        $this->_addAfter = false;
        parent::_construct();
    }


    protected function getYesNoArray() {
        return [
            '0' => __("No"),
            '1' => __("Yes")
        ];
    }

    protected function _prepareArrayRow(DataObject $row)
    {
        $data = $this->getTableData();
        $options = [];
        foreach (array_keys($data) as $columnId) {
            $columnData = $data[$columnId];

            if (isset($columnData['values'])) {
                $options['option_' . $this->getRenderer($columnId, $columnData)->calcOptionHash($row->getData($columnId))] = 'selected="selected"';
            }
        }

//        echo "<pre>";
//        print_r($options);

        $row->setData('option_extra_attrs', $options);
    }
}
