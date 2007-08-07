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
	
	function MultiServerMemcache(&$conf,$debug = false) {
		//parent::__construct();
		if (!$conf['host'])
			return;
		$valid = false;
		if (@$this->connect($conf['host'], $conf['port']))
			$valid = true;
		elseif ($debug) 
			die(" Can't connect to memcache server on: {$conf['host']}, {$conf['port']}<br>\n");
			
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

	function delete($namespace, $key, $timeout = 0) {
		if (!$this->valid) return false;
		return parent::delete($this->prefix.$key, $timeout);
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
}

?>