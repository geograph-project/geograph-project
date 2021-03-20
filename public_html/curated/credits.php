<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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


$smarty->display('_std_begin.tpl');

	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

print "<h2>Curated Education Images</h2>";

if ($USER->registered) {

	$clrf = md5($USER->user_id.'.'.$CONF['token_secret'].'.'.date('G'));

	if (isset($_POST['pref']) && $_POST['clrf'] == $clrf) {
		$USER->setPreference('curated.credit', $_POST['pref']);
	}
}

$rows= $db->getAssoc("select c.user_id,realname,count(*) count,value from curated1 c inner join user u using (user_id) left join user_preference p on (p.user_id = c.user_id and pkey = 'curated.credit') group by c.user_id order by rand()");

if ($USER->registered && isset($rows[$USER->user_id])) {
	$row = $rows[$USER->user_id];
?>
	<h3>Your Preference</h3>
	<form method=post>
	<input type=hidden name=clrf value="<? echo $clrf; ?>">
	<select name=pref onchange="document.getElementById('btn').style.display = ''">
<?
	$options = array(''=>'Anonymous','nocount'=>'Show but only name','count'=>'Show with count');
	foreach ($options as $key => $value)
		printf('<option value="%s"%s>%s</option>',$key,($row['value'] == $key)?' selected':'',$value);
?>
	</select>
	<input type=submit id="btn" style="display:none">
	</form>
	<hr>
	<br>
<? }

$anon=0;
print "Credits: "; $sep = '';
foreach ($rows as $user_id => $row) {
	if (empty($row['value'])) {
		$anon++;
		unset($rows[$user_id]);
		continue;
	} elseif($row['value'] == 'nocount') {
		$rows[$user_id]['count'] = null;
	}

	print "$sep <a href=\"/profile/{$user_id}\">".htmlentities($row['realname'])."</a>";
	if ($rows[$user_id]['count'] > 1)
		print "({$rows[$user_id]['count']})";
	$sep = ', ';
}

if (!empty($anon)) {
	if ($sep)
		print " and ";
	print "$anon Anonymous User(s)";
}

$smarty->display('_std_end.tpl');



