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


if (!empty($_POST['choice'])) {
	$db = GeographDatabaseConnection(false);


	foreach ($_POST['choice'] as $change_id => $status) {
                $updates = array();
                $updates['change_id'] = intval($change_id);
                $updates['user_id'] = $USER->user_id;
                $updates['status'] = intval($status);

                $db->Execute('INSERT INTO category_mapping_change_log SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	}
}

if (empty($db))
	$db = GeographDatabaseConnection(true);


$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$suggestions = $db->getAll("SELECT c.*,realname
FROM category_mapping_change c INNER JOIN user USING (user_id)
LEFT JOIN category_mapping_change_log l ON (c.change_id = l.change_id AND l.user_id = {$USER->user_id})
WHERE c.status > 0 AND action != 'checked' AND log_id IS NULL
ORDER BY imageclass,change_id"); //todo user_id != $USER->user_id

$smarty->assign_by_ref('suggestions',$suggestions);

$smarty->assign('fields',array('context','subject','tag','canonical'));
$smarty->display('stuff_category_mapping_review.tpl');





