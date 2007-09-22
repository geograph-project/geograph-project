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

#this seems to break some sessions, and is NOT needed anyway
#init_session();




$smarty = new GeographPage;

$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):0;

$template = 'js_categories.tpl';
$cacheid = "cat|$u.".isset($_GET['full']);

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (empty($db)) die('Database connection failed');

	if ($u) {
		$where = "where submitted > date_sub(now(),interval 1 day) and user_id = $u";
		$table = 'gridimage';
		$smarty->assign('varname','catListUser');
	} else {
		$where = '';
		$table = 'gridimage_search';
		$smarty->assign('varname','catList');
	}
	$having = isset($_GET['full'])?'':'cnt>5 and';

	$arr = $db->getCol("select imageclass,count(*) as cnt  from $table $where group by imageclass   having $having length(imageclass)>0;");
	
	$smarty->assign_by_ref('classes',$arr);
	
}

if ($u) {
	customExpiresHeader(300,false);
} else {
	customExpiresHeader(3600*3,true);
}

header("Content-type: text/javascript");

customGZipHandlerStart();

//always turn off debugging, it will break the js
$smarty->debugging=false;
$smarty->display($template, $cacheid);

	
?>
