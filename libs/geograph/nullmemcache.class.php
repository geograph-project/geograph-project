<?php

/**
 * Null Object pattern implementation for Cache.
 * Returns false for all data manipulation methods.
 * Used when a cache section is missing or the PECL extension is unavailable.

In particular code to just call $memcache->get() which will just fail fase if no cache avaialble!

 */

	class NullMemcache {
                public $valid = false;
		function set($key, &$val, $flag = false, $expire = 0) {return false;}
		function get($key) {return false;}
		function delete($key, $timeout = 0) {return false;}
		function increment($key, $value = 1,$create = false) {return false;}
		function decrement($key, $value = 1,$create = false) {return false;}
		function name_set($namespace, $key, &$val, $flag = false, $expire = 0) {return false;}
		function name_get($namespace, $key) {return false;}
		function name_delete($namespace, $key, $timeout = 0) {return false;}
		function name_increment($namespace, $key, $value = 1,$create = false) {return false;}
		function name_decrement($namespace, $key, $value = 1,$create = false) {return false;}
	}
