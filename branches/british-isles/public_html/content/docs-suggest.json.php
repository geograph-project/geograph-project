<?php
/**
 * $Project: GeoGraph $
 * $Id: tags.json.php 7424 2011-09-22 21:37:52Z barry $
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


if (!empty($_GET['callback'])) {
	header('Content-type: text/javascript');
} else {
	header('Content-type: application/json');
}

$db = GeographDatabaseConnection(true);

$sql = array();

$sql['tables'] = array();
$sql['tables']['t'] = 'document_words';

$sql['columns'] = "word";

$sql['order'] = "uses DESC";

if (!empty($_GET['q']) || !empty($_GET['term'])) {
	if (!empty($_GET['term'])) {
		$bits = explode(' ',$_GET['term']);
		
		if (count($bits) > 1) {
			
			$_REQUEST['q'] = array_pop($bits);
			
			$sql['columns'] = "CONCAT(".$db->Quote(implode(' ',$bits).' ').",word) AS word";
		} else {
			$_REQUEST['q'] = $_GET['term'];
		}	
	}
	customExpiresHeader(3600);

	if (!empty($CONF['sphinx_host'])) {
				
                $q = trim(preg_replace('/[^\w]+/',' ',str_replace("'",'',$_REQUEST['q'])));
		
		$sphinx = new sphinxwrapper($q);
		
		$sphinx->pageSize = $pgsize = 60; 
		
		$pg = (!empty($_REQUEST['page']))?intval(str_replace('/','',$_REQUEST['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}
		
		$offset = (($pg -1)* $sphinx->pageSize)+1;
	
		if ($offset < (1000-$pgsize) ) { 
			$client = $sphinx->_getClient();

                        $client->SetRankingMode(SPH_RANK_NONE);

			
			$sphinx->sort = "uses DESC"; //within group order
			$client->SetGroupBy('stemcrc',SPH_GROUPBY_ATTR,"uses DESC, @id DESC"); //overall sort order

				
			$ids = $sphinx->returnIds($pg,'document_words');
			
			if (!empty($ids) && count($ids)) {
				$idstr = join(",",$ids);
				$where = "id IN(".join(",",$ids).")";
	
				$sql['wheres'] = array("`id` IN ($idstr)");
				
				$sql['limit'] = count($ids);
			} else {
				$sql['wheres'] = array(0);
			}
		} else {
			$sql['wheres'] = array(0);
		}
	} else {
		$sql['wheres'] = array("`word` LIKE ".$db->Quote($_GET['q'].'%'));

		$sql['limit'] = 100;
	}

} else {
	die("todo");
	
       
}

$query = sqlBitsToSelect($sql);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
if (!empty($_GET['term'])) {
	$data = $db->getCol($query);
} else {
	$data = $db->getAll($query);
}

if (!empty($_GET['callback'])) {
        $callback = preg_replace('/[^\w\.-]+/','',$_GET['callback']);
        echo "{$callback}(";
}

require_once '3rdparty/JSON.php';
$json = new Services_JSON();
print $json->encode($data);

if (!empty($_GET['callback'])) {
        echo ");";
}



