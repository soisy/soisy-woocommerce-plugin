# Soisy WooCommerce plugin

## For WooCommerce administrators/users

Please download the [latest version (2.0.0)](https://github.com/soisy/soisy-woocommerce-plugin/archive/2.0.0.zip) of this plugin. Any previous version is no longer supported.

## Installation
Download the [latest version](https://github.com/soisy/soisy-woocommerce-plugin/releases) of this plugin and unzip its content inside your `./wp-content/plugins` directory.  
Enable the plugin by going to _WP Admin Panel_ > _WooCommerce_ > _Settings_ > _Payments_ > _Soisy_.

Please refer to our [documentation](https://doc.soisy.it) for further reading.


## For Developers

It's strongly advised to use the WP-CLI and PHPUnit inside the [soisy/soisy-woocommerce-docker](https://github.com/soisy/soisy-woocommerce-docker) repo (Access to that repo is limited.)


### Installation

Within your docker PHP-FPM container, navigate to the `soisy-woocommerce-plugin` directory:
```
$ cd public/wordpress-49/wp-content/plugins/soisy-woocommerce-plugin/
```

and then run:  
```
$ bash INSTALL.sh
```

### Running PHPUnit tests

Tests are, of course, stored inside `./tests/` directory.  
 
To run them all just run: 
```
$ phpunit
```


### Reminder
Remember to activate WooCommerce and Soisy WooCommerce plugin's in your WordPress admin panel.