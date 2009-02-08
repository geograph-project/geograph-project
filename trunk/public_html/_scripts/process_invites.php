<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/eventprocessor.class.php');

set_time_limit(5000); 


//need perms if not requested locally
if (!isLocalIPAddress())
{
	init_session();
        $smarty = new GeographPage;
        $USER->mustHavePerm("admin");
}

$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  
$smarty = new GeographPage;

$all = $db->getAll("SELECT ha.*,u.realname,expiry > now() as indate
FROM hectad_assignment ha 
INNER JOIN user u USING (user_id) 
WHERE status IN ('new','offered') 
ORDER BY status+0 desc, sort_order,created"); //todo more sorting!

$count = $done = array();

foreach ($all as $id => $row) {
	if (empty($done[$row['hectad']]) && $row['status'] == 'new') {
		print "sending {$row['hectad']} to {$row['user_id']}/{$row['realname']}";
		
		if (!empty($_GET['run'])) {
			$sql = "UPDATE hectad_assignment SET status = 'offered',expiry = DATE_ADD(NOW(),INTERVAL 7 DAY) WHERE hectad_assignment_id = {$row['hectad_assignment_id']}";
			$db->Execute($sql);
		}
		if (!empty($_GET['email']) && (empty($count[$row['user_id']]) || $count[$row['user_id']] < intval($_GET['email']))) {
			$smarty->assign($row);

			$subject = "[Geograph] Your interest in {$row['hectad']}";
			$body=$smarty->fetch('email_hectad_invite.tpl');

			$headers = array();
			$headers[] = "From: Geograph <noreply@geograph.org.uk>";


			print " email sent";
			//@mail("$to_name <$to_email>", $subject, $body, implode("\n",$headers));
			
			@$count[$row['user_id']]++;
		}
		print "<BR>";
		
	} elseif ($row['status'] == 'new') {
		print "{$row['hectad']} to {$row['user_id']}/{$row['realname']} has already gone";
		$d = $done[$row['hectad']];
		if ($d['realname']) {
			print " to {$d['realname']}";
		}
		print "<BR>";
	}
	if ($row['status'] == 'new' || ($row['status'] == 'offered' && $row['indate']) ) {
		$done[$row['hectad']] = $row;
	}
}

?>
