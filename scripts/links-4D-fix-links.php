<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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

$param=array(
        'number'=>1,   //number to do each time
	'execute'=>0, //run for real?
	'name' => 'list.english-heritage.org.uk',
	'like' => 'http://list.english-heritage.org.uk/%uid=%', //matching in mysql LIKE
	'src' => 'http:\/\/list\.english-heritage\.org\.uk\/resultsingle\.aspx\?uid=(\d+)&?(searchtype=mapsearchv?)?([,;):]*)', //preg_replace
	'dst' => 'https://historicengland.org.uk/listing/the-list/list-entry/\\1 \\3',
);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$sql = "SELECT gridimage_link_id,url,gridimage_id,content_id,first_used FROM gridimage_link
        WHERE url like ".$db->Quote($param['like'])."
        AND parent_link_id = 0
        AND next_check < '9999-00-00'
	LIMIT {$param['number']}";


$user_id = 3;

$recordSet = $db->Execute($sql);

if (!$recordSet->recordCount())
	die("No rows for\n$sql;\n");

while (!$recordSet->EOF) {
        $bindts = $db->BindTimeStamp(time());
        $row = $recordSet->fields;
        $updates = array();
        $content = '';

        $url=$row['url'];

        print str_repeat("#",80)."\n";
        print_r($row);

        $after = preg_replace('/'.$param['src'].'/',$param['dst'], $row['url']);
        if ($row['url'] == $after) {
                 die("replacement failed for {$row['url']} \n\n");
        }


        $sqls = array();

	if (!empty($row['gridimage_id'])) {

                $replace = "REGEXP_REPLACE(comment,".$db->Quote($param['src']).",".
                                                    $db->Quote($param['dst']).")";

                $sqls[] = "INSERT INTO gridimage_ticket SET
                                                                gridimage_id={$row['gridimage_id']},
                                                                suggested=NOW(),
                                                                user_id=$user_id,
                                                                updated=NOW(),
                                                                status='closed',
                                                                notes='Fixing the {$param['name']} link',
                                                                type='minor',
                                                                notify='',
                                                                public='everyone'";

                $sqls[] = "SET @ticket_id := LAST_INSERT_ID()";

                $sqls[] = "INSERT INTO gridimage_ticket_item SELECT
                                                                NULL AS gridimage_ticket_item_id,
                                                                @ticket_id AS gridimage_ticket_id,
                                                                $user_id AS approver_id,
                                                                'comment' AS field,
                                                                comment AS oldvalue,
                                                                $replace AS newvalue,
                                                                'immediate' AS status,
                                                                NOW() AS updated
                                        FROM gridimage WHERE gridimage_id = {$row['gridimage_id']}";

                $sqls[] = "UPDATE gridimage SET comment = $replace WHERE gridimage_id = {$row['gridimage_id']}";
                $sqls[] = "UPDATE gridimage_search SET comment = $replace WHERE gridimage_id = {$row['gridimage_id']}";


                foreach ($sqls as $sql) {
			if ($param['execute']) {
	                        $db->Execute($sql);
	                        print "Rows = ".$db->Affected_Rows().", ";
			} else {
	                        print preg_replace("/\s+/",' ',$sql).";\n";
			}
                }

                $updates['next_check'] = '9999-01-01'; //mark the link as deleted!
                $updates['fix_attempted'] = $bindts;

                //only update the very specific link, because its the only image we've modifided!
                $where = "gridimage_link_id = ?";
                $where_value = $row['gridimage_link_id'];

	} elseif (!empty($row['content_id'])) {
		$table = $column = $pkey = null;
		$data = $db->getRow("SELECT content_id,source,foreign_id from content where content_id = {$row['content_id']}");
		if (empty($data)) {
			print "{$row['content_id']}  Content not found\n";
		} elseif($data['source'] == 'article') {
			$table = 'article'; $pkey = "{$table}_id";
			$column = 'content';
			//todo, insert into article_revision
		} elseif($data['source'] == 'snippet') {
			$table = 'snippet'; $pkey = "{$table}_id";
			$column = 'comment';
		} elseif($data['source'] == 'blog') {
			$table = 'blog'; $pkey = "{$table}_id";
			$column = 'content';
		} elseif($data['source'] == 'trip') {
			$table = 'geotrips'; $pkey = "id";
			$column = 'descr';
		}

		if (!empty($table)) {
	                $replace = "REGEXP_REPLACE($column,".$db->Quote($param['src']).",".
        	                                            $db->Quote($param['dst']).")";

			$sql = "UPDATE $table SET $column = $replace WHERE $pkey = {$data['foreign_id']}";
			if ($param['execute']) {
				print preg_replace("/\s+/",' ',$sql).";\n";
				$db->Execute($sql);
	                        print "Rows = ".$db->Affected_Rows()."\n";
			} else {
				print preg_replace("/\s+/",' ',$sql).";\n";
			}


	                $updates['next_check'] = '9999-01-01'; //mark the link as deleted!
	                $updates['fix_attempted'] = $bindts;

                	//only update the very specific link, because its the only image we've modifided!
        	        $where = "gridimage_link_id = ?";
	                $where_value = $row['gridimage_link_id'];
		}
	}

        if (!empty($updates)) {

		if ($param['execute']) {
	                $db->Execute('UPDATE gridimage_link SET `'.implode('` = ?,`',array_keys($updates))."` = ? WHERE $where",
                        array_merge(array_values($updates),array($where_value)) );

                        print "Rows = ".$db->Affected_Rows()."\n";
//                      print_r($db->getAll("SHOW WARNINGS()"));
                } else {
			       print 'UPDATE gridimage_link SET `'.implode('` = ?,`',array_keys($updates))."` = ? WHERE $where". ";\n";
		}


		//because we want to perseve the first_used, insert a new row directly!
		$bits = explode(" ",$after);
		$row['url'] = $bits[0]; //if there is something after a space, want to keep just the URL
		$row['next_check'] = $bindts;
		$row['last_found'] = $bindts;
		$row['created'] = $bindts;
		unset($row['gridimage_link_id']);

		if ($param['execute']) {
			$db->Execute('INSERT INTO gridimage_link SET `'.implode('` = ?,`',array_keys($row)).'` = ?',array_values($row));
		} else {
  			       print 'INSERT INTO gridimage_link SET `'.implode('` = ?,`',array_keys($row)).'` = ?'. ";\n";
		}
        }

        $recordSet->MoveNext();
}
$recordSet->Close();


