<?php defined('SYSPATH') or die('No direct script access.');
return array
(
	'redis' => array(
		'driver'				=> 'redis',
		'cache_prefix' 			=> 'cache',
		'servers'            	=> array(
			'local' => array(
//				'host'          => 'unix:///var/run/redis/redis.sock',
				'host'          => 'localhost',
				'port'          => 6379,
				'persistent'    => FALSE,
				'prefix'        => '',
				'password'		=> '',
			),
		),
	)
);
