<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

######################

$smarty->assign('responsive', true);
$smarty->display("_std_begin.tpl",md5($_SERVER['PHP_SELF']));

######################

if (!empty($_GET['map'])) {
	print "<div style=\"float:right;width:510px\">";
	$query = htmlentities($_SERVER['QUERY_STRING']);
	print "<iframe src=\"pano-map.php?$query\" width=\"500\" height=\"700\"></iframe>";
	print "</div>";
}

######################

$db = GeographDatabaseConnection(true);

$where = array();
$where[] = "original_width > 640";
$where[] = "prefix = 'panorama'";
$limit = 20;
if (!empty($_GET['limit']))
	$limit = min(100,intval($_GET['limit']));

if (!empty($_GET['tag']) && $_GET['tag'] == 360)
	$where[] = "tag IN ('360','photosphere')"; //photosphere are still 360!
elseif (!empty($_GET['tag']) && preg_match('/^\w+$/',$_GET['tag']))
	$where[] = "tag = ".$db->Quote($_GET['tag']);

if (!empty($_GET['user_id']))
	$where[] = "g.user_id = ".intval($_GET['user_id']);


$where = implode(' AND ',$where);

if (!empty($_GET['pending']) && !empty($_GET['user_id']) && $USER->user_id == $_GET['user_id']) {
	$rows = $db->getAll($sql = "
		SELECT gridimage_id,g.user_id,grid_reference,title,if(g.realname!='',g.realname,user.realname) as realname,imagetaken
                FROM gridimage g INNER JOIN user USING (user_id) INNER JOIN gridsquare using (gridsquare_id)
		 inner join gridimage_size s USING (gridimage_id)  inner join tag_public t using (gridimage_id)
                WHERE $where
		 order by imagetaken desc limit $limit
	");
} else {
$rows = $db->getAll($sql = "
select distinct gridimage_id,width,height,original_width,original_height,original_diff, g.user_id,tags,title,realname,grid_reference,comment, imagetaken
 from gridimage_size s inner join gridimage_search g using (gridimage_id) inner join tag_public t using (gridimage_id)
 where $where
 order by imagetaken desc limit $limit
");
}

//print $sql;

$last = null;
foreach ($rows as $row) {
	$image = new GridImage();
	$image->fastInit($row);
	$image->compact();

	if ($last != $image->imagetaken) {
		print "<h3>".$image->getFormattedTakenDate()."</h3>";
		$last =  $image->imagetaken;
	}
	$title = htmlentities($image->title.' by '.$image->realname);

	print "<div style=\"float:left;width:640px\">";

	print "<a href=\"/pano.php?id={$image->gridimage_id}\" title=\"$title\">";
	$path = $image->getImageFromOriginal(640,640, true);
	print "<img src=$path style=\"max-width:94vw;width:640px\">";
	print "</a>";

	print "</div>";
	print "<div style=\"float:left;width:640px;padding-left:10px\">";
	print "<b>$title</b> {$image->grid_reference}";
	print "</div>";

	print "<br style=clear:left>";
}

print "<br style=clear:both>";

print "<p>Note, this is only images that have been specificially tagged as panoramic, we have some other images that have not been specifically tagged. They dont currently show here";

$smarty->display("_std_end.tpl",'test');
