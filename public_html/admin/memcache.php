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

if (!empty($_GET['Folder']) && preg_match('/^[\w]+$/',$_GET['Folder'])) {
	$CONF['template'] = $_GET['Folder'];
}

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
				if (is_numeric($a[$name][$column])) {
					print "<td align=\"right\">".number_format($a[$name][$column])."</td>";
				} else {
					print "<td>{$a[$name][$column]}</td>";
				}
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

} elseif (!empty($_GET['CacheID'])) {
	$key = $_GET['Folder'].$_GET['CacheID'];
	print "<h2>Smarty Cache</h2>";
	print "<h3>Memcache key: <tt>$key</tt></h3>";
	if ($_GET['action'] == 'view') {
		$v = $memcache->get($key);
		print "Length: ".strlen($v);
		print "<pre style='background-color:silver;border:4px solid black;padding:10px'>";
		print htmlentities($v);
		print "</pre>";
	} elseif ($_GET['action'] == 'delete') {
		$ok = $memcache->delete($key);
		print "<p>Delete return value: $ok</p>";
	}

} elseif (isset($_GET['clear_cache'])) {
	print "<h2>Clear Smarty Cache</h2>";
		
	if (!empty($_GET['Folder'])) {
		$smarty->clear_this_template_only=true;
	}
	$smarty->clear_cache($_GET['template'],$_GET['cache_id']);
	print "<h3>done</h3>";

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
                print "<p>Delete return value: $ok</p>";
                $ok = $memcache->name_delete($namespace,intval($_GET['post_id'])."www.geograph.org.uk")?1:0;
                print "<p>Delete www.geograph.org.uk return value: $ok</p>";
                $ok = $memcache->name_delete($namespace,intval($_GET['post_id'])."www.geograph.ie")?1:0;
                print "<p>Delete www.geograph.ie return value: $ok</p>";
        }

} elseif (!empty($_GET['key'])) {
	$namespace = 'fp';
	$key = $memcache->prefix.($_GET['key']);
	print "<h2>ANY Cache</h2>";
	print "<h3>Memcache key: <tt>$key</tt></h3>";
	if ($_GET['action'] == 'view') {
		$v = $memcache->get($_GET['key']);
		print "return value:";
		print "<pre>";
		print htmlentities(print_r($v,true));
		print "</pre>";
	} elseif ($_GET['action'] == 'delete') {
		$ok = $memcache->delete($_GET['key'])?1:0;
		print "<p>Delete return value: $ok</p>";
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
		print "<p>Delete return value: $ok</p>";
	}

} elseif (isset($_GET['flushMemcache'])) {
	print "<h2>Flush Memcache</h2>";
	print $memcache->flush();
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
<h3>Any</h3>
key <input type="text" name="key" value="" size="7"/> <input type="submit" value="Go"><br/>

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
<h3>Smarty Cache</h3>
Folder: <input type="text" name="Folder" value="<? echo $CONF['template']; ?>" size="7"/> <input type="submit" value="Go"><br/>
CacheID: <input type="text" name="CacheID" value="" size="50"/> (db-key)<br/>

<input type="radio" name="action" value="view" checked> View Contents<br/>
<input type="radio" name="action" value="delete"> Delete Cache (does not delete from meta db)<br/>
</form>

<hr/>
<form method="get">
<h4>Clear Cache - using smarty->clear_cache</h4>
Folder: <input type="text" name="Folder" value="" size="7"/> (leave blank to clear all folders)<br/>
$template: <input type="text" name="template" value="homepage.tpl" size="50"/>  <input type="submit" name="clear_cache" value="Go"><br/>
and/or $cache_id: <input type="text" name="cache_id" value="5" size="50"/>(clears all caches with this prefix if contains |) <br/>

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
