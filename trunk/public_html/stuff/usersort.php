<?php
/**
 * $Project: GeoGraph $
 * $Id: search.php 2403 2006-08-16 15:55:41Z barry $
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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");


$v=inEmptyRequestInt('v',0);


$db=NewADOConnection($GLOBALS['DSN']);

	
if ($USER->hasPerm("admin")) {
	if ($_GET['pop']) {
		$a = $db->getAll("
		select gridimage_id id,imageclass c ,user_id u ,YEARWEEK(imagetaken) as t
		from gridimage_search
		group by imageclass,user_id,YEARWEEK(imagetaken)");
	
		shuffle($a);
		$donec = array();
		$doneu = array();
		$donet = array();
		
		foreach ($a as $i => $r) {
			if ( !isset($donec[$r['c']]) && !isset($doneu[$r['u']]) && !isset($donet[$r['t']]) ) {
				$db->Execute("insert into usersort_image set gridimage_id = {$r['id']},v = $v");
				$donec[$r['c']] = 1;
				$doneu[$r['u']] = 1;
				$donet[$r['t']] = 1;
			}
		
		}
		die("done!");
	}
}

if (!$v) {
	$row = $db->getRow("select * from usersort_vote where user_id = {$USER->user_id} and upd_timestamp > date_sub(now(),interval 3 hour)");
	
	if (count($row)) {
		$v = $row['v'];
		$v_info = $db->getRow("select * from usersort_vector where v = $v");
	} else {
		$v_info = $db->getRow("
		select ve.*,count(user_id) as c
		from usersort_vector ve
			left join usersort_vote vo using (v)
		group by vo.v
		order by c desc,rand()
		limit 1");
		$v = $v_info['v'];
	}
} else {
	$v_info = $db->getRow("select * from usersort_vector where v = $v");
}

if (isset($_POST['Save'])) {
	$plus = explode(',',$_POST['plus']);
	$minus = explode(',',$_POST['minus']);
	$c = count($plus) -1; //the last one is blank
	for($q=0;$q<$c;$q++) {
		$sql = "INSERT INTO usersort_vote SET v = $v, user_id={$USER->user_id}, id1 = {$plus[$q]}, id2 = {$minus[$q]}";
		$db->Execute($sql);
	}
	$smarty->assign('message', 'Votes Saved, Thank You');
	
	if (empty($_POST['more'])) {
		header("Location: /");
		exit;
	}
}


$all = $db->getAll("
select gi.*
from usersort_image im 
	inner join gridimage_search gi
		on (gi.gridimage_id = im.gridimage_id )
	left join usersort_vote vo 
		on (vo.v = $v and vo.user_id = {$USER->user_id} and (gi.gridimage_id = id1 or gi.gridimage_id = id2))
where 
	im.v = $v and
	vo.user_id IS NULL
group by im.gridimage_id
order by rand()
limit 20");

$pairs = array();
for($q = 0; $q<count($all);$q+=2) {
	if ($all[$q+1]) {
		$row = array();
		
		$row[0] = new GridImage;
		$row[0]->fastInit($all[$q]);
		$row[0]->url = $row[0]->getThumbnail(213,160,true);
		$row[0]->other_id = $all[$q+1]['gridimage_id'];
		
		$row[1] = new GridImage;
		$row[1]->fastInit($all[$q+1]);
		$row[1]->url = $row[1]->getThumbnail(213,160,true);
		$row[1]->other_id = $all[$q]['gridimage_id'];
		
		$pairs[] = $row;
	}
}

$smarty->assign('v',$v);
$smarty->assign_by_ref('v_info',$v_info);
$smarty->assign_by_ref('pairs',$pairs);
$smarty->assign('pairs_count',count($pairs));

$smarty->display('stuff_usersort.tpl');

	
?>
