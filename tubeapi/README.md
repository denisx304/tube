# TubeApi

# Deployment Guide for Ubuntu 14.04

Prerequisites:  
1. [Lamp Stack](https://help.ubuntu.com/community/ApacheMySQLPHP)  
2. [Composer](https://getcomposer.org/) 

Configuration 
```
sudo apt-get install php5-curl
sudo a2enmod rewrite
```
Set in your php.ini proper maximum size for post and upload file.  
Make sure your apache.conf is also configured.  
Make sure your /var/www has proper permissions.  
Change the database password in db_config.php.  


In your tubeapi folder install the following dependencies with composer:
```
composer require firebase/php-jwt
composer require "phpunit/phpunit=4.6.*"
composer require php-curl-class/php-curl-class
```


Running tests:

```
php test.php

```
