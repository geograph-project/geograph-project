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
$USER->mustHavePerm("basic");

pageMustBeHTTPS();

$db = GeographDatabaseConnection(false);

$year = date('Y')+1; // we currently working on next years calendar

####################################

if (empty($_GET['id'])) {
        $updates = array();
	$updates['user_id'] = intval($USER->user_id);
	$updates['title'] = 'Best Of Geograph only order';
	$updates['quantity'] = 0;
	$updates['best_quantity'] = 2;

	$db->Execute('INSERT IGNORE INTO calendar SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

	$_GET['id'] = $db->Insert_ID();
}

####################################

$row = $db->getRow("SELECT * FROM calendar WHERE calendar_id = ".intval($_GET['id']));

if (empty($row) || $row['user_id'] != $USER->user_id)
	die("Calendar not found");

if ($row['status'] == 'processed')
        die("This calendar is now processed, and can no longer be edited");


if (date('Y-m-d') > '2022-10-10' && !in_array($USER->user_id, array(3,9181,11141,135767)) ) {
        die("Sorry, we are not currently accepting new orders");
}

if (empty($row['alpha'])) {
	$ids = $db->getCol("SELECT calendar_id FROM calendar WHERE user_id = {$row['user_id']} AND ordered > '1000-00-00' AND year = '$year' ORDER BY ordered");
	$idx = array_search($row['calendar_id'],$ids);
	if (!is_numeric($idx)) //not found should be be on the END. (the specific order isnt marked as ordered YET!)
		$idx = count($ids);
	$row['alpha'] = chr(65+$idx); //starting at A
}

####################################

if (!empty($_POST)) {
	$updates= $errors = array();
	if (isset($_POST['calendar_title']) && $_POST['calendar_title'] != $row['title'])
		$updates['title'] = $_POST['calendar_title'];

	$updates['print_title'] = @$_POST['print_title']+0;
	$updates['background'] = @$_POST['background']+0;

	foreach (array('quantity','best_quantity','delivery_name','delivery_line1','delivery_line2','delivery_line3','delivery_line4','delivery_postcode') as $key) {
		if (isset($_POST[$key]) && $_POST[$key] != $row[$key])
			$updates[$key] = $_POST[$key];
		if (empty($updates[$key]) && empty($row[$key]) && $key != 'delivery_line2' && $key != 'delivery_line4')
			$error[$key] = 'Required';
	}

	if (empty($errors)) {
		if ($row['status'] == 'new') {
			$updates['status'] = 'ordered';
			$updates['alpha'] = $row['alpha'];
			$updates['ordered'] = $db->getOne("SELECT NOW()");
		}

		if (!empty($updates)) {
			$db->Execute('UPDATE calendar SET `'.implode('` = ?,`',array_keys($updates)).'` = ?'.
				' WHERE calendar_id = '.$row['calendar_id'], array_values($updates));

			foreach($updates as $key => $value)
				$row[$key] = $updates[$key];
		}

		if ($row['paid'] > '2') {
			header("Location: ./");
		} else {
			//this needs converting to IGN. The return URL doesnt contain in any identifiers
			$_SESSION['calendar_id'] = $row['calendar_id'];

			$cost = (8.00 * $row['quantity']) + (8.00 * $row['best_quantity']) + 3.00;

			$token=new Token;
			$token->setValue("i", $row['calendar_id']);
			$token->setValue("u", $row['user_id']);
			$token->setValue("a", $row['alpha']);

		?>

Proceeding to payment...

<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="paypal@geograph.org.uk">
<input type="hidden" name="lc" value="GB">
<input type="hidden" name="item_name" value="Calendar Order Ref:<? echo "{$row['calendar_id']}/{$row['user_id']}{$row['alpha']}/{$row['year']}"; ?>">
<input type="hidden" name="invoice" value="<? echo "{$row['user_id']}{$row['alpha']}{$row['year']}"; ?>">
<input type="hidden" name="amount" value="<? echo $cost; ?>">
<input type="hidden" name="currency_code" value="GBP">
<input type="hidden" name="button_subtype" value="services">
<input type="hidden" name="no_note" value="0">
<input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynowCC_LG.gif:NonHostedGuest">
<input type="hidden" name="returnurl" value="https://www.geograph.org.uk/calendar/return.php?t=<? echo $token->getToken(); ?>">
<input type="hidden" name="cancelurl" value="https://www.geograph.org.uk/calendar/order.php?id=<? echo $row['calendar_id']; ?>">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
<script>
window.onload = function() {
	document.forms[0].submit();
}
</script>
<p>(Click the button if nothing happens within 5 seconds)</p>
<?
		}
		exit;

	} else {
		$smarty->assign('errors',$errors);
	}
}

$smarty->assign('calendar',$row);

require_once('geograph/imagelist.class.php');
$imagelist=new ImageList;
$imagelist->_setDB($db);//to reuse the same connection

//this is NOT normal rows, but gridimage_calendar has enough rows, that it works! (at least to get thumbnails!)
$sql = "SELECT * FROM gridimage_calendar
	LEFT JOIN gridimage_size using (gridimage_id)
	WHERE calendar_id = {$row['calendar_id']} ORDER BY sort_order";
$imagelist->_getImagesBySql($sql);

if (!empty($row['cover_image'])) {
	$image = new Gridimage();
	$data = $db->getRow("SELECT *,0 as sort_order FROM gridimage_search
        LEFT JOIN gridimage_size using (gridimage_id)
        WHERE gridimage_id = {$row['cover_image']}");
	$image->fastInit($data);

	array_unshift($imagelist->images, $image);
}



$smarty->assign_by_ref('images', $imagelist->images);


$smarty->display('calendar_order.tpl');




