<?php
/**
 * $Project: GeoGraph $
 * $Id: category-listing.php 5502 2009-05-13 14:18:23Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2013 BArry Hunter (geo@barryhunter.co.uk)
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

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(true);

?>

<html>
<head>
<title>Geograph Categories </title>
<style type="text/css">
body {
	font-family: verdana;
}

ol {
	padding-left:120px;
	color:gray;
}

ol a {
	text-decoration:none;
	font-family: monospace;
	font-size:1.3em;
	color:#050505;
}

ol a b {
	color:black;
}

ol a:hover {
	background-color: skyblue;
}
ol li.break {
	margin-top:13px;
	border-top:1px solid silver;
	padding-top:13px;
}
ol li.padder {
	margin-top:3px;
}
li div {
	float:right;
	position:relative;
	font-size:9em;
	color:silver;
}
</style>
</head>
<body>
<h2>Geograph Categories as at <? echo ($date = date('Y-m-d')); ?></h2><p>Click a term to run a search directly on Geograph, press 'alt' and <i>letter</i> at any time to jump</p><p>Jump: | 
<?


$data = array(''=>1212423)+$db->getAssoc("SELECT imageclass,c FROM category_stat ORDER BY imageclass");

$alphas = array();
foreach ($data as $category => $count) {
	@$alphas[strtoupper(substr(trim($category),0,1))]++;
}
unset($alphas['']);
foreach ($alphas as $key => $value) {
	print " <a href=\"#$key\" title=\"$count categories\">$key</a> |";
}
?>
</p><hr><ol>

<?


$lastalpha = $last = '';

foreach ($data as $category => $count) {
	$category = trim($category);
	if (empty($category)) {
		$alpha = '-';
		$url = "-";
		$html = "<i>unclassified</i>";
	} else {
		$alpha = strtoupper(substr($category,0,1));
		$url = urlencode($category);
		$html = htmlentities($category);
	}
	
	$p = 0;
	$len = min(strlen($category),strlen($last));
	while($p < $len) {
		if ($category[$p] != $last[$p])
			break;
		$p++;
	}
	$common = substr($category,0,$p);
	$html = preg_replace("/^".preg_quote($common,'/')."/","$common<b>",$html)."</b>";
	
	print "<li value=\"$count\"";
        if ($alpha != $lastalpha) {
		print " class=\"break\">";
		if ($alphas[$alpha] > 4)
			print "<div>$alpha</div>";
		print "<a name=\"$alpha\" accesskey=\"".strtolower($alpha)."\" href=\"#$alpha\"></a";
	} elseif (strpos($html,' <') === FALSE) {
		print " class=\"padder\"";
	}
        print "><a href=\"http://www.geograph.org.uk/search.php?imageclass=$url\">$html</a></li>";
	$lastalpha = $alpha;
	$last = $category;
}

?>
</ol>

<div style="text-align:center; border:1px solid green; background-color:lightgreen; width:750px;padding:10px"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" border="0" style="vertical-align: middle"></a> &copy; Copyright <a href="/credits/<? echo $date; ?>">Geograph Project Limited and its Contributors</a> <br/> and
licensed for reuse under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>

<br/>
<div></div>

</body></html>






