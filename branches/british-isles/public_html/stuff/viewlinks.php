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

	list($desc,$gr) = $db->getRow("select comment,grid_reference from gridimage_search where gridimage_id= $gid");
	$desc1 = $desc;
	
	if (empty($desc)) {
		die("This image does not have a description. (or is not a valid image)");
	}

	$desc = htmlentities($desc1);
	
	$link = "/search.php?gridref=$gr&do=1&orderby=relevance&searchtext=";
	
	
	$desc = preg_replace('/(?<!^\.\s|^)\b([A-Z][a-z]+)\b(\s+[A-Z][a-z]+)?/e','"<a href=\"'.$link.'".urlencode("$1 $2")."\">$1 $2</a>"',$desc);


?>
<style type="text/css">
a {
	text-decoration:none;
}
a:hover {
	text-decoration:underline;
}
</style>
Original:
	<div><?php echo htmlentities($desc1); ?></div>
<hr>
New:
	<div><?php echo $desc; ?></div>
<hr>
<a href="/photo/<?php echo $gid; ?>">Back to image</a>


	
	
	
<?php	
	
} else {
	die("nothing to do...");
}
