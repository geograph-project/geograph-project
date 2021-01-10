<?php
/**
 * $Project: GeoGraph $
 * $Id: RedisSessions.php 8023 2014-04-05 17:52:08Z barry $
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

$redis_handler = NULL;
$session_stat = array();

function redis_session_open($save_path, $session_name)
{
	global $redis_handler,$CONF;

	if (empty($redis_handler)) {
		$redis_handler = new Redis();
		$success = $redis_handler->connect($CONF['redis_host'], $CONF['redis_port']);

		if ($success && !empty($CONF['redis_session_db']))
		{
			$redis_handler->select($CONF['redis_session_db']);
		}
		return $success;
	} else {
		return $redis_handler->ping()?true:false;
	}
}

function redis_session_close()
{
	global $redis_handler;
	$redis_handler = NULL;
	return true;
}

function redis_session_read($id)
{
	global $redis_handler,$session_stat;
	$key = session_name().":".$id;

	$sess_data = $redis_handler->get($key);
	if (empty($sess_data))
	{
		return "";
	}
	$session_stat[$key] = md5($sess_data);
	return $sess_data;
}

function redis_session_write($id, $sess_data)
{
	global $redis_handler,$session_stat;

	$default = "user|O:12:\"GeographUser\":5:{s:7:\"user_id\";i:0;s:10:\"registered\";b:0;s:9:\"autologin\";b:0;s:5:\"stats\";a:0:{}s:16:\"use_autocomplete\";b:0;}searchq|N;";
	if ($sess_data == $default) {
		//bit of a bodge, but dont bother saving the 'uncustomised' session!
		return true;
	}

	$key = session_name().":".$id;
	$lifetime = ini_get("session.gc_maxlifetime");

	//check if anything changed in the session, only send if has changed
	if (!empty($session_stat[$key]) && $session_stat[$key] == md5($sess_data)) {
		//just sending EXPIRE should save a lot of bandwidth!
		$redis_handler->expire($key, $lifetime);
	} else {
		$redis_handler->setEx($key, $lifetime, $sess_data);
	}
	return true;
}

function redis_session_destroy($id)
{
	global $redis_handler;
	$key = session_name().":".$id;

	$redis_handler->del($key);
	return true;
}

function redis_session_gc($maxlifetime)
{
	//redis expires keys automatically (using SetEx)
	return true;
}

function redis_session_install()
{
	session_set_save_handler(
		"redis_session_open",
		"redis_session_close",
		"redis_session_read",
		"redis_session_write",
		"redis_session_destroy",
		"redis_session_gc"
	);
	register_shutdown_function('session_write_close');
}
