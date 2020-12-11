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
	'count'=>10,
	'verbose'=>true,
	'host'=>false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if ($param['host']) {
    $CONF['sphinx_host'] = $param['host'];

	 $CONF['sphinxql_dsn'] = "mysql://{$CONF['sphinx_host']}:{$CONF['sphinx_portql']}/";
}

print("Using server: $CONF[sphinx_host]\n");


//First one!
$sph = GeographSphinxConnection('sphinxql',true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
$i=0;

$value = $sph->getAssoc("SHOW STATUS LIKE 'command_callpq'");
print "$i: {$value['command_callpq']}\n";

$sph->raiseErrorFn = 'ignoreErrorHandler';


foreach(range(1,rand(3,10)) as $i) {
	$sph->Execute("CALL PQ('test')");//this IS an invalid query, but just wanting to increment command_callpq status!
}
 $sph->Execute("SHOW STATUS"); //calling show status right after call pq, doesnt work! https://github.com/manticoresoftware/manticoresearch/issues/464

$value = $sph->getAssoc("SHOW STATUS LIKE 'command_callpq'");
print "$i: {$value['command_callpq']}\n";

$first = $value['command_callpq'];


$CONF['sphinxql_dsn'] .= "?new=1";


foreach(range(1,$param['count']) as $i) {

	$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

	$value = $sph->getAssoc("SHOW STATUS LIKE 'command_callpq'");
	print "$i: {$value['command_callpq']}\n";
	if ($value['command_callpq'] != $first)
		break;
}

############################################



function ignoreErrorHandler()
{
	return true;
}

