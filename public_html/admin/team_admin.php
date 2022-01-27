<?php
/**
 * $Project: GeoGraph $
 * $Id: apikeys.php 939 2005-06-29 22:22:57Z barryhunter $
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

$USER->hasPerm("director") || $USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');

$smarty->display('_std_begin.tpl');

ini_set('display_errors',1);

?>

<form method=get>
	To add a new user to this list, enter their user_id: 
		<input type=text name=user_id value="<? echo htmlentities(@$_GET['user_id']); ?>">
		<input type=submit>
</form>


<h3>Team Admin</h3>

<form method=post>
<table>

<?

$where = '';
if (!empty($_GET['user_id']) && preg_match('/^\d+(,\d+)*$/',$_GET['user_id'])) {
	$where = " OR user_id IN ({$_GET['user_id']})";
	print "To add permissions for hte new user, tick some columns in their row below";
}

$data = $db->getAll("

select                 user.user_id,user.realname,user.nickname,user.rights,role,email,gravatar,deceased_date
 from user   
 where ( length(rights)>5 AND length(replace(replace(replace(replace(rights,'dormant',''),'basic',''),'member',''),'traineemod','')) > 3 OR role != '' $where)
AND rights NOT LIKE '%suspicious%' 
          group by user.user_id
");

if (!empty($_POST['right'])) {
	$count = 0;

	foreach ($data as $row) {
		$user_id = $row['user_id'];
		$rights = implode(',',array_keys($_POST['right'][$user_id]));
		if (!empty($rights) && $rights != $row['rights']) {
			print "<hr>{$row['realname']}<br>";
			print "was: {$row['rights']}<br>";
			print "now: {$rights}<br>";

			$db->Execute("UPDATE user SET rights = ".$db->Quote($rights)." WHERE user_id = $user_id");

			$count++;
		}
	}

	print "<h3>Saved $count changes</h3>";

	print "<a href=?>Go again</a>";

	exit;
}

$cols = $db->getAssoc("DESCRIBE user");
$rights = explode(',',str_replace("'",'',str_replace('set(','',trim($cols['rights']['Type'],')'))));



	print "<tr>";
	print "<td>".htmlentities('user_id');
	print "<td>".htmlentities('realname');
	print "<td>".htmlentities('nickname');
	print "<td>".htmlentities('role');
	foreach ($rights as $right) {
		print "<td style=\"text-orientation: sideways; writing-mode: vertical-rl;\">$right";
	}
	print "<td>".htmlentities('deceased');


foreach ($data  as $row) {
	$r = explode(',',$row['rights']);

	if (in_array('alumni',$r) !== FALSE)
		print "<tr style=color:gray>";
	else
		print "<tr>";
	print "<td>".htmlentities($row['user_id']);
	print "<td>".htmlentities($row['realname']);
	print "<td>".htmlentities($row['nickname']);


// if(role != '',role,if(rights like '%admin%','Developer',if(rights like '%moderator%','Moderator','-none-')
	if (empty($row['role'])) {
		if (in_array('admin',$r) !== FALSE)
			$row['role'] = 'Developer';
		elseif (in_array('moderator',$r) !== FALSE)
			$row['role'] = 'Moderator';
	}

	print "<td>".htmlentities($row['role']);
	foreach ($rights as $right) {
		print "<td>";
		$name = "right[{$row['user_id']}][$right]";
		$checked = (in_array($right,$r) !== FALSE)?' checked':'';
		if ($right == 'member' || $right == 'traineemod') {
			if ($checked)
				print "<input type=hidden name=\"$name\" value=\"1\">";
			$checked .= ' disabled';
		}
		print "<input type=checkbox name=\"$name\"$checked title=\"$right for ".htmlentities($row['realname'])."\">";
	}


	print "<td>".htmlentities($row['deceased_date']);
	print "</tr>\n";
}
?>


</table>

<input type="submit" value="save changes">

</form>


<h3>Notes:</h3>
<ul>
	<li><tt>basic</tt>: simple ability to login</li>
	<li><tt>moderator</tt>: image moderator</li>
	<li><tt>admin</tt>: system administrator</li>
	<li><tt>ticketmod</tt>: suggestion moderator (note if adding this, should set role mannually)</li>
	<li><tt>traineemod</tt>: hidden status, just that they applied to be a moderator</li>
	<li><tt>suspicious</tt>: suspected spam profile, shouldnt need to set this manaually</li>
	<li><tt>dormant</tt>: the account is inactive, in particular prevents geograph sending them email</li>
	<li><tt>director</tt>: company director (as special rights on the site)</li>
	<li><tt>member</tt>: company member, automatically synced from the Company Database</li>
	<li><tt>forum</tt>: forum moderator, access to special tools for forum</li>
	<li><tt>tagsmod</tt>: tags moderator, has access to a few special tag editing tools</li>
</ul>
The following rights, dont have any special permission as such, just so can be listed on Team page
<ul>
	<li><tt>founder</tt>: Started the project back in 2005!</li>
	<li><tt>poty</tt>: Organizes the weekly photo competitio</li>
	<li><tt>complaints</tt>: Liaises with landowners and other parties in case of dispute</li>
	<li><tt>support</tt>: Liaises with landowners and other parties in case of dispute</li>
	<li><tt>developer</tt>:  Writes code and keeps the site running</li>
	<li><tt>docs</tt>: Create pages to help site users find their way around the site</li>
	<li><tt>coordinator</tt>: Central point of contact for communication between moderators</li>
	<li><tt>social</tt>: Deals with offical social accounts</li>
	<li><tt>alumni</tt>: Moves their name to the list at bottom of the team page</li>
</ul>

<?


$smarty->display('_std_end.tpl');
