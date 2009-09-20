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
	global $CONF;
	
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
		$cache_content = $m->get($CONF['template'].$cache_file);
		
		// use compression?
		if(!empty($smarty_obj->use_gzip) && function_exists("gzuncompress")) {
			$cache_content = gzuncompress($cache_content);
		} 
		
		$return = true;
		break;
	
	case 'write':
		// use compression?
		if(!empty($smarty_obj->use_gzip) && function_exists("gzcompress")) {
			$cache_content = gzcompress($cache_content);
		} 
		
		// store the value in memcached
		$stored = $m->set($CONF['template'].$cache_file, $cache_content, false, (int)$exp_time);
		
		if($stored) {
			// store the metadata in mysql
			$db=&$m->_getDB();
			$db->Execute("REPLACE INTO smarty_cache_page VALUES('{$CONF['template']}',".$db->Quote($cache_file).",".$db->Quote($tpl_file).",".$db->Quote($cache_id).",NOW(),".intval($ttl).")"); 
		} else {
			$smarty_obj->trigger_error("cache_handler: set failed.");
		}
		
		$return = true;
		break;
	
	case 'clear':
		$db=&$m->_getDB();
		$where = '1';
		if (!empty($smarty_obj->clear_this_template_only)){ 
			$where = " Folder = '{$CONF['template']}'";
		}
		if(empty($cache_id) && empty($compile_id) && empty($tpl_file)) {
			// get all cache ids
		} else {
			if (!empty($tpl_file)) {
				$where.=" AND TemplateFile='" .$tpl_file ."'";
			}
			if(strpos($cache_id, '|') !== false) {
				$where.=" AND GroupCache LIKE ".$db->Quote($cache_id.'%');
			} else {
				$where.=" AND CacheID=".$db->Quote($cache_file);
			}
		} 
		$r = 1;
		$recordSet = &$db->Execute("SELECT Folder,CacheID FROM smarty_cache_page WHERE $where");
		while (!$recordSet->EOF) {
			$r += $m->delete($recordSet->fields['Folder'].$recordSet->fields['CacheID']);
			$recordSet->MoveNext();
		}
		$recordSet->Close();
		$db->Execute("DELETE FROM smarty_cache_page WHERE $where");
		
		if(!$r) {
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