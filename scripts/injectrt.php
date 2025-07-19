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

        'schema'=>false, //show the schema used to create a new sphinx index.
		'drop' => false, //add drop table to output
	'data'=>true, //include the actual data (can use to just get schema, but the default limit=1 means usually safe to test data queries.

	'file'=>false, //read a sphinx.conf file and output similar index (overrides index, select, and $options)

	'table'=>false, //read a table, and automatically create a select statement
		'delta'=>false, //specify a column to just get new rows
		'where'=>false, //optional filter ANDed to generated SELECT query.

	'index' => 'gridimage', //name of index to create (cluster name will add auotmatically)
	'select' => "SELECT * FROM sphinx_view", //the query will be used for getting data (using 'table' will automatically define this!)
	//BE WEARY OF ADDING GROUP BY TO THIS QUERY, AS THE SCHEMA BELOW USING LIMIT 1 WILL STRUGGLE.

	'cluster' => 'manticore',
	'extended' => true, //output extended inserts
	'limit' => 1, //if sepcified, myst be under 1000!

	'execute'=>false,
	'debug'=>false,
    'filename' => false,
);

$multis = $joineds = array();
$options = array();

#########################################################

$cwdir = getcwd();

chdir(__DIR__);
require "./_scripts.inc.php";
require "./injectrt_lib.php";

############################################

//bodge for now! - could be rad from sample6.conf
if (strpos($param['select'], 'sphinx_view') && empty($param['file']) && empty($param['table'])) {
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

$db_primary = null; //only need it if ding delta
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
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

#########################################################
// extract query from a sphinx.conf file

if (!empty($param['file'])) {
    extract_query_from_conf($param, $options, $cwdir);
	//updates $param directly
}

#########################################################
// generate a SELECT directly from the table definition

if (!empty($param['table'])) {
    generate_select_from_table($param);
	//updates $param directly
}

#########################################################
// SCHEMA

$schema_sql = '';
if (!empty($param['schema'])) {
    $schema_sql = generate_schema_sql($param, $multis, $joineds, $options);
}

if ($param['filename']) {
    $file_handle = fopen($param['filename'], 'a');
    if (!empty($schema_sql)) {
        fwrite($file_handle, $schema_sql);
    }
} elseif ($param['execute']) {
    $rt = GeographSphinxConnection('manticorert');
    if (!empty($schema_sql)) {
	$rt->Execute($schema_sql);
    }
} else {
        print $schema_sql;
}

#########################################################
// DATA

$data_sql = '';
if (!empty($param['data'])) {
    if ($param['filename']) {
        $file_handle = fopen($param['filename'], 'w');
        generate_data_sql($param, $multis, $joineds, $file_handle);
    } else {
        $data_sql = generate_data_sql($param, $multis, $joineds); //note this will deal with $param['execute'] automatically!

	//todo, perhaps generate_data_sql should output directly too!
        if (!$param['execute'])
		print "$data_sql\n";
    }
}

#########################################################
// any updates for delta tracking!

$update_sql = '';
if (!empty($param['delta']) && !empty($param['table'])) {
    $update_sql = update_last_indexed_sql($param);
}

if (!empty($update_sql)) {
    if ($param['filename']) {
	//actully this shoudl NOT be written to file - which is the sphixt/manticore!
        //fwrite($file_handle, $update_sql);
    } elseif ($param['execute']) {
	$db_primary->Execute($update_sql);
    } else {
	fwrite(STDERR, "\nRun this on PRIMARY database: $update_sql;\n");
    }
}

#########################################################

if ($param['filename']) {
    fclose($file_handle);
}

fwrite(STDERR,date('H:i:s ')."DONE!\n");
