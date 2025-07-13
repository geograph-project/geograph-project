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
    public $knncols = 'id, user_id, realname, title, 1 as reference_index, knn_dist() as grid_reference';

    public function getImagesSimilarToID($id, $limit = 100)
    {
        $id = intval($id);
        //always needs (id,user_id, title) (ideally realname,grid_reference too)
        $sql = "select {$this->knncols} from gridimage_embedding where knn ( {$this->vector}, $limit, $id ) limit $limit";

        return $this->getImagesBySphinxQL($sql);
    }

    public function getImagesSimilarToLabel($label, $limit = 100)
    {
        $db = $this->_getDB();
        $quoted = $db->Quote($label);
        $binary = $db->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted");

        if (empty($binary)) {
            return 0;
        }

        $list = unpack('g*', $binary);
        $value = "(".implode(', ', $list).")";

        //always needs (id,user_id, title) (ideally realname,grid_reference too)
        $sql = "select {$this->knncols} from gridimage_embedding where knn({$this->vector}, $limit, $value) limit $limit";

        return $this->getImagesBySphinxQL($sql);
    }

    public function getImagesByLocation($lat, $lon, $distance, $label = null, $limit = 100)
    {
        $lat = deg2rad(floatval($lat));
        $lon = deg2rad(floatval($lon));
        $distance = intval($distance);

        $where = "GEODIST(wgs84_lat, wgs84_long, $lat, $lon) < $distance";

        if (!is_null($label)) {
            $db = $this->_getDB();
            $quoted = $db->Quote($label);
            $binary = $db->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted");

            if (empty($binary)) {
                return 0;
            }

            $list = unpack('g*', $binary);
            $value = "(".implode(', ', $list).")";
            $where .= " AND knn({$this->vector}, $limit, $value)";
        }

        $sql = "select {$this->knncols} from gridimage_embedding where $where limit $limit";

        return $this->getImagesBySphinxQL($sql);
    }

    private function _getRange()
    {
        $sph = $this->_getSph();
        return $sph->getRow("select min(wgs84_lat) as mnlt,min(wgs84_long) as mnln,max(wgs84_lat) as mxlt,max(wgs84_long) as mxln from sample8");
    }

    private function _mapRange(float $value, float $in_min, float $in_max, float $out_min, float $out_max): float
    {
        return ($value - $in_min) * ($out_max - $out_min) / ($in_max - $in_min) + $out_min;
    }

    public function getImagesByLocationVector($lat, $lon, $label, $limit = 100)
    {
        $db = $this->_getDB();
        $quoted = $db->Quote($label);
        $binary = $db->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted");

        if (empty($binary)) {
            return 0;
        }

        $range = $this->_getRange();

        $lat = deg2rad(floatval($lat));
        $lon = deg2rad(floatval($lon));

        $binary .= pack('g*',
            $this->_mapRange($lat, $range['mnlt'], $range['mxlt'], -1, 1),
            $this->_mapRange($lon, $range['mnln'], $range['mxln'], -1, 1)
        );

        $list = unpack('g*', $binary);
        $value = "(".implode(', ', $list).")";

        $sql = "select {$this->knncols} from gridimage_embedding where knn({$this->vector}, $limit, $value) limit $limit";

        return $this->getImagesBySphinxQL($sql);
    }

    public function getImagesByCriteria(array $criteria, $limit = 100)
    {
        $where = [];
        $params = [];

        if (!empty($criteria['lat']) && !empty($criteria['lon']) && !empty($criteria['distance'])) {
            $lat = deg2rad(floatval($criteria['lat']));
            $lon = deg2rad(floatval($criteria['lon']));
            $distance = intval($criteria['distance']);
            $where[] = "GEODIST(wgs84_lat, wgs84_long, $lat, $lon) < $distance";
        }

        if (!empty($criteria['label'])) {
            $db = $this->_getDB();
            $quoted = $db->Quote($criteria['label']);
            $binary = $db->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted");

            if (!empty($binary)) {
                $list = unpack('g*', $binary);
                $value = "(".implode(', ', $list).")";
                $where[] = "knn({$this->vector}, $limit, $value)";
            }
        }

        if (!empty($criteria['keywords'])) {
            $where[] = "MATCH(?)";
            $params[] = $criteria['keywords'];
        }

        if (empty($where)) {
            return 0;
        }

        $sql = "select {$this->knncols} from gridimage_embedding where ".implode(' AND ', $where)." limit $limit";

        return $this->getImagesBySphinxQL($sql, true, ...$params);
    }
}
?>
