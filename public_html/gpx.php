<?php
/**
 * $Project: GeoGraph $
 * $Id: gpx.php 7525 2011-12-10 15:50:49Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

foreach(array('distance','gridref','submit','type') as $key)
	if (!empty($_REQUEST[$key]) && !preg_match('/^[\w \.>]*$/',$_REQUEST[$key])) {
	     header('HTTP/1.0 451 Unavailable For Legal Reasons');
	     exit;
	}

require_once('geograph/global.inc.php');
require_once('geograph/searchcriteria.class.php');
require_once('geograph/searchengine.class.php');
require_once('geograph/imagelist.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/gridimage.class.php');

init_session();

$smarty = new GeographPage;

if (!empty($_GET['login'])) //easy way to prompt login
    $USER->mustHavePerm('basic');

$template='gpx.tpl';
$cacheid = ($USER->registered)?1:0;

##################################################

if (!empty($_REQUEST['scout'])) { // so works as a GET :)

    $USER->mustHavePerm('basic');


				$db=GeographDatabaseConnection(false);
				if (!$db) die('Database connection failed');


//{"categories":["11"],"unphotographed":true,"fewPhotos":true,"noRecent":true,"personal":false,"personalUpdate":true,"done":false,"newPhotos":false,"fewpoi":false,"emptyHectad":false}

	$letters = $squares = array();
	$crit = array();
	$where = array('(g.percent_land>0 || ug.imagecount > 0)');
	$tables = array();
	$tables['g'] = "gridsquare g";
	$tables['ug'] = "left join user_gridsquare ug ON (ug.grid_reference = g.grid_reference AND ug.user_id = " . intval($USER->user_id) . ")";

	if (!empty($_REQUEST['unphotographed'])) {
		$crit[] = "g.has_geographs = 0"; //actully we contcentratig on non-geographs
		$letters[] = 'A';
	}
	if (!empty($_REQUEST['fewPhotos'])) {
		$crit[] = "g.imagecount < LEAST(GREATEST(4, g.nearby_avg * 0.2), 25)";
		$letters[] = 'B';
	}
	if (!empty($_REQUEST['noRecent'])) {
		$crit[] = "g.has_recent = 0";
		$letters[] = 'C';
	}
	if (!empty($_REQUEST['personal'])) { //its not has personal, but doesnt!
		$crit[] = "(ug.has_geographs = 0 OR ug.imagecount IS NULL)"; //shouldnt have a row if zero, but just in case!
		$letters[] = 'D';
	}

		if (empty($crit)) {
			//if ONLY wanting done, then might as well make it a hard join
			$tables['ug'] = "inner join user_gridsquare ug using (grid_reference)";
			$where[] = "ug.user_id = ".intval($USER->user_id);
		}

	if (!empty($_REQUEST['personalUpdate'])) {
		$crit[] = "ug.has_recent = 0"; //HAS a row, but zero
		$letters[] = 'E';
	}
	if (!empty($_REQUEST['done'])) {
		$crit[] = "ug.has_geographs > 0";
		$letters[] = 'F';
	}
	//todo, if count($crit) == 6, then MIGHT be able to avoid the filter, as want ALL squars anyway!
	$where[] = "(".implode(' OR ',$crit).")";

	//$squares will be used to filter geographically - will be running one SQL query per sequare anyway!
	if (!empty($_REQUEST['squares']))
		foreach(preg_split('/[\s,;\.-]/',trim($_REQUEST['squares'])) as $square)
			if (preg_match('/^([A-Za-z]{1,2})(\d{2})?/',$square))
				$squares[] = strtoupper($square);
	if (empty($squares)) {
		die("Need to specify square(s)\n");
	} elseif (count($squares)>10)
		$squares = array_slice($squares,0,10);

	//long filename, but user needs to keep track!
	$filename = sprintf("geograph-coverage-%s-%d-%s-%s.gpx", date('Y-m-d'), $USER->user_id, implode('',$letters), implode('_',$squares));

	//////////////////////////////////////////////////

	// 1. Set the correct headers for streaming an XML file download
	header('Content-Type: application/gpx+xml; charset=iso-8859-1');
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // In the past
//header('Content-Type: text/plain'); //for debug!

	// 2. Open a writable stream pointer to the output buffer
	$output = fopen('php://output', 'w');

	// 3. Helper function to escape text for XML using ISO-8859-1
	function xml_escape($string) {
	    return htmlspecialchars($string, ENT_QUOTES | ENT_XML1, 'ISO-8859-1');
	}

	// 4. Variables context (Assuming these are passed or available in scope)
	$http_host_esc   = xml_escape($_SERVER['HTTP_HOST'] ?? '');
	$self_host_esc   = xml_escape($CONF['SELF_HOST'] ?? '');
	$searchdesc_esc  = xml_escape("Coverage for ".$USER->realname." on ".date('Y-m-d'));
    $email_esc  = xml_escape($CONF['contact_email']);
	$current_time    = date('Y-m-d\TH:i:s');

	// 5. Output the GPX Header
	fwrite($output, '<?xml version="1.0" encoding="iso-8859-1"?>' . "\n");
	fwrite($output, '<gpx xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" version="1.0" ' . "\n");
	fwrite($output, 'creator="' . $http_host_esc . '" ' . "\n");
	fwrite($output, 'xsi:schemaLocation="http://www.topografix.com/GPX/1/0 http://www.topografix.com/GPX/1/0/gpx.xsd" xmlns="http://www.topografix.com/GPX/1/0">' . "\n");
	fwrite($output, '<desc>' . $searchdesc_esc . '</desc>' . "\n");
	fwrite($output, '<author>' . $http_host_esc . '</author>' . "\n");
	fwrite($output, '<email>' . $email_esc . '</email>' . "\n");
	fwrite($output, '<url>' . $self_host_esc . '/gpx.php</url>' . "\n");
	fwrite($output, '<urlname>Geograph Britain and Ireland</urlname>' . "\n");
	fwrite($output, '<time>' . $current_time . '</time>' . "\n");
	fwrite($output, '<keywords>geograph, photo, photograph</keywords>' . "\n");

	// Explicitly flush the header chunk out of PHP's buffer
	flush();

	// 6. Stream the waypoints loop

	require_once('geograph/conversions.class.php');
	$conv = new Conversions;

    $statusMap = [
        'unphotographed' => ['sym' => 'Flag, Green',   'color' => 'Green'],
        'fewPhotos'      => ['sym' => 'Flag, Yellow',  'color' => 'Yellow'],
        'noRecent'       => ['sym' => 'Navaid, Violet', 'color' => 'Magenta'],
        'personal'       => ['sym' => 'Flag, Blue',    'color' => 'Blue'],
        'personalUpdate' => ['sym' => 'Navaid, Orange', 'color' => 'Orange'],
        'done'           => ['sym' => 'Flag, Red',     'color' => 'Red']
    ];
    $defaultStyle = ['sym' => 'Waypoint', 'color' => 'DarkGray'];

	//create a query for each square, seems better than one huge query set
	foreach ($squares as $square) {

		//compute bbox from $square
		if (strlen($square)<=2) {
			//we oroginally called myriads a prefix
			$prefix = $db->getRow("SELECT origin_x,origin_y,reference_index FROM gridprefix WHERE prefix = ".$db->Quote($square));
			if (empty($prefix))
				continue;

			$ri = $prefix['reference_index'];

			$left=$prefix['origin_x'];
			$right=$prefix['origin_x']+99; //we could use width, but lets just grab whole square, we ARE filtering by reference_index anyway.
			$top=$prefix['origin_y']+99;
			$bottom=$prefix['origin_y'];

		} else {
			$hectad = $db->getRow("SELECT x,y,reference_index FROM hectad_stat WHERE hectad = ".$db->Quote($square));
			if (empty($hectad))
				continue;

			$ri = $hectad['reference_index'];
			//in hectad_stat havent been clamped to origin
	                $left = ( intval(($hectad['x'] - $CONF['origins'][$ri][0])/10)*10 ) +  $CONF['origins'][$ri][0];
        	        $bottom = ( intval(($hectad['y'] - $CONF['origins'][$ri][1])/10)*10 ) +  $CONF['origins'][$ri][1];
			$right = $left+9;
			$top = $bottom+9;
		}

        $rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

        $sql_where = "CONTAINS(GeomFromText($rectangle),g.point_xy)";

		$sql = sqlBitsToSelect(array(
			'columns' => 'g.grid_reference,g.x,g.y, g.imagecount as c, g.has_recent as r, g.max_ftf as g'.
				', g.nearby_avg as n, LEAST(GREATEST(4, g.nearby_avg * 0.2), 25) as b'.
				', ug.imagecount as uc, ug.has_recent as ur, percent_land as l, ug.max_ftf as ug',
			'tables'=>$tables,
			'wheres'=>array_merge($where, [$sql_where, "g.reference_index = $ri"]),
			'limit'=>10000 //just being safe! A myriad should only be 10000 squares.
		));
		$data = $db->getAll($sql);

		foreach ($data as $row) {

			list($lat,$lon) = $conv->internal_to_wgs84($row['x'],$row['y']);
			$lat = round($lat,6);
			$lon = round($lon,6);

		    $grid_ref = xml_escape($row['grid_reference'] ?? '');

            $bits = array();
            $bits[] = "{$row['c']} Total Images";
            if (!empty($row['c']) && empty($row['g']))
                $bits[] = "No Geograph Yet!";
            elseif (!empty($row['c']) && empty($row['r']))
                $bits[] = "No Recent";
            if ($row['n'] > 0.01) { //unlikly, but avoid a div/zero
                $ratio = $row['c'] / $row['n'];
                if ($ratio > 0.01)
                    $bits[] = sprintf("%.1f Local Average (%.0f%%)", $row['n'], $ratio * 100);
                else
                    $bits[] = sprintf("%.1f Local Average", $row['n']); //seems messy to bother saying 0 is 0%
            }

            if (!empty($row['uc']))
                $bits[] = "{$row['uc']} Personal Images";
            if (!empty($row['uc']) && empty($row['ug']))
                $bits[] = "No Geograph Yet!";
            elseif (!empty($row['ug']) && empty($row['ur']))
                $bits[] = "No Personal Recent";

            $status = null;
    		//Note, we check if they requested layer, so only mark it if something specifically requested
    		if (!empty($_REQUEST['unphotographed']) && !$row['g']) {           $status = 'unphotographed'; } //max_ftf=0 means no geo! (has_geographs is just binary flag, dont have actual count of geos)
    		elseif (!empty($_REQUEST['fewPhotos']) && $row['c'] < $row['b']) { $status = 'fewPhotos'; } //todo, n is still to be implemented via convoslution
    		elseif (!empty($_REQUEST['noRecent']) && !$row['r']) {             $status = 'noRecent'; }
    		elseif (!empty($_REQUEST['personal']) && !$row['ug']) {            $status = 'personal'; }
    		elseif (!empty($_REQUEST['personalUpdate']) && $row['uc'] && !$row['ur']) { $status = 'personalUpdate'; }
    		elseif (!empty($_REQUEST['done']) && $row['ug'] && $row['ur']) {   $status = 'done'; }

    		// Pick style configuration
    		$style = $statusMap[$status] ?? $defaultStyle;
    		$sym   = $style['sym'];
    		$color = $style['color'];


		    $wpt = "        <wpt lat=\"{$lat}\" lon=\"{$lon}\">\n";
		    $wpt .= "                <name>{$grid_ref}</name>\n";
		    $wpt .= "                <desc>{$grid_ref} :: ".implode(' / ',$bits)."</desc>\n";
		    $wpt .= "                <url>{$self_host_esc}/gridref/{$grid_ref}</url>\n";
		    $wpt .= "                <urlname>View {$grid_ref}</urlname>\n";
		    $wpt .= "                <sym>{$sym}</sym>\n";
		    $wpt .= "        </wpt>\n";

/*
// Stream Grid Boundary Line (Hollow Polygon Track)
    $trk = "  <trk>\n" .
           "    <name>{$gridRef} Bound</name>\n" .
           "    <extensions>\n" .
           "      <gpxx:TrackExtension>\n" .
           "        <gpxx:DisplayColor>{$color}</gpxx:DisplayColor>\n" .
           "      </gpxx:TrackExtension>\n" .
           "    </extensions>\n" .
           "    <trkseg>\n" .
           "      <trkpt lat=\"{$row['bottom_lat']}\" lon=\"{$row['left_long']}\"></trkpt>\n" .
           "      <trkpt lat=\"{$row['bottom_lat']}\" lon=\"{$row['right_long']}\"></trkpt>\n" .
           "      <trkpt lat=\"{$row['top_lat']}\" lon=\"{$row['right_long']}\"></trkpt>\n" .
           "      <trkpt lat=\"{$row['top_lat']}\" lon=\"{$row['left_long']}\"></trkpt>\n" .
           "      <trkpt lat=\"{$row['bottom_lat']}\" lon=\"{$row['left_long']}\"></trkpt>\n" .
           "    </trkseg>\n" .
           "  </trk>\n";
    fwrite($stream, $trk);
*/

		    fwrite($output, $wpt);
		}
		unset($data);

	    // Periodically flush the buffer to the client if processing large sets
	    flush();
	}

	// 7. Output Footer & Clean up
	fwrite($output, '</gpx>' . "\n");
	fclose($output);
	exit;
}

