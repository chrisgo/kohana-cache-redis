kohana-cache-redis
==================

Module to enable caching in Kohana using Redis

* Builds on other projects 
  * https://github.com/puneetk/kohana-redis-cache
  * https://github.com/Zogame/Kohana_Cache_Redis
  * https://github.com/nicolasff/phpredis
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
    ...
    'cache' => 'cache',                       // enable kohana cache engine 
    ...
    'redis-cache' => 'kohana-redis-cache',    // enable redis as a cache engine
    ...
);
```

* bootstrap.php - use Redis as default caching engine at the end of bootstrap

`Cache::$default = 'redis';`

* config/cache.php
  * Copy `config/cache.php` to your `APPPATH./config/cache.php`
  * Make changes as necessary

Usage
----
