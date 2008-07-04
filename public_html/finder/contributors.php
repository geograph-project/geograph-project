<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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

if (!empty($_GET['q'])) {
	$q=trim($_GET['q']);

	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc) 
	$cacheid = $sphinx->q;

	$sphinx->pageSize = $pgsize = 15;

	$sphinx->processQuery();

	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$ids = $sphinx->returnUserIds($pg);	
	
	if (count($ids)) {
		$where = $sphinx->where;

		$db=NewADOConnection($GLOBALS['DSN']);

		$limit = 25;

		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$rows = $db->getAssoc("
		select user.user_id,nickname,realname,images
		from user 
		left join user_stat using (user_id)
		where $where
		limit $limit");

		$results = array();
		foreach ($ids as $c => $id) {
			$row = $rows[$id];
			$row['user_id'] = $id;
			$results[] = $row;
		}
		$smarty->assign_by_ref('results', $results);
		$cacheid = md5($q);
	}
	$ADODB_FETCH_MODE = $prev_fetch_mode;
	
	$smarty->assign("q",$sphinx->q);
	
}

$smarty->display('finder_contributors.tpl',$cacheid);

	
?>
