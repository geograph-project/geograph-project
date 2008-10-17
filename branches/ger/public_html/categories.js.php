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

if (isset($_GET['days'])) {
	$_GET['days']=$_SESSION['days']=min(max(intval($_GET['days']),1),30);
} elseif (isset($_SESSION['days'])) {
	$_GET['days']=min(max(intval($_SESSION['days']),1),30);
} else {
	$_GET['days']=3;
}

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (empty($db)) die('Database connection failed');

	if ($u) {
		$where = "where submitted > date_sub(now(),interval {$_GET['days']} day) and user_id = $u";
		$having = isset($_GET['full'])?'':'having cnt>5';
		$table = 'gridimage';
		$smarty->assign('varname','catListUser');
		
		$arr = $db->getCol("select imageclass,count(*) as cnt from $table $where group by imageclass $having");
	} else {
		$where = isset($_GET['full'])?'':'where c>5';
		$table = 'category_stat';
		$smarty->assign('varname','catList');
	
		$arr = $db->getCol("select imageclass,c as cnt from $table $where");
	}
	
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
