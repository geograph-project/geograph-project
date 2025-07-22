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

include "3rdparty/s3vectors.inc.php";

/**
* Provides the ImageListKNN class
*
* @package Geograph
*/

/**
* ImageListKNN class
* Provides facilities for building a list of GridImage instances using KNN
*/
class ImageListS3Vector extends ImageList
{
    public $vector_bucket = 'geograph-vector-bucket';
    public $vector_index = 'image-clip';
    public $awsRegion = "us-east-1"; //s3vector, isnt available in all regions - so we have to define the region to use!

        //to an image id
    public function getImagesSimilarToID($id, $limit = 30, $type='image')
    {
        $queryPayload = [
            'vectorBucketName' => $this->vector_bucket,
            'indexName' => $this->vector_index,
            'queryVector' => ['float32' => $this->_getImageVectorValueList($id, $type)],
            'topK' => $limit,
            'returnDistance' => true,
            'returnMetadata' => false,
        ];
	return $this->_getImagesByPayload($queryPayload);
    }

       //just label
    public function getImagesSimilarToLabel($label, $limit = 30)
    {
        $queryPayload = [
            'vectorBucketName' => $this->vector_bucket,
            'indexName' => $this->vector_index,
            'queryVector' => ['float32' => $this->_getLabelVectorValueList($label)],
            'topK' => $limit,
            'returnDistance' => true,
            'returnMetadata' => false,
        ];
	return $this->_getImagesByPayload($queryPayload);
    }
	//label + lat/long using geodist - label is NOT optional
    public function getImagesByLocation($lat, $lon, $distance, $label, $limit = 30)
    {
	if ($distance < 10)
		$distance = 5000;

        $queryPayload = [
            'vectorBucketName' => $this->vector_bucket,
            'indexName' => $this->vector_index,
            'queryVector' => ['float32' => $this->_getLabelVectorValueList($label)],
            'topK' => $limit,
            'returnDistance' => true,
            'returnMetadata' => false,
	    'filter' => $this->_getFilters(array('lat'=>$lat,'lng'=>$lon,'dist'=>$distance))
        ];

	return $this->_getImagesByPayload($queryPayload);
    }

    function _getImagesByPayload($queryPayload) {
        $results = queryS3Vectors(
            $queryPayload,
            $this->awsRegion
        );

	if (!empty($results['vectors'])) {
		$ids = array();
	        foreach ($results['vectors'] as $i => $vector) {
			$ids[intval($vector['key'])] = $vector['distance'];
		}
		$count = $this->getImagesByIdList(array_keys($ids));
		foreach ($this->images as &$image) {
			$image->grid_reference .= sprintf(" (dist: %.1f)", $ids[$image->gridimage_id]);
		}
		return $count;
	} else {
	        echo "No vectors found for the query or an error occurred.\n";
		return 0;
    	}

    }

    private function _getFilters($param) {
        $queryFilter = array();

	    $parts = array(); //will be specifically a list if ANDed criteria
	    if (!empty($param['lat'])) {
		$delta = $this->calculateDegreesFromMeters($param['dist'], $param['lat']);

//	        $parts[] = array('slat' => array('$gte' => $param['lat']- $delta['lat'], '$lte' => $param['lat']+ $delta['lat']));

		$parts[] = array('slat' => array('$gte' => $param['lat']- $delta['lat']));
		$parts[] = array('slat' => array('$lte' => $param['lat']+ $delta['lat']));

//##	        $parts[] = array('slng' => array('$gte' => $param['lng']- $delta['lon'], '$lte' => $param['lng']+ $delta['lon']));
		$parts[] = array('slng' => array('$gte' => $param['lng']- $delta['lon']));
		$parts[] = array('slng' => array('$lte' => $param['lng']+ $delta['lon']));
	    }

	    if (!empty($param['user_id']))
	        $parts[] = array('user_id' => array('$eq' => intval($param['user_id'])));

	    if (!empty($parts)) {
	        if (count($parts) > 1) {
	        //multiple actully need nesting.
	           $queryFilter['$and'] =$parts;
	        } else {
	        //todo  if 1 then use directly?
	           $queryFilter = $parts[0];
	        }
	    }
	return $queryFilter;
    }

    private function _getImageVectorValueList($id,$type='image')
    {
        $db = $this->_getDB();
        $type = $db->Quote($type);
        $binary = $db->getOne("SELECT embeddings FROM gridimage_embedding WHERE gridimage_id = ".intval($id)." AND type=$type");

        if (empty($binary)) {
            return null;
        }

        return array_values(unpack('g*', $binary));
    }

    private function _getLabelVectorValueList($label)
    {
        $db = $this->_getDB();
        $quoted = $db->Quote($label);
        $binary = $db->getOne("SELECT embeddings FROM label_embedding WHERE label = $quoted");

        if (empty($binary)) {
            return null;
        }

        return array_values(unpack('g*', $binary));
    }

	//label + lat,lon using plus_vector
    public function getImagesByLocationVector($lat, $lon, $label, $limit = 30, $incgeodist = false)
    {
        die("todo");
    }

    public function getImagesByCriteria(array $criteria, $limit = 100)
    {
        die("todo");
    }

	function calculateDegreesFromMeters(float $meters, float $latitude): array
	{
	    // Earth's radius in meters (mean radius)
	    // This value is used to calculate the circumference of the Earth,
	    // which is then used to determine the length of one degree of latitude.
	    $earthRadiusMeters = 6371000;

	    // Approximate length of one degree of latitude in meters.
	    // This is relatively constant globally.
	    // Circumference = 2 * PI * R
	    // Degrees in a circle = 360
	    // Length of 1 degree latitude = (2 * PI * R) / 360
	    $metersPerDegreeLatitude = ($earthRadiusMeters * 2 * M_PI) / 360;

	    // Convert latitude from degrees to radians for trigonometric functions.
	    $latitudeRadians = deg2rad($latitude);

	    // Approximate length of one degree of longitude in meters at the given latitude.
	    // This varies significantly with latitude, decreasing as you move away from the equator.
	    // Length of 1 degree longitude = (2 * PI * R * cos(latitude)) / 360
	    $metersPerDegreeLongitude = $metersPerDegreeLatitude * cos($latitudeRadians);

	    // Calculate the change in latitude degrees for the given meters.
	    $deltaLatDegrees = $meters / $metersPerDegreeLatitude;

	    // Calculate the change in longitude degrees for the given meters.
	    // Handle potential division by zero if latitude is exactly +/- 90 degrees (poles).
	    // At the poles, a degree of longitude has effectively zero length, so delta_lon would be infinite.
	    // In practical terms for bounding boxes, if at the poles, delta_lon can be considered 180 (covers all longitudes).
	    $deltaLonDegrees = 0.0; // Default to 0
	    if (abs($metersPerDegreeLongitude) > 0.000001) { // Avoid division by very small numbers near poles
	        $deltaLonDegrees = $meters / $metersPerDegreeLongitude;
	    } else {
	        // If at or very near the poles, a small meter distance can cover all longitudes.
	        // For bounding box purposes, we might want to set a large value or handle this case specifically.
	        // Here, we'll just set a very large value to ensure it encompasses everything.
	        // A more robust solution might return a specific flag or throw an error for pole-centric queries.
	        $deltaLonDegrees = 180.0; // Effectively covers all longitudes if at the pole
	    }


	    return [
	        'lat' => $deltaLatDegrees,
	        'lon' => $deltaLonDegrees,
	    ];
	}

}

