<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Observer;


use Codilar\SolrConnector\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class LayoutLoadBefore implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * LayoutLoadBefore constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isEnabled()) {
            /* @var \Magento\Framework\View\LayoutInterface $layout */
            $layout = $observer->getEvent()->getData('layout');
            $layout->getUpdate()->addHandle('codilar_solr_connector_topsearch');
        }
    }
}