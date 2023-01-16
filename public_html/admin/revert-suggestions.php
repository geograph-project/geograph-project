<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2302 2006-07-05 12:15:49Z barryhunter $
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

require_once('geograph/global.inc.php');
init_session();

//ini_set('display_errors',1);

$smarty = new GeographPage;

$USER->mustHavePerm("director");

$db = NewADOConnection($GLOBALS['DSN']);

$where= array();
if (!empty($_GET['user_id']))
	$where[] = "t.user_id = ".intval($_GET['user_id']);
else
	$where[] = "t.user_id != ".$USER->user_id; //we need to exclude the ones by the user that does the reverting!

if (!empty($_GET['gridimage_id']))
	$where[] = "t.gridimage_id = ".intval($_GET['gridimage_id']);

if (!empty($_GET['when']))
	$where[] = "t.suggested like ".$db->Quote($_GET['when'].'%');

if (empty($where))
	die("unknown where");

$where[] = "t.status = 'closed'";

$where[] = "t.type != 'revert'"; //to exclude the new ticket created during revertion
$where[] = "t.reverted_by = 0"; //to exclude the already processed tickts!

$where[] = "i.status in ('immediate','approved')";

//todo, use ticket_merge? so that can include archived tickets!
$sql = "select gridimage_ticket_id,gridimage_id,t.user_id,i.field,i.status,i.oldvalue,i.newvalue,i.updated,`public`
 from gridimage_ticket t inner join gridimage_ticket_item i using (gridimage_ticket_id)
 where ".implode(" AND ",$where)."
 order by i.updated desc";

$limit = 200;
$sql .= " limit 200"; //need to be careful, as could split a ticket in half!

#######################################################################

	print "<b style=color:green>Green - the old value that was overwritten - and will be restored</b>";
	print "<hr><span style=color:blue>Blue - the new value (that is currently on image)</span>";
	print "<hr><span style=color:Red>Red - the new value the ticket introduced, does NOT match what is currently on image.</span>";
	print "<br><br>";


$recordSet = $db->Execute($sql);
//$row = $recordSet->fields;

$last = null;
$updated = array();
$changes = array();

print "<form method=post>";
print "Suggestions created will be owned by:<br>";
print "<input type=radio name=suggestor value=".$USER->user_id.">Your acccount (".htmlentities($USER->realname).")<br>";
print "<input type=radio name=suggestor value=123491 checked>Trustees Account (123491)";

print "<table border=1 cellpadding=4 cellspacing=0 bordercolor=#eeeeee>";
print "<p>".$recordSet->recordCount()." records shown</p>";
while (!$recordSet->EOF) {
        $row = $recordSet->fields;

	###################

	if ($row['gridimage_ticket_id'] != $last) {
		if ($last) {
			print "<tr><td colspan=7 bgcolor=silver style=color:black>";
			applyChanges($changes);
			print "<tr><td colspan=7 bgcolor=black>";
		}

		$changes = array();
	}
	$changes[] = $row;

	###################

	print "<tr>";
	print "<td><a href=\"/editimage.php?id=".htmlentities($row['gridimage_id'])."\">".htmlentities($row['gridimage_id'])."</a>";
	print "<td style=color:silver>".htmlentities($row['status']);
	print "<td>".htmlentities($row['field']);

	###################

	if (!empty($updated[$row['gridimage_id']][$row['field']])) {
		$current = $updated[$row['gridimage_id']][$row['field']];
	} else {
		$current = $db->getOne("SELECT {$row['field']} FROM gridimage WHERE gridimage_id = {$row['gridimage_id']}");
	}

	if ($current != $row['newvalue']) {
		$color = 'red';
        } else {
		$color = 'blue';
        }

	print "<td><b style=color:green>".htmlentities($row['oldvalue'])."</b>";
	print "<hr><span style=color:$color>".htmlentities($row['newvalue'])."</span>";

	if ($current != $row['newvalue']) {
		print "<span style=color:red>WARNING - New value does not match current ($current)</span>";
//	} else {
//		print "<td>Match";
	}

	###################

	$updated[$row['gridimage_id']][$row['field']] = $row['oldvalue']; //we replaying backwards. so updating TO the OLDvalue!

	$last = $row['gridimage_ticket_id'];
        $recordSet->MoveNext();
}

if ($last && $recordSet->recordCount() < $limit) {
	print "<tr><td colspan=7 bgcolor=black style=color:white>Last...";
	applyChanges($changes);
} else {
	print "<tr><td colspan=7 bgcolor=black style=color:white>Cant revert last one in list";
}

print "</table><input type=submit></form>";

print "<a href=?".htmlentities(http_build_query($_GET)).">Reload Page</a>";

#######################################################################

function applyChanges($changes) {
	global $db, $USER;

	$first = reset($changes); //doesnt matter which row use, should be all the same ticket!

	//lets use the class function to do it, as it will deal with GR fields!
	$ticket=new GridImageTroubleTicket();
	$ticket->_setDb($db);
	$ticket->setSuggester($_POST['suggestor']);
	//$ticket->setModerator($USER->user_id);
	$ticket->setPublic($first['public']);
	$ticket->setImage($first['gridimage_id']);
	$ticket->setType('revert'); //ensures doesnt send email notification to image owner
	$ticket->setNotify(''); //dont notify the suggestor (althoguh immidate closed tickets dont use this!)
	$ticket->setNotes("Auto-generated ticket, Reverting Ticket #{$first['gridimage_ticket_id']}");
	foreach ($changes as $item) {
		//this updates the internal 'image' object on the ticket, but does NOT call 'commitChanges'!
		$ticket->updateField($item['field'], $item['newvalue'], $item['oldvalue'], false); //rememeber old and new are backwards, as 'rerverting'. the false is because want it to be 'immdate'
	}

	if (!empty($_GET['run']) || !empty($_POST['tickets'][$first['gridimage_ticket_id']])) {

		$ticket->commit('closed'); //commits 'immdate' changes, and creates and closes the ticket!
		if (!empty($ticket->gridimage_ticket_id)) {
			$db->Execute("UPDATE gridimage_ticket SET reverted_by = {$ticket->gridimage_ticket_id}, updated=updated WHERE gridimage_ticket_id = {$first['gridimage_ticket_id']}");
			print "Created Ticket #<a href=\"/editimage.php?id=".htmlentities($first['gridimage_id'])."\">".$ticket->gridimage_ticket_id."</a>";
		}
	} else {
		$ticket->db = null;
		$ticket->gridimage->db = null;
		$ticket->gridimage->grid_square->db = null;
		if (!empty($_GET['print']))
			print_r($ticket->changes);
		print "Revert this ticket? <input type=checkbox name=\"tickets[{$first['gridimage_ticket_id']}]\">";
	}
}

