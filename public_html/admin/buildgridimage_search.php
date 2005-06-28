<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);




	//this takes a long time, so we output a header first of all
	$smarty->display('_std_begin.tpl');
	echo "<h3>Rebuilding gridimage_search Table...</h3>";
	flush();
	set_time_limit(3600*24);
	
	$db->Execute("DELETE FROM gridimage_search");
	
	$db->Execute("INSERT INTO gridimage_search
SELECT gridimage_id, gi.user_id, moderation_status, title, submitted, imageclass, imagetaken, upd_timestamp, x, y, gs.grid_reference, user.realname,reference_index,comment
FROM gridimage AS gi
INNER JOIN gridsquare AS gs
USING ( gridsquare_id ) 
INNER JOIN user ON ( gi.user_id = user.user_id ) 
WHERE moderation_status != 'rejected' ");
	echo "<h3>Finished.</h3>";

	$smarty->display('_std_end.tpl');
	exit;
	


	
?>
