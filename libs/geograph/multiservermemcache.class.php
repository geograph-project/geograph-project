<?php

/**
 * $Project: GeoGraph $
 * $Id: mapbrowse.php 2630 2006-10-18 21:12:28Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * Extends Memcache to :
 - configure from a config array
 - work with redis!
 - automatically connect to multiple servers
 - allow multiple client clusters to use the same server
 - simplistic support for namespaces
 */

class MultiServerMemcache extends Memcache {
	
	//contstants
	var $period_temp = 60;
	var $period_short = 3600; //hour
	var $period_med = 86400; //24h;
	var $period_long = 604800; //7day
	
	//extra variables
	var $compress = false; //|| MEMCACHE_COMPRESSED
	var $valid = false;
	
	var $db=null;
	var $redis=null;

	function MultiServerMemcache(&$conf,$debug = false) {

		if (isset($conf['redis'])) {
			//kind of a hack, but functional enough.
			global $CONF;
			$this->redis = new Redis();
			$this->redis->connect($CONF['redis_host'], $CONF['redis_port']);
			if (is_numeric($conf['redis']))
				$this->redis->select($conf['redis']);
			$this->prefix = isset($conf['p'])?($conf['p'].'~'):'';
			//if ($this->redis->ping())
				$this->valid = true;
			return;
		}

		if (empty($conf['host']) && empty($conf['host1']))
			return;

		parent::__construct();

		split_timer('memcache'); //starts the timer

		$valid = false;
		if (!empty($conf['host'])) {
			if (@$this->connect($conf['host'], $conf['port']))
				$valid = true;
			elseif ($debug) 
				die(" Can't connect to memcache server on: {$conf['host']}, {$conf['port']}<br>\n");
		}

		foreach (array('1','2','3','4') as $b)
			if (!empty($conf['host'.$b])) {
				if (@$this->addServer($conf['host'.$b], $conf['port'.$b]))
					$valid = true;
				elseif ($debug)
					die(" Can't connect to memcache server on: ".$conf['host'.$b].", ".$conf['port'.$b]."<br>\n");
			}

		if ($this->valid = $valid) {
			$this->prefix = isset($conf['p'])?($conf['p'].'~'):'';
		}
		
		split_timer('memcache','connect'); //logs the wall time	
	}

	//extended to allow quick exit if memcache not in use
	// and to have a global simple namespace;
	function set($key, &$val, $flag = false, $expire = 0) {
		if (!$this->valid) return false;
		if ($this->redis) {

if (!is_string($val) && !is_int($val)) {
	//memcache can deal with serializing complex varibles itself. Redis cant. it expects a string!

	//we have to use a second variable as the original is passed by reference which dont want to change.
	$val2 = "SERIALIZED:".serialize($val); //use a special prefix, so we can detect in get!

	$val =& $val2; //rebind it without affecting original!
}

			if ($expire)
				return $this->redis->setEx($this->prefix.$key, $expire, $val);
			else
				return $this->redis->set($this->prefix.$key, $val);
		}
		return parent::set($this->prefix.$key, $val, $flag, $expire);
	}

	function get($key,&$param1=null,&$param2=null) {
		if (!$this->valid) return false;
		if ($this->redis) {
			$r = $this->redis->get($this->prefix.$key);
			if (strlen($r) > 11 && substr($r,0,11) == "SERIALIZED:")
				$r = unserialize(substr($r,11));
			return $r;
		}
		return parent::get($this->prefix.$key);
	}

	function parent_get($key) {
		$tmp =& parent::get($key);
		return $tmp;
	}

	function delete($key, $timeout = 0) {
		if (!$this->valid) return false;
		if ($this->redis)
			return $this->redis->del($this->prefix.$key);
		return parent::delete($this->prefix.$key, $timeout);
	}

	function increment($key, $value = 1,$create = false) {
		if (!$this->valid) return false;
		if ($this->redis)
			return $this->redis->incrBy($this->prefix.$key, $value);
		$v = parent::increment($this->prefix.$key, $value);
		if ($v === false && $create) {
			$this->set($key,$value);
		}
		return $v;
	}

	function decrement($key, $value = 1,$create = false) {
		if (!$this->valid) return false;
		if ($this->redis)
			return $this->redis->decrBy($this->prefix.$key, $value);
		$v = parent::decrement($this->prefix.$key, $value);
		if ($v === false && $create) {
			$v2 = $value*-1;
			$this->set($key,$v2);
		}
		return $v;
	}

	//the following are basic currently, but setup to be able to add namespace invalidation
	//http://lists.danga.com/pipermail/memcached/2006-July/002545.html
	function name_set($namespace, $key, &$val, $flag = false, $expire = 0) {
		if (!$this->valid) return false;
		if ($this->redis) {

if (!is_string($val) && !is_int($val)) {
	//memcache can deal with serializing complex varibles itself. Redis cant. it expects a string!

	//we have to use a second variable as the original is passed by reference which dont want to change.
	$val2 = "SERIALIZED:".serialize($val); //use a special prefix, so we can detect in get!

	$val =& $val2; //rebind it without affecting original!
}


			if ($expire)
				return $this->redis->setEx($this->prefix.$namespace.':'.$key, $expire, $val);
			else
				return $this->redis->set($this->prefix.$namespace.':'.$key, $val);
		}

		if (isset($_GET['remote_profile'])) {
			$start = microtime(true);
			print "$start  :: name_Set($namespace, $key, ".@strlen($val).")<br>";
		}

		split_timer('memcache'); //starts the timer
		$tmp =& parent::set($this->prefix.$namespace.':'.$key, $val, $flag, $expire);
		split_timer('memcache','set',$namespace.':'.$key); //logs the wall time	
		return $tmp;
	}

	function name_get($namespace, $key) {
		if (!$this->valid) return false;
		if ($this->redis) {
			$tmp = $this->redis->get($this->prefix.$namespace.':'.$key);
			if (strlen($tmp) > 11 && substr($tmp,0,11) == "SERIALIZED:") {
				$r = unserialize(substr($tmp,11));
				$tmp =& $r;
			}
			return $tmp;
		}

		if (isset($_GET['remote_profile'])) {
			$start = microtime(true);
		}
		split_timer('memcache'); //starts the timer
		$tmp =& parent::get($this->prefix.$namespace.':'.$key);
		split_timer('memcache','get',$namespace.':'.$key); //logs the wall time	
		return $tmp;
	}

	function name_delete($namespace, $key, $timeout = 0) {
		if (!$this->valid) return false;
		if ($this->redis)
			return $this->redis->del($this->prefix.$namespace.':'.$key);

		split_timer('memcache'); //starts the timer
		$tmp = parent::delete($this->prefix.$namespace.':'.$key, $timeout);
		split_timer('memcache','delete',$namespace.':'.$key); //logs the wall time	
		return $tmp;
	}

	function name_increment($namespace, $key, $value = 1,$create = false) {
		return $this->increment($namespace.':'.$key, $value, $create);
	}

	function name_decrement($namespace, $key, $value = 1,$create = false) {
		return $this->decrement($namespace.':'.$key, $value, $create);
	}

        /**
         * get stored db object, creating if necessary
	 * Note, this looks odd on this object, but memcache_cache_handler.inc.php uses a Db as a companion!
         * @access private
         */
        function &_getDB()
        {
                if (!is_object($this->db))
                        $this->db=NewADOConnection(!empty($GLOBALS['DSN2'])?$GLOBALS['DSN2']:$GLOBALS['DSN']);
                if (!$this->db) die('Database connection failed');
                return $this->db;
        }

}


