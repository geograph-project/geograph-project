<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($CONF['forums'])) {
	$smarty = new GeographPage;
        $smarty->display('static_404.tpl');
        exit;	
}

init_session();

$smarty = new GeographPage;

$cacheid = $USER->hasPerm('basic')?$USER->user_id:0;
if (!empty($_GET['tag'])) {
	$cacheid .= '.'.md5($_GET['tag']);
}
if (!empty($_GET['u'])) {
	$cacheid .= '.'.intval($_GET['u']);
}

$when = (isset($_GET['when']) && preg_match('/^\d{4}(-\d{2}|)(-\d{2}|)$/',$_GET['when']))?$_GET['when']:'';
$cacheid .= '.'.$when;

$isadmin=$USER->hasPerm('moderator')?1:0;
$smarty->assign_by_ref('isadmin', $isadmin);

$template = 'blogs.tpl';

if (isset($_GET['preview'])) {
        $template = 'blogs2.tpl';
}

if ($isadmin) {
	if (!empty($_GET['id']) && preg_match('/^\d+$/',$_GET['id'])) {
		$db = GeographDatabaseConnection(false);
		
		$a = intval($_GET['approve']);	
		
		$sql = "UPDATE blog SET approved = $a WHERE blog_id = ".$db->Quote($_GET['id']);
		$db->Execute($sql);

		$smarty->clear_cache('blogs.tpl');
		$smarty->clear_cache('blogs.tpl',$cacheid);
		$smarty->clear_cache('blog_entry.tpl',$_REQUEST['id']);
		$smarty->clear_cache('blog_entry.tpl',$_REQUEST['id']."|".$USER->user_id);
	}
}




if (!$smarty->is_cached($template, $cacheid))
{
	$smarty->assign('google_maps_api_key',$CONF['google_maps_api_key']);
	$datecolumn = 'created';
	
	$db=GeographDatabaseConnection(false);
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	$where = '';
	
	if (!empty($_GET['u'])) {
		$profile=new GeographUser(intval($_GET['u']));
		if ($profile->registered) {
                	$where = " AND blog.user_id = ".intval($_GET['u']);
	                $smarty->assign('user_id',intval($_GET['u']));
			$smarty->assign('realname',$profile->realname);
		}
        }

	if (!empty($_GET['tag'])) {
		$where .= " AND tags LIKE ".$db->Quote("%{$_GET['tag']}%");
		$smarty->assign('thetag',$_GET['tag']);
	}

	$archive = $db->getAll("SELECT substring(published,1,4) AS year,substring(published,6,2) AS month,count(*) AS c FROM blog WHERE (approved = 1 and published < now()) $where GROUP BY substring(published,1,7) ORDER BY year DESC,month ASC");
	$smarty->assign_by_ref('archive',$archive);

	if (!empty($when)) {
		$where .= " AND published LIKE ".$db->Quote("{$when}%");
		$smarty->assign('when',$when);
	}
	
	$list = $db->getAll("
	select blog.*,realname,gs.grid_reference,x,y,unix_timestamp($datecolumn) as $datecolumn
	from blog 
		left join user using (user_id)
		left join gridsquare gs on (blog.gridsquare_id = gs.gridsquare_id)
	where ((approved = 1 and published < now()) 
		or blog.user_id = {$USER->user_id}
		or ($isadmin and approved != -1)
		) $where
	order by blog_id desc limit 25");
	
	$conv = new Conversions;
	$geo = 0;
	foreach ($list as $i => $row) {
		if ($row['gridimage_id']) {
			$list[$i]['image'] = new GridImage;
			$g_ok = $list[$i]['image']->loadFromId($row['gridimage_id'],true);
			if ($g_ok && $list[$i]['image']->moderation_status == 'rejected')
				$g_ok = false;
			if (!$g_ok) {
				unset($list[$i]['image']);
			}
		}
		$diff = time() - $row[$datecolumn];
		if ($diff > (3600*24*31)) {
			$list[$i][$datecolumn] = sprintf("%d months ago",$diff/(3600*24*31));
		} elseif ($diff > (3600*24)) {
			$list[$i][$datecolumn] = sprintf("%d days ago",$diff/(3600*24));
		} elseif ($diff > 3600) {
			$list[$i][$datecolumn] = sprintf("%d hours ago",$diff/3600);
		} else {
			$list[$i][$datecolumn] = sprintf("%d minutes ago",$diff/60);
		}
	
		if ($row['gridsquare_id']) {
			list($list[$i]['wgs84_lat'],$list[$i]['wgs84_long']) = $conv->internal_to_wgs84($row['x'],$row['y']);
			$geo++;
		}
	}
	#$smarty->assign_by_ref('geo', $geo);
	$smarty->assign_by_ref('list', $list);

	$rows = $db->getCol("SELECT tags FROM blog WHERE (approved = 1 AND published < NOW()) $where");
	$tags = array();
	foreach ($rows as $row) {
		$bits = explode(',',$row);
		foreach ($bits as $tag) {
			$tags[trim(strtolower($tag))]++;
		}
	}
	unset($tags['']);
	ksort($tags);
	$smarty->assign_by_ref('tags', $tags);
}

$smarty->display($template, $cacheid);

