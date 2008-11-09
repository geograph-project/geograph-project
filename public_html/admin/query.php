<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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


if (isset($_POST['submit'])) {

	$updates= $_POST;
	unset($updates['submit']);

	if (empty($updates['id'])) {
		unset($updates['id']);
		$db->Execute('INSERT INTO queries SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		$i = $db->Insert_ID();
	} else {
		$i = intval($updates['id']);
		unset($updates['id']);
		
		$db->Execute('UPDATE queries SET `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE id='.$i,array_values($updates));
	}	
	
	
	$smarty->assign_by_ref("i",$i);
	
} else {
	$i = intval($_GET['i']);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$row = $db->getRow("SELECT * FROM queries WHERE id = $i");
	$smarty->assign_by_ref("row",$row);
	$smarty->assign_by_ref("id",$i);
	
	$desc = $db->getAssoc("DESCRIBE queries");
	
	
	foreach ($desc as $col => $data) {
		if (preg_match('/(set|enum)\(\'(.*)\'\)/',$data['Type'],$m)) {
			$desc[$col]['values'] = array_combine(explode("','",$m[2]),explode("','",$m[2]));
			$desc[$col]['multiple'] = ($m[1] == 'set')?' multiple':'';
		} elseif (preg_match('/(char|int)\((\d+)\)/',$data['Type'],$m)) {
			$desc[$col]['size'] = min(60,$m[2]);
			$desc[$col]['maxlength'] = $m[2];
		}
	}
	$smarty->assign_by_ref("desc",$desc);
	
	$map = array(
		'limit1' => 'user_id',
		'limit2' => 'moderation_status',
		'limit3' => 'imageclass',
		'limit4' => 'reference_index',
		'limit5' => 'myriad',
		'limit6' => 'submitted',
		'limit7' => 'imagetaken',
		'limit8' => 'distance',
		'limit9' => 'gridimage_post',
		'limit10' => 'route_id',
	);
	$smarty->assign_by_ref("map",$map);	
	
}



$smarty->display('admin_query.tpl');
exit;
?>