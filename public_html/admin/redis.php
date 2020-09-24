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

if (empty($redis)) {
        $redis = new Redis();
        $redis->connect($CONF['redis_host'], $CONF['redis_port']);
}
if (!empty($_GET['db']))
	$redis->select(intval($_GET['db']));



$smarty = new GeographPage;

$smarty->display('_std_begin.tpl');
flush();


if (isset($_GET['verifySmarty'])) {
	$db = GeographDatabaseConnection(false);

	if (is_numeric($CONF['memcache']['sessions']['redis']))
                 $redis->select($CONF['memcache']['sessions']['redis']);

	$limit = 10;
	if (!empty($_GET['limit']))
		$limit = intval($_GET['limit']);

	$rows = $db->getAll("select * from smarty_cache_page order by TimeStamp desc limit $limit");

/*
8~basic4-https^%%19^19D^19D6A856%%homepage.tpl

8~basic1^%%19^19D^19D6A856%%homepage.tpl

| Folder | CacheID                                                   | TemplateFile            | GroupCache    
+--------+-----------------------------------------------------------+-------------------------+---------------
| basic  | 1^%%19^19D^19D6A856%%homepage.tpl                         | homepage.tpl            | 1             

*/

	foreach ($rows as $row) {
		//todo, could perhaps the $memcache directly??
		// note memcache_cache_handler.inc.php uses ->get directly NOT ->name_get!

		//because actully via libs/geograph/multiservermemcache.class.php  - it adds the memcache->prefix!
		$key = $memcache->prefix.$row['Folder'].$row['CacheID'];

		$str = $redis->get($key);

		print "$key ===> ".strlen($str)." bytes<br>";

	}
	exit;


} elseif (isset($_GET['getInfo'])) {

	print "<pre>";
	print_r($redis->info());
	print "</pre>";

} elseif (isset($_GET['getKeys'])) {

	print "<pre>";
	print_r($redis->keys($_GET['pattern']));
	print "</pre>";

} elseif (isset($_GET['getKey'])) {

	print "<pre>";
	print_r($redis->get($_GET['key']));
	print "</pre>";

} else {
?>
<h2>Redis Toolkit</h2>

<hr/>
<form method="get">
<h3>Overview Statistics</h3>
db:<input type=text name=db size=1>
<input type=submit name="getInfo" value="Go">
</form>

<hr/>
<form method="get">
<h3>List Keys</h3>
db:<input type=text name=db size=1 required>
pattern:<input type=text name=pattern size=10>
<input type=submit name="getKeys" value="Go"> (use with caution on big databases!)
</form>

<hr/>
<form method="get">
<h3>View Key</h3>
db:<input type=text name=db size=1 value="0" required>
key:<input type=text name=key size=30>
<input type=submit name="getKey" value="Go">
</form>

<?

}
$smarty->display('_std_end.tpl');

