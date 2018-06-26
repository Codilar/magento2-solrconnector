<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Plugin;

use Magento\Theme\Block\Html\Pager as Subject;

class Pager extends Toolbar
{

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return string
     */
    public function aroundGetPreviousPageUrl($subject, callable $proceed) {
        if ($this->getToolbarParameters()) {
            return $subject->getPageUrl($this->getToolbarParameters()->getCurrentPage() - 1);
        } else {
            return $proceed();
        }
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return string
     */
    public function aroundGetNextPageUrl($subject, callable $proceed) {
        if ($this->getToolbarParameters()) {
            return $subject->getPageUrl($this->getToolbarParameters()->getCurrentPage() + 1);
        } else {
            return $proceed();
        }
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return string
     */
    public function aroundGetLastPageUrl($subject, callable $proceed) {
        if ($this->getToolbarParameters()) {
            return $subject->getPageUrl($this->getToolbarParameters()->getLastPageNum());
        } else {
            return $proceed();
        }
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return array
     */
    public function aroundGetPages($subject, callable $proceed) {
        if ($this->getToolbarParameters()) {
            $start = 1;
            $end = $this->getToolbarParameters()->getLastPageNum();
            return range($start, $end);
        } else {
            return $proceed();
        }
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @return array
     */
    public function aroundGetFramePages($subject, callable $proceed) {
        if ($this->getToolbarParameters()) {
            if ($this->getToolbarParameters()->getLastPageNum() <= $subject->getFrameLength()) {
                $start = 1;
                $end = $this->getToolbarParameters()->getLastPageNum();
            } else {
                $half = ceil($subject->getFrameLength() / 2);
                $currentPage = $this->getToolbarParameters()->getCurrentPage();
                $lastPageNum = $this->getToolbarParameters()->getLastPageNum();
                if ($currentPage >= $half &&
                    $currentPage <= $lastPageNum - $half
                ) {
                    $start = $currentPage - $half + 1;
                    $end = $start + $subject->getFrameLength() - 1;
                } elseif ($currentPage < $half) {
                    $start = 1;
                    $end = $subject->getFrameLength();
                } elseif ($currentPage > $lastPageNum - $half) {
                    $end = $lastPageNum;
                    $start = $end - $subject->getFrameLength() + 1;
                } else {
                    $start = 1;
                    $end = $lastPageNum;
                }
            }
            return range($start, $end);
        } else {
            return $proceed();
        }
    }
}