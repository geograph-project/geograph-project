<?

header("Location: ./?best=1");
exit;


require_once('geograph/global.inc.php');


$smarty = new GeographPage;

init_session();
$USER->mustHavePerm("basic");


$db = GeographDatabaseConnection(true);

$smarty->assign('maincontentclass', 'content_photo'.'black');

$smarty->display('_std_begin.tpl');

print "<div style=padding:10px>"; //content_photoblack has no padding so photo page can put stuff right upto edges
print "<h2>Image Voting</h2>";

print "<p>Click the stars under each image to give your rating. Your last vote on an image counts (so can change vote).<br>";
print "<i>Consider 3 stars the average within this set, so give higher score to your favorite(s). Although if prefer can just give 5 stars to your top image each month.</i></p>";


$type = "i164568868"; //still needed for voting
$query_id = 164568868;

$engMon=array('January','February','March','April','May','June','July','August','September','October','November','December');

$imagelist=new ImageList;

$imagelist->_getImagesBySql("SELECT {$imagelist->cols},imagetaken FROM gridimage_query inner join gridimage_search using (gridimage_id)
		 where query_id = $query_id ORDER BY month(imagetaken),rand()");

$votes = $db->getAssoc("select id,vote from vote_log where type='$type' and user_id = ".$USER->user_id);

$last = '';
$c = 0;
$_GET['large'] =0;
foreach ($imagelist->images as $idx => $image) {
	$month = substr($image->imagetaken,5,2);
	if ($last != $month) {
		print "<hr>";
		print "<h2>".$engMon[intval($month)-1]."</h2>";
		$c=0;
	}
	$last = $month;

	$title = htmlentities2($image->title.' by '.$image->realname);

	print "<div style=\"display:inline-block;width:650px;height:500px;padding:3px\">";
	print "<a href=\"/photo/{$image->gridimage_id}\" title=\"$title\">";
	print $image->getFull();

	print "</a>";
	print "<div id=\"votediv{$image->gridimage_id}\">";
		smarty_function_votestars(array('type'=>$type,'id'=>$image->gridimage_id));
	if (!empty($votes[$image->gridimage_id]))
		print " (existing: {$votes[$image->gridimage_id]})</div></div>";
	else
		print "</div></div>";

	$c++;
}

print "</div>";

$smarty->display('_std_end.tpl');


function smarty_function_votestars($params) {
	global $CONF;
	static $last;

	$type = $params['type'];
	$id = $params['id'];
	$names = array('','Hmm','Below average','So So','Good','Excellent');
	foreach (range(1,5) as $i) {
		print "<a href=\"javascript:void(record_vote('$type',$id,$i));\" title=\"{$names[$i]}\"><img src=\"{$CONF['STATIC_HOST']}/img/star-light.png\" width=\"14\" height=\"14\" alt=\"$i\" onmouseover=\"star_hover($id,$i,5)\" onmouseout=\"star_out($id,5)\" name=\"star$i$id\"/></a>";
	}
	if ($last != $type) {
		print " (<a href=\"/help/voting\">about</a>)";
	}
	$last = $type;
}
