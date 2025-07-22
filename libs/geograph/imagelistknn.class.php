<?php
/**
 * $Project: GeoGraph $
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2023 Barry Hunter (barry@geograph.org.uk)
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

require_once('imagelist.class.php');

/**
* Provides the ImageListKNN class
*
* @package Geograph
*/

/**
* ImageListKNN class
* Provides facilities for building a list of GridImage instances using KNN
*/
class ImageListKNN extends ImageList
{
    public $vector = 'image_vector';
        //always needs (id,user_id, title) (ideally realname,grid_reference too)
    public $knncols = 'id, user_id, realname, title, 1 as reference_index, grid_reference'; //focing ri=1 means always .org.uk links

    public function getImagesSimilarToID($id, $limit = 100)
    {
        $id = intval($id);

        $sql = "select {$this->knncols}, knn_dist() as k from gridimage_embedding where knn ( {$this->vector}, $limit, $id ) limit $limit";

        return $this->getImagesBySphinxQL($sql);
    }

    public function getImagesSimilarToLabel($label, $limit = 100)
    {
        $value = $this->_getLabelVectorValue($label);
        if (is_null($value)) {
            return 0;
        }

        $this->sql = $sql = "select {$this->knncols}, knn_dist() as k from gridimage_embedding where knn({$this->vector}, $limit, $value) limit $limit";

        return $this->getImagesBySphinxQL($sql);
    }

    public function getImagesByLocation($lat, $lon, $distance, $label = null, $limit = 100)
    {
        $cols = $this->knncols;
        list($dist_col, $dist_where) = $this->_getGeoDistClause($lat, $lon, $distance);
        $cols .= ", $dist_col";
        $where = [$dist_where];

        if (!is_null($label)) {
            $value = $this->_getLabelVectorValue($label);
            if (is_null($value)) {
                return 0;
            }
            $where[] = "knn({$this->vector}, 1000, $value)"; //need to deliberatly oversample for now!
	    $cols .= ", knn_dist() as k";
        }

        $this->sql = $sql = "select $cols from gridimage_embedding where ".implode(' AND ', $where)." limit $limit";

        return $this->getImagesBySphinxQL($sql);
    }

    private function _getRange($cached = true)
    {
	if ($cached) {
		//partly for speed, but also should be set to what the index actully uses, to prevent creep!
		//radians, as what manticore uses!
		return array ('mnlt' => '0.87028730','mnln' => '-0.23890854','mxlt' => '1.06221426','mxln' => '0.03133320');
	}
        $sph = $this->_getSph();
        return $sph->getRow("select min(wgs84_lat) as mnlt,min(wgs84_long) as mnln,max(wgs84_lat) as mxlt,max(wgs84_long) as mxln from sample8");
    }

    private function _mapRange(float $value, float $in_min, float $in_max, float $out_min, float $out_max): float
    {
        return ($value - $in_min) * ($out_max - $out_min) / ($in_max - $in_min) + $out_min;
    }

    private function _getLabelVectorValue($label)
    {
        $db = $this->_getDB();
        $quoted = $db->Quote($label);
        $binary = $db->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted");

        if (empty($binary)) {
            return null;
        }

        $list = unpack('g*', $binary);
        return "(".implode(', ', $list).")";
    }

    private function _getGeoDistClause($lat, $lon, $distance)
    {
        $lat_rad = deg2rad(floatval($lat));
        $lon_rad = deg2rad(floatval($lon));
        $dist = intval($distance);
        return [
            "GEODIST(wgs84_lat, wgs84_long, $lat_rad, $lon_rad) as distance",
            "distance < $dist"
        ];
    }

    private function _appendLocationToVector($binary, $lat, $lon)
    {
        $range = $this->_getRange();

        $lat = deg2rad(floatval($lat));
        $lon = deg2rad(floatval($lon));

        $binary .= pack('g*',
            $this->_mapRange($lat, $range['mnlt'], $range['mxlt'], -1, 1),
            $this->_mapRange($lon, $range['mnln'], $range['mxln'], -1, 1)
        );

        $list = unpack('g*', $binary);
        return "(".implode(', ', $list).")";
    }

    public function getImagesByLocationVector($lat, $lon, $label, $limit = 100, $incgeodist = false)
    {
        $db = $this->_getDB();
        $quoted = $db->Quote($label);
        $binary = $db->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted");

        if (empty($binary)) {
            return 0;
        }
	if ($incgeodist) {
		list($dist_col, $dist_where) = $this->_getGeoDistClause($lat, $lon, 0);
		$this->knncols.= ", $dist_col"; //adds the distance column
		//ignoring the $where here, the whole point is to test using plus_vector! not to filter
	}

	$this->vector = 'plus_vector';
        $value = $this->_appendLocationToVector($binary, $lat, $lon);

        $this->sql = $sql = "select {$this->knncols}, knn_dist() as k from gridimage_embedding where knn({$this->vector}, $limit, $value) limit $limit";

        return $this->getImagesBySphinxQL($sql);
    }

    public function getImagesByCriteria(array $criteria, $limit = 100)
    {
        $cols = $this->knncols;
        $where = [];
        $params = [];

        if (!empty($criteria['lat'])) {
            list($dist_col, $dist_where) = $this->_getGeoDistClause($criteria['lat'], $criteria['lon'], $criteria['distance']);
            $cols .= ", $dist_col"; //adds the distance column
	    if (!empty($criteria['distance']))
            	$where[] = $dist_where;
        }

        if (!empty($criteria['label'])) {
	    $cols = ", knn_dist() as k";
            $value = $this->_getLabelVectorValue($criteria['label']);
            if (!is_null($value)) {
                $where[] = "knn({$this->vector}, $limit, $value)";
            }
        }

        if (!empty($criteria['keywords'])) {
	    $cols .= ", WEIGHT() AS w";
            $where[] = "MATCH(?)";
            $params[] = $criteria['keywords'];
        }

        if (empty($where)) {
            return 0;
        }

        $this->sql = $sql = "select $cols from gridimage_embedding where ".implode(' AND ', $where)." limit $limit";

        return $this->getImagesBySphinxQL($sql, true, ...$params);
    }
}

