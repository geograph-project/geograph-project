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

$smarty = new GeographPage;
$template='frame.tpl';	

customExpiresHeader(3600*6,true,true);

if (!empty($_REQUEST['q'])) {
	$q=trim($_REQUEST['q']);
	
	$sphinx = new sphinxwrapper($q);

	$sphinx->pageSize = $pgsize = 1; 
	$pg = 1;
	
	$sphinx->processQuery();

	if (!empty($_REQUEST['random'])) {
		$client = $sphinx->_getClient();
		$client->SetRankingMode(SPH_RANK_NONE);
		
		
		if ($_REQUEST['random'] == 2) {
			
			$date = date('Y-m-d');
			if (!empty($_REQUEST['date']) && preg_match('/(\d{4})-(\d{2})-(\d{2})/',$_REQUEST['date'],$m)) {
				if ($m[0] > $date) {
					die("unable to predict the future");
				}
				$date = $m[0];
			}
	
			//create the filter
			$filters = array('submitted' => array(strtotime("2005-01-01"),strtotime($date." 00:00:01"))); 
			//add the filters
			$sphinx->addFilters($filters);
			//apply the filters
			$sphinx->getFilterString();
	
			//remove them (otherwise returnIds will just add them again) 
			$sphinx->filters = array();
			
			$images = min(1000,$sphinx->countMatches('_images'));
			
			if ($images) {
				$md = md5($CONF['register_confirmation_secret'].$date);

				$int = hexdec(substr($md,2,2).substr($md,12,2).substr($md,22,2));

				$pg = $int % $images;
			}
		} 
		
		$sphinx->sort = "@random ASC";
	}
	
	$ids = $sphinx->returnIds($pg,'_images');

	if (!empty($ids) && count($ids)) {

		$_REQUEST['id'] = $ids[0];
	}
}

if (isset($_REQUEST['id'])) {
	$cacheid = intval($_REQUEST['id']);
	
	if (!$smarty->is_cached($template, $cacheid)) {
		
		$image=new GridImage();
		$ok = $image->loadFromId($_REQUEST['id']);

		if (!$ok || $image->moderation_status=='rejected') {
			//clear the image
			$image=new GridImage;
			header("HTTP/1.0 410 Gone");
			header("Status: 410 Gone");
			$template = "static_404.tpl";
		} else {
			//bit late doing it now, but at least if smarty doesnt have it cached we might be able to prevent generating the whole page
			customCacheControl(strtotime($image->upd_timestamp),$cacheid);

			$smarty->assign_by_ref('image', $image);
		}
	}
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$template = "static_404.tpl";
	die("Sorry, unable to load image. <a href=\"/\" target=\"_top\">Open Geograph Homepage</a>");
}


if (strpos($_SERVER['HTTP_REFERER'],$_SERVER['HTTP_HOST']) === FALSE) {
        $smarty->assign("external",true);
}

$smarty->display($template, $cacheid);

