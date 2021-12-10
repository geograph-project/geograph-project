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

	$redis_scan = (!empty($m->redis)); //if has redis member, its actully redis, so we can use it for clear scans!
	$mysql_scan = !$redis_scan;

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
			if ($mysql_scan) {
				// store the metadata in mysql
				$db=$m->_getDB();
				$db->Execute("REPLACE INTO smarty_cache_page VALUES('{$CONF['template']}',".$db->Quote($cache_file).",".$db->Quote($tpl_file).",".$db->Quote($cache_id).",NOW(),".intval($exp_time).")"); 
			}
			if ($redis_scan) {
				//write a secondary index in redis

				//prefix indexes
				if(strpos($cache_id, '|') !== false) { //todo, mayby only if img|user|article etc? (ie used for tpl_file'less clear)
					$key2 = '';
					foreach (explode('|',$cache_id) as $key) {
						if (empty($key)) continue;

						$key2 .= "$key|";

						  $m->redis->hSet($CONF['template'].'pr'.$key2, $cache_file, 1);
if (!empty($m->redis->debug))
	print "hSet({$CONF['template']}pr$key2, $cache_file, 1)\n";
						$m->redis->expire($CONF['template'].'pr'.$key2, 86400);
					}
				}

				//template file index
				if (!empty($cache_id) && !preg_match('/\|$/',$cache_id))
	                               	$cache_id .="|"; //our hashes always has | always on the end!

				// set even if cache_id is empty!
				  $m->redis->hSet($CONF['template'].'tpl'.$tpl_file, $cache_id, $cache_file);
if (!empty($m->redis->debug))
	print "hSet({$CONF['template']}tpl$tpl_file, $cache_id, $cache_file)\n";
				$m->redis->expire($CONF['template'].'tpl'.$tpl_file, 86400);
			}
		} else {
			$smarty_obj->trigger_error("cache_handler: set failed.");
		}

		$return = true;
		break;

	case 'clear':
		if ($mysql_scan) {
			$db=$m->_getDB();
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
			$recordSet = $db->Execute("SELECT Folder,CacheID FROM smarty_cache_page WHERE $where");
			while (!$recordSet->EOF) {
				$r += $m->delete($recordSet->fields['Folder'].$recordSet->fields['CacheID']);
				$recordSet->MoveNext();
			}
			$recordSet->Close();
			$db->Execute("DELETE FROM smarty_cache_page WHERE $where");
		}
		if ($redis_scan) {
			if(empty($cache_id) && empty($compile_id) && empty($tpl_file)) {
			       // ... um, maybe delete the whole database: FLUSHDB. Although mayeb could some sort of scan if $exp_time is set

			} elseif (!empty($cache_id)) {
				 if(strpos($cache_id, '|') !== false) {
					if (!preg_match('/\|$/',$cache_id))
						$cache_id .="|"; //our hashes always has | always on the end!

					if (!empty($tpl_file)) {
						//scan the keys for the file
if (!empty($m->redis->debug))
	print "hScan({$CONF['template']}tpl$tpl_file, \"$cache_id*\"); ";
						$it = NULL;
						$keys = array();
						/* Don't ever return an empty array until we're done iterating */
						$m->redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
						while($arr_keys = $m->redis->hScan($CONF['template'].'tpl'.$tpl_file, $it, "$cache_id*")) {
							if (!empty($arr_keys)) {
								$keys += $arr_keys;
								foreach ($arr_keys as $field => $dummy) //technically could pass all the keys at once!
									$m->redis->hDel($CONF['template'].'tpl'.$tpl_file, $field);
							}
						}
					} else {
						//get all the keys for prefix (we DONT do a prefix scan, as the seting a whole list at each prefix)
if (!empty($m->redis->debug))
	print "hGetAll({$CONF['template']}pr$cache_id); ";
						$keys = array_keys($m->redis->hGetAll($CONF['template'].'pr'.$cache_id)); //returns key/value array
						$m->redis->del($CONF['template'].'pr'.$cache_id);
					}
				} else {
					if (!empty($tpl_file)) {
						//easy! delete the cache id directly! No need to use the 'secondary index'
if (!empty($m->redis->debug))
	print "del({$CONF['template']}$cache_file);\n";
						$m->delete($CONF['template'].$cache_file);
						if (!empty($cache_id) && !preg_match('/\|$/',$cache_id))
							$cache_id .="|"; //our hashes always has | always on the end!
						$m->redis->hDel($CONF['template'].'tpl'.$tpl_file, $cache_id);
						return true;
					} else {
						//umm, in theory should never get here? (wont scan all templates for non-prefix cache id)
					}
				}
			} else {
				//all the caches for the file
if (!empty($m->redis->debug))
	print "hGetAll({$CONF['template']}tpl$tpl_file); ";
				$keys = $m->redis->hGetAll($CONF['template'].'tpl'.$tpl_file); //returns key/value array
				$m->redis->del($CONF['template'].'tpl'.$tpl_file);
			}

			if (!empty($keys)) {
if (!empty($m->redis->debug)) {
	print " Keys = ".count($keys)."\n";
	print_r($keys);
}
				foreach ($keys as $cache_file)
					$m->delete($CONF['template'].$cache_file);
			} else {
if (!empty($m->redis->debug))
	print " No Keys\n";
			}
		}

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

