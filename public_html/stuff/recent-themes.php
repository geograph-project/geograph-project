<?


require_once('geograph/global.inc.php');

init_session();


$smarty = new GeographPage;

customExpiresHeader(3600,false,true);

$smarty->display('_std_begin.tpl');


require_once('3rdparty/Carrot2.class.php');

$db = GeographDatabaseConnection(true);

$carrot = Carrot2::createDefault();

$limit = 100;

$data= $db->getAll("SELECT gridimage_id,title,comment,imageclass,grid_reference,user_id,realname FROM gridimage_search ORDER BY gridimage_id DESC LIMIT $limit");

$mkey = "recent".$limit;
//fails quickly if not using memcached!
$c = $memcache->name_get('carrot',$mkey);

if (empty($c)) {

	foreach ($data as $row) {

		$carrot->addDocument(
			(string)$row['gridimage_id'],
			utf8_encode(htmlentities($row['title'])),
			utf8_encode(htmlentities($row['comment']." ".$row['imageclass'])) #." ".$row['realname']
		);

	}

	$c = $carrot->clusterQuery();

	$memcache->name_set('carrot',$mkey,$c,$memcache->compress,$memcache->period_short);

}


function cmp(&$a, &$b)
{
    if ($a->score == $b->score) {
	return 0;
    }
    return ($a->score > $b->score) ? -1 : 1;
}




if (!empty($c) && count($c) > 1) {

	print "<h3>Themes from recent images</h3>";
	print "<p>Built by inspecting the most recent $limit images. Click a title to view more images</p>";
	print "<hr/>";
	usort($c, "cmp");
	$num = 1;

	$thumbh = 120;
	$thumbw = 120;

	foreach ($c as $cluster) {
		if ($cluster->label != '(Other)') {
			print "<b><a href=\"/search.php?searchtext=".urlencode($cluster->label)."&amp;displayclass=full&amp;orderby=submitted&amp;breakby=submitted&amp;reverse_order_ind=1&amp;do=1\">".htmlentities($cluster->label)."</a></b>";
			print " (about ".count($cluster->document_ids)." images)<br/>";
#			print " ".$cluster->score." ".count($cluster->document_ids);


			$d = 0;
			foreach ($cluster->document_ids as $sort_order => $document_id) {
				
				if ($row = $data[$document_id]) {
				
					$image = new GridImage();
					$image->fastInit($row);
					$image->compact();

?>
          <div style="float:left;position:relative; width:130px; height:130px">
          <div align="center">
          <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a></div>
          </div>
<?
				
					if ($d == 4)
						break;
					$d++;
				}
			}

			print "<hr style=\"clear:both\"/>";
		
		}
	}
} else {
	print "unable to load topics at this time";
}

$smarty->display('_std_end.tpl');

exit;


print "<pre>";
print_r($c);
