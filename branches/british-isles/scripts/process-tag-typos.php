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


//these are the arguments we expect
$param=array(
	'dir'=>'/var/www/geograph_live',		//base installation dir

	'config'=>'www.geograph.org.uk', //effective config

        'action'=>'dummy',

	'help'=>0,		//show script help?
);

//very simple argument parser
for($i=1; $i<count($_SERVER['argv']); $i++)
{
	$arg=$_SERVER['argv'][$i];

	if (substr($arg,0,2)=='--')

	{
		$arg=substr($arg,2);
		$bits=explode('=', $arg,2);
		if (isset($param[$bits[0]]))
		{
			//if we have a value, use it, else just flag as true
			$param[$bits[0]]=isset($bits[1])?$bits[1]:true;
		}
		else die("unknown argument --$arg\nTry --help\n");
	}
	else die("unexpected argument $arg - try --help\n");

}


if ($param['help'])
{
	echo <<<ENDHELP
---------------------------------------------------------------------
process-tag-typos.php
---------------------------------------------------------------------
php process-tag-typos.php
    --dir=<dir>         : base directory (/home/geograph)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    --action=<event>    : use action=execute to run for real
    --help              : show this message
---------------------------------------------------------------------

ENDHELP;
exit;
}

//set up  suitable environment
ini_set('include_path', $param['dir'].'/libs/');
$_SERVER['DOCUMENT_ROOT'] = $param['dir'].'/public_html/';
$_SERVER['HTTP_HOST'] = $param['config'];

//--------------------------------------------

require_once('geograph/global.inc.php');

require_once('adodb/adodb-errorhandler.inc.php');

$db = GeographDatabaseConnection(false);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$check = $db->getAll("select tag_id,tag,group_concat(tag2),group_concat(status),count(distinct tag2) as dist from
 tag_report where status != 'rejected' and type != 'split' and type != 'canonical' group by tag having dist > 1");

if (!empty($check)) {
 $con = print_r($check,TRUE);
 mail('geograph@barryhunter.co.uk','[Geograph] MULTIPLE TAGS',$con);
 print "\n\nFAILED MULTI CHECK\n\n";
 exit;
}

$sql = "select report_id,r.status,r.type,r.tag_id,r.tag,tag2,tag2_id,r.user_id,r.approver_id,gridimage_id
from tag_report r inner join gridimage_tag gt using (tag_id) inner join gridimage_search using (gridimage_id)
where r.status in ('approved','moved') and type != 'canonical' and r.updated < date_sub(now(),interval 4 day) order by gridimage_id,tag_id";

$recordSet = &$db->Execute($sql);

if ($recordSet->RecordCount() === 0) {
        //we done. Dont print anything to keep cron output empty :)
        exit;
}


$tickets = $items = $sqls = array();
while (!$recordSet->EOF)
{
        $r =& $recordSet->fields;

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
			}
		}
	} else {
		$user_id = empty($r['approver_id'])?$r['user_id']:$r['approver_id'];

		if (empty($r['tag2_id'])) {
			//create tag!
			$sqls[] = "#INSERT INTO tag SET created=NOW(),".implode(', ',$values).",user_id={$r['user_id']}";
			if ($param['action'] == 'execute') {
				//we actully need to run it now, so all actions can use the newly created tag. (cos we build all the sqls first)
				$db->Execute("INSERT INTO tag SET created=NOW(),".implode(', ',$values).",user_id={$r['user_id']}");
				$r['tag2_id'] = mysql_insert_id();
			}
		}

		if ($r['status'] == 'moved') {
			$sqls[] = "#Applying {$r['tag']} > {$r['tag2']} AGAIN";
		}

		if ($param['action'] == 'execute' && (empty($r['tag_id']) || empty($r['tag2_id'])) ) {
			print_r($r);
			$con = print_r($r,TRUE);
			mail('geograph@barryhunter.co.uk','[Geograph] MISSING TAG IDS',$con);

			die("MISSING TAG IDS!\n");
		}

	        $sqls['t:'.$r['tag_id']] = "UPDATE tag SET status =0".((!empty($r['tag2_id']) && $r['type']!='split')?", canonical = {$r['tag2_id']}":'')." WHERE tag_id = {$r['tag_id']} # {$r['tag']} > {$r['tag2']}";
		$sqls['r:'.$r['report_id']] = "UPDATE tag_report SET status = 'moved' WHERE report_id = {$r['report_id']} # {$r['tag']} > {$r['tag2']}";

		if ($r['type']=='split') {
			$sqls[] = "INSERT IGNORE INTO gridimage_tag SELECT gridimage_id,{$r['tag2_id']} as tag_id,user_id,created,status FROM gridimage_tag WHERE tag_id = {$r['tag_id']} AND gridimage_id = {$r['gridimage_id']} # {$r['tag']} > {$r['tag2']}";
		} else {
			$sqls[] = "UPDATE IGNORE gridimage_tag SET tag_id = {$r['tag2_id']} WHERE tag_id = {$r['tag_id']} AND gridimage_id = {$r['gridimage_id']} # {$r['tag']} > {$r['tag2']}";
			//this is trickly. Any of the above that failed (due to duplicate key), means the 'new' tag is already on the image, and so the old one can be zapped.
		}
		$final['d:'.$r['tag_id'].':'.$r['gridimage_id']] = "DELETE FROM gridimage_tag WHERE tag_id = {$r['tag_id']} AND gridimage_id = {$r['gridimage_id']} # old={$r['tag']}";

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

                $items[$r['gridimage_id']][$r['tag_id']] = "INSERT INTO gridimage_ticket_item SET
                                                                gridimage_ticket_id = LAST_INSERT_ID(),
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
	die("no queries\n");
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

if ($param['action'] == 'execute') {
	foreach ($sqls as $sql) {
		if (strpos($sql,'#') !== 0) {
			$db->Execute($sql);
			$rows = $db->Affected_Rows();
			print "[$rows] ";
		}

		print preg_replace('/\s+/s',' ',$sql)."\n";
	}
} else {
	foreach ($sqls as $sql) {
		print preg_replace('/\s+/s',' ',$sql)."\n";
	}
}

print "/END\n\n";
