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
class Kohana_Cache_Redis extends Cache
{

	/**
	 * @var object Redis instance
	 */
	protected $_redis = null;

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

		if (empty($servers))
		{
			throw new Kohana_Cache_Exception('No Redis servers defined in configuration');
		}

		foreach($servers as $server)
		{
			// Connection method
			$method = Arr::get($server, 'persistent', FALSE) ? 'pconnect': 'connect';
			$this->_redis->{$method}($server['host'], $server['port'], 1);
			// See if there is a password
			$password = Arr::get($server, 'password', NULL);
			if ( ! empty($password))
			{
				$this->_redis->auth($password);
			}
			// Prefix a name space
			$prefix = Arr::get($server, 'prefix', NULL);
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
		return call_user_func_array(array($this->_redis, $method), $args);
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
		if (Kohana::$caching === FALSE)
		{
			return NULL;
		}

		$value = NULL;

		if (is_array($id))
		{
			// sanitize keys
			$ids = array_map(array($this, '_sanitize_id'), $id);
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
	public function set($id, $data, $lifetime = 3600)
	{
		if (is_array($id))
		{
			// sanitize keys
			$ids = array_map(array($this, '_sanitize_id'), $id);
			// use mset to put it all in redis
			$this->_redis->mset(array_combine($ids, array_values($data)));
			$this->_set_ttl($ids, $lifetime);  // give it an array of keys and one lifetime
		}
		else
		{
			$id = $this->_sanitize_id($id);
			$this->_redis->mset(array($id=>$data));
			$this->_set_ttl($id, $lifetime);
		}

		return TRUE;
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
	protected function _set_ttl($keys, $lifetime = Date::DAY)
	{
		if (is_int($lifetime))
		{
			$lifetime += time();
		}
		else
		{
			$lifetime = strtotime($lifetime);
		}

		if (is_array($keys))
		{
			foreach ($keys as $key)
			{
				$this->_redis->expireAt($key, $lifetime);
			}
		}
		else
		{
			$this->_redis->expireAt($keys, $lifetime);
		}
	}
}