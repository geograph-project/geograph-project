<?php
/**
 * $Project: GeoGraph $
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2025 Barry Hunter (barry@geograph.org.uk)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once('imagelist.class.php');
require_once("geograph/vectors.inc.php"); // Ensure getTextEmbedding is available

/**
* Provides the ImageListKNN class, which interacts with Manticore Search (SphinxQL) for image search.
*
* @package Geograph
*/

/**
* ImageListKNN class
* Provides facilities for building a list of GridImage instances using KNN via Manticore Search.
* Extends ImageList for common image list functionalities.
*/
class ImageListKNN extends ImageList
{
    public $vector = 'image_vector'; // Default vector attribute name for image embeddings
    // Columns always needed: id, user_id, title (ideally realname, grid_reference too)
    public $knncols = 'id, user_id, realname, title, 1 as reference_index, grid_reference'; // Forcing ri=1 means always .org.uk links

    /**
     * Retrieves images similar to a given image ID using KNN in Manticore.
     *
     * @param int $id The ID of the image to find similar images to.
     * @param int $limit The maximum number of similar images to return.
     * @return int The number of images found and loaded into the list.
     */
    public function getImagesSimilarToID($id, $limit = 100)
    {
	//note, we dont need to use _getImageVectorValue/getImageEmbeddingById as manticore can do it directly!
        $id = intval($id);
        $sql = "SELECT {$this->knncols}, knn_dist() AS k FROM gridimage_embedding WHERE KNN ( {$this->vector}, $limit, $id ) LIMIT $limit";

        return $this->getImagesBySphinxQL($sql);
    }

    /**
     * Retrieves images similar to a given label using KNN in Manticore.
     *
     * @param string $label The label text to find similar images to.
     * @param int $limit The maximum number of similar images to return.
     * @return int The number of images found and loaded into the list. Returns 0 if label vector cannot be obtained.
     */
    public function getImagesSimilarToLabel($label, $limit = 100)
    {
        $value = $this->_getLabelVectorValue($label);
        if (is_null($value)) { // _getLabelVectorValue returns null if label embedding not found
            error_log('ImageListKNN:getImagesSimilarToLabel: Label vector not found for label: ' . $label);
            return 0;
        }

        $this->sql = $sql = "SELECT {$this->knncols}, knn_dist() AS k FROM gridimage_embedding WHERE KNN({$this->vector}, $limit, $value) LIMIT $limit";
        return $this->getImagesBySphinxQL($sql);
    }

    /**
     * Retrieves images by location using Manticore's GEODIST function, optionally combined with a label search.
     *
     * @param float $lat Latitude.
     * @param float $lon Longitude.
     * @param float $distance Search radius in meters.
     * @param string|null $label Optional label text for an additional KNN filter.
     * @param int $limit The maximum number of images to return.
     * @return int The number of images found and loaded into the list.
     */
    public function getImagesByLocation($lat, $lon, $distance, $label = null, $limit = 100)
    {
        $cols = $this->knncols;
        list($dist_col, $dist_where) = $this->_getGeoDistClause($lat, $lon, $distance);
        $cols .= ", $dist_col";
        $where = [$dist_where];

        if (!is_null($label)) {
            $value = $this->_getLabelVectorValue($label);
            if (is_null($value)) {
                error_log('ImageListKNN:getImagesByLocation: Label vector not found for label: ' . $label);
                return 0;
            }
            // Manticore KNN expects a limit as part of the function
            $where[] = "KNN({$this->vector}, " . ($limit * 10) . ", $value)"; // Deliberately oversample for now, increase limit for KNN filter
            $cols .= ", knn_dist() AS k"; // Add KNN distance column
        }

        $this->sql = $sql = "SELECT $cols FROM gridimage_embedding WHERE ".implode(' AND ', $where)." LIMIT $limit";
        return $this->getImagesBySphinxQL($sql);
    }

    /**
     * Returns a cached or live range for latitude and longitude in radians.
     * Used for mapping coordinates to vector space.
     *
     * @param bool $cached Whether to use cached range values.
     * @return array An associative array with min/max latitude and longitude in radians.
     */
    private function _getRange($cached = true)
    {
        if ($cached) {
            // These values should ideally be consistent with how the Manticore index was built.
            // Radians, as what Manticore uses!
            return ['mnlt' => '0.87028730', 'mnln' => '-0.23890854', 'mxlt' => '1.06221426', 'mxln' => '0.03133320'];
        }
        $sph = $this->_getSph();
        // This query assumes 'sample8' index has 'wgs84_lat' and 'wgs84_long' attributes
        return $sph->getRow("SELECT min(wgs84_lat) AS mnlt, min(wgs84_long) AS mnln, max(wgs84_lat) AS mxlt, max(wgs84_long) AS mxln FROM sample8");
    }

    /**
     * Maps a value from an input range to an output range.
     *
     * @param float $value The value to map.
     * @param float $in_min The minimum of the input range.
     * @param float $in_max The maximum of the input range.
     * @param float $out_min The minimum of the output range.
     * @param float $out_max The maximum of the output range.
     * @return float The mapped value.
     */
    private function _mapRange(float $value, float $in_min, float $in_max, float $out_min, float $out_max): float
    {
        // Avoid division by zero if input range is zero
        if ($in_max - $in_min === 0.0) {
            return $out_min; // Or handle error appropriately
        }
        return ($value - $in_min) * ($out_max - $out_min) / ($in_max - $in_min) + $out_min;
    }

