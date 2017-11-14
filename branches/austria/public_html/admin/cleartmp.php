<?php
/**
 * $Project: GeoGraph $
 * $Id$
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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);


	$smarty->display('_std_begin.tpl');

if ($_POST['delete'] && $_POST['time']) {
	$cuttoff = $_POST['time'];

	$root='/tmp';
	$folder = '/';
		$dh = opendir($root.$folder);
		
		while (($file = readdir($dh)) !== false) {
			if (is_dir($root.$folder.$file) || strpos($file,'.') === 0 || strpos($file,'.') === FALSE) {
				//skip!
			} elseif (filemtime($root.$folder.$file) < $cuttoff) {
				print "$file...";
				unlink($root.$folder.$file);
				print " deleted<br/>";
			}		
	}

} elseif ($_POST['list'] && $_POST['time']) {
	$cuttoff = $_POST['time'];
$count++;
	$root='/tmp';
	$folder = '/';
		$dh = opendir($root.$folder);
		
		while (($file = readdir($dh)) !== false) {
			if (is_dir($root.$folder.$file) || strpos($file,'.') === 0 || strpos($file,'.') === FALSE) {
				//skip!
			} elseif (filemtime($root.$folder.$file) < $cuttoff) {
				print "$file<br/>";
				$count++;
			}		
	}
	print "COUNTL:$count";
	print "<form method=post>";
	print "Time: <input name=time value=$cuttoff>";
	print "<input type=submit name=delete value=\"Delete Files\"/>";
	print "</form>";
} else {
	print "<form method=post>";
	print "Time: <input name=time>";
	print "<input type=submit name=list value=\"List Files\"/>";
	print "</form>";

}

	
	$smarty->display('_std_end.tpl');

	
?>
