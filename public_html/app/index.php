<?php
// Mock revisions for local development if not provided
if (!isset($REVISIONS)) {
    $REVISIONS = [];
}

// Ensure $CONF and $LIVE exist for the revision function
if (!isset($CONF)) {
    $CONF = ['STATIC_HOST' => ''];
}
if (!isset($LIVE)) {
    $LIVE = [];
}

/**
 * Helper to get versioned URLs mirroring the site's logic
 */
function pma_revision($filename) {
    global $REVISIONS, $CONF, $LIVE;

    // Normalize path for lookup
    $lookupPath = $filename;
    if (strpos($lookupPath, '/app/') === 0) {
        $lookupPath = substr($lookupPath, 4); // Remove /app prefix if present in REVISIONS keys
    }

    if (isset($LIVE[$filename])) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filename;
        return $filename . "?" . (file_exists($fullPath) ? filemtime($fullPath) : time());
    } elseif (isset($REVISIONS[$filename])) {
        return $CONF['STATIC_HOST'] . preg_replace('/\.(js|css)$/', ".v{$REVISIONS[$filename]}.$1", $filename);
    } elseif (isset($REVISIONS[$lookupPath])) {
        return $CONF['STATIC_HOST'] . preg_replace('/\.(js|css)$/', ".v{$REVISIONS[$lookupPath]}.$1", $filename);
    } else {
        // Fallback to filemtime for development
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filename;
        if (file_exists($fullPath)) {
            return $filename . "?v=" . filemtime($fullPath);
        }
        return $filename;
    }
}

/**
 * Generates an Import Map for all JS files in the app
 */
function generate_import_map($dir, $basePath = '/app/js/') {
    $map = ['imports' => []];
    $fullDir = $_SERVER['DOCUMENT_ROOT'] . $dir;

    if (!is_dir($fullDir)) return json_encode($map);

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullDir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'js') {
            $relativePath = str_replace($fullDir, '', $file->getPathname());
            $appPath = $basePath . ltrim($relativePath, '/');
            $map['imports'][$appPath] = pma_revision($appPath);
        }
    }
    return json_encode($map, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PMA - Personal Management Application</title>
    <link rel="stylesheet" href="<?= pma_revision('/app/assets/css/style.css') ?>">

    <script type="importmap">
    <?= generate_import_map('/app/js/') ?>
    </script>
</head>
<body>
    <header id="main-header">
        <div class="nav-left">
            <button id="btn-home" class="nav-btn" data-route="/app/">
                <span class="icon-container"></span>
            </button>
        </div>
        <div class="nav-center">
            <h1 id="page-title">PMA</h1>
        </div>
        <div class="nav-right">
            <button id="btn-search" class="nav-btn" data-route="/app/search">
                <span class="icon-container"></span>
            </button>
            <button id="btn-profile" class="nav-btn" data-route="/app/profile">
                <span class="icon-container"></span>
            </button>
        </div>
    </header>

    <main id="app-container">
        <!-- Dynamic content goes here -->
    </main>

    <div id="iframe-container">
        <!-- Persistent iframes go here -->
    </div>

    <footer id="main-footer">
        <div class="nav-left">
             <button id="btn-upload" class="nav-btn" data-route="/app/upload">
                <span class="icon-container"></span>
            </button>
            <button id="btn-map" class="nav-btn" data-route="/app/map">
                <span class="icon-container"></span>
            </button>
        </div>
        <div class="nav-right">
            <button id="btn-menu" class="nav-btn">
                <span class="icon-container"></span>
            </button>
        </div>
    </footer>

    <div id="menu-overlay" class="hidden">
        <nav id="drawer-menu">
            <ul>
                <li><a href="/app/settings" data-route="/app/settings">Settings</a></li>
                <li><a href="/app/recent" data-route="/app/recent">Recent Submissions</a></li>
                <li><a href="/app/help" data-route="/app/help">App Help</a></li>
                <li><a href="/app/contact" data-route="/app/contact">Contact Us</a></li>
                <li><a href="/app/tos" data-route="/app/tos">Terms of Service</a></li>
            </ul>
        </nav>
    </div>

    <script type="module" src="<?= pma_revision('/app/js/main.js') ?>"></script>
</body>
</html>
