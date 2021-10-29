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


$check = $db->getAll("select tag_id,tag,group_concat(tag2),group_concat(status),count(distinct tag2) as dist from
 tag_report where status != 'rejected' and type != 'split' and type != 'canonical' group by tag having dist > 1");

if (!empty($check)) {
 $con = print_r($check,TRUE);
 debug_message('[Geograph] MULTIPLE TAGS',$con);
 print "\n\nFAILED MULTI CHECK\n\n";
 exit;
}

$sql = "select report_id,r.status,r.type,r.tag_id,r.tag,tag2,tag2_id,r.user_id,r.approver_id,gridimage_id
from tag_report r inner join gridimage_tag gt using (tag_id) inner join gridimage_search using (gridimage_id)
where r.status in ('approved','moved') and type != 'canonical' and gt.status > 0 and r.updated < date_sub(now(),interval 2 day) order by gridimage_id,tag_id";

$recordSet = $db->Execute($sql);

if ($recordSet->RecordCount() === 0) {
        //we done. Dont print anything to keep cron output empty :)
        exit;
}


$tickets = $items = $sqls = array();
while (!$recordSet->EOF)
{
        $r = $recordSet->fields;

	$bits = explode(':',$r['tag2'],2);
        if (count($bits) > 1) {
                list($prefix2,$tag2) = $bits;
	} else {
		$prefix2 = '';$tag2 = $bits[0];
	}
	$values = array("prefix = ".$db->Quote(strtolower(trim($prefix2))),"tag = ".$db->Quote(trim($tag2)));

	//if (empty($r['tag2_id'])) {
	//it might of been created since the report was created!
	$r['tag2_id'] = $db->getOne("SELECT tag_id FROM tag WHERE ".implode(' AND ',$values));
	//}

	if (strtolower($r['tag']) == strtolower($r['tag2']) && $r['tag_id'] == $r['tag2_id']) {
		//tag renamed, no images needs moving!

		if (empty($sqls['r:'.$r['report_id']])) { //we only need to do it once!

			if (!$db->getOne("SELECT tag_id FROM tag WHERE ".implode(' AND ',$values)." AND tag_id != {$r['tag_id']}")) {
				$sqls[] = "UPDATE tag SET ".implode(', ',$values)." WHERE tag_id = {$r['tag_id']} # old={$r['tag']}";

				$sqls['r:'.$r['report_id']] = "UPDATE tag_report SET status = 'renamed' WHERE report_id = {$r['report_id']} # {$r['tag']} > {$r['tag2']}";

				//need to make sure scripts/update_tags.php notices the tag changed!
                                $sqls[] = "UPDATE gridimage_tag SET updated=NOW() WHERE tag_id = {$r['tag_id']} AND status = 2";
			}
		}
	} else {
		$user_id = empty($r['approver_id'])?$r['user_id']:$r['approver_id'];

		if (empty($r['tag2_id'])) {
			//create tag!
			$sqls[] = "#INSERT INTO tag SET created=NOW(),".implode(', ',$values).",user_id={$r['user_id']}";
			if ($param['action'] != 'dummy') {
				//we actully need to run it now, so all actions can use the newly created tag. (cos we build all the sqls first)
				$db->Execute("INSERT INTO tag SET created=NOW(),".implode(', ',$values).",user_id={$r['user_id']}");
				$r['tag2_id'] = $db->Insert_ID();
			}
		}

		if ($r['status'] == 'moved') {
			$sqls[] = "#Applying {$r['tag']} > {$r['tag2']} AGAIN";
		}

		if ($param['action'] != 'dummy' && (empty($r['tag_id']) || empty($r['tag2_id'])) ) {
			print_r($r);
			$con = print_r($r,TRUE);
			debug_message('[Geograph] MISSING TAG IDS',$con);

			die("MISSING TAG IDS!\n");
		}

	        $sqls['t:'.$r['tag_id']] = "UPDATE tag SET status =0".((!empty($r['tag2_id']) && $r['type']!='split')?", canonical = {$r['tag2_id']}":'')." WHERE tag_id = {$r['tag_id']} # {$r['tag']} > {$r['tag2']}";
		$sqls['r:'.$r['report_id']] = "UPDATE tag_report SET status = 'moved',tag2_id = {$r['tag2_id']} WHERE report_id = {$r['report_id']} # {$r['tag']} > {$r['tag2']}";

		if ($r['type']=='split') {
			//for a split, we duplicate the tag, we cant 'change' the existing tag, as need the original for the other splits
			$sqls[] = "INSERT IGNORE INTO gridimage_tag SELECT gridimage_id,{$r['tag2_id']} as tag_id,user_id,created,status,NOW() as updated FROM gridimage_tag WHERE tag_id = {$r['tag_id']} AND gridimage_id = {$r['gridimage_id']} # {$r['tag']} > {$r['tag2']}";
		} else {
			$sqls[] = "UPDATE IGNORE gridimage_tag SET tag_id = {$r['tag2_id']} WHERE tag_id = {$r['tag_id']} AND gridimage_id = {$r['gridimage_id']} # {$r['tag']} > {$r['tag2']}";
			//this is trickly. Any of the above that failed (due to duplicate key), means the 'new' tag is already on the image, and so the old one can be zapped.
		}
		//$final['d:'.$r['tag_id'].':'.$r['gridimage_id']] = "DELETE FROM gridimage_tag WHERE tag_id = {$r['tag_id']} AND gridimage_id = {$r['gridimage_id']} # old={$r['tag']}";
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
                                                                approver_id = {$r['approver_id']},
                                                                field = 'tag',
                                                                oldvalue = ".$db->Quote($r['tag']).",
                                                                newvalue = ".$db->Quote($r['tag2']).",
                                                                status = 'immediate'";
	}

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

