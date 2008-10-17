<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

//this page isnt actully heavy, but the searches generated could be!
dieUnderHighLoad();


$template='explore_searches.tpl';
$cacheid = $is_mod=$USER->hasPerm('admin')?1:0;

$i = 0;
if (isset($_REQUEST['i']) && is_numeric($_REQUEST['i'])) {
	$i = intval($_REQUEST['i']);
} 


if ($is_mod && $i && isset($_GET['a'])) {
	$db=NewADOConnection($GLOBALS['DSN']);

	$a = intval($_GET['a']);	

	$sql = "UPDATE queries_featured SET approved = $a WHERE id = ".$db->Quote($i);
	$db->Execute($sql);

	$smarty->clear_cache($template, $cacheid);
	$i = 0;
}

if ($i) {
	$template='explore_searches_suggest.tpl';
	
	$db=NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');  

	if (isset($_POST['submit'])) {
		$sql = "INSERT INTO queries_featured SET
				id = $i,
				user_id = {$USER->user_id},
				comment = ".$db->Quote($_POST['comment']).",
				created = NOW()";
		$ok = @$db->Execute($sql)?1:0;
		$smarty->assign('ok',$ok);
		$smarty->assign('saved',1);
	} else {
	
	
		$where = array();
		$where[] = 'id = '.$i;

		if (count($where))
			$where_sql = " where ".join(' AND ',$where);

		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
		$query =& $db->getRow("
		select
			id,searchdesc,comment,created
		from
			queries
			left join queries_featured using (id)
		$where_sql
		");

		$smarty->assign_by_ref('query',$query);
	}
	
	$smarty->assign_by_ref('i',$i);
	
	$smarty->display($template, $cacheid);
	exit;
}

if ($is_mod) {
	$smarty->caching = 0;
} else {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600*24; //24hr cache
}

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	
	$where = array();
	if ($USER->hasPerm('moderator')) {
		$where[] = 'approved > -1';
	} else {
		$where[] = 'approved = 1';
	}
	
	if (count($where))
		$where_sql = " where ".join(' AND ',$where);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$queries =& $db->getAll("
	select
		id,searchdesc,`count`,comment,created,approved
	from
		queries_featured
		inner join queries using (id)
		left join queries_count using (id)
	$where_sql
	order by 
		updated desc");
	
	$smarty->assign_by_ref('queries',$queries);
} 

$smarty->display($template, $cacheid);

	
?>
