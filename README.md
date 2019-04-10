# Soisy woocommerce plugin

It's strongly advised to use the WP-CLI and PHPUnit inside the `soisy-docker-php72` repo.

## Installation

Go the `soisy-woocommerce-plugin` with:
```
$ cd public/wordpress-49/wp-content/plugins/soisy-woocommerce-plugin/
```

and then run:  
```
$ bash INSTALL.sh
```

## Running PHPUnit tests

Tests are stored inside `./tests/` directory.  
 
To run them all just do: 
```
$ phpunit
```