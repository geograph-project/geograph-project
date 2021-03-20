<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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
$USER->mustHavePerm("basic");

$smarty->assign('page_title','Sample Education Images - for Teachers');
$smarty->display('_std_begin.tpl',md5($_SERVER['PHP_SELF']));

?>

<h2>Sample Education Images - for Teachers</h2>

<div class=interestBox>
	Note: this section is just a basic experiment, to explore the possiblity providing resources. The features here are far from polished, and may even be non-functional!
</div>


<div style="width:100%; height:190px; white-space: nowrap; overflow:hidden">
<?

$sph = GeographSphinxConnection('sphinxql',true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$where = '';
if (!empty($_GET['g']))
        $where .= " AND MATCH(".$sph->Quote('@group '.$_GET['g']);
if (!empty($_GET['l']))
        $where .= " AND `label` = ".$sph->Quote($_GET['l']);


$cols = "label";
$sql = "SELECT id,user_id,realname,title,grid_reference,$cols, if(larger='',0,1) as has_larger, if(types = '_SEP_ Geograph _SEP_',1,0) as is_geo
        FROM curated1
        WHERE label NOT IN('weather','season')
	ORDER BY has_larger DESC, is_geo DESC, hash DESC LIMIT 10";

$images = $sph->getAll($sql);
if (empty($images))
        die("nothing to display right now, please try later");

$thumbw = 213;
$thumbh = 160;
foreach ($images as $row) {
	$row['gridimage_id'] = $row['id'];
	$image = new GridImage;
        $image->fastInit($row);
	?>
		<div style="display:inline-block">
		 <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a><br>
		 <b><? echo $row['label']; ?></b>
		</div>
	<?
}
?>
</div>
<br><br>

<h2>Key Stage 1 - National Curriculum - England</h2>
<table cellpadding=10 style="font-size:1.3em;text-align:center">
	<tr>
		<th>Key Physical Features</th>
		<th>Key Human Features</th>
	</tr>
	<tr>
		<td><a href="play.php?g=ks1+%3E+key+physical+features">Slide Show</a>
		<td><a href="play.php?g=ks1+%3E+key+human+features">Slide Show</a>
	</tr>
	<tr>
		<td><a href="example.php?g=ks1+%3E+key+physical+features">Multi-Choice Quiz</a>
		<td><a href="example.php?g=ks1+%3E+key+human+features">Multi-Choice Quiz</a>
	</tr>
        <tr>
                <td><a href="sample.php#group=ks1+%3E+key+physical+features">View/Download</a>
                <td><a href="sample.php#group=ks1+%3E+key+human+features">View/Download</a>
        </tr>
</table>

<br><br>

... see also EXAMPLE <a href="worksheet.php">printable worksheet</a> using the above.

<br><br>
<hr>
<br><br>

<?

if (!empty($_GET['why'])) {

print "Thank you for responce!";

} else {?>
<div class=interestBox>
	<form>
		<h4>Teacher? Please let us know why here...</h4>
		<?
		$why = array(
			'Looking to download images for lessons',
			'Doing research about a specific location',
			'Looking for ready made activies to download',
			'Looking for interactive activies for pupils/students'
		);

		foreach ($why as $reason)
			print "<label><input type=radio name=why value=\"$reason\"> $reason</label><br>";
		?>
		 <input type=radio name=why value=other> other:<input type=text name=other size=50><br>
		<input type=submit>
	</form>
</div>
<? } ?>

<br><br>
<hr>
<br><br>

<p>Want to help curate images for the above? <a href="collecter.php">click here</a>

<?

$smarty->display('_std_end.tpl');



