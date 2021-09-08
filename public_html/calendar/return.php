<?php
/**
 * $Project: GeoGraph $
 * $Id: record_vote.php 6944 2010-12-03 21:44:38Z barry $
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

$ABORT_GLOBAL_EARLY = 1;
require_once('geograph/global.inc.php');


$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) failed('Database connection failed');


store_everything('paypal_return', $_POST);


?>
Thank you for your payment. Your transaction has completed, and we are sending you an email containing the receipt for your purchase.

<a href="/">Return to homepage</a> (this page is very basic at the moment, while still testing!)

<?







function store_everything($table,$values) {
	global $db;

        $values['REQUEST_TIME'] = $_SERVER['REQUEST_TIME']; //our own local timestamp!

        $keys = array_keys($values);

        if ($db->getOne("SHOW TABLES LIKE '$table'")) {
	        $exist = $db->getAssoc("DESCRIBE `$table`");
                $sql = "ALTER TABLE `$table`"; $sep = '';
                foreach ($keys as $key) {
                        $key = preg_replace('/[^\w]+/','',trim($key));
                        if (isset($exist[$key]))
                                continue;
                        $type = 'VARCHAR(255)';
                        $sql .= " $sep ADD `$key` $type DEFAULT NULL"; $sep = ",";
                }
        } else {
                $sql = "CREATE TABLE `$table` (";
                foreach ($keys as $key) {
                        $key = preg_replace('/[^\w]+/','',trim($key));
                        $type = 'VARCHAR(255)';
                        $sql .= "`$key` $type DEFAULT NULL,";
                }
                $sql .= " KEY(`REQUEST_TIME`) ) ENGINE=myisam"; $sep = ",";
        }
        if (!empty($sep)) //at least one column added!
	        $db->Execute($sql) or failed("$sql;\n<hr>\n".$db->ErrorMsg()."\n");



        $sql = "INSERT INTO `$table` SET "; $sep = '';
        foreach ($keys as $key) {
                $value = $values[$key];

                $key = preg_replace('/[^\w]+/','',trim($key));
                $value = is_numeric($value)?$value:$db->Quote($value);
                $sql .= " $sep `$key` = $value";  $sep = ",";
        }
        $db->Execute($sql) or failed("$sql;\n<hr>\n".$db->ErrorMsg()."\n");
}

