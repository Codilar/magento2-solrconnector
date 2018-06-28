# Magento 2 Solr Connector

[![Codilar](https://www.codilar.com/codilar-logo.png)](https://www.codilar.com/)

Power up your Magento 2 search. We built a [Solr](http://lucene.apache.org/solr/) connector for you. It will help your customer to find there needs as fast as Solr. Its fully customizable and search with multiple product and category attributes. Whatever attributes you want to search and retreive, you can customize from Magento admin dashboard. 

# Requirements

  - Solr server
  - PHP >=7.0
  - Magento 2.2

### Installation

How to install Solr connector in Magento 2

Install with Composer

```sh
$ cd magento2
$ composer require codilar/magento2-solrconnector
$ bin/magento setup:upgrade
$ bin/magento setup:static-content:deploy -f
```

Manual Intallation

```sh
$ cd magento2
$ cd app/code
$ mkdir Codiar
$ mv <Downloaded Solr Connector path>/codilar/magento2-solrconnector SolrConnector
$ bin/magento setup:upgrade
$ bin/magento setup:static-content:deploy -f
```

Solr Installation in your system.

Please follow this link for [Solr Installation](https://lucene.apache.org/solr/guide/7_3/installing-solr.html)

```sh
$ cd <solr intalled directory>/
$ bin/solr -e cloud
It will ask some options please enter below values
Shared - 1
Port - 8983
Replica - 1
Config - _default
```

<!-- If you have any problem with installation please refer this video.
[How to install Magento 2 Solr Connector](https://www.codilar.com/) -->

You can reach us through email support@codilar.com.

License
----

OSL, AFL


**Open Source Extension**
