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
$USER->user_id == 135767 || $USER->user_id == 9181 || $USER->mustHavePerm("director");


$db = GeographDatabaseConnection(false);


####################################

if (!empty($_POST['processed'])) {
        foreach ($_POST['processed'] as $calendar_id => $dummy) {
                $calendar_id = intval($calendar_id);
		$db->Execute("UPDATE calendar SET status = 'processed' WHERE calendar_id = $calendar_id");
	}
}

####################################

$year = date('Y')+1; // we currently working on next years calendar

$list = $db->getAll("SELECT c.*,realname FROM calendar c INNER JOIN user USING (user_id) WHERE ordered > '1000-01-01' and year = '$year' ORDER BY ordered,calendar_id");

$stat = array();
$total = $orders = $processed = 0;
foreach ($list as $idx => &$row) {
	if ($row['ordered'] > '1000') {
		if (empty($row['alpha'])) {
			$row['alpha'] = chr(65+@$stat[$row['user_id']]); //starting at A
		}
		@$stat[$row['user_id']]++;
	}
	if ($row['paid'] > '1000') {
		$total += $row['quantity'];
		$orders++;
	}
	if ($row['status'] == 'processed')
		$processed++;
}

$smarty->assign_by_ref('list', $list);
$smarty->assign('total', $total);
$smarty->assign('orders', $orders);
$smarty->assign('processed', $processed);
$smarty->assign('year', $year);

$smarty->display('calendar_admin.tpl');




