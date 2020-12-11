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

############################################

//these are the arguments we expect
$param=array(
	'verbose'=>true,
	'host'=>false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);

$db_read = GeographDatabaseConnection(true);

############################################

if ($param['host']) {
    $CONF['sphinx_host'] = $param['host'];

	 $CONF['sphinxql_dsn'] = "mysql://{$CONF['sphinx_host']}:{$CONF['sphinx_portql']}/";
}

print("Using server: $CONF[sphinx_host]\n");

$sph = GeographSphinxConnection('sphinxql',true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

############################################

$results = array();

$results['db_primary_gridimage'] =
	$db->getRow("SELECT COUNT(*) AS images, MAX(gridimage_id) AS last_id, MAX(submitted) AS last_submitted
	FROM gridimage WHERE moderation_status in ('accepted','geograph')");

$results['db_primary_gridimage_search'] =
	$db->getRow("SELECT COUNT(*) AS images, MAX(gridimage_id) AS last_id, MAX(submitted) AS last_submitted
	FROM gridimage_search");

$results['db_primary_user_stat'] =
	$db->getRow("select sum(images) AS images,max(last) as last_id FROM user_stat where user_id > 0");

$results['db_primary_user_gridsquare'] =
	$db->getRow("select sum(imagecount) AS images,max(last) as last_id FROM user_gridsquare");

################################

if ($db_read->readonly) { //no point running if NOT the slave
	$results['db_replica_gridimage'] =
		$db->getRow("SELECT COUNT(*) AS images, MAX(gridimage_id) AS last_id, MAX(submitted) AS last_submitted
		FROM gridimage WHERE moderation_status in ('accepted','geograph')");

	$results['db_replica_gridimage_search'] =
		$db->getRow("SELECT COUNT(*) AS images, MAX(gridimage_id) AS last_id, MAX(submitted) AS last_submitted
		FROM gridimage_search");

	$results['db_replica_user_stat'] =
		$db->getRow("select sum(images) AS images,max(last) as last_id FROM user_stat where user_id > 0");

	$results['db_replica_user_gridsquare'] =
		$db->getRow("select sum(imagecount) AS images,max(last) as last_id FROM user_gridsquare");
}

################################

$indexes = array('gi_stemmed,gi_stemmed_delta', 'sample8', 'viewpoint');
foreach ($indexes as $index) {
	$cols = ($index == 'viewpoint')?'':',MAX(submitted) AS last_submitted';
	$results["sph_$index"] =
	        $sph->getRow("SELECT COUNT(*) AS images, MAX(id) AS last_id $cols
        	FROM $index");
}

################################
# we connected to random manticore server, try connecting to the OTHER... 

$sph->raiseErrorFn = 'ignoreErrorHandler';
$CONF['sphinxql_dsn'] .= "?new=1";


foreach(range(1,rand(3,10)) as $i) {
	$sph->Execute("CALL PQ('test')");//this IS an invalid query, but just wanting to increment command_callpq status!
}
$sph->Execute("SHOW STATUS"); //calling show status right after call pq, doesnt work! https://github.com/manticoresoftware/manticoresearch/issues/464

$value = $sph->getAssoc("SHOW STATUS LIKE 'command_callpq'");
$first = $value['command_callpq'];

print "F=$first\n";
foreach(range(1,5) as $i) {
	$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$value = $sph->getAssoc("SHOW STATUS LIKE 'command_callpq'");
print "F={$value['command_callpq']}\n";
	if ($value['command_callpq'] != $first)
		break;
}

if ($value['command_callpq'] != $first)
	foreach ($indexes as $index) {
		$cols = ($index == 'viewpoint')?'':',MAX(submitted) AS last_submitted';
		$results["sph2_$index"] =
		        $sph->getRow("SELECT COUNT(*) AS images, MAX(id) AS last_id $cols
	        	FROM $index");
	}

################################

foreach ($results as $key => $data) {
	if (empty($data['last_submitted'])) $data['last_submitted'] = '';

	printf("%40s   %15s   %15s   %15s \n",  $key,
				number_format($data['images'],0),
				number_format($data['last_id'],0),
				is_numeric($data['last_submitted'])?date('Y-m-d H:i:s',$data['last_submitted']):$data['last_submitted'] );

}




############################################



function ignoreErrorHandler()
{
	return true;
}




