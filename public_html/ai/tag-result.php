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

 customExpiresHeader(3600,false,true);

	$smarty->display('_std_begin.tpl');

?>

<div style="max-width:60em">
<h2>AI-Generated Visual Tags (Alpha)</h2>
<p><strong>What is this?</strong>

These tags are created by a "Computer Vision" model that looks at the image and describes what it sees in plain English. This process 
is entirely automatic - the AI does not see the original title, tags, or location data submitted by the photographer.</p>

</div>

<hr>
<br>

<form method=get style="background-color:#eee;padding:10px">
	Query: <input name=q value="<? echo htmlentities($_GET['q']??''); ?>" placeholder="Enter a single tag (tip: use tags from examples below to try it out)" size=80>
	<button type=submit>Search</button>
</form>

<p>Note, this demo is only using a small sample of about 10,000 images, don't expect lots of results.

</div>

<hr>
<br>
<?

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$andwhere = $leftjoin = $extra = '';

$model = 'md3';
if (!empty($_GET['model']) && preg_match('/^\w+$/',$_GET['model']))
	$model = $_GET['model'];

$type = 'tags'; //short/normal/long
if (!empty($_GET['type']) && preg_match('/^\w+$/',$_GET['type']))
	$type = $_GET['type'];

//this is only intended as a QUICk test, will setup a proper keywords index!!
if (!empty($_GET['word']) && preg_match('/^\w+[ \w-]*$/',$_GET['word']))
	$andwhere = " and caption LIKE ".$db->Quote('%'.$_GET['word'].'%');

if (!empty($_GET['tag']) && preg_match('/^\w+[ \w-]*$/',$_GET['tag']))
	$andwhere = " and caption REGEXP ".$db->Quote('(^|[\\\\[",])'.$_GET['tag'].'([,"\\\\]|$)');



####################################################

	$imagelist=new ImageList;

	$thumbw=213; $thumbh=160;

if (empty($_GET['q'])) {
	print "<h3>Some examples with generated tags</h3>";
	//just some some examples!
	$sql = "SELECT {$imagelist->cols}, caption as labels, reference_index
		FROM gridimage_search gi
		INNER JOIN gridimage_caption c USING (gridimage_id)
		WHERE c.type='$type' AND c.model = '$model' $andwhere
		AND length(caption) < 2048
		LIMIT 24";
	//the AI getting stuck in a loop, can produice long lists!

	$imagelist->_getImagesBySql($sql);
//	$imagelist->outputThumbs($thumbw,$thumbh);
} else {
	print "<h3>Search Results...</h3>";
	//our caption index is a RT index!
	$rt = GeographSphinxConnection('manticorert',true);

	$imagelist->_setSph($rt); //need to force it to use RT backend

	if (strpos($_GET['q'],'@') === false)
		$_GET['q'] = "@labels ".$_GET['q'];

	$q = $rt->Quote($_GET['q']); //not suing sphinxwrapper for now (its not a normal keywords index!

	//note will automatically setup 'id' -> gridimage_id etc!
	$sql = "select id, user_id, title, realname, grid_reference, labels from caption where match($q) and labels != ''";

	$imagelist->getImagesBySphinxQL($sql);
	if ($imagelist->images) {
		$meta = $rt->getAssoc("SHOW META");
		print "<p>showing ".count($imagelist->images)." of {$meta['total_found']}</p>";
	} else { ?>
<h2>No visual matches found... yet!</h2>
	<?
	}
}

	$stat = array();
	foreach ($imagelist->images as $image) {
		$image->reference_index = (strlen($image->grid_reference) == 5)?2:1;
		 ?>
	    <div class="image-entry">
	        <div class="image-thumbnail">
        	    <a title="<?php echo $image->grid_reference; ?> : <?php echo htmlentities($image->title) ?> by <?php echo htmlentities($image->realname); ?> - click to view full size image" 
	               href="<?php echo $CONF['canonical_domain'][$image->reference_index]; ?>/photo/<?php echo $image->gridimage_id; ?>">
        	        <?php echo $image->getThumbnail($thumbw, $thumbh, false, true); ?>
	            </a>
        	</div>

	        <div class="image-caption">
        	    <p><?php //echo htmlentities2(utf8_to_latin1($image->caption));
			 $list = explode(',', strtolower(preg_replace('/[\r\n]+/','',utf8_to_latin1($image->labels))));
        		 foreach($list as $tag) {
	                        $tag = trim($tag, '[]", ');
                	        @$stat[$tag]++;
				print "<span class=tag>".htmlentities2($tag)."</span> ";
		        }

		     ?></p>
	        </div>
	    </div>
	<?php 
	}

	if (count($imagelist->images) == 24)
		print "Only 24 examples shown, there may be more";

	if (count($stat) > 2) {
		ksort($stat);
                print "<h3>Common tags</h3><p>This is to demonstrate the kind of keywords could serach to find the above images</p>&middot; ";
                foreach ($stat as $word => $count) {
                        if ($count == 1) continue; //cut down on noice a bit
                        $size = min(10,max(log($count),1)); //dont actll care about the count, just showing relative counts
                        $opacity = min(1, 0.3 + ($count / max($word_counts)));
                        print "<span class=nowrap style=\"font-size:{$size}em;opacity: {$opacity};\">".htmlentities($word)."</span> &middot ";
                }
	}

?>
<style>
.image-entry {
    display: flex;
    flex-wrap: wrap;       /* Allows the caption to wrap under if the screen is very narrow */
    max-width: 60em;      /* prevent extra long lines of text */
    gap: 20px;            /* Modern way to add spacing between columns */
    margin-bottom: 20px;
    align-items: flex-start; /* Keeps the thumbnail at the top if the text is long */
}

.image-thumbnail {
    flex: 0 0 213px;      /* Don't grow, don't shrink, stay at 130px */
    width: 212px;
    height: 160px;
    display: flex;
    justify-content: center; /* Centers the image inside the 130px box */
    align-items: center;
background-color: #f9f9f9; /* Optional: adds a subtle box behind the thumb */
}

.image-caption {
    flex: 1;              /* Take up all remaining horizontal space */
    min-width: 200px;     /* Forces a wrap to a new line on small mobile screens */
}

.image-caption p {
    margin: 0;            /* Removes default top margin to align with image top */
}

span.tag {
	display:inline-block;
	white-space:nowrap;
	background-color:lightgreen;
	padding:4px;
	margin:2px;
	border-radius:5px;
}
</style>
<?


####################################################


	$smarty->display('_std_end.tpl');
	exit;

