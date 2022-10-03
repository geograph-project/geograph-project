<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
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


//these are the arguments we expect
$param=array(
	'host'=>false, //override the mysql host to dump from
	'rt'=>false, //override rt host

	'table'=>false, //specify the table to copy
		'delta'=> false, //give the name of a date column! - generall need to prvide either delta, or where
		'where'=> false, //a "where" filter
	'index'=>false, //specify the index name (defaults to same as table!)


	'cluster'=>'manticore', //todo, will come from container configmap

	'limit'=>100, //just a quick sanity check
	'extended'=>true,
	'execute'=>false,
	'debug'=>false,
);

$multis = $joineds = array();
$options = array();

#########################################################

$cwdir = getcwd();

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

//bodge for now! - could be rad from sample6.conf
if ($param['table'] == 'gridimage') {
	//sql_attr_multi          = uint my_square from ranged-main-query; \
	//        select gridimage_id, u.user_id from gridimage_search inner join user_gridsquare u using (grid_reference) WHERE  WHERE gridimage_id>=$start AND gridimage_id<=$end order by gridimage_id
	$multis['my_square'] = 'select u.user_id from gridimage_search inner join user_gridsquare u using (grid_reference) WHERE gridimage_id = $id';

	$multis['content_ids'] = '
        (select content_id from gridimage_content WHERE gridimage_id = $id)
        UNION (select 1 as content_id from gallery_ids where baysian > 3 AND id = $id)
        UNION (select 2 as content_id from gridimage_daily where showday <= date(now()) and gridimage_id = $id)
        UNION (select content_id from content inner join gridimage_post gp on (foreign_id=topic_id) where source in (\'gallery\',\'themed\',\'gsd\') and  gp.gridimage_id = $id)
        UNION (select content_id from content inner join gridimage_snippet gs on (foreign_id=snippet_id) where source = \'snippet\' AND gs.gridimage_id = $id)';

} elseif ($param['table'] == 'snippet') {
	//in theory better do with GROUP_CONCAT, but here may be MANY rows!

	$multis['image_ids'] = 'SELECT gridimage_id FROM gridimage_snippet WHERE snippet_id = $id';

	//lookup the squares that use the image
	$joineds['image_squares'] = 'SELECT DISTINCT grid_reference FROM gridimage_snippet INNER JOIN gridimage_search USING (gridimage_id) WHERE snippet_id = $id';
}

############################################
//connect first to the REAL primary

if (!empty($param['delta'])) {
	//we need to get the current value from primary database!
	$db_primary = GeographDatabaseConnection(false);
}

############################################
// then the db to dump from

$host = empty($CONF['db_read_connect'])?$CONF['db_connect']:$CONF['db_read_connect'];
if ($param['host']) {
    $host = $param['host'];
}
fwrite(STDERR,date('H:i:s')."\tUsing db server: $host\n");
$DSN_READ = str_replace($CONF['db_connect'],$host,$DSN);

//we've setup $DSN_READ, using $param[host] even if isn't a db_read_connect
$db = GeographDatabaseConnection(true);

############################################

//if setup to use the balancer, we actully need a worker to be able to insert into index!
$CONF['manticorert_host'] = str_replace('balancer','worker', $CONF['manticorert_host']);

if (!empty($param['rt']))
      $CONF['manticorert_host'] = $param['rt'];
print(date('H:i:s')."\tUsing rt server: {$CONF['manticorert_host']}\n");

$rt = GeographSphinxConnection('manticorert',true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#########################################################

if ($param['table'] == 'gridprefix') {
	//harcdoded example!
	replace_into_index('gridprefix','gridprefix',null, array("prefix = 'TQ'"), $param['execute'], true);

} elseif ($param['index'] == 'gallery_ids') { //special case that does NOT need to use delta or where for now! (its used with views, that have the where AND limit defined!)
	$wheres = array();
	if (!empty($param['where']))
		$wheres[] = $param['where'];

	replace_into_index($param['table'], $param['index'], $param['delta'], $wheres, true, false);

} elseif (!empty($param['table']) && (!empty($param['delta']) || !empty($param['where'])) ) {
	$wheres = array();
	if (!empty($param['where']))
		$wheres[] = $param['where'];
	replace_into_index($param['table'], $param['index'], $param['delta'], $wheres, $param['execute']);

} else {
	$tables = $rt->getAssoc("SHOW TABLES");
	foreach ($tables as $table => $type) //todo, check mysql table eixsts?
		print "--table=$table --where='{$table}_id = ...'\n";
}



//replace_into_index('sphinx_view','gridimage',null, array("id = {$this->gridimage_id}"), true); //sphinx_view already has the primary aialised to id
//replace_into_index('gridsquare','gridsquare',null, array("gridsquare_id = {$this->gridsquare_id}"), true);
//replace_into_index('gridprefix','gridprefix',null, array("prefix = '{$this->prefix}'"), true, true); //no ID column, so REPLACE into wouldnt work, need to delete!

//replace_into_index('gallery_view','gallery_ids',null, array(), true, false);
//replace_into_index('gallery_view_delta','gallery_ids',null, array(), true, false);

#########################################################
// mostly duplicates injectrt.php, but doesnt need most of the magic, it just gathers columns to copy from the actual index!


//either use 'delta' to update ALL new rows.
// or provide a filter with $wheres, eg the single row, you know updated!
function replace_into_index($table, $index = null, $delta = null, $wheres = array(), $execute = false, $autodelete=false) {
	global $param, $CONF, $db, $rt, $multis, $joineds, $db_primary;

	//use $param, just so code is mostly copy/pastable
	$param['table'] = $table;
	$param['index'] = $index?$index:$table;
	$param['delta'] = $delta;
	$param['execute'] = $execute;

	$table_columns = $db->getAssoc("DESCRIBE {$param['table']}");
	$index_columns = $rt->getAssoc("DESCRIBE {$param['index']}");
	$primary = null;
	if ($table == 'sphinx_view')
		$primary = 'id'; //the view, does show show primary key in DESCRIBE!
	elseif ($table == 'gridprefix')
		$primary = ''; //we dont have a numeric id, so rely on auto-insert-id. REPLAACE INTO fails, so need to delete, see below
	else foreach ($table_columns as $name => $row)
		if (strpos($row['Type'],'int(') !== FALSE)
                        if ($row['Key'] == 'PRI')
				$primary = $name; //todo will be confused if multiple keys. But probably not using this function for them!

	$cols = array();
	foreach ($index_columns as $name => $row) {
		if (!empty($multis[$name]) || !empty($joineds[$name]))
			continue;

		if (isset($table_columns[$name])) {
			$row = $table_columns[$name];
			if ($name == 'enabled' || $name == 'status' || $name == 'approved') {
	                        //the index should only include enabled rows!
                        	if ($param['table'] == 'gridimage_tag')
                                $wheres[] = "$name = 2";
                	        else
        	                        $wheres[] = "$name > 0";
	                } elseif ($name == 'wgs84_lat' || $name == 'wgs84_long' || $name == 'vlat' || $name == 'vlong') {
				$cols[] = "RADIANS($name) as $name";
			} elseif ($row['Type'] == 'datetime' || $row['Type'] == 'timestamp') {
                        	$cols[] = "UNIX_TIMESTAMP($name) AS $name";
        	        } elseif ($row['Type'] == 'date') {
	                        $cols[] = "TO_DAYS($name) AS $name";
			} else {
				$cols[] = $name;
			}

		} elseif (isset($table_columns[$name.'_utf'])) {
			if (($idx = array_search($name,$cols)) !== FALSE)
                                $cols[$idx] = "{$name}_utf as {$name}"; //replace it!
                        else
                                $cols[] = "{$name}_utf as {$name}";
		} elseif ($name == 'hectad') {
                        $cols[] = "CONCAT(SUBSTRING(grid_reference,1,LENGTH(grid_reference)-3),SUBSTRING(grid_reference,LENGTH(grid_reference)-1,1)) AS hectad";
		} elseif ($name == 'myriad') {
			$cols[] = "SUBSTRING(grid_reference,1,LENGTH(grid_reference)-4) AS myriad";
		} elseif ($name == 'id' && !empty($primary)) {
			$cols[] = "{$primary} as id";
		} else {
			print "unknown $name";
		}
	}

	#################################################

	$cols = implode(", ",$cols);
        $param['select'] = "SELECT $cols FROM {$param['table']}";

        if (!empty($param['delta'])) {
                $bits = explode('.',$CONF['manticorert_host']);
                $param['date'] = $db_primary->getOne($sql = "SELECT last_indexed FROM sph_server_index WHERE index_name = '{$param['index']}' AND server_id = '{$bits[0]}'");

                if (empty($param['date'])) {
                        fwrite(STDERR,"#Warning: unable to find last index date - aborting replace_into_index()\n");
			return false;
                } else
                        $wheres[] = "{$param['delta']} > ".$db_primary->Quote($param['date']);

		if ($param['debug'])
			fwrite(STDERR,"#IMPORTANT, does not yet delete the old rows\n"); //todo
        }

        if (!empty($wheres))
                $param['select'] .= " WHERE ".implode(' AND ',$wheres);

	#################################################
	// actually fetch the rows

	if (!empty($param['cluster'])) //the inserts need the cluster name (the select query does not!)
		$param['index'] = "{$param['cluster']}:{$param['index']}";

	if ($param['debug'])
		print_r($param);

	$result = mysqli_query($db->_connectionID,$param['select']) or die("unable to run {$param['select']}\n".mysqli_error($db->_connectionID)."\n\n");

	if (mysqli_num_rows($result) > $param['limit'])
		die("too many rows! ".mysqli_num_rows($result)."\n");

	if (mysqli_num_rows($result)) {
		//if there is a row, we first need to delete
		if (!empty($wheres) && $autodelete) { //where needs ot be compatible with mysql AND manticore!!
			$delete = "DELETE FROM {$param['index']} WHERE ".implode(' AND ',$wheres);
                        if (!empty($param['execute'])) {
                                $rt->Execute($delete);
                        } else {
				fwrite(STDERR,"$delete;\n");
			}
		}

		$names=array();
		$types=array();
		$fields=mysqli_fetch_fields($result);

		foreach ($fields as $key => $obj) {
			$names[] = $obj->name;
			switch($obj->type) {
		                case MYSQLI_TYPE_INT24 :
        		        case MYSQLI_TYPE_LONG :
                		case MYSQLI_TYPE_LONGLONG :
		                case MYSQLI_TYPE_SHORT :
        		        case MYSQLI_TYPE_TINY :
					$types[] = 'int'; break;
				case MYSQLI_TYPE_FLOAT :
				case MYSQLI_TYPE_DOUBLE :
				case MYSQLI_TYPE_DECIMAL :
					$types[] = 'real'; break;
				default:
					if (preg_match('/_ids$/',$obj->name)) { //dont have a better way at the moment!
						$types[] = 'mva'; break;
					}
					$types[] = 'other'; break; //we dont actully care about the exact type, other than knowing numeric
			}
		}
		foreach($multis as $name => $query)
			$names[] = $name;
		foreach($joineds as $name => $query)
			$names[] = $name;

		//really need to always do 'complete' inserts, as manticore expects columns in same order as EXPLAIN, which is typically diffent to create table, eg all fields first. Also 'string attribute index' then need inserting twice, by naming columns dont need to duplicate!
		$insert = "REPLACE INTO {$param['index']} (".implode(",",$names).") VALUES\n";

		$c=0; $buffer = '';
		while($row = mysqli_fetch_row($result)) {
		        if ($param['extended'] && $c%100) { //ideally should work on length of line, but just number of lines. (so each insert fits in one packet (16M?)
		                $sep = "),\n(";
		        } elseif ($c) {
				if ($buffer && !empty($param['execute'])) {
					$buffer .= ")";
					if ($param['debug'] === '2')
						print "$buffer;\n";
					$rt->Execute($buffer);
					if ($param['debug'])
						 fwrite(STDERR,"affected: ".$rt->Affected_Rows()."\n");
				} else {
					fwrite(STDERR,"$buffer;\n");
				}
				$buffer = '';
		                $sep = "$insert(";
		        } else {
		                $sep = "$insert(";
		        }
			foreach($row as $idx => $value) {
				if ($types[$idx] == 'mva') //mva's need special treatment if importing into index
					$value = "(".mysqli_real_escape_string($db->_connectionID,$value).")";
				elseif (is_null($value))
					$value = "''"; //doesnt support null!
				elseif ($types[$idx] != 'int' && $types[$idx] != 'real') { //Don't just use 'is_numeric', as inserting a number into string attribute, silently fails!
					$enc = mb_detect_encoding($value, 'UTF-8, ISO-8859-15, ASCII');
					if ($enc == 'ISO-8859-15' || strpos($value,'&#')!==FALSE) //dont just blindly convert, as while MOST columns in database are latin1, not quite all!
						$value = latin1_to_utf8($value);
					$value = "'".mysqli_real_escape_string($db->_connectionID,$value)."'";
				}
				$buffer .= "$sep$value";
				$sep = ',';
			}
			foreach($multis as $name => $query) {
				$query = str_replace('$id',$row[0],$query);
				$ids = $db->getCol($query);
				$buffer .= "$sep(".implode(',',$ids).")";
			}
			foreach($joineds as $name => $query) {
				$query = str_replace('$id',$row[0],$query);
				$words = $db->getCol($query);
				$buffer .= "$sep".$db->Quote(implode(' ',$words));
			}
			$c++; //ideally should work on length of line,
		}
		$buffer .= ")";
		if ($buffer && !empty($param['execute'])) {
			if ($param['debug'] === '2')
				print "$buffer;\n";
			$rt->Execute($buffer);
			if ($param['debug'])
				 fwrite(STDERR,"affected: ".$rt->Affected_Rows()."\n");
		} else {
			fwrite(STDERR,"$buffer;\n");
		}
	}

	#################################################
	//update the last_indexed, note will update the 'updated' even if there are no rows above :)

	if (!empty($param['delta']) && !empty($param['table'])) {
		//get the last date from database we connected to... (eg it could be a lagging replica!)
		//todo, should gather this from the actual query above
		$row = $db->getRow("SELECT {$param['delta']} FROM {$param['table']} ORDER BY {$param['delta']} desc LIMIT 1");

		$param['index'] = preg_replace('/\w+:/','',$param['index']); //the cluster added this, but dont want it here!
		$bits = explode('.',$CONF['manticorert_host']);
		$sql = "REPLACE INTO sph_server_index SET index_name = '{$param['index']}', server_id = '{$bits[0]}', last_indexed = '{$row[$param['delta']]}', updated=NOW()";

		if ($param['execute'] > 1)
        		$db_primary->Execute($sql);
		else
			fwrite(STDERR, "\nRun this on PRIMARY database: $sql;\n");
	}
}

#########################################################

fwrite(STDERR,date('H:i:s ')."DONE!\n");

