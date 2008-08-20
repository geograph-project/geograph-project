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
	
	// check memcache object
	if (!in_array(strtolower(get_class($m)),array('multiservermemcache','memcached'))) {
		$smarty_obj->trigger_error('cache_handler: $GLOBALS[\'memcached_res\'] is not a memcached object');
		return false;
	}
	
	// unique cache id
	$_auto_id = $smarty_obj->_get_auto_id($cache_id,$compile_id);
	$cache_file = substr($smarty_obj->_get_auto_filename(".",$tpl_file,$_auto_id),2);
	
	switch ($action) {
	case 'read':
		// grab the key from memcached
		$contents = $m->get($cache_file);
		
		// use compression
		if(!empty($smarty_obj->use_gzip) && function_exists("gzuncompress")) {
			$cache_content = gzuncompress($contents);
		} else {
			$cache_content = $contents;
		}
		
		$return = true;
		break;
	
	case 'write':
		// use compression
		if(!empty($smarty_obj->use_gzip) && function_exists("gzcompress")) {
			$contents = gzcompress($cache_content);
		} else {
			$contents = $cache_content;
		}
		
		$current_time = time();
		if (is_null($exp_time) || $exp_time < $current_time)
			$ttl = 0;
		else
			$ttl = $exp_time - time(); 
		
		// store the metadata in mysql
		$db=&$m->_getDB();
		$db->Execute("REPLACE INTO smarty_cache_page VALUES(
			'$cache_file',
			'$tpl_file',
			'$cache_id')"); 
		
		// store the value in memcached
		$stored = $m->set($cache_file, $contents, false, $ttl);
		
		if(!$stored) {
			$smarty_obj->trigger_error("cache_handler: set failed.");
		}
		
		$return = true;
		break;
	
	case 'clear':
		$db=&$m->_getDB();
		if(empty($cache_id) && empty($compile_id) && empty($tpl_file)) {
			// get all cache ids
			$results = memcache_cache_handler_clear_helper($db,$m,'');
		} else {
			if(strpos($cache_id, '|') !== false) {
				if(!empty($tpl_file)) {
					$results = memcache_cache_handler_clear_helper($db,$m,"WHERE TemplateFile='" .$tpl_file ."' AND GroupCache LIKE '$cache_id%'");
				} else {
					$results = memcache_cache_handler_clear_helper($db,$m,"WHERE GroupCache LIKE '$cache_id%'");
				}
			} else {
				$results = memcache_cache_handler_clear_helper($db,$m,"WHERE CacheID='$cache_file'");
			}
		} 
		if(!$results) {
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

function memcache_cache_handler_clear_helper(&$db,&$m,$where = '') {
	$r = 1;
	$recordSet = &$db->Execute("SELECT CacheID FROM smarty_cache_page $where");
	while (!$recordSet->EOF) 
	{
		$cid = $recordSet->fields['CacheID'];

		$r += $m->delete($cid);

		$recordSet->MoveNext();
	}
	$recordSet->Close();
	$db->Execute("DELETE FROM smarty_cache_page $where");
	return $r;
}

?>