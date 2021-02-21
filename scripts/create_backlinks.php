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

$param = array('debug'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


//insert a FAKE log (just so we can plot on a graph ;)
$db->Execute("INSERT INTO event_log SET
        event_id = 0,
        logtime = NOW(),
        verbosity = 'trace',
        log = 'running event_handlers/every_day/".basename($argv[0])."',
        pid = 33");

############################################

if ($param['debug'])
	print "Starting. ".date('r')."\n";

$perpage = 1000;

extract($db->GetRow("select min(gridimage_id) as `min`,max(gridimage_id) as `max` from gridimage_search"),
	EXTR_PREFIX_INVALID, 'numeric'); //need to cope with row being either Assoc or Both. Can't assume with be Both. But can assume not Num only.

$start=floor($min / $perpage) * $perpage;
if (!$start) $start =1;

if ($param['debug'])
	print "list($min,$max)=>$start)\n\n";

$start_date = $db->getOne("SELECT date_sub(now(),interval 1 hour)");//yes know can do this in php avoiding db call, but makes sure its right timezone, as well as right format

for ($from=$start; $start<=$max; $start+=$perpage) {

	$sql = "select gridimage_id,comment from gridimage_search where gridimage_id between $start and ".($start+$perpage-1)." and (comment like '%[[%' or comment like '%/photo/%')";

	$bits = array();

	$recordSet = $db->Execute($sql);

	while (!$recordSet->EOF) {
		$gridimage_id =  $recordSet->fields['gridimage_id'];

		if (preg_match_all('/\[\[(\d+)\]\]/',$recordSet->fields['comment'],$g_matches)) {
			foreach ($g_matches[1] as $g_i => $g_id) {
				$bits[] = "($g_id,$gridimage_id,NOW(),NOW())";
			}
		}
		if (preg_match_all('/geograph\.(org\.uk|ie)\/(p|photo)\/(\d+)\b(?!\.kml)/',$recordSet->fields['comment'],$g_matches)) {
			foreach ($g_matches[3] as $g_i => $g_id) {
				$bits[] = "($g_id,$gridimage_id,NOW(),NOW())";
			}
		}

		$recordSet->MoveNext();
	}
	$recordSet->Close();

	if (count($bits)) {
		$sql = "INSERT INTO gridimage_backlink VALUES ".implode(',',$bits)." ON DUPLICATE KEY UPDATE `updated`=NOW()";
		if ($param['debug'])
			print "Inserting ".count($bits)." Rows...";
		$db->Execute($sql);

	} elseif ($param['debug']) {
		print "no images between $start and ".($start+$perpage-1)."\n";
	}
}

########################################

$sql = "DELETE FROM gridimage_backlink WHERE updated < ".$db->Quote($start_date);
if ($param['debug'])
	print "$sql\n";
$db->Execute($sql);


########################################

//the limit 100, is just to throttle them!
$sql = "insert into gridimage_typo
        select from_gridimage_id as gridimage_id, 'self image link' as word, now() as created, updated, 0 as muted, 0 as moderator, 'link' as `type`
        from gridimage_backlink where gridimage_id = from_gridimage_id
        order by created desc limit 100
        on duplicate key update updated=gridimage_backlink.updated, word='self image link'";
if ($param['debug'])
	print "$sql\n";
$db->Execute($sql);

########################################

//the join to gridimage (rather than gridimage_search) allows us to match pending. Although then need to filter rejected too.

$sql = "insert into gridimage_typo
	select from_gridimage_id as gridimage_id, CONCAT('broken link [[',b.gridimage_id,']]') as word, now() as created, updated, 0 as muted, 0 as moderator, 'link' as `type`
	from gridimage_backlink b left join gridimage g using (gridimage_id)
	where g.gridimage_id IS NULL OR g.moderation_status = 'rejected'
	order by created desc limit 100
	on duplicate key update updated=b.updated, word=CONCAT('broken link [[',b.gridimage_id,']]')";
if ($param['debug'])
	print "$sql\n";
$db->Execute($sql);

########################################

if ($param['debug'])
	print "Done. ".date('r')."\n";

