<?php
/**
 * $Project: GeoGraph $
 * $Id: at-home.php 6629 2010-04-13 21:07:14Z geograph $
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
init_session();

$smarty = new GeographPage;
$USER->user_id == 135767 || $USER->user_id == 9181 || $USER->mustHavePerm("director");

customNoCacheHeader();

#########################################

	$db = GeographDatabaseConnection(true);
	if (!$db) die('Database connection failed');

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$year = date('Y')+1; // we currently working on next years calendar

$sql = "SELECT calendar_id,user_id,alpha,realname,created,quantity,delivery_name,email,delivery_line1,delivery_line2,delivery_line3,delivery_line4,delivery_postcode,status,paid,ordered
FROM calendar c LEFT JOIN user USING (user_id) WHERE ordered > '2000-01-01' and status != 'deleted' and year = '$year' ORDER BY ordered,calendar_id";


				$recordSet = $db->Execute($sql);

header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"orders-".date('Y-m-d').".csv\"");

				$f = fopen("php://output", "w");
				if (!$f) {
					die("ERROR:unable to open output stream");
				}

				fputcsv($f,array_keys($recordSet->fields));

				while (!$recordSet->EOF)
				{
					//$recordSet->fields['topic_title'] = latin1_to_utf8($recordSet->fields['topic_title']);
					//$recordSet->fields['title'] = latin1_to_utf8($recordSet->fields['title']);
					$recordSet->fields['realname'] = utf8_encode($recordSet->fields['realname']);

					fputcsv($f,$recordSet->fields);
					$recordSet->MoveNext();
				}

			$recordSet->Close();
