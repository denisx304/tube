# TubeApi

# Deployment Guide for Ubuntu 14.04

Prerequisites
1. [Lamp Stack](https://help.ubuntu.com/community/ApacheMySQLPHP)
2. [Composer](https://getcomposer.org/)

In your tubeapi folder install the following dependencies with composer:
```
composer require firebase/php-jwt
composer require "phpunit/phpunit=4.6.*"
composer require php-curl-class/php-curl-class
composer require "phpdocumentor/phpdocumentor:2.*"
```


Running tests:

```
vendor/bin/phpunit tubeapi_test.php 

```
