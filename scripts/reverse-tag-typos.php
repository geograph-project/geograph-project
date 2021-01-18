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

error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

############################################

//these are the arguments we expect
$param=array(
        'action'=>'dummy',
	'user'=>3,
	'id'=>false,
	'ids'=>false,
);

$HELP = <<<ENDHELP
    --action=dummy|execute|verbose : set to execute to run for real, verbose to get extra detail
ENDHELP;

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

require_once('adodb/adodb-errorhandler.inc.php');

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (empty($param['id']))
	die("specify id\n");
if (empty($param['ids']))
	die("specify ids\n");

$sql = "select report_id,gridimage_id,
tag2_id as tag_id, tag2 as tag,
r.tag_id as tag2_id, tag as tag2
from tag_report r inner join gridimage_tag gt on (gt.tag_id = r.tag2_id)
where r.report_id = {$param['id']} AND gridimage_id IN ({$param['ids']}) AND gt.status > 0
 order by gridimage_id,tag_id";

print "$sql\n";

$recordSet = $db->Execute($sql);

if ($recordSet->RecordCount() === 0) {
        if ($param['action'] != 'execute')
                print "no images\n";

        exit;
}


$tickets = $items = $sqls = array();
while (!$recordSet->EOF)
{
        $r = $recordSet->fields;


		$user_id = $param['user'];


		if (empty($r['tag_id']) || empty($r['tag2_id']) ) {
			print_r($r);
			$con = print_r($r,TRUE);

			if ($param['action'] != 'dummy')
				mail('geograph@barryhunter.co.uk','[Geograph] MISSING TAG IDS',$con);

			die("MISSING TAG IDS!\n");
		}

		$sqls[] = "UPDATE IGNORE gridimage_tag SET tag_id = {$r['tag2_id']} WHERE tag_id = {$r['tag_id']} AND gridimage_id = {$r['gridimage_id']} # {$r['tag']} > {$r['tag2']}";
			//this is trickly. Any of the above that failed (due to duplicate key), means the 'new' tag is already on the image, and so the old one can be zapped.

		$final['d:'.$r['tag_id'].':'.$r['gridimage_id']] = "UPDATE gridimage_tag SET status = 0 WHERE tag_id = {$r['tag_id']} AND gridimage_id = {$r['gridimage_id']} # old={$r['tag']}";

		//store these up, because they need to be applied in strict order, so the LAST_INSERT_ID function works
                $tickets[$r['gridimage_id']] = "INSERT INTO gridimage_ticket SET
                                                                gridimage_id={$r['gridimage_id']},
                                                                suggested=NOW(),
                                                                user_id=$user_id,
                                                                updated=NOW(),
                                                                status='closed',
                                                                notes='Applying a change to a tag(s)',
                                                                type='minor',
                                                                notify='',
                                                                public='everyone'";

		$items[$r['gridimage_id']][0] = "SET @ticket_id := LAST_INSERT_ID()";

                $items[$r['gridimage_id']][$r['tag_id']] = "INSERT INTO gridimage_ticket_item SET
                                                                gridimage_ticket_id = @ticket_id,
                                                                approver_id = $user_id,
                                                                field = 'tag',
                                                                oldvalue = ".$db->Quote($r['tag']).",
                                                                newvalue = ".$db->Quote($r['tag2']).",
                                                                status = 'immediate'";


        $recordSet->MoveNext();
}

$recordSet->Close();

if (empty($tickets) && empty($final)) {
	if ($param['action'] != 'execute')
		print "no queries\n";
	exit;
}

foreach ($tickets as $gridimage_id => $sql) {
	$sqls[] = $sql;
	foreach ($items[$gridimage_id] as $sql) {
		$sqls[] = $sql;
	}
}
foreach ($final as $idx => $sql) {
	$sqls[] = $sql;
}

if ($param['action'] != 'dummy') {
	foreach ($sqls as $sql) {
		if (strpos($sql,'#') !== 0) {
			$db->Execute($sql);
			if ($param['action'] = 'verbose') {
				$rows = $db->Affected_Rows();
				print "[$rows] ";
			}
		}

		if ($param['action'] = 'verbose')
			print preg_replace('/\s+/s',' ',$sql)."\n";
	}
} else {
	foreach ($sqls as $sql) {
		print preg_replace('/\s+/s',' ',$sql)."\n";
	}
}

if ($param['action'] != 'execute')
	print "/END\n\n";

