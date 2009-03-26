<?php
/**
 * $Project: GeoGraph $
 * $Id: ecard.php 3886 2007-11-02 20:14:19Z barry $
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
$template='submit_competition.tpl';	


if (isset($_REQUEST['code']) && trim(strtoupper($_REQUEST['code'])) == 'MN1') {
	if (isset($_REQUEST['id'])) {
		$smarty->assign('code', $_REQUEST['code']);
		require_once('geograph/gridsquare.class.php');
		require_once('geograph/gridimage.class.php');

		$image=new GridImage();
		$ok = $image->loadFromId($_REQUEST['id']);

		if (!$ok || $image->moderation_status=='rejected') {
			//clear the image
			$image=new GridImage;
			header("HTTP/1.0 410 Gone");
			header("Status: 410 Gone");
			$template = "static_404.tpl";
		} elseif (!empty($_REQUEST['finalise'])) {
			$db=NewADOConnection($GLOBALS['DSN']);

			$updates = array();

			$updates['user_id'] = $USER->user_id;
			$updates['gridimage_id'] = intval($_REQUEST['id']);
			$updates['code'] = $_REQUEST['code'];
			
			$db->Execute('INSERT INTO competition_code SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

			$smarty->assign('saved', 1);
		}
		$smarty->assign_by_ref('image', $image);
	} else {
		$template = "static_404.tpl";
	}
} else {
	$smarty->assign('id', intval($_REQUEST['id']));
}


$smarty->display($template);

?>
