<?php
/**
 * $Project: GeoGraph $
 * $Id: contributors.php 6407 2010-03-03 20:44:37Z barry $
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

foreach(array('page') as $key)
	if (!empty($_REQUEST[$key]) && !preg_match('/^[\w \.>]*$/',$_REQUEST[$key])) {
	     header('HTTP/1.0 451 Unavailable For Legal Reasons');
	     exit;
	}

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;
$template = 'finder_recent.tpl';

pageMustBeHTTPS();

$extra = array();

if (true) {
	if (!empty($_GET['q'])) {
		$q=trim($_GET['q']);
	} else {
		$q = '';
	}

	$sphinx = new sphinxwrapper($q);
	if (!empty($sphinx->q))
		$extra[] = "q=".urlencode($sphinx->q);

	//gets a cleaned up verion of the query (suitable for filename etc)
	$cacheid = $sphinx->q;

	$sphinx->pageSize = $pgsize = 100;

	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$cacheid .=".".$pg;

	if (isset($_REQUEST['inner'])) {
		$cacheid .= '.iframe';
		$smarty->assign('inner',1);
		$extra[] = "inner";
	}

        if (!empty($_REQUEST['status']) && preg_match('/^\w+$/',$_REQUEST['status'])) {
                $cacheid .= '.'.$_REQUEST['status'];
                $smarty->assign('status',$_REQUEST['status']);
                $extra[] = "status=".$_REQUEST['status'];

		$sphinx->q .= " @status {$_REQUEST['status']}";
        }
	$cacheid = md5($cacheid);

	if (!$smarty->is_cached($template, $cacheid)) {

		$imagelist=new ImageList();
		$sph = $imagelist->_getSph();
		$index = (strpos($_SERVER['CONF_DB_DB'],'staging') !== FALSE)?'sample8':'sample8E,sample8D';

		$filter = "user_id != 124913";
		if ($CONF['template'] == 'ireland') {
			$filter .= " AND scenti >= 2000000000";
		}

		if ($filter) {
			$rr = $sph->getRow("SELECT id FROM $index WHERE $filter ORDER BY id DESC LIMIT 999,1");
		} else {
			$rr = $sph->getRow("SELECT id FROM $index ORDER BY id DESC LIMIT 999,1");
		}
                $min = $rr['id']; // GetOne annoyingly blindy adds LIMIT 1 to end, even if already a LIMIT :( - getRow does NOT!

		$filter .= " AND id >= $min";

			$bits = array();
			$bits[] = "uniqueserial(takendays)";
			$bits[] = "uniqueserial(placename_id)";
			$bits[] = "uniqueserial(scenti)";
			$bits[] = "uniqueserial(viewsquare)";
			if (!preg_match('/user_id/',$q)) {
				$bits[] = "uniqueserial(user_id)";
			}

		$col = implode('+',$bits)." as myint";
		$sqlpage = ($pg -1)* $sphinx->pageSize;

		$sql = "
		select id,realname,user_id,title,grid_reference,takenday,scenti, $col
		from $index
		where $filter
		order by myint ASC,sequence ASC
		limit $sqlpage,{$sphinx->pageSize}";

		$imagelist->getImagesBySphinxQL($sql, true, $sphinx->q);

		$smarty->assign_by_ref('results', $imagelist->images);

		if ($imagelist->numberOfPages > 1) {
			$smarty->assign('pagesString', pagesString($pg,$imagelist->numberOfPages,$_SERVER['PHP_SELF']."?".implode('&amp;',$extra)."&amp;page=") );
			$smarty->assign("offset",(($pg -1)* $sphinx->pageSize)+1);
		}
	}

	if (!empty($sphinx->qclean))
		$smarty->assign("q",$sphinx->qclean);

        $smarty->assign("yesterday",date('Y-m-d',time()-3600*24));
}


$smarty->display($template,$cacheid);

