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

require_once("3rdparty/s3vectors.inc.php"); //defines queryS3Vectors
require_once("geograph/vectors.inc.php"); //defines getTextEmbedding (and getImageEmbedding, getKNNResults, etc.)

/**
* Provides the ImageListS3Vector class, which interacts with S3Vectors for image search.
*
* @package Geograph
*/

/**
* ImageListS3Vector class
* Provides facilities for building a list of GridImage instances using S3Vector service for KNN.
* Extends ImageList for common image list functionalities.
*/
class ImageListS3Vector extends ImageList
{
    private $vector_bucket;
    public $vector_index = 'image-clip';
    public $model = 'clip';
    public $awsRegion = "us-east-1"; //s3vector, isn't available in all regions - so we have to define the region to use!

    /**
     * Constructor for ImageListS3Vector.
     * Initializes the vector bucket from global configuration.
     */
    function __construct() {
        global $CONF;
        $this->vector_bucket = $CONF['s3_vector_bucket'];
    }

    function setModel($model) {
	global $CONF;
	if (in_array($model,array('clip','pe'))) {
		$this->model = $model;
		$this->vector_index = "image-$model";
		if ($model == 'pe') //image-pe model is now moved to the local zone, no longer need to use the US preview
			$this->awsRegion = $CONF['s3_region'] ?? "eu-west-1";
		else
			$this->awsRegion = "us-east-1"; 
	}
    }

    /**
     * Retrieves images similar to a given image ID using S3Vectors.
     *
     * @param int $id The ID of the image to find similar images to.
     * @param int $limit The maximum number of similar images to return.
     * @param string $type The type of image embedding (e.g., 'image').
     * @return int The number of images found and loaded into the list.
     */
    public function getImagesSimilarToID($id, $limit = 30, $type='image')
    {
        $queryPayload = [
            'vectorBucketName' => $this->vector_bucket,
            'indexName' => $this->vector_index,
            'queryVector' => ['float32' => getImageEmbeddingById($id,$type, $this->model) ],
            'topK' => $limit,
            'returnDistance' => true,
            'returnMetadata' => false,
        ];
        return $this->_getImagesByPayload($queryPayload);
    }

    /**
     * Retrieves images similar to a given label using S3Vectors.
     *
     * @param string $label The label text to find similar images to.
     * @param int $limit The maximum number of similar images to return.
     * @return int The number of images found and loaded into the list.
     */
    public function getImagesSimilarToLabel($label, $limit = 30)
    {
        $queryPayload = [
            'vectorBucketName' => $this->vector_bucket,
            'indexName' => $this->vector_index,
            'queryVector' => ['float32' => getTextEmbeddingFromQuery($label, $this->model) ],
            'topK' => $limit,
            'returnDistance' => true,
            'returnMetadata' => false,
        ];
        return $this->_getImagesByPayload($queryPayload);
    }

    /**
     * Retrieves images by location and label using S3Vectors with geo-filtering.
     * Label is not optional for this search type.
     *
     * @param float $lat Latitude.
     * @param float $lon Longitude.
     * @param float $distance Search radius in meters. Will default to 5000m if less than 10m.
     * @param string $label The label text for the vector query.
     * @param int $limit The maximum number of images to return.
     * @return int The number of images found and loaded into the list.
     */
    public function getImagesByLocation($lat, $lon, $distance, $label, $limit = 30)
    {
        if ($distance < 10) {
            $distance = 5000;
        }
        $queryPayload = [
            'vectorBucketName' => $this->vector_bucket,
            'indexName' => $this->vector_index,
            'queryVector' => ['float32' => getTextEmbeddingFromQuery($label, $this->model) ],
            'topK' => $limit,
            'returnDistance' => true,
            'returnMetadata' => false,
            'filter' => $this->_getFilters(array('lat'=>$lat,'lng'=>$lon,'dist'=>$distance))
        ];

        return $this->_getImagesByPayload($queryPayload);
    }

