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

if ($USER->user_id) {
	$db = NewADOConnection($GLOBALS['DSN']);

	if (isset($_GET['accept'])) {
		$hectad = strtoupper(preg_replace('/[^\w]/','',$_GET['accept']));
		
		if($db->Execute("
			UPDATE hectad_assignment
			SET status = 'accepted',expiry = DATE_ADD(NOW(),INTERVAL 1 YEAR)
			WHERE user_id = {$USER->user_id} AND status IN ('offered','accepted')
			AND hectad = '$hectad'")) {
				
			$smarty->assign('message','Offer Accepted for '.$hectad);
			
			//invalidate any other offers!
			$db->Execute("UPDATE hectad_assignment
			SET status = 'new',expiry = 0
			WHERE user_id != {$USER->user_id} AND status IN ('offered')
			AND hectad = '$hectad'");
		}
		
	}



	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$hectads = $db->getAll("
	SELECT *,expiry > now() as indate
	FROM hectad_assignment
	WHERE user_id = {$USER->user_id} AND status IN ('offered','accepted')
	ORDER BY sort_order");
	$smarty->assign_by_ref('hectads',$hectads);
}

$smarty->display('adopt.tpl');

	
?>
