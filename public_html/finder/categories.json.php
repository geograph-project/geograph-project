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


if (!empty($_GET['callback'])) {
	header('Content-type: text/javascript');

        $callback = preg_replace('/[^\w\.-]+/','',$_GET['callback']);
        echo "{$callback}(";
} else {
	header('Content-type: application/json');
}

customExpiresHeader(3600);


if (!empty($_GET['canonical'])) {
	$db = GeographDatabaseConnection(true);

	$table = isset($_GET['more'])?'category_canonical_log':'category_canonical';

	$rows = $db->getCol("
	SELECT imageclass
	FROM $table
	WHERE canonical = ".$db->Quote($_GET['canonical'])."
	GROUP BY LOWER(imageclass)");

	$sep = "[";
	$results = array();
	foreach ($rows as $c) {
		print $sep;
		print '"'.trim(addslashes($c)).'"';
		$sep = ",";
	}
	print "]\n";

} elseif (!empty($_REQUEST['q'])) {
	$q=trim($_REQUEST['q']);

	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc)
	$cacheid = $sphinx->q;

	$sphinx->pageSize = $pgsize = 60;

	$pg = (!empty($_REQUEST['page']))?intval(str_replace('/','',$_REQUEST['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$cacheid .=".".$pg;

	$offset = (($pg -1)* $sphinx->pageSize)+1;

	if ($offset < (1000-$pgsize) ) {
		$sphinx->processQuery();

		if ($sphinx->q == '..') {
			$sphinx->q = '';
		} elseif (!empty($sphinx->q) && $sphinx->q != '..')
			$sphinx->q = "\"^{$sphinx->q}$\" | ($sphinx->q)";


                $client = $sphinx->_getClient();

                $client->SetSelect('id'); //we dont need any, but sphinx wants somethingt

                if (isset($_GET['mine'])) {
                        init_session();
                        if (!$USER->registered) {
                                die("{error: 'not logged in'}");
                        }
                        $sphinx->addFilters(array('user_id'=>array($USER->user_id)));

			$ids = $sphinx->returnIds($pg,'category2');
                } else {
			$ids = $sphinx->returnIds($pg,'category');
		}

		if (!empty($ids) && count($ids)) {

			$where = "category_id IN(".join(",",$ids).")";

			$db = GeographDatabaseConnection(true);

			$limit = 60;

			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$rows = $db->getAssoc("
			SELECT category_id,imageclass
			FROM category_stat
			WHERE $where
			LIMIT $limit");

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

if (!empty($_GET['callback'])) {
        echo ");";
}
