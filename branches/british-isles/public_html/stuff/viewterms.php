<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
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
init_session();

$USER->mustHavePerm("basic");



$db = NewADOConnection($GLOBALS['DSN']);

if (!empty($_GET['id'])) {
        $gid = intval($_GET['id']);

	if (empty($gid)) {
		die("please specify a image");
	}

	$desc = $db->getOne("select comment from gridimage_search where gridimage_id= $gid");

	if (empty($desc)) {
		die("This image does not have a description. (or is not a valid image)");
	}

	$terms = $db->getCol("select result from at_home_result where gridimage_id= $gid");

	if (empty($terms)) {
		die("No terms for this image- either not processed yet, or no terms could be extracted");
	}
	$desc = htmlentities2($desc);

	print "<h2>Highlighted Terms</h2>";

	print "<div style='width:700px'>";

	foreach ($terms as $term) {
		$t = preg_replace("/[^\w]+/",'[^\w]+',$term); //convert non word chars to regular expression!

		$desc = preg_replace("/\b($t)\b/i",'<>$1</>',$desc);
	}
	$desc = str_replace('<>','<b>',$desc);
	$desc = str_replace('</>','</b>',$desc);


	print "<p><tt>$desc</tt></p>";

	print "</div>";

	print "<pre>";
	print_r($terms);
	print "</pre>";

} elseif (!empty($_GET['gridref'])) {
	$square=new GridSquare;

	$grid_given=true;
	$grid_ok=$square->setByFullGridRef($_GET['gridref'],false,true);

	if (!$grid_ok) {
		die($square->errormsg);
	}
	
	$having = (isset($_GET['all']))?'':"HAVING c > 1";
		
	$sql = "SELECT result,count(*) AS c 
	FROM gridimage_search 
	INNER JOIN at_home_result USING (gridimage_id) 
	WHERE grid_reference = '{$square->grid_reference}'
	GROUP BY result
	$having";

	$data = $db->getAssoc($sql);
	
	if (empty($data)) {
		die("no data to plot");
	}
	
	print "<style>body {font-family:Georgia} a:hover { background-color:yellow }</style>";
	
	if (isset($_GET['all'])) {
		print "<h2>All Terms from <a href=\"/gridref/{$square->grid_reference}\">{$square->grid_reference}</h2>";
	} else {
		print "<h2>Common Terms from <a href=\"/gridref/{$square->grid_reference}\">{$square->grid_reference}</h2>";
	}
	
	print "<hr/>";
	foreach ($data as $term => $count) {
		$size = 0.8+log($count);
		$term1 = urlencode($term);
		$term2 = htmlentities($term);
		print "<nobr><a style=\"font-size:{$size}em\" href=\"/search.php?searchtext=$term1&amp;gridref={$square->grid_reference}&amp;do=1\">$term2</a>";

		print "</nobr> ";
	}


	print "<hr/>";
	print "<p>Click a term above to find images in {$square->grid_reference}</p>";
	if (isset($_GET['all'])) {
		print "<a href=\"?gridref={$square->grid_reference}\">Show <b>Common Terms</b> from {$square->grid_reference}</a>";
	} else {
		print "<a href=\"?gridref={$square->grid_reference}&amp;all=1\">Show <b>All Terms</b> from {$square->grid_reference}</a>";
	}
} else {
	die("nothing to do...");
}

print "<hr/>Powered by <i>Yahoo Term Extractor</i> and <i>Geograph-At-Home clients</i>";
	
?>
