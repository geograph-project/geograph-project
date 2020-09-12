<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

if (empty($CONF['forums'])) {
	$smarty = new GeographPage;
        $smarty->display('static_404.tpl');
        exit;
}

init_session();

$smarty = new GeographPage;

$USER->mustHavePerm('basic');

$smarty->display('_std_begin.tpl');

print "<h2>Recent Activity</h2>";

	$db=GeographDatabaseConnection(true);
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$types = array('feature' => 'Feature','extension' =>'Extension','bugfix'=>'Bug Fix');
$a = array();

$ideas = $db->getAll("SELECT * FROM project_idea WHERE approved = 1 ORDER BY created DESC LIMIT 10");
foreach ($ideas as $row) {
	$row['comment'] = "New {$types[$row['type']]} Request";
	$a[$row['created'].'|'.$row['project_idea_id']] = $row;
}

$items = $db->getAll("SELECT i.*,title FROM project_idea_item i INNER JOIN project_idea USING (project_idea_id,approved) WHERE i.approved = 1 ORDER BY i.created DESC LIMIT 15");
foreach ($items as $row) {
	$row['comment'] = "New {$row['item_type']} for idea";
	$a[$row['created'].'|'.$row['project_idea_id']] = $row;
}

$votes = $db->getAll("SELECT v.*,title,10/POW(vote,1.1) as score FROM project_idea_vote v INNER JOIN project_idea USING (project_idea_id,approved) WHERE v.approved = 1 AND vote > 0 ORDER BY v.created DESC LIMIT 15");
foreach ($votes as $row) {
	$row['score'] = sprintf('%.1f',$row['score']);
	$row['comment'] = "Added <b>{$row['score']}</b> to idea";
	$a[$row['created'].'|'.$row['project_idea_id']] = $row;
}

krsort($a);

print "<ul>";
foreach ($a as $date => $row) {
	list($date,) = explode('|',$date);
	print "<li>";
	if (!empty($row['content']) && !empty($row['views']))
		print "<big>";
	print "{$row['comment']}: ";
	print "<a href=\"idea.php?id={$row['project_idea_id']}\">".htmlentities($row['title'])."</a>";
	print "</big> <i>".date('F, jS',strtotime($date))."</i><br>";
	if (!empty($row['content']) && !empty($row['views'])) {
		$lines = explode("\n",wordwrap($row['content']));
		print "<small>".$lines[0].(count($lines)>1?' ...':'')."</small><br>";
	}
	print "<br></li>";
}
print "</ul>";

$smarty->display('_std_end.tpl');

