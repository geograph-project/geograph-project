<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 5068 2008-12-02 02:24:19Z barry $
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


header('Content-type: application/json');


customExpiresHeader(3600);

if (!empty($_REQUEST['q'])) {
	$q=trim($_REQUEST['q']);
	
	$fuzzy = !empty($_REQUEST['f']);
	
	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc) 
	$cacheid = $sphinx->q.'.'.$fuzzy;

	$sphinx->pageSize = $pgsize = 60; 

	
	$pg = (!empty($_REQUEST['page']))?intval(str_replace('/','',$_REQUEST['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}
	
	$cacheid .=".".$pg;
	

		
	$offset = (($pg -1)* $sphinx->pageSize)+1;

	if ($offset < (1000-$pgsize) ) { 
		$sphinx->processQuery();



		if ($fuzzy) {
			$sphinx->_getClient()->SetIndexWeights(array('gaz'=>10,'gaz_meta'=>1));
			$ids = $sphinx->returnIds($pg,'gaz,gaz_meta');	
		} else {
			$ids = $sphinx->returnIds($pg,'category');	
		}

		if (!empty($ids) && count($ids)) {

			$where = "category_id IN(".join(",",$ids).")";

			$db = GeographDatabaseConnection(true);

			$limit = 60;

			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$rows = $db->getAssoc("
			select category_id,imageclass
			from category_stat 
			where $where
			limit $limit");

			$sep = "[";
			$results = array();
			foreach ($ids as $c => $id) {
				$row = $rows[$id];
				if (!preg_match('/^(Supplemental|Geograph|Accept)/i',$row['imageclass'])) {
				
					print $sep;
					print '"'.trim(addslashes($row)).'"';

					$sep = ",";
				}
			}
			print "]\n";
			
			
			
		} else {
			print "[]";
		}
	} else {
		print "[]";

	}
} else {
	print "[]";
}
	


?>
