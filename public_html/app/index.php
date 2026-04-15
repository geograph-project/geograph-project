<?php

require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm('basic');

//but still, need to force use of autologin!
if (empty($_COOKIE['autologin']) && empty($_POST['remember_me'])) { //this is the actual login!
	//force a new login, the login form will force ticking remember_me !?!
	$USER->registered = false; //!
	$USER->login();
}


$mtime = filemtime(__FILE__);

/**
 * Helper to get versioned URLs mirroring the site's logic
 */
function pma_revision($filename) {
    global $REVISIONS, $CONF, $LIVE;

    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $filename;

		global $mtime;
		if (file_exists($fullPath))
			$mtime = max($mtime,filemtime($fullPath));

    if (isset($LIVE[$filename]) || !isset($REVISIONS[$filename])) {
        return $filename . "?" . (file_exists($fullPath) ? filemtime($fullPath) : time());
    } else {
        return preg_replace('/\.(js|css)$/', ".v{$REVISIONS[$filename]}.$1", $filename);
	//for now, doesnt work leading them of CDN. As on cloudflare doesnt really matter loading them from www anyway!
        return $CONF['STATIC_HOST'] . preg_replace('/\.(js|css)$/', ".v{$REVISIONS[$filename]}.$1", $filename);
    }
}

/**
 * Generates an Import Map for all JS files in the app
 */
function generate_import_map($dir, $basePath = '/app/js/') {
    $map = ['imports' => []];

	//might need to add other dependancies, as they converted to modules
	$appPath = "/js/location-selector.module.js";
	$map['imports'][$appPath] = pma_revision($appPath);

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
    <title>Geograph App</title>
    <link rel="stylesheet" href="<?= pma_revision('/app/assets/css/style.css') ?>">

    <script type="importmap">
    <?= generate_import_map('/app/js/') ?>
    </script>

    <script>
        window.GEOGRAPH_USER_PREFERENCES = {
	    user_id: <? echo intval($USER->user_id); ?>,
            uploadMaxDimension: <?= json_encode($USER->upload_size ?: 65536) ?>
        };
    </script>

    <link rel="shortcut icon" type="image/x-icon" href="<? echo $CONF['STATIC_HOST']; ?>/favicon.ico">
    <link rel="manifest" href="/app/manifest.json">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

</head>
<body>
    <header id="main-header">
        <div class="nav-left">
            <button id="btn-home" class="nav-btn" data-route="/app/">
                <span class="icon-container"></span>
            </button>
        </div>
        <div class="nav-center">
            <a data-route="/app/" id="page-title">Geograph</a>
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
        <div class="nav-left" id="bottom-buttons">
            <button id="btn-camera" class="nav-btn" data-route="/app/capture" aria-label="Take Photo">
                <span class="fa fa-camera"></span>
            </button>
            <button id="btn-upload" class="nav-btn" data-route="/app/upload">
                <span class="fa fa-cloud-upload"></span>
            </button>
            <button id="btn-submit" class="nav-btn" data-route="/app/uploaded">
                <span class="fa fa-pencil-square-o"></span>
            </button>
            <button id="btn-resume" class="nav-btn" data-route="/app/submit">
                <span class="fa fa-forward"></span>
            </button>
            <button id="btn-map" class="nav-btn" data-route="/app/map">
                <span class="fa fa-map-o"></span>
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
                <li><a href="/" target="_blank">Open Main Site</a></li>
                <li><a href="/app/settings" data-route="/app/settings">Settings</a></li>
                <li><a href="/app/recent" data-route="/app/recent">Recent Submissions</a></li>
                <li><a href="/app/help" data-route="/app/help">App Help</a></li>
                <li><a href="/app/contact" data-route="/app/contact">Contact Us</a></li>
                <li><a href="/app/contact" data-route="/app/contact" data-param="concern">Report a Concern</a></li>
                <li><a href="/app/tos" data-route="/app/tos">Terms of Service</a></li>
                <li><a href="/discuss/" target="_blank">Open Discussion Forum</a></li>
                <li><a href="https://forms.gle/T69BLFZyeQP9qfMKA" target="_blank">App Feedback</a></li>
	        <li><a href="javascript:history.go(0)" style=color:silver>Reload App (during dev)</a></li>
	        <li style=color:green id="versionNumber">V1.4<small>.<? echo date('mdH',$mtime); ?></small></li>
            </ul>
        </nav>
    </div>

    <script type="module" src="<?= pma_revision('/app/js/main.js') ?>"></script>
</body>
</html>
