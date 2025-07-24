<?php

// Ensure these are required. In a real application, you might have an autoloader.
require_once('imagelist.class.php'); // Assuming base ImageList class
require_once('imagelists3vector.class.php'); // ImageListS3Vector definition
require_once('imagelistknn.class.php');     // ImageListKNN definition

/**
 * Provides a factory method for creating ImageList instances.
 *
 * @package Geograph
 */
class ImageListFactory
{
    /**
     * Creates and returns an instance of an ImageList class (either ImageListS3Vector or ImageListKNN)
     * based on the presence of the 's3_vector_bucket' configuration.
     *
     * @param array $conf The global configuration array, expected to contain 's3_vector_bucket'.
     * @return ImageList An instance of ImageListS3Vector if 's3_vector_bucket' is configured,
     * otherwise an instance of ImageListKNN.
     * @throws Exception If ImageList base class or its required extensions are not found.
     */
    public static function createImageList(array $conf): ImageList
    {
        if (!empty($conf['s3_vector_bucket'])) {
            if (!class_exists('ImageListS3Vector')) {
                // This would typically mean a require_once is missing or class name is wrong.
                throw new Exception('ImageListS3Vector class not found. Ensure imagelists3vector.class.php is included.');
            }
            return new ImageListS3Vector();
        } else {
            if (!class_exists('ImageListKNN')) {
                 // This would typically mean a require_once is missing or class name is wrong.
                throw new Exception('ImageListKNN class not found. Ensure imagelistknn.class.php is included.');
            }
            return new ImageListKNN();
        }
    }
}
