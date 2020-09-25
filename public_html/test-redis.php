<?php

ini_set('display_errors',1);
//print "."; flush(); //so that can see the eror produced by session class!

require_once('geograph/global.inc.php');




if (empty($CONF['redis_host']))
	die("redis_host not configired");



        if (empty($redis)) {
                $redis = new Redis();
                $redis->connect($CONF['redis_host'], $CONF['redis_port']);
        }
        if (!empty($CONF['redis_api_db']))
                $redis->select($CONF['redis_api_db']);



##################################################

if (!empty($_GET['info'])) {
	print "<pre>";
	print_r($redis->info());
	exit;
}

##################################################
// stolen from test.php for now!
	function outputRow($one,$two,$three) {
		print htmlentities("$two: $one: $three")."<br>";
	}


        $test_key = "test.php-timestamp";
        $value = $redis->get($test_key);

        if ($value && $value > (time()-604800) && $value < time()) {
                outputRow('Redis Daemon','pass','read a recent timestamp from Redis: '.$value);
        } else {
                $redis->set($test_key, time());
                sleep(1);
                $value = $redis->get($test_key);
                if ($value && $value > (time()-3) && $value < time()) {
                        outputRow('Redis Daemon','pass','tested writing and reading: '.$value);
                } else {
                        outputRow('Redis Daemon','error','not read a value from redis');
                }
        }

	//set for next time!
        $redis->set($test_key, time());

		print "<br>";

##################################################

if (empty($CONF['memcache']))
	die("nothing configured in memcache");

$mkey = "test.php"; //use the same key, using the namespace to avoid collisions!

foreach ($CONF['memcache'] as $key => $value) {
	if (isset($value['redis'])) {
		print "<hr><b>memcache\[$key\] is using redis</b><br>";
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

