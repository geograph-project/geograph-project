<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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




$smarty = new GeographPage;

$template = 'js_categories.tpl';
$cacheid = isset($_GET['full']);

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (empty($db)) die('Database connection failed');

	
	$having = isset($_GET['full'])?'':'cnt>5 and';

	$arr = $db->getCol("select imageclass,count(*) as cnt  from gridimage_search  group by imageclass   having $having length(imageclass)>0;");
	
	$smarty->assign_by_ref('classes',$arr);
	
}

header("Cache-Control: Public");
header("Expires: ".date("D, d M Y H:i:s",time()+3600*3 )." GMT");

header("Content-type: text/javascript");

//always turn off debugging, it will break the js
$smarty->debugging=false;
$smarty->display($template, $cacheid);

	
?>
