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

$data= $db->getAll("SELECT gridimage_id,title,comment,imageclass,grid_reference,user_id,realname FROM gridimage_search WHERE gridimage_id IN (6160802,2506546,5380310,5251740,1431665,6116926,406265,1013343,3110451,3804756,305664,137027,207036,6031164,3926378,5668223,5041936,2062088,5584825,3007054,4779181,1483511,4416213,733555,4123482,3471275,2643516,1244306,3646509,596233,2287566,3830270,1658532,3083884,4567054,2954560,2828554,3945420,1683346,3884329,3523109,4454560,3556629) LIMIT $limit");

$mkey = "cdemo";
//fails quickly if not using memcached!
$c = $memcache->name_get('carrot',$mkey);

if (empty($c) || !empty($_GET['nocache'])) {

	foreach ($data as $row) {

		$carrot->addDocument(
			(string)$row['gridimage_id'],
			latin1_to_utf8($row['title']),
			latin1_to_utf8($row['comment']." ".$row['imageclass']) #." ".$row['realname']
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

	print "<h3>Automatic Clusters from random images</h3>";
	print "<p>This page shows taking a small selection of images and clustering via title/description.</p>";
	print "<hr/>";
	usort($c, "cmp");
	$num = 1;

	$thumbh = 120;
	$thumbw = 120;

	foreach ($c as $cluster) {
		if ($cluster->label != '(Other)') {
			print "<div class=interestBox><b>".htmlentities($cluster->label)."</b>";
			print " (about ".count($cluster->document_ids)." images)</div>";
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

					if ($d == 6)
						break;
					$d++;
				}
			}

			print "<br style=\"clear:both\"/>";
		}
	}
} else {
	print "unable to load topics at this time";
}

$smarty->display('_std_end.tpl');

