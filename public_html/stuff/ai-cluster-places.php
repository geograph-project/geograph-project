<?

$_GET['ddev'] = 1;

require_once('geograph/global.inc.php');



$input = "../../ai-schema/ai-cluster-places.example.json";

$d = file_get_contents($input);
$j = json_decode($d,true);

init_session();


$smarty = new GeographPage;

// customExpiresHeader(3600,false,true);


        $db = GeographDatabaseConnection(false);

        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$ids = array();
foreach($j['places'] as $row) {
	foreach($row['images'] as $image) {
		$ids[$image['id']]=1;
	}
}

$ids = implode(',',array_keys($ids));
$images = $db->getAssoc("SELECT gridimage_id,user_id,title,realname,grid_reference,reference_index FROM gridimage_search WHERE gridimage_id IN ($ids)");


	$smarty->display('_std_begin.tpl');
	$thumbw = $thumbh = 120;

	print "<p>This demo is a sample of search rsults with 'ffestiniog' in the title, and uses an AI to seperate the actual depictions of places, vs images that jsut make reference to a place";
	print ". Its not perfect, but it perhaps shows promise of being about to do more accurate place searching (avoiding images mentioning a place, but not actully showing the place)";

	$relations = array('depiction','reference');
	print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee style=position:relative>";
	print "<tr style=background-color:white;position:sticky;top:0>";
	print "<td>Place</td>";
	foreach($relations as $relation) {
                print "<td>$relation";
	}
	$places = array();
	foreach($j['places'] as $row) {
		if (preg_match('/Town|Village/',$row['type']))
			$places[] = $row;
	}
	foreach($j['places'] as $row) {
		if (!preg_match('/Town|Village/',$row['type']))
			$places[] = $row;
	}
	foreach($places as $row) {
		$rel = array();
		foreach($row['images'] as $image) {
			@$rel[$image['relation']][] = $image['id'];
		}

//		if (empty($rel['depiction']))
//			continue;

		print "<tr>";
		//print "<th>".htmlentities($row['name'])."</th>";
		//print "<td>".htmlentities($row['type'])."</td>";

		print "<td><b><big>".htmlentities($row['name'])."</big></b>";
		print "<br>(".htmlentities($row['type']).")";

		foreach($relations as $relation) {
			print "<td title=$relation>";
			if (!empty($rel[$relation])) {

				if ($relation == 'depiction') {
					$thumbh = 160;
		                        $thumbw = 213;
				} else {
					$thumbw = $thumbh = 120;
				}


				foreach ($rel[$relation] as $id) {
					$r = $images[$id];
					$r['gridimage_id'] = $id;
					$image = new GridImage();
					$image->fastInit($r);

	                                print '<a title="'.$image->grid_reference.' : '.htmlentities($image->title).' by '.htmlentities($image->realname).' - click to view full size image"';
	                                print ' href="https://www.geograph.org.uk/photo/'.$image->gridimage_id.'">'.$image->getThumbnail($thumbw,$thumbh,false,true).'</a>';
				}
			}
		}
	}
	print "</table>";

	$smarty->display('_std_end.tpl');
