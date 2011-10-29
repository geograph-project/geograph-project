<?php
/**
 * $Project: GeoGraph $
 * $Id: hashchanger.php 1268 2005-09-28 20:08:28Z barryhunter $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);




if (!empty($_GET['q']) && !empty($_GET['tag'])) {

	$andwhere = '';

	if (isset($_GET['prefix'])) {
		$andwhere = " AND prefix = ".$db->Quote($_GET['prefix']);

	}

	if (!empty($_GET['tag'])) {

		if (strpos($_GET['tag'],':') !== FALSE) {
			list($prefix,$_GET['tag']) = explode(':',$_GET['tag'],2);

			$andwhere = " AND prefix = ".$db->Quote($prefix);

		} 
	}

	$row= $db->getRow("SELECT * FROM tag WHERE status = 1 AND tag=".$db->Quote($_GET['tag']).$andwhere);

	if (!empty($row)) {
		
		print "<h3>Starting</h3>";
				
		if (!empty($CONF['sphinx_host'])) {
		

			$q = trim(preg_replace('/[^\w]+/',' ',str_replace("'",'',$_REQUEST['q'])));
			
			$pg = (!empty($_REQUEST['page']))?intval(str_replace('/','',$_REQUEST['page'])):0;
			if (empty($pg) || $pg < 1) {$pg = 1;}
			
			$more = true;
			$startid = 0;
							
			while ($more) {

				$sphinx = new sphinxwrapper($q);

				$sphinx->pageSize = $pgsize = 200; 

				$offset = (($pg -1)* $sphinx->pageSize)+1;

				if ($offset < (1000-$pgsize) ) { 
				
					$client = $sphinx->_getClient();

					$client->SetRankingMode(SPH_RANK_NONE);

					$sphinx->sort = "@id ASC"; //within group order
				
					if ($startid > 0) {
						 $cl->SetIDRange($startid+1,999999999);
					}
					
					$ids = $sphinx->returnIds($pg,'_images');

					if (!empty($ids) && count($ids)) {
						print "<h3>#From id $startid found ".count($ids)." images</h3>";
						
						$where = "gridimage_id IN(".join(", ",$ids).")";

						$sql = "INSERT IGNORE INTO `tagornot` 
						SELECT NULL, {$row['tag_id']} AS tag_id, gridimage_id, '' AS user_ids, 0 AS done, NOW() AS created, 0 AS updated FROM gridimage_search WHERE $where";
						
						print "<p>$sql;</p>";

												
					} else {
						print "<h3>From #$startid found no images</h3>";
					}
					
					if (count($ids) == $pgsize) {
						$more = true;
						
						$startid = array_pop($ids);
						
					} else {
						$more = false;
					}
					
				}
			} 
		
		} else {
			die("no sphinx");
		}
		
	}
		
	
}