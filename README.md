kohana-cache-redis
==================

Module to enable caching in Kohana (3.3.x) using Redis

* Builds on other projects
  * https://github.com/puneetk/kohana-redis-cache
  * https://github.com/Zogame/Kohana_Cache_Redis
  * https://github.com/nicolasff/phpredis
* Added ability to connect to Redis that needs authorization (using `Redis::auth()`)
* Picks up the correct configuration settings for **each server**
  * The other projects don't go into the nested `servers` to pick up persistence, etc.
  * Follows more the `__construct()` of the memcache cache engine
* Implements Tagging interface

Requirements
----

* redis.so (on Debian wheezy with php5-fpm `sudo apt-get install php5-redis`)

Install
----

* Copy into this project into `MODPATH/kohana-cache-redis`

Configuration
----

* boostrap.php - Enable caching in the init

```
Kohana::init([
    ...
    'caching' => true,
    ...
]);
```

* bootstrap.php - Enable module

```
$modules = [
    ...
    'cache' => 'cache',                       // enable kohana cache engine
    ...
    'cache-redis' => 'kohana-cache-redis',    // enable redis as a cache engine
    ...
];
```

* bootstrap.php - use Redis as default caching engine at the end of bootstrap

```
Cache::$default = 'redis';
```

* config/cache.php
  * Copy `config/cache.php` to your `APPPATH.'/config/cache.php';`
  * Make changes as necessary

Usage
----

Once everything above is done, it should follow the standard Kohana cache usage found here:
http://kohanaframework.org/3.3/guide/cache/usage

```
// Instance
$cache = Cache::instance();                         // if defaulted to Redis
$redis_cache = Cache::instance('redis');            // if not defaulted

// Set
$object = new stdClass;
$object->foo = 'bar';
Cache::instance()->set('foo', $object);

// Get
$object = Cache::instance()->get('foo', FALSE);     //

echo Debug::vars($object);

// Set with tags
Cache::instance()->set_with_tags('foo', 'bar', 3600, ['tagged']);
echo Debug::vars(Cache::instance()->get('foo'));
Cache::instance()->delete_tag('tagged');
echo Debug::vars(Cache::instance()->get('foo'));    // should be null
```

If it doesn't look it is working, the easiest way to test this is to use a `get()`
with a default value that is a string.  The cache engine will return `NULL` if
it can't connect to Redis

```
// For Testing, get() a key that does not exist
echo Debug::vars(Cache::instance('redis')->get('somekey', 'not'));
```

If your system outputs `NULL`, your Redis configuration is wrong.  

If your system outputs `not`, then the Redis cache engine is working
