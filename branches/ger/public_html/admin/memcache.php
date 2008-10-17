<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
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

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

$smarty->display('_std_begin.tpl');
flush();
	
if (isset($_GET['getExtendedStats'])) {
	$a = $memcache->getExtendedStats();
	$a = array_reverse($a);
	print "<h2>Overview Memcache Statistics</h2>";
	print "<table border=1 cellspacing=0>";
	if (isset($_GET['r'])) {
		print "<tr>";
		print "<th>server</th>";
		foreach ($a as $name => $row) {
			print "<th>{$name}</th>";
		}
		print "</tr>";
		$keys = array_keys($a);
		$keys = array_keys($a[$keys[0]]);
		foreach ($keys as $id => $column) {
			print "<tr>";
			print "<th>$column</th>";
			foreach ($a as $name => $row) {
				print "<td align=\"right\">{$a[$name][$column]}</td>";
			}
			print "</tr>";
		}
	} else {
		$first = true;
		foreach ($a as $name => $row) {
			if ($first) {
				print "<tr>";
				print "<th>.</th>";
				foreach ($row as $column => $value) {
					print "<th style=\"direction: rtl; writing-mode: tb-rl;\">$column</th>";
				}
				print "</tr>";
				$first = false;
			}
			print "<tr>";
			print "<th>$name</th>";
			foreach ($row as $column => $value) {
				print "<td align=\"right\">$value</td>";
			}
			print "</tr>";
		}
		
	}
	print "</table>";
} elseif (!empty($_GET['post_id'])) {
	$namespace = 'fp';
	$key = $memcache->prefix.$namespace.':'.intval($_GET['post_id']);
	print "<h2>Discussion Forum Post Cache</h2>";
	print "<h3>Memcache key: <tt>$key</tt></h3>";
	if ($_GET['action'] == 'view') {
		$v = $memcache->name_get($namespace,intval($_GET['post_id']));
		print "return value:";
		print "<pre>";
		print htmlentities(print_r($v,true));
		print "</pre>";
	} elseif ($_GET['action'] == 'delete') {
		$ok = $memcache->name_delete($namespace,intval($_GET['post_id']))?1:0;
		print "Delete return value: $ok";
	}
} elseif (!empty($_GET['image_id'])) {
	$namespace = 'is';
	$size = "120x120";if (!empty($_GET['size'])) $size = $_GET['size'];
	$key = $memcache->prefix.$namespace.':'.intval($_GET['image_id']).':'.$size;
	print "<h2>Thumbnail Stat Cache</h2>";
	print "<h3>Memcache key: <tt>$key</tt></h3>";
	if ($_GET['action'] == 'view') {
		$v = $memcache->name_get($namespace,intval($_GET['image_id']).':'.$size);
		print "<pre>";
		print htmlentities(print_r($v,true));
		print "</pre>";
	} elseif ($_GET['action'] == 'delete') {
		$ok = $memcache->name_delete($namespace,intval($_GET['image_id']).':'.$size);
		print "Delete return value: $ok";
	}

} elseif (isset($_GET['flushMemcache'])) {
	print "<h2>Flush Memcache</h2>";
	print $memcache->flush();
	print "<h3>done</h3>";
} elseif (isset($_GET['clearSmarty'])) {
	print "<h2>Clear Whole Smarty Cache</h2>";
	$smarty->clear_cache();
	print "<h3>done</h3>";
} elseif (isset($_GET['get'])) {
	$v = $memcache->get($_GET['get']);
	print "<pre>";
	var_dump($v);
	print "</pre><pre>";
	var_dump(unserialize($v));
	exit;
} elseif (isset($_GET['set'])) {
	$v = $memcache->set($_GET['set'],$_GET['v']);
	print "<pre>";
	var_dump($v);
	print "</pre>";
} else {
?>
<h2>Memcache Toolkit</h2>
<hr/>
<form method="get">
<h3>Overview Memcache Statistics</h3>
<input type=submit name="getExtendedStats" value="Go"> (<input type="checkbox" name="r" checked> Rotated)
</form>

<hr/>
<form method="get">
<h3>Discussion Forum Post Cache</h3>
post_id: <input type="text" name="post_id" value="" size="7"/> <input type="submit" value="Go"><br/>

<input type="radio" name="action" value="view" checked> View Contents<br/>
<input type="radio" name="action" value="delete"> Delete Cache<br/>
</form>

<hr/>
<form method="get">
<h3>Image Stat Cache</h3>
post_id: <input type="text" name="image_id" value="" size="7"/> <input type="submit" value="Go"><br/>

<input type="radio" name="size" value="120x120" checked> 120x120 <br/>
<input type="radio" name="size" value="213x160"> 213x160 <br/>
<input type="radio" name="size" value="F"> Full <br/>
<br/>

<input type="radio" name="action" value="view" checked> View Contents<br/>
<input type="radio" name="action" value="delete"> Delete Cache<br/>
</form>

<hr/>
<form method="get">
<h3>Clear Whole Smarty Cache</h3>
<input type=submit name="clearSmarty" value="Go"> (NOT recommended)
</form>

<hr/>
<form method="get">
<h3>Flush Memcache</h3>
<input type=submit name="flushMemcache" value="Go"> (NOT recommended)
</form>
<?

}
$smarty->display('_std_end.tpl');
exit;
?>