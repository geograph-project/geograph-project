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

} else if (!empty($_GET['simple'])) {
	$template = 'tags_multitagger-simple.tpl';
	if (empty($_GET['q']))
		$_GET['onlymine'] = 1;
}

$USER->mustHavePerm("basic");

$src = 'data-src';
if ((stripos($_SERVER['HTTP_USER_AGENT'], 'http')!==FALSE) ||
        (stripos($_SERVER['HTTP_USER_AGENT'], 'bot')!==FALSE)) {
        $src = 'src';//revert back to standard non lazy loading
}
$smarty->assign("src",$src);

##############################################################

if (!empty($_GET['tag'])) {
	$smarty->assign('thetag', $_GET['tag']);

	$tags = new Tags();

	$tag_id = $tags->getTagId($_GET['tag'], false);

	if (empty($tag_id))
		die("Tag not found, currently will not create new tag, only add existing one");

	if (!empty($_POST['yes'])) {
		//multiCommit, will fully sanitize input, so safe to pass GET/POST etc directy

		$_GET['onlymine'] = 1; //for now, FORCE this. Later we could perhaps relax this. Eg to allow setting private tags

		$tags->multiCommit($_POST['yes'], $tag_id, $USER->user_id, $_GET['onlymine'], 2);
	}
}

##############################################################

if (!empty($_GET['q']) || !empty($_GET['onlynull'])) {
	$q=trim($_GET['q']);

	$mine = !empty($_GET['onlymine']);
	$nulled = !empty($_GET['onlynull']);
	$exclude = !empty($_GET['exclude']);

	$q = str_replace("(anything) near",'',$q);
	$q = str_replace("near (anywhere)",'',$q);
	$q = preg_replace('/(\s+)\bnear\b\s+/','$1',$q);

	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc)
	$cacheid = $sphinx->q.'.'.($mine?($USER->user_id):0).'.'.$nulled.$exclude;

//todo, thetag would need adding to cacheid?

	$sphinx->pageSize = $pgsize = 50;


	#$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$cacheid .=".".$pg;
	$cacheid .=".$src";


	$smarty->assign('q', $sphinx->q);

	if (!$smarty->is_cached($template, $cacheid) || $template == 'tags_multitagger-simple.tpl') {

		$filters = array();
		if (!empty($_REQUEST['onlymine'])) {
			$filters['auser_id'] = array($USER->user_id); //this is an absolute filter
			$sphinx->q .= " @user_id ".intval($USER->user_id); //should make the query more effient
			$smarty->assign("onlymine",1);
		}
		if (!empty($_REQUEST['onlynull'])) {
			$sphinx->q .= " @tags ^null";
			$smarty->assign("onlynull",1);
		}
		if (!empty($_REQUEST['exclude']) && !empty($_GET['tag'])) {
			if (strpos($_GET['tag'],':') !== FALSE) {
				list($prefix,$_GET['tag']) = explode(':',$_GET['tag'],2);

			        $sphinx->q .= " @tags -\"__TAG__ $prefix __TAG__ {$_GET['tag']} __TAG__\"";
			} else {
			        $sphinx->q = " @tags -\"__TAG__ {$_GET['tag']} __TAG__\"";
			}
			$smarty->assign("exclude",1);
		}

		if (!empty($filters)) {
			$sphinx->addFilters($filters);
		}

		$ids = $sphinx->returnIds($pg,'_images');
		if (!empty($ids) && count($ids)) {
			$smarty->assign('idlist', $idstr = implode(',',$ids));

			$images=new ImageList();
			$images->getImagesByIdList($ids);

			$smarty->assign_by_ref('images', $images->images);

			$smarty->assign('imagecount', count($images->images));
			$smarty->assign('totalcount', $sphinx->resultCount);

			if ($template == 'tags_multitagger.tpl') {
				if (!empty($tags) && !empty($tags->db)) {
					$db = $tags->db;
				} else {
					$db = GeographDatabaseConnection(true);
				}

				$used = $db->getAll("SELECT tag_id,prefix,tag,count(distinct gridimage_id) as images FROM gridimage_tag gs INNER JOIN tag s USING (tag_id) WHERE gridimage_id IN (".implode(',',$ids).") AND (gs.user_id = {$USER->user_id}) AND gs.status > 0 GROUP BY tag_id");

				$smarty->assign_by_ref('used',$used);
			}

			if (!empty($tag_id) && $template == 'tags_multitagger-simple.tpl') {
				// importantly want to do it directly in database (as may of just modified it!!, NOT just rely cached version (even in gridimage_search, certainly not sphinx!)
				$done= $tags->db->getCol("SELECT gridimage_id FROM tag_public WHERE tag_id = $tag_id AND gridimage_id IN ($idstr) LIMIT 1000");
				$smarty->assign_by_ref('done',$done);
			}
		}
	}

##############################################################

} elseif (!empty($_GET['onlymine'])) {
	$cacheid = $USER->user_id;
	$smarty->assign("onlymine",1);

	if (!$smarty->is_cached($template, $cacheid) || $template == 'tags_multitagger-simple.tpl') {
		$images=new ImageList();
		$images->getImagesByUser($USER->user_id, '', 'gridimage_id desc', 50,false);

		$smarty->assign_by_ref('images', $images->images);

		$smarty->assign('imagecount', count($images->images));
		$smarty->assign('totalcount', '?');

		if (!empty($tag_id) && $template == 'tags_multitagger-simple.tpl') {
			$ids = array();
			foreach ($images->images as $i => $image)
				$ids[] = $image->gridimage_id;
			$idstr = implode(',',$ids);
			// importantly want to do it directly in database (as may of just modified it!!, NOT just rely cached version (even in gridimage_search, certainly not sphinx!)
			$done= $tags->db->getCol("SELECT gridimage_id FROM tag_public WHERE tag_id = $tag_id AND gridimage_id IN ($idstr) LIMIT 1000");
			$smarty->assign_by_ref('done',$done);

			if (!empty($_REQUEST['exclude'])) {
				foreach ($images->images as $i => $image)
					if (in_array($image->gridimage_id,$done) !== FALSE)
						unset($images->images[$i]);

				$smarty->assign("exclude",1);
			}
		}
	}
}

##############################################################

$smarty->display($template,$cacheid);
