<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Cache Class for Redis
 *
 * Taken from Yoshiharu Shibata <shibata@zoga.me>
 *
 * - required PhpRedis
 *
 * @link https://github.com/Zogame/Kohana_Cache_Redis.git
 * @link https://github.com/nicolasff/phpredis
 * @link https://github.com/puneetk/kohana-redis-cache
 *
 * @author Chris Go <chris@velocimedia.com>
 */
class Kohana_Cache_Redis extends Cache implements Cache_Tagging
{

    /**
     * @var object Redis instance
     */
    protected $_redis = null;

    /**
     * Prefix for tag
     * @var unknown
     */
    protected $_tag_prefix = '_tag';


    /**
     * [Override]
     * Ensures singleton pattern is observed, loads the default expiry
     *
     * @param  array  $config  configuration
     */
    public function __construct($config)
    {
        if ( ! extension_loaded('redis'))
        {
            throw new Cache_Exception(__METHOD__.'Redis PHP extention not loaded');
        }

        parent::__construct($config);

        $this->_redis = new Redis();

        $servers = Arr::get($this->_config, 'servers', null);

        // Global cache prefix so the keys in redis is organized
        $cache_prefix = Arr::get($this->_config, 'cache_prefix', null);
        $this->_tag_prefix = Arr::get($this->_config, 'tag_prefix', $this->_tag_prefix).":";

        if (empty($servers))
        {
            throw new Kohana_Cache_Exception('No Redis servers defined in configuration');
        }

        foreach($servers as $server)
        {
            // Connection method
            $method = Arr::get($server, 'persistent', false) ? 'pconnect': 'connect';
            $this->_redis->{$method}($server['host'], $server['port'], 1);
            // See if there is a password
            $password = Arr::get($server, 'password', null);
            if ( ! empty($password))
            {
                $this->_redis->auth($password);
            }
            // Prefix a name space
            $prefix = Arr::get($server, 'prefix', null);
            if ( ! empty($prefix))
            {
                if ( ! empty($cache_prefix))
                {
                    $prefix .= ':'.$cache_prefix;
                }
                $prefix .= ':';
                $this->_redis->setOption(Redis::OPT_PREFIX, $prefix);
            }
        }

        // serialize stuff
        // if use Redis::SERIALIZER_IGBINARY, "run configure with --enable-redis-igbinary"
        $this->_redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

    }

    /**
     * all the functions that in the redis driver are proxied here
     *
     * @param string $method PhpRedis method
     * @param string $args   PhpRedis parameters
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->_redis, $method], $args);
    }

    /**
     * [Override]
     * Get value
     *
     * @param mixed  $id      cached key
     * @param mixed  $default not found default
     */
    public function get($id, $default = null)
    {
        if (Kohana::$caching === false)
        {
            return null;
        }

        $value = null;

        if (is_array($id))
        {
            // sanitize keys
            $ids = array_map([$this, '_sanitize_id'], $id);
            // return key/value
            $value = array_combine($id, $this->_redis->mget($ids));
        }
        else
        {
            // sanitize keys
            $id = $this->_sanitize_id($id);
            $value = $this->_redis->get($id);

        }

        if (empty($value))
        {
            $value = $default;
        }

        return $value;
    }

    /**
     * [Override]
     * Set value
     *  - supports multi set but assumes count of ids == count of data
     *
     * @param mixed  $id       caching key or assoc
     * @param mixed  $data     caching value
     * @param int    $lifetime life time seconds
     */
    public function set($id, $data, $lifetime = null)
    {
        if (is_array($id))
        {
            // sanitize keys
            $ids = array_map([$this, '_sanitize_id'], $id);
            // use mset to put it all in redis
            $this->_redis->mset(array_combine($ids, array_values($data)));
            $this->_set_ttl($ids, $lifetime);  // give it an array of keys and one lifetime
        }
        else
        {
            $id = $this->_sanitize_id($id);
            $this->_redis->mset([$id => $data]);
            $this->_set_ttl($id, $lifetime);
        }

        return true;
    }

    /**
     * [Override]
     * Delete value
     *
     * @param string  $id       cached key
     */
    public function delete($id)
    {
        $id = $this->_sanitize_id($id);
        return $this->_redis->del($id);
    }

    /**
     * [Override]
     * Delete all value
     */
    public function delete_all()
    {
        return $this->_redis->flushdb();
    }

    /**
     * Set the lifetime
     *
     * @param mixed  $keys     caching key or array
     * @param int    $lifetime life time seconds
     */
    protected function _set_ttl($keys, $lifetime = null)
    {
        // If lifetime is null
        if (empty($lifetime) AND $lifetime != 0)
        {
            $lifetime = Arr::get($this->_config, 'default_expire', 3600);
        }
        if ($lifetime > 0) {
            if (is_array($keys))
            {
                foreach ($keys as $key)
                {
                    $this->_redis->expire($key, $lifetime);
                }
            }
            else
            {
                $this->_redis->expire($keys, $lifetime);
            }
        }
    }

    // ==================== TAGS ====================

    /**
     * Set a value based on an id with tags
     *
     * @param   string   $id        id
     * @param   mixed    $data      data
     * @param   integer  $lifetime  lifetime [Optional]
     * @param   array    $tags      tags [Optional]
     * @return  boolean
     */
    public function set_with_tags($id, $data, $lifetime = NULL, array $tags = NULL)
    {
        $id = $this->_sanitize_id($id);
        $result = $this->set($id, $data, $lifetime);
        if ($result and $tags)
        {
            foreach ($tags as $tag)
            {
                $this->_redis->lPush($this->_tag_prefix.$tag, $id);
            }
        }
        return $result;
    }

    /**
     * Delete cache entries based on a tag
     *
     * @param   string  $tag  tag
     * @return  boolean
     */
    public function delete_tag($tag)
    {
        if ($this->_redis->exists($this->_tag_prefix.$tag)) {
            $keys = $this->_redis->lrange($this->_tag_prefix.$tag, 0, -1);
            if (!empty($keys) AND count($keys))
            {
                foreach ($keys as $key)
                {
                   $this->delete($key);
                }
            }
            // Then delete the tag itself
            $this->_redis->del($this->_tag_prefix.$tag);
            return true;
        }
        return false;
    }

    /**
     * Find cache entries based on a tag
     *
     * @param   string  $tag  tag
     * @return  void
     * @throws  Cache_Exception
     */
    public function find($tag)
    {
        if ($this->_redis->exists($this->_tag_prefix.$tag))
        {
            $keys = $this->_redis->lrange($this->_tag_prefix.$tag, 0, -1);
            if (!empty($keys) AND count($keys))
            {
                $rows = [];
                foreach ($keys as $key)
                {
                    $rows[$key] = $this->get($key);
                }
                return $rows;
            }
        }
        return null;
    }
}
