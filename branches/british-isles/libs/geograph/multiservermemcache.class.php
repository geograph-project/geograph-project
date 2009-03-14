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
	
	function MultiServerMemcache(&$conf,$debug = false) {
		//parent::__construct();
		if (empty($conf['host']) && empty($conf['host1']))
			return;
		$valid = false;
		if (!empty($conf['host'])) {
			if (@$this->connect($conf['host'], $conf['port']))
				$valid = true;
			elseif ($debug) 
				die(" Can't connect to memcache server on: {$conf['host']}, {$conf['port']}<br>\n");
		}	
		if (!empty($conf['host1'])) {
			if (@$this->addServer($conf['host1'], $conf['port1']))
				$valid = true;
			elseif ($debug) 
				die(" Can't connect to memcache server on: {$conf['host1']}, {$conf['port1']}<br>\n");
		}
		
		if (!empty($conf['host2'])) {
			if (@$this->addServer($conf['host2'], $conf['port2']))
				$valid = true;
			elseif ($debug) 
				die(" Can't connect to memcache server on: {$conf['host2']}, {$conf['port2']}<br>\n");
		}
		
		if (!empty($conf['host3'])) {
			if (@$this->addServer($conf['host3'], $conf['port3']))
				$valid = true;
			elseif ($debug) 
				die(" Can't connect to memcache server on: {$conf['host3']}, {$conf['port3']}<br>\n");
		}
		
		if (!empty($conf['host4'])) {
			if (@$this->addServer($conf['host4'], $conf['port4']))
				$valid = true;
			elseif ($debug) 
				die(" Can't connect to memcache server on: {$conf['host4']}, {$conf['port4']}<br>\n");
		}
		
		if ($this->valid = $valid) {
			$this->prefix = isset($conf['p'])?($conf['p'].'~'):'';
		}
	}

	//extended to allow quick exit if memcache not in use
	// and to have a global simple namespace;
	function set($key, &$val, $flag = false, $expire = 0) {
		if (!$this->valid) return false;
		return parent::set($this->prefix.$key, $val, $flag, $expire);
	}

	function get($key) {
		if (!$this->valid) return false;
		$tmp =& parent::get($this->prefix.$key);
		return $tmp;
	}

	function delete($key, $timeout = 0) {
		if (!$this->valid) return false;
		return parent::delete($this->prefix.$key, $timeout);
	}

	function increment($key, $value = 1,$create = false) {
		if (!$this->valid) return false;
		$v = parent::increment($this->prefix.$key, $value);
		if ($v === false && $create) {
			$this->set($key,$value);
		}
		return $v;
	}

	function decrement($key, $value = 1,$create = false) {
		if (!$this->valid) return false;
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
		return parent::set($this->prefix.$namespace.':'.$key, $val, $flag, $expire);
	}

	function name_get($namespace, $key) {
		if (!$this->valid) return false;
		$tmp =& parent::get($this->prefix.$namespace.':'.$key);
		return $tmp;
	}

	function name_delete($namespace, $key, $timeout = 0) {
		if (!$this->valid) return false;
		return parent::delete($this->prefix.$namespace.':'.$key, $timeout);
	}

	function name_increment($namespace, $key, $value = 1,$create = false) {
		return $this->increment($namespace.':'.$key, $value, $create);
	}

	function name_decrement($namespace, $key, $value = 1,$create = false) {
		return $this->decrement($namespace.':'.$key, $value, $create);
	}
	
	/**
	 * get stored db object, creating if necessary
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

?>