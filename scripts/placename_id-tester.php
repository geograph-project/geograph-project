<?

$param = array('id'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

require_once('geograph/gazetteer.class.php');

$gaz = new Gazetteer();

############################################
$c = 0;
$data = array(
	array('gridimage_id'=>19),
	array('gridimage_id'=>8300000)
);

if (!empty($param['id'])) {
    $data = array(
        array('gridimage_id'=>$param['id'])
    );
}


	foreach ($data as $row) {
		print str_repeat('#',80)." {$row['gridimage_id']} --\n";
		$grid_ok = false;
                $image = new Gridimage($row['gridimage_id']);

		//$image->_initFromArray($row); -- wouldn't load grid_square!

                if ($image->isValid() && $image->moderation_status!='rejected') {
                        $grid_ok = 1;
       	                $square = $image->grid_square;
                }

		if ($grid_ok) {
			$place = $square->findNearestPlace(75000);
			print "square.findNearestPlace: ";
			ksort($place);
			print_r($place);print "\n";

			if (!empty($image->grid_square->placename_id)) {
				$places = $gaz->findPlacename($image->grid_square->placename_id);
				//findNearestPlace, garentees to call $square->getNatEastings() if 4fig GR!
				add_distaince($places[0], $image->grid_square);

				print "gridsquare.placename_id: ";
				ksort($places[0]);
				print_r($places[0]);print "\n";
			}
		}

		if (!empty($image->placename_id)) {
			$places = $gaz->findPlacename($image->placename_id);
			if (!empty($image->grid_square->nateastings))
				add_distaince($places[0], $image->grid_square);

			print "gridimage.placename_id: ";
			ksort($places[0]);
			print_r($places[0]);print "\n";
		}

		if (true) {
			$place = $image->findNearestPlace();
			//no add_distaince needed!
			print "gridimage.findNearestPlace: ";
			ksort($place);
			print_r($place);print "\n";
		}

		if (!($c%100))
			print "$c ";
		$c++;
	}

print "$c.\n";



//this is just a bodge - to mimic what findByNational does!
function add_distaince(&$place, $grid_square) {
	global $CONF;

	$place['direction'] = rad2deg(atan2( $grid_square->nateastings-$place['e'], $grid_square->natnorthings-$place['n'] ));

	//this is what gazetter class does!
	//$places['distance'] = round(sqrt($places['distance'])/1000)+0.01;

	$place['distance'] = round( sqrt( pow($place['e']-$grid_square->nateastings ,2)
		   	                + pow($place['n']-$grid_square->natnorthings,2) ) /1000)+0.01;;

        if (!empty($place['reference_index'])) {
                $place['reference_name'] = $CONF['references'][$place['reference_index']];
                if ($place['reference_index'] == 2 && $place['country'] == 'uk') {
                        $place['reference_name'] = "Northern Ireland";
                }
        }
}
