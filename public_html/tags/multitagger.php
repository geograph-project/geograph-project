<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 5786 2009-09-12 10:18:04Z barry $
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
//$template = 'tags_multitagger.tpl';

$template = 'tags_multitagger3.tpl';

if (!empty($_GET['preview'])) {
	$template = 'tags_multitagger2.tpl';
}

$USER->mustHavePerm("basic");

$src = 'data-src';
if ((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
        (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) {
        $src = 'src';//revert back to standard non lazy loading
}
 $smarty->assign("src",$src);


if (!empty($_GET['q']) || !empty($_GET['onlynull'])) {
	$q=trim($_GET['q']);

	$mine = !empty($_GET['onlymine']);
	$nulled = !empty($_GET['onlynull']);

	$q = str_replace("(anything) near",'',$q);
	$q = str_replace("near (anywhere)",'',$q);
	$q = preg_replace('/(\s+)\bnear\b\s+/','$1',$q);

	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc)
	$cacheid = $sphinx->q.'.'.($mine?($USER->user_id):0).'.'.$nulled;

	$sphinx->pageSize = $pgsize = 50;


	#$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$cacheid .=".".$pg;
	$cacheid .=".$src";


	$smarty->assign('q', $sphinx->q);

	if (!$smarty->is_cached($template, $cacheid)) {

		$filters = array();
		if (!empty($_REQUEST['onlymine'])) {
			$filters['auser_id'] = array($USER->user_id);
			$smarty->assign("onlymine",1);
		}
		if (!empty($_REQUEST['onlynull'])) {
			$sphinx->q .= " @tags ^null";
			$smarty->assign("onlynull",1);
		}
		if (!empty($filters)) {
			$sphinx->addFilters($filters);
		}

		$ids = $sphinx->returnIds($pg,'_images');
		if (!empty($ids) && count($ids)) {
			$smarty->assign('idlist', implode(',',$ids));

			$images=new ImageList();
			$images->getImagesByIdList($ids);

			$smarty->assign_by_ref('images', $images->images);

			$smarty->assign('imagecount', count($images->images));
			$smarty->assign('totalcount', $sphinx->resultCount);

			if ($template == 'tags_multitagger.tpl') {

				$db = GeographDatabaseConnection(true);

				$used = $db->getAll("SELECT tag_id,prefix,tag,count(distinct gridimage_id) as images FROM gridimage_tag gs INNER JOIN tag s USING (tag_id) WHERE gridimage_id IN (".implode(',',$ids).") AND (gs.user_id = {$USER->user_id}) AND gs.status > 0 GROUP BY tag_id");

				$smarty->assign_by_ref('used',$used);
			}

		}

	}

} elseif (!empty($_GET['onlymine'])) {
	$cacheid = $USER->user_id;
	$smarty->assign("onlymine",1);

	if (!$smarty->is_cached($template, $cacheid)) {
		$images=new ImageList();
		$images->getImagesByUser($USER->user_id, '', 'gridimage_id desc', 50,true);

		$smarty->assign_by_ref('images', $images->images);

		$smarty->assign('imagecount', count($images->images));
		$smarty->assign('totalcount', '?');

	}

}

$smarty->display($template,$cacheid);