    /**
     * Internal method to execute an S3Vectors query payload and populate the image list.
     *
     * @param array $queryPayload The S3Vectors query payload.
     * @return int The number of images found and loaded into the list, or 0 on error.
     */
    function _getImagesByPayload($queryPayload) {

        $results = queryS3Vectors(
            $queryPayload,
            $this->awsRegion
        );

        if (!empty($results['vectors'])) {
            $ids = array();
            foreach ($results['vectors'] as $vector) {
                // Ensure 'key' is present and can be converted to an int
                if (isset($vector['key'])) {
                    $ids[intval($vector['key'])] = $vector['distance'] ?? null; // Store distance if available
                }
            }
            if (empty($ids)) {
                error_log('ImageListS3Vector:_getImagesByPayload: No valid IDs found in S3Vectors response.');
                return 0;
            }
            $count = $this->getImagesByIdList(array_keys($ids));

            // Append distance to grid_reference for debugging/display, modifying image objects in place
            foreach ($this->images as &$image) {
                if (isset($ids[$image->gridimage_id])) {
                    //$image->grid_reference .= sprintf(" (%.2f%% Match)", (1-$ids[$image->gridimage_id])*100); //convert cosine dist to similarity percentage?
		    //better range conversion...
		    $similarity = $this->map($ids[$image->gridimage_id], 0.93, 0.60, 0, 100, false); //range determined expermentatlly, and might still be subject to change
		    $image->grid_reference .= sprintf(" (%.1f%% Match)", $similarity);
                }
            }
            return $count;
        } else {
            error_log('ImageListS3Vector:_getImagesByPayload: No vectors found for the query or an error occurred. Response: ' . json_encode($results));
            return 0;
        }
    }

    /**
     * Internal method to generate S3Vectors filter array from parameters.
     *
     * @param array $param An associative array containing filter parameters (e.g., 'lat', 'lng', 'dist', 'user_id').
     * @return array The S3Vectors compatible filter array.
     */
    private function _getFilters($param) {
        $queryFilter = array();
        $parts = array(); // Will be a list of ANDed criteria

        if (!empty($param['lat']) && !empty($param['lng']) && !empty($param['dist'])) {
            $delta = $this->calculateDegreesFromMeters($param['dist'], $param['lat']);
            $parts[] = array('slat' => array('$gte' => $param['lat'] - $delta['lat'], '$lte' => $param['lat'] + $delta['lat']));
            $parts[] = array('slng' => array('$gte' => $param['lng'] - $delta['lon'], '$lte' => $param['lng'] + $delta['lon']));
        }

	if (!empty($param['bbox'])) {
	    //xmin,ymin,xmax,ymax - same format as olbounds
	    list($xmin,$ymin,$xmax,$ymax) = explode(',', $param['bbox']);
            $parts[] = array('slat' => array('$gte' => floatval($ymin), '$lte' => floatval($ymax)));
            $parts[] = array('slng' => array('$gte' => floatval($xmin), '$lte' => floatval($xmax)));
	}

        if (!empty($param['user_id'])) {
	    if (is_array($param['user_id'])) {
                // a range of user_id's, perhaps doesn't sound useful, but can be used for sharding
                $parts[] = array('user_id' => array('$gte' => intval($param['user_id'][0]), '$lte' => intval($param['user_id'][1])));
            } elseif ($param['user_id'] < 0) {
                $parts[] = array('user_id' => array('$ne' => intval(abs($param['user_id']))));
            } else {
                $parts[] = array('user_id' => array('$eq' => intval($param['user_id'])));
            }
        }

	if (!empty($param['taken'])) {
	    if (is_array($param['taken'])) {
                $parts[] = array('taken' => array('$gte' => intval($param['taken'][0]), '$lte' => intval($param['taken'][1])));
            } else {
		//while here, might as well accept a single day
                $parts[] = array('taken' => array('$eq' => intval($param['taken'])));
            }
        }

        if (!empty($param['largest'])) {
            if (preg_match('/(\d+)\+/', $param['largest'], $m)) {
                // Match "1024+", using $gte (greater than or equal to)
                $parts[] = array('largest' => array('$gte' => intval($m[1])));
            } else {
                // Match "1024", using $eq (exact match)
                $parts[] = array('largest' => array('$eq' => intval($param['largest'])));
            }
        }

        foreach (['myriad', 'country', 'region', 'gridref'] as $field) {
            if (!empty($param[$field])) {
	        if (preg_match('/-([\w ]+)/',$param[$field],$m)) {
	                $parts[] = array($field => array('$ne' => $m[1], '$exists'=>true));
	        } else {
	                $parts[] = array($field => array('$eq' => $param[$field]));
	        }
            }
        }

        if (!empty($parts)) {
            if (count($parts) > 1) {
                $queryFilter['$and'] = $parts;
            } else {
                $queryFilter = $parts[0];
            }
        }
        return $queryFilter;
    }

