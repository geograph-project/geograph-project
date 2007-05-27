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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

function mcsopen($save_path, $session_name)
{
	return true;
}

function mcsclose()
{
	return true;
}

function mcsread($id)
{
	global $memcachesession;
	$tmp=& $memcachesession->name_get('s',$id);
	return $tmp;
}

function mcswrite($id, $sess_data)
{
	global $memcachesession;
	return $memcachesession->name_set('s',$id,$sess_data,$memcachesession->compress,$memcachesession->period);
}

function mcsdestroy($id)
{
	global $memcachesession;
	return $memcachesession->name_delete('s',$id);
}

function mcsgc($maxlifetime)
{
	return true;
}

session_set_save_handler("mcsopen", "mcsclose", "mcsread", "mcswrite", "mcsdestroy", "mcsgc");

?>