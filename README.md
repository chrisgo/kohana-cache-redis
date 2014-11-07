kohana-cache-redis
==================

Module to enable caching in Kohana using Redis

* Builds on other projects 
  *  
  *
  * 
* Added ability to connect to Redis that needs authorization (using `Redis::auth()`) 
* Picks up the correct configuration settings for **each server**
  * The other projects don't go into the nested `servers` to pick up persistence, etc.

Requirements
----

* redis.so (on Debian wheezy with php5-fpm `sudo apt-get install php5-redis`)

Install
----

* Copy into this project into `MODPATH/kohana-redis-cache`

Configuration
----

* boostrap.php - Enable caching in the init

```
Kohana::init(array(
    ...
    'caching' => TRUE,
    ...
));
```

* bootstrap.php - Enable module
   
 ```
$modules = array(
    'cache' => 'cache',                       // enable kohana cache engine 
    ...
    'redis-cache' => 'kohana-redis-cache',    // enable redis as a cache engine
    ...
);
```

* If you want Redis to be the default caching, put`Cache::$default = 'redis'` at the end of bootstrap

* config/cache.php
  * Copy `config/cache.php` to your `APPPATH./config/cache.php`
  * Make changes as necessary

Usage
----
