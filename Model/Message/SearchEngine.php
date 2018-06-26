<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Model\Message;


use Codilar\SolrConnector\Model\Config;
use Magento\Framework\Notification\MessageInterface;

class SearchEngine implements MessageInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * SearchEngine constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
    }

    /**
     * Retrieve unique message identity
     *
     * @return string
     */
    public function getIdentity()
    {
        return md5("SOLR_SEARCH_ENGINE_INFO");
    }

    /**
     * Check whether
     *
     * @return bool
     */
    public function isDisplayed()
    {
        return (bool)($this->config->getCurrentSearchEngine() !== "solr");
    }

    /**
     * Getter for text of message
     *
     * @return string
     */
    public function getText()
    {
        return "SOLR: Current Search engine used is \"".$this->config->getCurrentSearchEngine()."\". You can change it to Solr Search Engine by going to <br /><b>Stores > Configuration > Catalog > Catalog > Catalog Search > Search Engine</b><br /> and selecting \"Solr\"";
    }

    /**
     * Retrieve message severity
     *
     * @return int
     */
    public function getSeverity()
    {
        return self::SEVERITY_NOTICE;
    }
}