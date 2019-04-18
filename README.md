# Soisy WooCommerce plugin

It's strongly advised to use the WP-CLI and PHPUnit inside the [soisy/soisy-woocommerce-docker](https://github.com/soisy/soisy-woocommerce-docker) repo.


## Installation

Within your docker PHP-FPM container, navigate to the `soisy-woocommerce-plugin` directory:
```
$ cd public/wordpress-49/wp-content/plugins/soisy-woocommerce-plugin/
```

and then run:  
```
$ bash INSTALL.sh
```

## Running PHPUnit tests

Tests are, of course, stored inside `./tests/` directory.  
 
To run them all just run: 
```
$ phpunit
```


## Reminder
Remember to activate WooCommerce and Soisy WooCommerce plugin's in your WordPress admin panel.