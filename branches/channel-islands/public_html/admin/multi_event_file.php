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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
require_once('geograph/image.inc.php');
	require_once('geograph/event.class.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;




$db = NewADOConnection($GLOBALS['DSN']);



if (isset($_GET['edit_4_linking_post']))
{
	
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"events.php\">&lt;&lt;</a> Firing Events...</h3>";
	flush();
	
	$posts = $db->getCol("select post_id from geobb_posts where post_text LIKE '%[[%'");
	
	foreach ($posts as $post_id) {
		new Event('topic_edit', $post_id);
	}
	$smarty->display('_std_end.tpl');
	exit;
	
	
} elseif (isset($_GET['topic_id']))
{
	
	$smarty->display('_std_begin.tpl');
	echo "<h3><a href=\"events.php\">&lt;&lt;</a> Firing Events...</h3>";
	flush();
	
	$posts = $db->getCol("select post_id from geobb_posts where topic_id = ".$_GET['topic_id']." order by post_id");
	
	foreach ($posts as $post_id) {
		new Event('topic_edit', $post_id);
	}
	$smarty->display('_std_end.tpl');
	exit;
	
	
}



$smarty->display('admin_index.tpl');

	
?>
