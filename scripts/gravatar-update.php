<?php
/**
 * $Project: GeoGraph $
 * $Id: notification-mailer.php 7992 2013-11-15 14:24:20Z geograph $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2013 Barry Hunter (geo@barryhunter.co.uk)
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


chdir(__DIR__);
require "./_scripts.inc.php";



$db = GeographDatabaseConnection(false);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$recordSet = &$db->Execute("SELECT user_id,MD5(LOWER(email)) m5d FROM user INNER JOIN user_stat USING (user_id) WHERE gravatar = 'unknown' LIMIT 1000");
while (!$recordSet->EOF) {

	#"http://www.gravatar.com/avatar/53640618dabda27e268972addfe9bb83?d=404"
	$found = @file_get_contents("http://www.gravatar.com/avatar/{$recordSet->fields['m5d']}?d=404");

	$status = $found?'found':'none';

	$sql = "UPDATE user SET `gravatar` = '$status', updated = updated WHERE user_id = {$recordSet->fields['user_id']}";

	if ($param['action'] == 'execute') {
		$db->Execute($sql);
		//print "{$recordSet->fields['user_id']} ";
		sleep(1);
	} else
		print "$sql\n";


        $recordSet->MoveNext();
}
$recordSet->Close();

if ($param['action'] != 'execute')
	print "\n\n";
