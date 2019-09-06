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

	var $redis=null;

	function MultiServerMemcache(&$conf,$debug = false) {
		if ($conf == 'redis') {
			//kind of a hack, but functional enough.
			global $CONF;
			global $redis_handler;
			if (empty($redis_handler)) {
		                require("3rdparty/RedisServer.php");
	        	        $redis_handler = new RedisServer($CONF['redis_host'], $CONF['redis_port']);
	        	}
			$this->redis = $redis_handler;
			$this->prefix = $CONF['redis_db'].'~';
			//if ($redis_handler->connection)
				$this->valid = true;
			return;
		}

		if (empty($conf['host']) && empty($conf['host1']))
			return;

		$valid = false;
		foreach (array('','1','2','3','4') as $b)
			if (!empty($conf['host'.$b])) {
				if (@$this->connect($conf['host'.$b], $conf['port'.$b]))
					$valid = true;
				elseif ($debug)
					die(" Can't connect to memcache server on: ".$conf['host'.$b].", ".$conf['port'.$b]."<br>\n");
			}

		if ($this->valid = $valid) {
			$this->prefix = isset($conf['p'])?($conf['p'].'~'):'';
		}
	}

	//extended to allow quick exit if memcache not in use
	// and to have a global simple namespace;
	function set($key, &$val, $flag = false, $expire = 0) {
		if (!$this->valid) return false;
		if ($this->redis) {
			if ($expire)
				return $this->redis->SetEx($this->prefix.$key, $expire, $val);
			else
				return $this->redis->Set($this->prefix.$key, $val);
		}
		return parent::set($this->prefix.$key, $val, $flag, $expire);
	}

	function get($key) {
		if (!$this->valid) return false;
		if ($this->redis)
			return $this->redis->Get($this->prefix.$key);
		return parent::get($this->prefix.$key);
	}

	function delete($key, $timeout = 0) {
		if (!$this->valid) return false;
		if ($this->redis)
                        return $this->redis->Del($this->prefix.$key);
		return parent::delete($this->prefix.$key, $timeout);
	}

	function increment($key, $value = 1,$create = false) {
		if (!$this->valid) return false;
		if ($this->redis)
                        return $this->redis->IncrBy($this->prefix.$key, $value);
		$v = parent::increment($this->prefix.$key, $value);
		if ($v === false && $create) {
			$this->set($key,$value);
		}
		return $v;
	}

	function decrement($key, $value = 1,$create = false) {
		if (!$this->valid) return false;
		if ($this->redis)
                        return $this->redis->DecrBy($this->prefix.$key, $value);
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
			if ($expire)
				return $this->redis->SetEx($this->prefix.$namespace.':'.$key, $expire, $val);
			else
				return $this->redis->Set($this->prefix.$namespace.':'.$key, $val);
		}
		return parent::set($this->prefix.$namespace.':'.$key, $val, $flag, $expire);
	}

	function name_get($namespace, $key) {
		if (!$this->valid) return false;
		if ($this->redis)
                        return $this->redis->Get($this->prefix.$namespace.':'.$key);
		return parent::get($this->prefix.$namespace.':'.$key);
	}

	function name_delete($namespace, $key, $timeout = 0) {
		if (!$this->valid) return false;
		if ($this->redis)
                        return $this->redis->Del($this->prefix.$namespace.':'.$key);
		return parent::delete($this->prefix.$namespace.':'.$key, $timeout);
	}

	function name_increment($namespace, $key, $value = 1,$create = false) {
		return $this->increment($namespace.':'.$key, $value, $create);
	}

	function name_decrement($namespace, $key, $value = 1,$create = false) {
		return $this->decrement($namespace.':'.$key, $value, $create);
	}

}

