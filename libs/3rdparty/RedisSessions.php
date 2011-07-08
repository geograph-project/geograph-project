<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
 
require dirname(__FILE__)."/RedisServer.php";

$redis_handler = NULL;
$session_stat = array();

function redis_session_open($save_path, $session_name)
{
	global $redis_handler,$CONF;
	
	if ($redis_handler === NULL)
	{
		$redis_handler = new RedisServer($CONF['redis_host'], $CONF['redis_port']);
		if (!empty($CONF['redis_db']))
		{
			$redis_handler->Select($CONF['redis_db']);
		}
	}
}

function redis_session_close()
{
	global $redis_handler;
	#$redis_handler = NULL;
}

function redis_session_read($id)
{
	global $redis_handler,$session_stat;
	$key = session_name().":".$id;

	$sess_data = $redis_handler->Get($key);
	if ($sess_data === NULL)
	{
		return "";
	}
	$session_stat[$key] = md5($sess_data);
	
	return unserialize($sess_data);
}

function redis_session_write($id, $sess_data)
{
	global $redis_handler,$session_stat;
	$key = session_name().":".$id;
	$lifetime = ini_get("session.gc_maxlifetime");
	
	$sess_data = serialize($sess_data);
	
	
	//check if anything changed in the session, only send if has changed
	if (!empty($session_stat[$key]) && $session_stat[$key] == md5($sess_data)) {
		//just sending EXPIRE should save a lot of bandwidth!
		$redis_handler->Expire($key, $lifetime);
	} else {
		$redis_handler->SetEx($key, $lifetime, $sess_data);
	}
}

function redis_session_destroy($id)
{
	global $redis_handler;
	$key = session_name().":".$id;

	$redis_handler->Del($key);
}

function redis_session_gc($maxlifetime)
{
	//redis expires keys automatically (using SetEx)
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