    /**
     * Retrieves a label embedding vector from the database, or generates it via API if not found,
     * and formats it as a string for Manticore's KNN function.
     *
     * @param string $label The label text.
     * @return string|null A string like "(f1, f2, ...)" representing the vector, or null if not found/generated.
     */
    private function _getLabelVectorValue($label)
    {
	$vector = getTextEmbeddingWrapper($label);
	if (empty($vector)) {
		return null;
	}
        return "(" . implode(', ', $vector) . ")";
    }

    /**
     * Generates a GEODIST clause for Manticore Search.
     *
     * @param float $lat Latitude of the center point.
     * @param float $lon Longitude of the center point.
     * @param int $distance The radius for distance calculation in meters.
     * @return array An array containing the distance column definition and the WHERE clause.
     */
    private function _getGeoDistClause($lat, $lon, $distance)
    {
        $lat_rad = deg2rad(floatval($lat));
        $lon_rad = deg2rad(floatval($lon));
        $dist = intval($distance);
        return [
            "GEODIST(wgs84_lat, wgs84_long, $lat_rad, $lon_rad) AS distance",
            "distance < $dist"
        ];
    }

    /**
     * Appends mapped location coordinates to a binary vector and formats it as a string
     * for Manticore's plus_vector.
     *
     * @param string $binary The existing binary vector data.
     * @param float $lat Latitude.
     * @param float $lon Longitude.
     * @return string A formatted string like "(f1, f2, ..., lat_mapped, lon_mapped)".
     */
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
        return "(" . implode(', ', $list) . ")";
    }

    /**
     * Retrieves images by location and label using a combined 'plus_vector' in Manticore.
     *
     * @param float $lat Latitude.
     * @param float $lon Longitude.
     * @param string $label The label text.
     * @param int $limit The maximum number of images to return.
     * @param bool $incgeodist Whether to include GEODIST column for display (not used for filtering here).
     * @return int The number of images found and loaded into the list. Returns 0 if label vector cannot be obtained.
     */
    public function getImagesByLocationVector($lat, $lon, $label, $limit = 100, $incgeodist = false)
    {
        $vector_array = getTextEmbeddingWrapper($label);
        if (!empty($vector_array) && is_array($vector_array) && count($vector_array) > 0) {
            $binary = pack('g*', ...$vector_array);
        } else {
            error_log('ImageListKNN:getImagesByLocationVector: Label vector not found for label: ' . $label . ' from DB or API.');
            return 0;
        }

        if ($incgeodist) {
            list($dist_col, $dist_where) = $this->_getGeoDistClause($lat, $lon, 0);
            $this->knncols .= ", $dist_col"; //adds the distance column
            //ignoring the $where here, the whole point is to test using plus_vector! not to filter
        }

        $this->vector = 'plus_vector';
        $value = $this->_appendLocationToVector($binary, $lat, $lon);

        $this->sql = $sql = "SELECT {$this->knncols}, knn_dist() AS k FROM gridimage_embedding WHERE KNN({$this->vector}, $limit, $value) LIMIT $limit";
        return $this->getImagesBySphinxQL($sql);
    }

    /**
     * Retrieves images by various criteria including location, label, and keywords using Manticore.
     *
     * @param array $criteria An associative array of search criteria (e.g., 'lat', 'lng', 'dist', 'label', 'keywords').
     * @param int $limit The maximum number of images to return.
     * @return int The number of images found and loaded into the list.
     */
    public function getImagesByCriteria(array $criteria, $limit = 100)
    {
        $cols = $this->knncols;
        $where = [];
        $params = [];

        if (!empty($criteria['lat']) && isset($criteria['dist'])) {
            list($dist_col, $dist_where) = $this->_getGeoDistClause($criteria['lat'], $criteria['lng'], $criteria['dist']);
            $cols .= ", $dist_col"; //adds the distance column
            // only add to where clause if distance is explicitly set (can be 0)
	    if (!empty($criteria['distance'])) {
                $where[] = $dist_where;
            }
        }

        //todo  if (!empty($criteria['bbox'])

        if (!empty($criteria['label'])) {
            $value = $this->_getLabelVectorValue($criteria['label']);
            if (!is_null($value)) {
                $where[] = "KNN({$this->vector}, $limit, $value)";
                $cols .= ", knn_dist() as k";
            } else {
                 error_log('ImageListKNN:getImagesByCriteria: Label vector not found for label: ' . $criteria['label']);
            }
        }

        if (!empty($criteria['keywords'])) {
	        $cols .= ", WEIGHT() AS w";
            $where[] = "MATCH(?)";
            $params[] = $criteria['keywords'];
        }


	if (!empty($param['user_id'])) {
            $where[] = "user_id = ".intval($param['user_id']);
	}

        if (empty($where)) {
            return 0;
        }

        $this->sql = $sql = "SELECT $cols FROM gridimage_embedding WHERE ".implode(' AND ', $where)." LIMIT $limit";
        return $this->getImagesBySphinxQL($sql, true, ...$params);
    }


    public function getRawVectorsByCriteria(array $criteria, $limit = 30, $metadata=false) {
	die("todo getRawVectorsByCriteria");
	//call getKNNResults directly (rather than $this->getImagesBySphinxQL) as it already mimiks the s3vector output??
	//return getKNNResults(...);
    }
}
