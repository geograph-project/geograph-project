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

############################################

$param = array();

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);


$h = popen($cmd = 'find '.$CONF['photo_upload_dir'].'/ -type f -not -name "*.exif" -not -name "*.original.jpeg" -printf "%p %T@\n"', 'r');

print "$cmd\n";

while ($h && !feof($h)) {
	$line = trim(fgets($h));
	if (empty($line))
		continue;
	list($filename, $time) = explode(' ',$line);

print "$line\n";
			$updates= array();
			$updates['basename'] = basename($filename);
			$updates['class'] = 'upload';
			$updates['file_created'] = date('Y-m-d H:i:s', intval($time));
			$updates['hash'] = md5_file($filename);


 		       $db->Execute($sql = 'INSERT INTO full_md5 SET `'.implode('` = ?,`',array_keys($updates)).'` = ?'.
		 	        	   ' ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ?',
        	              		  array_merge(array_values($updates),array_values($updates))) or die("$sql\n\n".mysql_error()."\n");

			print ".";
}
print "\n";

