# Solr Export Config

A drupal module, that will export search_api_solr solr server configuration after each `drush config:export`

### Installation

Add the repository to composer config

```
composer config repositories.psu-libraries/solr_export_config github https://github.com/psu-libraries/solr_export_config
```

Install the module 
```
composer require psu-libraries/solr_export_config
```

Enable the module 
```
drush en solr_export_config
```

Export config
```
drush config:export
```


### configuration 
basedir for storing solr server configuration. the solr configuration will end up in {{ solr_conf_dir }}/{{ solr_server_id }}

```
solr_conf_dir: /var/www/html/solr/conf
```