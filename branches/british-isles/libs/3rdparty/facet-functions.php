<?

function sphinx_client() {
	return GeographSphinxConnection('client',true);
}
function mysql_sphinx() {
	return GeographSphinxConnection('mysql',true);
}

function mysql_database() {
	$db = GeographDatabaseConnection(true);
	return $db->_connectionID;
}


function indent($json) {

    $result      = '';
    $pos         = 0;
    $strLen      = strlen($json);
    $indentStr   = '  ';
    $newLine     = "\n";
    $prevChar    = '';
    $outOfQuotes = true;

    for ($i=0; $i<=$strLen; $i++) {

        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;

        // If this character is the end of an element,
        // output a new line and indent the next line.
        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }

        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element,
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }

            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }

        $prevChar = $char;
    }

    return $result;
}


//http://www.nearby.org.uk/project-kml.php?source=1

function calcLatLong($long, $lat, $distance, $bearing) {

 $EARTH_RADIUS_EQUATOR = 6378140.0;
 $RADIAN = 180 / pi();

 $b = $bearing / $RADIAN;
 $long = $long / $RADIAN;
 $lat = $lat / $RADIAN;
 $f = 1/298.257;
 $e = 0.08181922;

 $R = $EARTH_RADIUS_EQUATOR * (1 - $e * $e) / pow( (1 - $e*$e * pow(sin($lat),2)), 1.5);
 $psi = $distance/$R;
 $phi = pi()/2 - $lat;
 $arccos = cos($psi) * cos($phi) + sin($psi) * sin($phi) * cos($b);
 $latA = (pi()/2 - acos($arccos)) * $RADIAN;

 $arcsin = sin($b) * sin($psi) / sin($phi);
 $longA = ($long - asin($arcsin)) * $RADIAN;
 return array($longA,$latA);
}




function geoTiles($lat,$lng,$rad = 1000) {
        $conv = new Conversions;

	list($e,$n,$reference_index) = $conv->wgs84_to_national($lat,$lng,true);

	if (!empty($_GET['debug'])) {
		print "list($e,$n,$reference_index) --- $e,$n,$d<br>";
	}

                $e = $e/1000;
                $n = $n/1000;
                $d = $rad/1000; //units is km!

	if (!empty($_GET['debug'])) {
		print "list($e,$n, $d)<br>";
	}




                $grs = array();

			if ($d > 25) {
				$field = "myriad";
				$div = 100;
				$grlen = 1;
			} elseif ($d > 1) {
				$field = "hectad";
				$div = 10;
				$grlen = 2;
			} else {
				$field = "grid_reference";
                                $div = 1;
                                $grlen = 4;
			}

			if (($d*2) < $div) { //range will complain if $step is bigger than the range between start and finish (rather than just giving start) 
				$xs = array($e-$d);
				$ys = array($n-$d);
			} else {
				$xs = range($e-$d, $e+$d,$div);
				$ys = range($n-$d, $n+$d,$div);
			}

        if (!empty($_GET['debug'])) {
                print "range(".($e-$d).",".($e+$d).",$div);<br>";
        }
	if (!empty($_GET['debug'])) {
		print_r($xs);print "<br>";
		print_r($ys);print "<hr>";
	}


			//as well as the first, and intermediate, ALWAYS need the far edge too! (range will not add end)
			#if ($d > 1 && $d%$div != 0) {
				$xs[] = $e+$d;
				$ys[] = $n+$d;
			#}

	if (!empty($_GET['debug'])) {
		print_r($xs);print "<br>";
		print_r($ys);print "<hr>";
	}

			foreach($xs as $x) {
				 foreach ($ys as $y) {
                        //for($x=$e-$d;$x<=$e+$d;$x+=10) {
                        //        for($y=$n-$d;$y<=$n+$d;$y+=10) {
                                        list($gr2,$len) = $conv->national_to_gridref($x*1000,$y*1000,$grlen,$reference_index,false);
                                        if (strlen($gr2) && preg_match('/^[A-Z]{1,2}\d*$/',$gr2))
                                                $grs[$gr2] = $gr2;
                                }
                        }

	if (!empty($_GET['debug'])) {
		var_dump($grs);
		print "<hr>";
	}

	if (!empty($grs))
               return " @$field (".join(" | ",$grs).")";

	return '';
}


