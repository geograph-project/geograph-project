<?php

require_once('geograph/global.inc.php');

ini_set('display_errors',1);



if (empty($CONF['redis_host']))
	die("redis_host not configired");


var_dump($redis_handler);

if (empty($redis_handler)) { //one MIGHT of been started iether by RedisSessions.php OR by multiservermemcache.class.php
	require_once("3rdparty/RedisServer.php");
	print "Note, creating \$redis_handler myself!<br>";
        $redis_handler = new RedisServer($CONF['redis_host'], $CONF['redis_port']);
        if (!empty($CONF['redis_db']))
        {
                $redis_handler->Select($CONF['redis_db']);
        }
}

##################################################
// stolen from test.php for now!
	function outputRow($one,$two,$three) {
		print htmlentities("$two: $one: $three")."<br>";
	}


        $test_key = "test.php-timestamp";
        $value = $redis_handler->Get($test_key);

        if ($value && $value > (time()-604800) && $value < time()) {
                outputRow('Redis Daemon','pass','read a recent timestamp from Redis: '.$value);
        } else {
                $redis_handler->Set($test_key, time());
                sleep(1);
                $value = $redis_handler->Get($test_key);
                if ($value && $value > (time()-3) && $value < time()) {
                        outputRow('Redis Daemon','pass','tested writing and reading: '.$value);
                } else {
                        outputRow('Redis Daemon','error','not read a value from redis');
                }
        }

	//set for next time!
        $redis_handler->Set($test_key, time());

		print "<br>";

##################################################

if (empty($CONF['memcache']))
	die("nothing configured in memcache");

$mkey = "test.php"; //use the same key, using the namespace to avoid collisions!

foreach ($CONF['memcache'] as $key => $value) {
	if ($value == 'redis') {
		print "memcache\[$key\] is using redis<br>";
		if ($key == 'app') {

			//print "memcache->redis = {$memcache->redis}<br>";

			$object =& $memcache;
		} elseif ($key == 'adodb') {
			$object =& $ADODB_MEMCACHE_OBJECT;
		} elseif ($key == 'sessions') {
                        $object =& $memcachesession;
		} elseif ($key == 'smarty') {

			//needs to be expressly called!
			$smarty = new GeographPage;

			$object =& $GLOBALS['memcached_res'];
		} else {
			print "unknown key!<br>";
		}

		if ($object->redis)
			print "Has a 'redis' member<br>";

		$value = $object->name_get($key,$mkey);
		print "> exiting value = $value<br>";

		$timeout = rand(4,20);
		$value = time();
		print "> setting value for $timeout seconds<br>";
		$r = $object->name_set($key,$mkey,$value,false,$timeout);
		var_dump($r);
		print "<br>";

	} else {
		//technically could still run the above tests, as they dont actully use redis specific functions!
		print "memcache\[$key\] is NOT configured as redis<br>";
	}
}