    // --- Placeholder Methods for future implementation ---
    public function getImagesByLocationVector($lat, $lon, $label, $limit = 30, $incgeodist = false) {
        error_log('ImageListS3Vector: getImagesByLocationVector is a TODO and not implemented.');
        return 0;
    }

    // --- just pass critiera directly!
    public function getImagesByCriteria(array $criteria, $limit = 30) {

        $queryPayload = [
            'vectorBucketName' => $this->vector_bucket,
            'indexName' => $this->vector_index,
            'queryVector' => ['float32' => getTextEmbeddingFromQuery($criteria['label'], $this->model) ],
            'topK' => $limit,
            'returnDistance' => true,
            'returnMetadata' => false,
        ];
	$filter = $this->_getFilters($criteria);
	if (!empty($filter)) //might end up a empty array, which s3 does not like!
		$queryPayload['filter'] = $filter;

        return $this->_getImagesByPayload($queryPayload);
    }

    public function getRawVectorsByCriteria(array $criteria, $limit = 30, $metadata=false) {

        $queryPayload = [
            'vectorBucketName' => $this->vector_bucket,
            'indexName' => $this->vector_index,
            'queryVector' => ['float32' => $criteria['vector'] ?? getTextEmbeddingFromQuery($criteria['label'], $this->model) ],
            'topK' => $limit,
            'returnDistance' => true,
            'returnMetadata' => $metadata,
        ];
	$filter = $this->_getFilters($criteria);
	if (!empty($filter)) //might end up a empty array, which s3 does not like!
		$queryPayload['filter'] = $filter;

        return queryS3Vectors(
            $queryPayload,
            $this->awsRegion
        );
    }


    /**
     * Calculates the change in latitude and longitude degrees corresponding to a given distance in meters.
     * Useful for creating bounding box filters.
     *
     * @param float $meters The distance in meters.
     * @param float $latitude The current latitude in degrees (used for longitude calculation).
     * @return array An associative array with 'lat' (delta latitude in degrees) and 'lon' (delta longitude in degrees).
     */
    function calculateDegreesFromMeters(float $meters, float $latitude): array
    {
        $earthRadiusMeters = 6371000; // Earth's mean radius in meters

        // Approximate length of one degree of latitude in meters (constant)
        $metersPerDegreeLatitude = ($earthRadiusMeters * 2 * M_PI) / 360;

        $latitudeRadians = deg2rad($latitude);

        // Approximate length of one degree of longitude in meters at the given latitude
        $metersPerDegreeLongitude = $metersPerDegreeLatitude * cos($latitudeRadians);

        $deltaLatDegrees = $meters / $metersPerDegreeLatitude;

        $deltaLonDegrees = 0.0;
        if (abs($metersPerDegreeLongitude) > 0.000001) { // Avoid division by very small numbers near poles
            $deltaLonDegrees = $meters / $metersPerDegreeLongitude;
        } else {
            // At or very near the poles, a small meter distance can effectively cover all longitudes.
            // Setting a large value to encompass everything for bounding box purposes.
            $deltaLonDegrees = 180.0;
        }

        return [
            'lat' => $deltaLatDegrees,
            'lon' => $deltaLonDegrees,
        ];
    }

	/**
	 * Re-maps a number from one range to another.
	 *
	 * @param float $value The incoming value to be converted.
	 * @param float $start1 The lower bound of the value's current range.
	 * @param float $stop1 The upper bound of the value's current range.
	 * @param float $start2 The lower bound of the value's target range.
	 * @param float $stop2 The upper bound of the value's target range.
	 * @param bool $constrain If true, the resulting value will be clamped
	 * to stay within $start2 and $stop2. Defaults to true.
	 * @return float The converted and potentially constrained value.
	 */
	function map(float $value, float $start1, float $stop1, float $start2, float $stop2, bool $constrain = true): float {
	    // 1. Calculate the scaled value (same formula as before)
	    $normalized = ($value - $start1) / ($stop1 - $start1);
	    $scaled = $start2 + ($stop2 - $start2) * $normalized;
	    
	    // 2. Apply constraint if requested
	    if ($constrain) {
	        // Determine the actual minimum and maximum bounds of the target range
	        $min = min($start2, $stop2);
	        $max = max($start2, $stop2);
	        
	        // Clamp the scaled value to the determined bounds
	        $scaled = max($min, min($max, $scaled));
	    }
	    
	    return $scaled;
	}

}
