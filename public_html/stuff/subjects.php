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



if (!empty($_POST) && !empty($_POST['submitone'])) {
	$db = GeographDatabaseConnection(false);

}

$action = (isset($_GET['action']) && ctype_alnum($_GET['action']))?$_GET['action']:'reuse';
$smarty->assign('action',$action);

$template = 'stuff_subjects.tpl';
$cacheid = '';

if (!empty($_GET['admin'])) {
	$smarty->assign('admin',1);
	$cacheid=1;
}

if (!$smarty->is_cached($template, $cacheid)) {
	if (empty($db))
		$db = GeographDatabaseConnection(true);





	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;



$sql = " (
	select subject,t.tag,count(gridimage_id) images from subjects s left join tag t on (t.tag = s.subject and t.prefix = 'subject') left join gridimage_tag gt on (t.tag_id = gt.tag_id and gt.status = 2) group by subject order by null
) union (
	select subject,t.tag,count(gridimage_id) images from tag t inner join gridimage_tag gt using (tag_id) left join subjects s on (t.tag = s.subject) where t.prefix = 'subject' and gt.status = 2 and s.subject is null group by tag_id order by null
) order by coalesce(subject,tag)";

//union (
//      select canonical as subject,null as tag, from gridimage_search inner join category_mapping using (imageclass) group by canonical

	$list = $db->getAll($sql);


	$historic = $db->getAssoc("select canonical as subject,count(*) images,count(distinct imageclass) as cats from gridimage_search inner join category_mapping using (imageclass) group by canonical order by null");
	foreach ($list as $idx => $row) {
		if (!empty($historic[$row['subject']])) {
			$list[$idx]['historic'] = $historic[$row['subject']]['images'];
			$list[$idx]['cats'] = $historic[$row['subject']]['cats'];
			unset($historic[$row['subject']]);
		}
	}
	if (!empty($historic)) {
		foreach ($historic as $subject => $row) {
			//store in tag, because not offical subject
			$list[] = array('tag'=>$subject,'historic'=>$row['images'],'cats'=>$row['cats']);
		}
		unset($historic);
	}


	$smarty->assign_by_ref('list',$list);

}

$smarty->display($template, $cacheid);


