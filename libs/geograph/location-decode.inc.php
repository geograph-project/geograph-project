<?php

if (!empty($_GET['loc'])) {

        require_once('geograph/conversions.class.php');
        $conv = new Conversions;
        $square=new GridSquare;

        if (preg_match("/^(\d+),\s*(\d+)\s*([OSIGB]*)$/i",$_GET['loc'],$ee)) {
                $e = intval($ee[1]);
                $n = intval($ee[2]);
                $reference_index = (stripos($ee[3],'i')!==FALSE)?2:1;
                list($gr,$len) = $conv->national_to_gridref($e,$n,null,$reference_index,false);

                $_GET['loc'] = $gr;
        }

	if (preg_match("/^(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)$/",$_GET['loc'],$ll)) {

		 //actully we most interested in lat/long, square is just a useful intermediate.
		if (false) {
	                list($e,$n,$reference_index) = $conv->wgs84_to_national($ll[1],$ll[2],true);
        	        list($gr,$len) = $conv->national_to_gridref($e,$n,10,$reference_index,false);

                	$_GET['loc'] = $gr;
		}

                $lat = floatval($ll[1]);
                $lng = floatval($ll[2]);

        } elseif (preg_match_all('/\b([a-zA-Z]{1,2}) ?(\d{2,5})(\.\d*|) ?(\d{2,5})(\.*\d*|)\b/',$_GET['loc'],$matches)) {
                $gr = array_pop($matches[0]); //take the last, so that '/near/Grid%20Reference%20in%20C1931/C198310' works!
                $grid_ok=$square->setByFullGridRef($gr,true,true);
                $gru = urlencode(str_replace(' ','',$gr));
                $location = "grid reference";

	} elseif (
		($row = $db->getRow("select avg(wgs84_lat),avg(wgs84_long) from curated1 inner join gridimage_search using (gridimage_id) where region = ".$db->Quote($_GET['loc'])))
		&& $row[0]) {

		$lat = $row[0];
		$lng = $row[1];
		if ($smarty)
			$smarty->assign('region', $_GET['loc']);

        } else {
		//otherwise look it up...
		$qu = urlencode(trim($_GET['loc']));

                $str = file_get_contents("http://www.geograph.org.uk/finder/places.json.php?q=$qu&new=1");
                if (strlen($str) > 40) {
                        $decode = json_decode($str);
                }

		if (!empty($decode) && !empty($decode->total_found)) {
	                $gr = $decode->items[0]->gr;
        	        $grid_ok=$square->setByFullGridRef($gr,true,true);
	                $gru = urlencode(str_replace(' ','',$gr));
        	        $location = "location";
	                if (strpos($decode->items[0]->name,'Grid') !== FALSE)
        	                $location = "grid reference";
                	elseif (strpos($decode->items[0]->name,'Postcode') !== FALSE)
                        	$location = "postcode";
		}
        }

        //for some unexplainable reason, setByFullGridRef SOMETIMES returns false, and fails to set nateastings - even though allow-zero-percent is set. Fix that...
        if (!$square->nateastings && $square->x && $square->y) {
                list($e,$n,$reference_index) = $conv->internal_to_national($square->x,$square->y);
                $square->nateastings = $e;
                $square->natnorthings = $n;
                $square->reference_index = $reference_index;
                $grid_ok = 1;
        }

	//actully we most interested in lat/long, square is just a useful intermediate.

	if (empty($lat) && !empty($square->nateastings)) {
                list($lat,$lng) = $conv->national_to_wgs84($square->nateastings,$square->natnorthings,$square->reference_index);
	}

}
