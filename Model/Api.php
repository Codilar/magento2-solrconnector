<?php
/**
 *
 * @package     magento2
 * @author      Codilar Technologies
 * @license     https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 * @link        http://www.codilar.com/
 */

namespace Codilar\SolrConnector\Model;


use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;

class Api
{

    const TYPE_SCHEMA_ADD = "add-field";
    const TYPE_SCHEMA_REPLACE = "replace-field";
    const TYPE_SCHEMA_DELETE = "delete-field";


    /**
     * @var CurlFactory
     */
    private $curlFactory;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Json
     */
    private $json;

    /**
     * Api constructor.
     * @param CurlFactory $curlFactory
     * @param Config $config
     * @param Json $json
     */
    public function __construct(
        CurlFactory $curlFactory,
        Config $config,
        Json $json
    )
    {
        $this->curlFactory = $curlFactory;
        $this->config = $config;
        $this->json = $json;
    }

    /**
     * @param string $name
     * @return \Magento\Framework\HTTP\Client\Curl
     */
    public function createCollection($name) {
        $curl = $this->curlFactory->create();
        $curl->get($this->config->getUrl()."solr/admin/collections?action=CREATE&name=$name&numShards=1&replicationFactor=1&wt=json");
        $this->handleError($curl, "Couldn't create collection $name.");
        return $curl;
    }

    /**
     * @param string $name
     * @return \Magento\Framework\HTTP\Client\Curl
     */
    public function deleteCollection($name) {
        $curl = $this->curlFactory->create();
        $curl->get($this->config->getUrl()."solr/admin/collections?action=DELETE&name=$name");
        $curl->get($this->config->getUrl()."solr/admin/configs?action=DELETE&name=$name.AUTOCREATED");
        $this->handleError($curl, "Couldn't delete collection $name.");
        return $curl;
    }

    /**
     * @param string $collection
     * @param array $schema
     * @param string $type
     * @return \Magento\Framework\HTTP\Client\Curl
     */
    public function addSchema($collection, $schema = [], $type = self::TYPE_SCHEMA_ADD) {
        $curl = $this->curlFactory->create();
        $curl->addHeader('Content-Type', 'application/json');
        $curl->post($this->config->getUrl()."solr/$collection/schema?commit=true", $this->json->serialize([$type => array_values($schema)]));
        $this->handleError($curl, "Error updating schema for $collection");
        return $curl;
    }

    /**
     * @param string $collection
     * @param array $data
     * @return \Magento\Framework\HTTP\Client\Curl
     */
    public function addObjects($collection, $data = []) {
        $curl = $this->curlFactory->create();
        $curl->addHeader('Content-Type', 'application/json');
        $curl->post($this->config->getUrl()."solr/$collection/update/json/docs?commit=true", $this->json->serialize($data));
        $this->handleError($curl, "Couldn't insert data into collection $collection.");
        return $curl;
    }

    /**
     * @param string $collection
     * @param string $query
     * @param array $searchableAttributes
     * @param int $pageSize
     * @param int $curPage
     * @return \Magento\Framework\HTTP\Client\Curl
     */
    public function search($collection, $query, $searchableAttributes = ["*"], $pageSize = 10, $curPage = 1) {
        $url = $this->config->getUrl()."solr/$collection/select?q=";
        $searchQuery = [];
        foreach ($searchableAttributes as $searchableAttribute) {
            $searchQuery[] = "$searchableAttribute:*$query*";
        }
        $offset = $pageSize * ($curPage-1);
        $url = $url.str_replace(" ", "%20", implode(" OR ", $searchQuery))."&rows=".$pageSize."&start=".$offset;
        $curl = $this->curlFactory->create();
        $curl->get($url);
        $this->handleError($curl, "Error fetching results from $collection");
        return $curl;
    }

    /**
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param string $message
     * @throws LocalizedException
     */
    protected function handleError($curl, $message = "") {
        if ($curl->getStatus() !== 200) {
            try {
                $body = $this->json->unserialize($curl->getBody());
                $reason = $curl->getBody();//$body['error']['msg'];
            } catch (\InvalidArgumentException $invalidArgumentException) {
                $body = $curl->getBody();
                $reason = $body;
            }
            throw new LocalizedException(__("$message REASON: $reason"));
        }
    }
}