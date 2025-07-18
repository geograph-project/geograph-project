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
require "./injectrt_lib.php";

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

//function inject_delta_data($table, $index = null, $delta = null, $wheres = array(), $execute = false, $autodelete=false)


if ($param['table'] == 'gridprefix') {
	//harcdoded example!
	inject_delta_data('gridprefix','gridprefix',null, array("prefix = 'TQ'"), $param['execute'], true);

} elseif ($param['index'] == 'gallery_ids') { //special case that does NOT need to use delta or where for now! (its used with views, that have the where AND limit defined!)
	$wheres = array();
	if (!empty($param['where']))
		$wheres[] = $param['where'];

	inject_delta_data($param['table'], $param['index'], $param['delta'], $wheres, true, false);

} elseif (!empty($param['table']) && (!empty($param['delta']) || !empty($param['where'])) ) {
	$wheres = array();
	if (!empty($param['where']))
		$wheres[] = $param['where'];
	inject_delta_data($param['table'], $param['index'], $param['delta'], $wheres, $param['execute']);

} else {
	$tables = $rt->getAssoc("SHOW TABLES");
	foreach ($tables as $table => $type) //todo, check mysql table eixsts?
		print "--table=$table --where='{$table}_id = ...'\n";
}



//inject_delta_data('sphinx_view','gridimage',null, array("id = {$this->gridimage_id}"), true); //sphinx_view already has the primary aialised to id
//inject_delta_data('gridsquare','gridsquare',null, array("gridsquare_id = {$this->gridsquare_id}"), true);
//inject_delta_data('gridprefix','gridprefix',null, array("prefix = '{$this->prefix}'"), true, true); //no ID column, so REPLACE into wouldnt work, need to delete!

//inject_delta_data('gallery_view','gallery_ids',null, array(), true, false);
//inject_delta_data('gallery_view_delta','gallery_ids',null, array(), true, false);

#########################################################

fwrite(STDERR,date('H:i:s ')."DONE!\n");