##################################################
// This is the old basic output, relies on smarty for GPX generation

$types = array(
	'all'=>'all squares',
	'with'=>'with any images',
	'few'=>'with few (less than 4)',
	'nogeos'=>'without geograph (first available)',
	'norecent'=>'with no recent (tpoint available)',
	'without'=>'with no images (first available)'
);

	if (isset($_REQUEST['submit'])) {
		$d=(!empty($_REQUEST['distance']))?min(100,intval(stripslashes($_REQUEST['distance']))):5;

		$type=(isset($_REQUEST['type']))?stripslashes($_REQUEST['type']):'few';
		switch($type) {
			case 'all': $crit = 'percent_land>0'; $d = min($d,10); break;
			case 'withgeos': $crit = 'has_geographs>0'; break;
			case 'with': $crit = 'imagecount>0'; break;
			case 'few': $crit = 'imagecount<4 and (percent_land > 0 || imagecount>1)'; break;
			case 'nogeos': $crit = 'has_geographs=0 and percent_land > 0'; break;
			case 'recent': $crit = 'percent_land > 0 and has_recent=1'; break;
			case 'norecent': $crit = 'percent_land > 0 and has_recent=0'; break;
			default: $type = 'without'; $crit = 'imagecount=0 and percent_land > 0'; break;
		}
		$typename = $types[$type];

		$square=new GridSquare;
		if (!empty($_REQUEST['ll']) && preg_match("/\b(-?\d+\.?\d*)[, ]+(-?\d+\.?\d*)\b/",$_REQUEST['ll'],$ll)) {
			$conv = new Conversions;
			list($x,$y,$reference_index) = $conv->wgs84_to_internal($ll[1],$ll[2]);
			$grid_ok=$square->loadFromPosition($x, $y, true);
		} else {
			$grid_ok=$square->setByFullGridRef($_REQUEST['gridref']);
		}

		if ($grid_ok)
		{
			$template='gpx_download_gpx.tpl';
			$cacheid = $square->grid_reference.'-'.($type).'-'.($d);

			//regenerate?
			if (!$smarty->is_cached($template, $cacheid))
			{
				$searchdesc = "squares within {$d}km of {$square->grid_reference} $typename photographs";

				$x = $square->x;
				$y = $square->y;

				$sql_where = $crit.' and ';

				$left=$x-$d;
				$right=$x+$d;
				$top=$y+$d;
				$bottom=$y-$d;

				$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

				$sql_where .= "CONTAINS(GeomFromText($rectangle),point_xy)";

				//shame cant use dist_sqd in the next line!
				$sql_where .= " and ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) < ".($d*$d);

				$sql_fields .= ", ((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) as dist_sqd";
				$sql_order = ' dist_sqd ';

				$sql = "SELECT grid_reference,x,y,imagecount $sql_fields
					FROM gridsquare gs
					WHERE $sql_where
					ORDER BY $sql_order";

				$db=GeographDatabaseConnection(false);
				if (!$db) die('Database connection failed');

				$data = $db->getAll($sql);

				require_once('geograph/conversions.class.php');
				$conv = new Conversions;
				foreach ($data as $q => $row) {
					list($data[$q]['lat'],$data[$q]['long']) = $conv->internal_to_wgs84($row['x'],$row['y']);
				}

				$smarty->assign_by_ref('data', $data);
				$smarty->assign_by_ref('searchdesc', $searchdesc);
			}

			header("Content-type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"Geograph-$cacheid.gpx\"");
			customExpiresHeader(3600*24*14,true);

			$smarty->display($template, $cacheid);
			exit;

##################################################

		}
		else
		{
			//preserve the input at least
			$smarty->assign('gridref', stripslashes($_REQUEST['gridref']));
			$smarty->assign('distance', $d);
			$smarty->assign('type', $type);

			$smarty->assign('errormsg', $square->errormsg);
		}

	} else {
		$smarty->assign('distance', 5);
		$smarty->assign('type', 'without');
		if (isset($_REQUEST['gridref'])) {
			$smarty->assign('gridref', stripslashes($_REQUEST['gridref']));
		}
	}
	$smarty->assign('distances', array(1,3,5,10,15,20,30,50,75,100));
	$smarty->assign_by_ref('types', $types);

$smarty->display($template, $cacheid);


