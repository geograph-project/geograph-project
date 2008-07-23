<?php

/**
 * Project: Smarty memcached cache handler function
 * Author: Mads Sülau Jørgensen <php at mads dot sulau dot dk>
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
function memcache_cache_handler($action, &$smarty_obj, &$cache_content, $tpl_file=null, $cache_id=null, $compile_id=null, $exp_time=null) {
	// ref to the memcache object
	$m = $GLOBALS['memcached_res'];
	
	// the key to store cache_ids under, used for clearing
	$key = 'smarty_caches';

	// check memcache object
	if (!in_array(strtolower(get_class($m)),array('multiservermemcache','memcached'))) {
        $smarty_obj->trigger_error('cache_handler: $GLOBALS[\'memcached_res\'] is not a memcached object');
		return false;
	}
	
	// unique cache id
	$cache_id = ($tpl_file.$cache_id.$compile_id);
	
	switch ($action) {
	case 'read':
		// grab the key from memcached
		$contents = $m->get($cache_id);
		
		// use compression
		if($smarty_obj->use_gzip && function_exists("gzuncompress")) {
			$cache_content = gzuncompress($contents);
		} else {
			$cache_content = $contents;
		}
		
		$return = true;
		break;
	
	case 'write':
		// use compression
		if($smarty_obj->use_gzip && function_exists("gzcompress")) {
			$contents = gzcompress($cache_content);
		} else {
			$contents = $cache_content;
		}
		
		// add the cache_id to the $key string
		$caches = $m->get($key);
		if (!is_array($caches)) {
			$caches = array($cache_id);
			$m->set($key, $caches);
		} else if (!in_array($cache_id, $caches)) {
			array_push($caches, $cache_id);
			$m->set($key, $caches);
		}
		
		// store the value in memcached
		$stored = $m->set($cache_id, $contents);
		
		if(!$stored) {
			$smarty_obj->trigger_error("cache_handler: set failed.");
		}
		
		$return = true;
		break;
	
	case 'clear':
		if(empty($cache_id) && empty($compile_id) && empty($tpl_file)) {
			// get all cache ids
			$caches = $m->get($key);
			
			if (is_array($caches)) {
				$len = count($caches);
				for ($i=0; $i<$len; $i++) {
					// assume no errors
					$m->delete($caches[$i]);
				}
				
				// delete the cache ids
				$m->delete($key);
				
				$result = true;
			}
		} else {
			$result = $m->delete($cache_id);
		}
		if(!$result) {
			$smarty_obj->trigger_error("cache_handler: query failed.");
		}
		$return = true;
		break;
		
	default:
		// error, unknown action
		$smarty_obj->trigger_error("cache_handler: unknown action \"$action\"");
		$return = false;
		break;
	}
	
	return $return;
}

?>