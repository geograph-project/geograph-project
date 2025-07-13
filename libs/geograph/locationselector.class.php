<?php

class LocationSelector
{
    public function __construct()
    {
    }

    public function extractLatLng($locationString)
    {
        global $db, $CONF;

        $lat = null;
        $lng = null;

        if (!empty($locationString)) {
            require_once('geograph/conversions.class.php');
            $conv = new Conversions;
            $square = new GridSquare;

            if (preg_match("/^(\d+),\s*(\d+)\s*([OSIGB]*)$/i", $locationString, $ee)) {
                $e = intval($ee[1]);
                $n = intval($ee[2]);
                $reference_index = (stripos($ee[3], 'i') !== false) ? 2 : 1;
                list($gr, $len) = $conv->national_to_gridref($e, $n, null, $reference_index, false);
                $locationString = $gr;
            }

            if (preg_match("/^(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)$/", $locationString, $ll)) {
                $lat = floatval($ll[1]);
                $lng = floatval($ll[2]);
            } elseif (preg_match_all('/\b([a-zA-Z]{1,2}) ?(\d{2,5})(\.\d*|) ?(\d{2,5})(\.*\d*|)\b/', $locationString, $matches)) {
                $gr = array_pop($matches[0]);
                $grid_ok = $square->setByFullGridRef($gr, true, true);
            } elseif (($row = $db->getRow("select avg(wgs84_lat),avg(wgs84_long) from curated1 inner join gridimage_search using (gridimage_id) where region = " . $db->Quote($locationString))) && $row[0]) {
                $lat = $row[0];
                $lng = $row[1];
            } else {
                $qu = urlencode(trim($locationString));
                $str = get_internal_url($CONF['API_HOST'] . "/finder/places.json.php?q=$qu&new=1");
                if (strlen($str) > 40) {
                    $decode = json_decode($str);
                }
                if (!empty($decode) && !empty($decode->total_found)) {
                    $gr = $decode->items[0]->gr;
                    $grid_ok = $square->setByFullGridRef($gr, true, true);
                }
            }

            if (!$square->nateastings && $square->x && $square->y) {
                list($e, $n, $reference_index) = $conv->internal_to_national($square->x, $square->y);
                $square->nateastings = $e;
                $square->natnorthings = $n;
                $square->reference_index = $reference_index;
                $grid_ok = 1;
            }

            if (empty($lat) && !empty($square->nateastings)) {
                list($lat, $lng) = $conv->national_to_wgs84($square->nateastings, $square->natnorthings, $square->reference_index);
            }
        }

        return array($lat, $lng);
    }

    public function getForm($loc = '', $regions = 'null')
    {
        $input = $this->getInput($loc, $regions);
        return <<<HTML
<form method=get name=locForm>
    Optional Location Focus: {$input}
	<input type=submit value="Go&gt;">
	<a href="#" onclick="getLocation();return false">Find my Location</a><br>
	<small>Search for a place to focus the results on the location, so that if possible will highlight local images first</small>
</form>
HTML;
    }

    public function getInput($loc = '', $regions = 'null')
    {
        $loc = htmlspecialchars($loc);
        return <<<HTML
<input type="search" name="loc" value="{$loc}" placeholder="(enter coordinate/placename/postcode)" id="loc" size=50>
<script type="text/javascript">
var regions = {$regions};
</script>
HTML;
    }

    public function getScripts()
    {
        return <<<HTML
<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/themes/ui-lightness/jquery-ui.css" rel="stylesheet"/>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.22/jquery-ui.min.js"></script>
<script type="text/javascript" src="/js/location-selector.js"></script>
HTML;
    }
}
