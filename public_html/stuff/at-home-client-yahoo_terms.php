<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2009 Barry Hunter (geo@barryhunter.co.uk)
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

if (isset($_COOKIE['workerActive'])) {
	die("You can only run one browser window at once. If the other window has died, please wait 15 minutes and try again.");
} 

if (!$db) {
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');
}
if ($wid = $db->getOne("SELECT at_home_worker_id FROM at_home_worker WHERE `ip` = INET_ATON('".mysql_real_escape_string(getRemoteIP())."')")) { 

	$row = $db->getRow("SELECT * FROM at_home_job INNER JOIN at_home_result USING (at_home_job_id) WHERE at_home_worker_id = $wid AND at_home_result.created > DATE_SUB(NOW(),INTERVAL 10 MINUTE)");

	if (count($row)) {
		die("You can only run one browser window at once. If the other window has died, please wait at least 15 minutes and try again.");
	}
}


$smarty = new GeographPage;

$template='stuff_at_home_yahoo_terms.tpl';

$cacheid='';




$smarty->display($template,$cacheid);

	
?>
