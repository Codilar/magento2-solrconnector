<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Model\Data;


class ToolbarParameters
{
    /**
     * @var int
     */
    private $firstNum;

    /**
     * @var int
     */
    private $lastNum;

    /**
     * @var int
     */
    private $totalNum;

    /**
     * @var int
     */
    private $lastPageNum;

    /**
     * @var int
     */
    private $currentPage;

    /**
     * @return int
     */
    public function getFirstNum(): int
    {
        return $this->firstNum;
    }

    /**
     * @param int $firstNum
     * @return $this
     */
    public function setFirstNum(int $firstNum)
    {
        $this->firstNum = $firstNum;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastNum(): int
    {
        return $this->lastNum;
    }

    /**
     * @param int $lastNum
     * @return $this
     */
    public function setLastNum(int $lastNum)
    {
        $this->lastNum = $lastNum;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalNum(): int
    {
        return $this->totalNum;
    }

    /**
     * @param int $totalNum
     * @return $this
     */
    public function setTotalNum(int $totalNum)
    {
        $this->totalNum = $totalNum;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastPageNum(): int
    {
        return $this->lastPageNum;
    }

    /**
     * @param int $lastPageNum
     * @return $this
     */
    public function setLastPageNum(int $lastPageNum)
    {
        $this->lastPageNum = $lastPageNum;
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @param int $currentPage
     * @return $this
     */
    public function setCurrentPage(int $currentPage)
    {
        $this->currentPage = $currentPage;
        return $this;
    }
}