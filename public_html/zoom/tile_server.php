<?php

// Basic security: Define a base directory for images
define('IMAGE_BASE_DIR', __DIR__ . '/sample_images/');
define('DEFAULT_TILE_SIZE', 256);

// Function to send JSON response
function send_json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Function to send an error response
function send_error_response($message, $statusCode = 400) {
    send_json_response(['error' => $message], $statusCode);
}

// Check if GD extension is available
if (!extension_loaded('gd')) {
    // Try to enable it if possible (might not work depending on server config)
    // Check if dl() function exists before calling it, as it might be disabled.
    if (function_exists('dl') && !dl('gd.so') && !dl('php_gd.dll')) {
         send_error_response('GD extension is not available and dl() failed to load it. Please enable it in your PHP configuration.', 500);
    } elseif (!function_exists('dl') && !extension_loaded('gd')) {
        // dl() doesn't exist and extension is not loaded
        send_error_response('GD extension is not available and dl() is disabled. Please enable it in your PHP configuration.', 500);
    }
}

// Get action from query parameters
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (empty($action)) {
    send_error_response('Action parameter is missing.');
}

// --- Handle get_image_info action ---
if ($action === 'get_image_info') {
    $image_path_param = isset($_GET['image_path']) ? $_GET['image_path'] : '';
    if (empty($image_path_param)) {
        send_error_response('image_path parameter is missing for get_image_info.');
    }

    // Sanitize and validate the image path
    $image_filename = basename($image_path_param); // Get only the filename
    $full_image_path = realpath(IMAGE_BASE_DIR . $image_filename);

    if ($full_image_path === false || strpos($full_image_path, realpath(IMAGE_BASE_DIR)) !== 0) {
        send_error_response('Invalid image path or image does not exist in the allowed directory.', 404);
    }

    if (!file_exists($full_image_path)) {
        send_error_response('Image file not found.', 404);
    }

    $image_size = getimagesize($full_image_path);
    if ($image_size === false) {
        send_error_response('Could not read image dimensions. The file may be corrupted or not a supported image format.', 500);
    }

    send_json_response([
        'width' => $image_size[0],
        'height' => $image_size[1],
        'tile_size' => DEFAULT_TILE_SIZE // Also send the server-defined tile size
    ]);
}
// --- Handle get_tile action ---
else if ($action === 'get_tile') {
    // Parameters for get_tile
    $image_path_param = isset($_GET['image_path']) ? $_GET['image_path'] : '';
    $zoom_level = isset($_GET['zoom_level']) ? (int)$_GET['zoom_level'] : null;
    $tile_x = isset($_GET['tile_x']) ? (int)$_GET['tile_x'] : null;
    $tile_y = isset($_GET['tile_y']) ? (int)$_GET['tile_y'] : null;

    // Basic validation for required parameters
    if (empty($image_path_param)) {
        send_error_response('image_path parameter is missing for get_tile.');
    }
    if ($zoom_level === null || $zoom_level < 0) {
        send_error_response('zoom_level parameter is missing or invalid.');
    }
    if ($tile_x === null || $tile_x < 0) {
        send_error_response('tile_x parameter is missing or invalid.');
    }
    if ($tile_y === null || $tile_y < 0) {
        send_error_response('tile_y parameter is missing or invalid.');
    }

    // Sanitize and validate the image path (similar to get_image_info)
    $image_filename = basename($image_path_param);
    $full_image_path = realpath(IMAGE_BASE_DIR . $image_filename);

    if ($full_image_path === false || strpos($full_image_path, realpath(IMAGE_BASE_DIR)) !== 0) {
        send_error_response('Invalid image path or image does not exist in the allowed directory for get_tile.', 404);
    }

    if (!file_exists($full_image_path)) {
        send_error_response('Image file not found for get_tile.', 404);
    }

    // Determine image type and load image
    $image_info = getimagesize($full_image_path);
    if ($image_info === false) {
        send_error_response('Could not read image info for get_tile.', 500);
    }
    $mime__type = $image_info['mime']; // Corrected variable name
    $source_image = null;

    switch ($mime_type) { // Corrected variable name
        case 'image/jpeg':
            $source_image = @imagecreatefromjpeg($full_image_path);
            break;
        case 'image/png':
            $source_image = @imagecreatefrompng($full_image_path);
            if ($source_image) { // Check if image creation was successful
                imagealphablending($source_image, true);
                imagesavealpha($source_image, true);
            }
            break;
        case 'image/gif':
            $source_image = @imagecreatefromgif($full_image_path);
            break;
        default:
            send_error_response('Unsupported image type: ' . $mime_type, 415); // Corrected variable name
    }

    if (!$source_image) {
        send_error_response('Failed to load image. It might be corrupted or an unsupported format.', 500);
    }

    $source_width = imagesx($source_image);
    $source_height = imagesy($source_image);

    // Tile Calculation Logic (as in the original prompt)
    // Higher zoom_level means more zoomed IN.
    // zoom_level 0: 1 tile pixel = 1 source image pixel (if DEFAULT_TILE_SIZE is output size)
    // zoom_level 1: 1 tile pixel = 0.5 source image pixels (source region is smaller, scaled up)
    $pixels_in_source_covered_by_tile_edge = DEFAULT_TILE_SIZE / pow(2, $zoom_level);

    $src_x = $tile_x * $pixels_in_source_covered_by_tile_edge;
    $src_y = $tile_y * $pixels_in_source_covered_by_tile_edge;
    $src_w = $pixels_in_source_covered_by_tile_edge;
    $src_h = $pixels_in_source_covered_by_tile_edge;

    // Create the destination tile image
    $dest_tile_image = imagecreatetruecolor(DEFAULT_TILE_SIZE, DEFAULT_TILE_SIZE);
    if ($mime_type === 'image/png') { // Corrected variable name
        imagealphablending($dest_tile_image, false);
        imagesavealpha($dest_tile_image, true);
        $transparent_color = imagecolorallocatealpha($dest_tile_image, 0, 0, 0, 127);
        imagefill($dest_tile_image, 0, 0, $transparent_color);
        // imagecolortransparent($dest_tile_image, $transparent_color); // Not strictly needed if alpha saved
    } else {
        $white = imagecolorallocate($dest_tile_image, 255, 255, 255);
        imagefill($dest_tile_image, 0, 0, $white);
    }

    imagecopyresampled(
        $dest_tile_image,
        $source_image,
        0, 0,
        (int)round($src_x), (int)round($src_y),
        DEFAULT_TILE_SIZE, DEFAULT_TILE_SIZE,
        (int)round($src_w), (int)round($src_h)
    );

    // Output the tile
    $expires_seconds = 60 * 60 * 24 * 7; // Cache for 7 days
    header('Cache-Control: public, max-age=' . $expires_seconds); // Removed extra 'public'
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires_seconds) . ' GMT');

    switch ($mime_type) { // Corrected variable name
        case 'image/jpeg':
            header('Content-Type: image/jpeg');
            imagejpeg($dest_tile_image, null, 85);
            break;
        case 'image/png':
            header('Content-Type: image/png');
            imagepng($dest_tile_image, null, 9); // Max PNG compression for smallest size
            break;
        case 'image/gif':
            header('Content-Type: image/gif');
            imagegif($dest_tile_image);
            break;
        default:
            imagedestroy($source_image);
            imagedestroy($dest_tile_image);
            send_error_response('Unsupported image type for output.', 500); // Should have been caught earlier
            break;
    }

    // Free memory
    imagedestroy($source_image);
    imagedestroy($dest_tile_image);
    exit;

}
// --- Unknown action ---
else {
    send_error_response('Unknown action: ' . htmlspecialchars($action));
}

?>
