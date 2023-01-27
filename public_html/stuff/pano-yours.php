<?

require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(true);

$sph = GeographSphinxConnection('sphinxql',true);

######################

if (empty($_GET['q']))
	$_GET['q'] = "(pano|panorama|360)";

$query = $sph->Quote("user{$USER->user_id} ".$_GET['q']);

$images = $sph->getAssoc("select id,user_id,realname,grid_reference,tags,title from sample8 where match($query) LIMIT 100");

if (empty($images))
	die("no results");

$smarty = new GeographPage;
$smarty->display('_std_begin.tpl');
?>

<form method=get>
Show your images matching: <input type=search name=q value="<? echo htmlentities($_GET['q']); ?>">
<input type=submit value=go>
<form>

<table cellspacing=0 cellpadding=3 border=1 bordercolor=#eee>
	<tr>
		<th>Title</th>
		<th>Normal</th>
		<th>Larger</th>
		<th>Panorama:</th>
		<th>vfov:</th>
		<th>hfov:</th>
		<th>panodirection:</th>
	</tr>
<?

$ids = implode(',',array_keys($images));
$tags = array();
$tagdata = $db->getAll("SELECT gridimage_id,prefix,tag FROM tag_public WHERE gridimage_id IN ($ids)");
foreach ($tagdata as $idx => $row) {
	@$tags[$row['gridimage_id']][$row['prefix']][] = $row;
}

$sizes = $db->getAssoc("SELECT * FROM gridimage_size WHERE gridimage_id IN ($ids)");

foreach ($images as $id => $row) {
	$row['gridimage_id'] = $id;
	$image = new GridImage();
	$image->fastInit($row);
	print "<tr>";
	print "<td><a href=\"/photo/{$id}\">".htmlentities($image->title)."</a></td>";
	print "<td valign=top>".$image->getThumbnail(213,160);

	print "<td valign=top>";
	$largest = max($sizes[$id]['original_width'],$sizes[$id]['original_height']);
	if ($largest > 640) {
		if ($sizes[$id]['original_diff'] == 'yes')
			$path = $image->getImageFromOriginal(640,640);
		elseif ($largest > 800)
	        	$path = $image->getImageFromOriginal(800,800);
		else
			$path = $image->_getOriginalpath(true, false);
		print "<img src=\"{$CONF['STATIC_HOST']}$path\" style=\"max-width:212px\"><br>";
		print "{$largest}px";
		print " - <a href=\"/resubmit.php?id=$id\">ReUpload Larger</a>";
	} else {
		print "<a href=\"/resubmit.php?id=$id\">Upload Larger</a>";
	}

	$type = print_tag($id,@$tags[$id]['panorama'],'X');
	print_tag($id,@$tags[$id]['vfov'],($type == '360' || $type == 'wideangle')?'X':'-');
	print_tag($id,@$tags[$id]['hfov'],($type == 'wideangle')?'X':'-');
	print_tag($id,@$tags[$id]['panodirection'],'-');

	print "<tr><td colspan=7>";
?>
<div style="text-align:center" id="hidetag<? echo $id; ?>"><a href="#" onclick="document.getElementById('tagframe<? echo $id; ?>').src='/tags/tagger.php?gridimage_id=<? echo $id; ?>';show_tree('tag<? echo $id; ?>');return false;">Open <b>Tagging</b> Box</a></div>
        <div class="interestBox" id="showtag<? echo $id; ?>" style="display:none">
                <iframe src="about:blank" height="300" width="100%" id="tagframe<? echo $id; ?>">
                </iframe>
                <div><a href="#" onclick="hide_tree('tag<? echo $id; ?>');return false">- Close <i>Tagging</I> box</a> </div>
        </div>
<?
	print "<tr><td colspan=7 style=background-color:gray>";
}
print "</table>";

$smarty->display('_std_end.tpl');

function print_tag($id,$r,$none) {
	if (!empty($r)) {
		print "<td>";
		foreach ($r as $row) {
			if (!empty($row['prefix'])) {
				if ($row['prefix'] == 'panorama')
					print "<a href=\"/pano.php?id={$id}\">";
				print htmlentities($row['prefix']).":";
			}
			print htmlentities($row['tag'])."<br>";
			return $row['tag'];
		}
	} else {
		print "<td>$none";
	}
}
