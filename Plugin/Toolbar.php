<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Plugin;

use Codilar\SolrConnector\Model\Adapter\Solr;
use Codilar\SolrConnector\Model\Data\ToolbarParameters;
use Magento\Catalog\Block\Product\ProductList\Toolbar as Subject;
use Magento\Framework\Registry;

class Toolbar
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * Toolbar constructor.
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry
    )
    {
        $this->registry = $registry;
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundGetFirstNum($subject, callable $proceed) {
        if ($this->getToolbarParameters()) {
            return $this->getToolbarParameters()->getFirstNum();
        } else {
            return $proceed();
        }
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundGetLastNum($subject, callable $proceed) {
        if ($this->getToolbarParameters()) {
            return $this->getToolbarParameters()->getLastNum();
        } else {
            return $proceed();
        }
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundGetTotalNum($subject, callable $proceed) {
        if ($this->getToolbarParameters()) {
            return $this->getToolbarParameters()->getTotalNum();
        } else {
            return $proceed();
        }
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundGetLastPageNum($subject, callable $proceed) {
        if ($this->getToolbarParameters()) {
            return $this->getToolbarParameters()->getLastPageNum();
        } else {
            return $proceed();
        }
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundIsFirstPage($subject, callable $proceed) {
        if ($this->getToolbarParameters()) {
            return $this->getToolbarParameters()->getCurrentPage() == 1;
        } else {
            return $proceed();
        }
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundGetCurrentPage($subject, callable $proceed) {
        if ($this->getToolbarParameters()) {
            return $this->getToolbarParameters()->getCurrentPage();
        } else {
            return $proceed();
        }
    }

    /**
     * @return ToolbarParameters|null
     */
    protected function getToolbarParameters() {
        $toolbarParameters = $this->registry->registry(Solr::TOOLBAR_PARAMETERS_REGISTRY_KEY);
        if ($toolbarParameters instanceof ToolbarParameters) {
            return $toolbarParameters;
        } else {
            return null;
        }
    }
}