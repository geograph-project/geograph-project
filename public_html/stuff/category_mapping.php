<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
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

$smarty = new GeographPage;

$USER->mustHavePerm("basic");



if (!empty($_POST) && !empty($_POST['submitone'])) {
	$db = GeographDatabaseConnection(false);

}

if (empty($db))
	$db = GeographDatabaseConnection(true);


$action = (isset($_GET['action']) && ctype_alnum($_GET['action']))?$_GET['action']:'reuse';
$smarty->assign('action',$action);



$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($_GET['subject'])) {
	$crit = $db->Quote($_GET['subject']);
	$suggestions = $db->getAll("SELECT m.*,count(*) images from category_mapping m inner join gridimage_search gi using (imageclass) where m.imageclass like $crit OR m.canonical like $crit group by imageclass");
	$smarty->assign('subject',$_GET['subject']);
} else {
	$suggestions = $db->getAll("SELECT m.*,count(*) images from category_mapping m inner join gridimage_search gi using (imageclass) where user_id = {$USER->user_id} group by imageclass");
}
$smarty->assign_by_ref('suggestions',$suggestions);


$smarty->display('stuff_category_mapping.tpl');


