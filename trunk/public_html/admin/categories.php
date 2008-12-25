<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

$USER->hasPerm("admin") || $USER->hasPerm("ticketmod") || $USER->mustHavePerm("moderator");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  
#$db->debug = true;



	
	if (isset($_POST['submit'])) {
		$message = "<p>Making the following changes:</p>";
		$skip = array();
		for ($c = 1; $c <= $_POST['highc']; $c++) {
			if ($_POST['old'.$c] != $_POST['new'.$c] && empty($skip[$_POST['old'.$c]])) {
				$isanother = false;
				//check if this is actully a swap?
				for($d = $c+1; $d <= $_POST['highc']; $d++) {
					if ($_POST['old'.$c] == $_POST['new'.$d] &&
						$_POST['old'.$d] == $_POST['new'.$c]) {
						$isanother = true;
					}
				}
				if ($isanother) {
					//change one to a temp value
					$message .= "<p>Updating '<i>".$_POST['old'.$c]."</i>' to '<b>-".$_POST['new'.$c]."</b>'.</p>";
					$sql = "UPDATE gridimage SET `imageclass` = ".$db->Quote("-".$_POST['new'.$c])." WHERE `imageclass` = ".$db->Quote($_POST['old'.$c]);
					$db->Execute($sql);	
						$sql = "UPDATE gridimage_search SET `imageclass` = '-".$_POST['new'.$c]."' WHERE `imageclass` = ".$db->Quote($_POST['old'.$c]);
						$db->Execute($sql);	
						//do the backwards swap
						$message .= "<p>Updating '<i>".$_POST['new'.$c]."</i>' to '<b>".$_POST['old'.$c]."</b>'.</p>";
						$sql = "UPDATE gridimage SET `imageclass` = ".$db->Quote($_POST['old'.$c])." WHERE `imageclass` = ".$db->Quote($_POST['new'.$c]);
						$db->Execute($sql);	
							$sql = "UPDATE gridimage_search SET `imageclass` = ".$db->Quote($_POST['old'.$c])." WHERE `imageclass` = ".$db->Quote($_POST['new'.$c]);
							$db->Execute($sql);	
					//correct the temp value
					$message .= "<p>Updating '<i>-".$_POST['new'.$c]."</i>' to '<b>".$_POST['new'.$c]."</b>'.</p>";
					$sql = "UPDATE gridimage SET `imageclass` = ".$db->Quote($_POST['new'.$c])." WHERE `imageclass` = '-".$_POST['new'.$c]."'";
					$db->Execute($sql);	
						$sql = "UPDATE gridimage_search SET `imageclass` = ".$db->Quote($_POST['new'.$c])." WHERE `imageclass` = ".$db->Quote("-".$_POST['new'.$c]);
						$db->Execute($sql);	
					//we already have done the swap so dont want it to happen on the next iteration
					$skip[$_POST['new'.$c]]++;
				} else {
					$message .= "<p>Updating '<i>".$_POST['old'.$c]."</i>' to '<b>".$_POST['new'.$c]."</b>'.</p>";
					$sql = "UPDATE gridimage SET `imageclass` = ".$db->Quote($_POST['new'.$c])." WHERE `imageclass` = ".$db->Quote($_POST['old'.$c]);
					$db->Execute($sql);	
						$sql = "UPDATE gridimage_search SET `imageclass` = ".$db->Quote($_POST['new'.$c])." WHERE `imageclass` = ".$db->Quote($_POST['old'.$c]);
						$db->Execute($sql);	
				}
			}

		}
		$message .= "<p>All values updated</p>";
		$smarty->assign('message',  $message);
	}
	
	$where = '';
	if (!empty($_REQUEST['q'])) {
		$a = explode(' ',preg_replace("/[^ \w'\(\)]+/",'',$_REQUEST['q']));
		$where = " AND (imageclass LIKE '%".implode("%' OR imageclass LIKE '%",$a)."%' )";
		$smarty->assign('q', implode(" ",$a));
	}
	
	$arr = $db->GetAssoc("select imageclass,count(*) from gridimage where moderation_status != 'rejected' $where group by imageclass");
	
	$smarty->assign('arr',  $arr);


$smarty->display('admin_categories.tpl');


	
?>
