<?php

require_once('geograph/global.inc.php');
require_once('geograph/uploadmanager.class.php');

init_session();

$USER->mustHavePerm('basic');

function failMessage($text, $um) {
    print '<meta name="viewport" content="width=device-width, initial-scale=1">';
    
    // Minimal CSS for mobile legibility
    print "<style>
	body { font-family: -apple-system, system-ui, sans-serif;  line-height: 1.6;  color: #333;  padding: 16px;  margin: 0; background-color: #f4f7f6; }
	.card { max-width: 500px;  margin: 20px auto;  background: #fff;  padding: 24px;  border-radius: 12px;  box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
	h3 { color: #d93025; margin-top: 0; font-size: 1.4rem; }
	p { margin-bottom: 16px; font-size: 1rem; }
	.explanation { background: #fff3cd;  padding: 12px;  border-radius: 6px;  font-size: 0.95rem;  color: #856404;  border-left: 4px solid #ffeeba; }
	.btn-group { display: flex; flex-direction: column; gap: 12px; margin-top: 20px; }
	.btn { display: block;  text-align: center; padding: 14px;  text-decoration: none;  border-radius: 8px;  font-weight: 600; }
	.btn-primary { background: #1a73e8; color: #fff; }
	.btn-secondary { background: #e8eaed; color: #3c4043; }
    </style>";

    print "<div class='card'>";
    print "<h3>" . htmlentities($text) . "</h3>";

    if (!empty($um->existing)) {
        $existing = intval($um->existing);

	echo "<p class='explanation'><strong>Note:</strong> The most likely cause is simply that the form was submitted multiple times in quick succession. It doesn't really matter - we already have your submission below.</p>";

        echo "<p>This image was already processed as ID: <strong>$existing</strong>.</p>";
	echo "<div class='btn-group'>";
        echo "  <a href='/photo/$existing' class='btn btn-primary'>View the Photo Page</a>";
    } else {
        print "<p>Please go back, correct the values, and press <strong>'I Agree'</strong> again.</p>";
        echo "<div class='btn-group'>";
    }
    echo "  <a href='/app/' target='_top' class='btn btn-secondary'>Back to Home</a>";
    echo "</div>";

    print "</div>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($_POST['email'])) { //aovid a login request!

    $db = GeographDatabaseConnection(false);

    $GLOBALS['STARTTIME'] = microtime(true);

    $um = new UploadManager();
    $gs = new GridSquare();

    $um->setUploadId($_POST['upload_id']);

    $gs->setByFullGridRef($_POST['grid_reference']);
    if (!empty($gs->errormsg)) {
        failMessage($gs->errormsg, $um);
    }

    //need to deal with potential partial dates, while allowing for missing
    $partial = trim($_POST['date_partial'] ?? '');
    $standard = trim($_POST['imagetaken'] ?? '');
    $takendate = parseDate(!empty($partial) ? $partial : $standard); //will return 0000-00-00 for empty!

    if (!$takendate) {
        failMessage("Invalid date format or out of range (must be > 1800)", $um);
    } elseif ($takendate > date('Y-m-d')) {
        failMessage("Date taken in future", $um);
    } else {
        $um->setTaken($takendate);
    }

    // set up attributes from uploaded data
    $um->setSquare($gs);
    $um->setViewpoint($_POST['photographer_gridref']);
    if (!empty($_POST['use6fig']))
        $um->setUse6fig(stripslashes($_POST['use6fig']));
    $um->setDirection($_POST['view_direction']);
    $um->setTitle($_POST['title']);
    $um->setComment($_POST['comment']);

    if (!empty($_POST['imageclass'])) {
        if (preg_match('/subject:(.*)/',$_POST['imageclass'],$m)) {
            $um->setSubject($m[1]);
        } else
            $um->setClass($_POST['imageclass']);
    }

    if (!empty($_POST['subject']))
        $_POST['tags'][] = "subject:".$_POST['subject'];
    if (!empty($_POST['vfov']))
        $_POST['tags'][] = "vfov:".$_POST['vfov'];
    if (!empty($_POST['hfov']))
        $_POST['tags'][] = "hfov:".$_POST['vfov'];

    if (!empty($_POST['tags'])) {
        if (is_array($_POST['tags'])) {
            $um->setTags($_POST['tags']);
        } else {
            $um->setTags(preg_split('/\s*;\s*/',trim(utf8_decode($_POST['tags']))));
        }
    }
    if (!empty($_POST['contexts'])) {
        $um->setContexts($_POST['contexts']);
    }

    if ($_POST['pattrib'] == 'other') {
        $um->setCredit(stripslashes(utf8_decode($_POST['pattrib_name'])));
    } elseif ($_POST['pattrib'] == 'self') {
        $um->setCredit('');
    }

    $um->setLargestSize($_POST['largestsize']);


    if (!empty($um->errormsg)) {
        failMessage($um->errormsg, $um);
    } else {
        // so far so good... can we commit the submission?
        $method = 'app';
        $rc = $um->commit($method);
        if ($rc == "") {

                        //clear user profile
                        $ab=floor($USER->user_id/10000);
                        $smarty = new GeographPage;
                        $smarty->clear_cache(null, "user$ab|{$USER->user_id}");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submission Status</title>
    <link rel="stylesheet" href="<? echo smarty_modifier_revision('/app/assets/css/style.css'); ?>">
    <style>
        body { padding:10px; text-align: center; background-color: #e7ffe7;}
        .idNum { font-size:2em; font-family: math, sans-serif; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body>

    <h3 align=center>Submission Successful</h3>
    <br>
    <hr>
    <br>
    <p>ID: <a class="idNum" href="https://www.geograph.org.uk/photo/<?= (int)$um->gridimage_id ?>" target="_blank"><?= (int)$um->gridimage_id ?></a> <span class=nowrap>(open photo page in browser)</span></p>

    <?php if ($need_larger): ?>
        <br>
        <b>If you now need to add the full size Panorama</b>:
        <a href="/resubmit.php?id=<?= (int)$um->gridimage_id ?>" target="_blank" class="btn btn-primary">Add Larger Image</a>
        (Opens in browser)<br><br>
    <?php endif; ?>

    <button class="btn btn-primary" onclick="navigateTo('/app/uploaded')">Submit Another</button>

    <button class="btn btn-primary" onclick="navigateTo('/app/upload')">Upload Another</button>

    <a href="/app/" class="btn" target="_top" onclick="navigateTo('/app/home'); return false;">Back To Home</a>

    <script type="module">
	import { navigateTo, updateAppState, setupSettingsListener } from '/app/js/utils.js?<? echo filemtime('js/utils.js'); ?>';

	//so the page can ues it
	window.navigateTo = navigateTo;

        //set this up right away
        setupSettingsListener();

        //now the submission is finished, need to tell the app, there is no longer an active upload_id - none is a special value
        window.addEventListener('DOMContentLoaded', function() {
            updateAppState({upload_id: 'none'});
        });
    </script>

	<? if (!empty($_POST['filename'])) { ?>
		<script src="<?php echo smarty_modifier_revision("/js/Geograph.MediaDatabase.class.js"); ?>"></script>
		<script>
			const dbHistory = new MediaDatabase();
			dbHistory.updateMediaHistory(<? echo json_encode($_POST['filename']); ?>, {
			    status: 'submitted'
			});
		</script>
	<? } ?>

</body>
</html>
<?php

        } else {
            failMessage($rc, $um);
        }
    }

    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary: #007AFF;
            --success: #28a745;
            --bg: #f8f9fa;
            --accent: #6c757d;
        }

        body { font-family: -apple-system, system-ui, sans-serif; background: var(--bg); margin: 0; padding: 0; }

        /* Very important this is on the preview image at least, so users see image needs rotating */
        img {
            image-orientation: none;
        }

        .btn { padding: 14px 28px; border-radius: 12px; border: none; cursor: pointer; touch-action: manipulation; font-weight: 600; transition: all 0.2s; display: inline-block; margin: 8px 0; font-size: 16px; }
        .btn-primary { background: var(--primary); color: white; width: 100%; box-sizing: border-box; }


        /* 1. The Main Header (Scrolls normally) */
        .main-header {
            background: #fff;
            border-bottom: 1px solid #ddd;
            text-align: center;
            width: 100%;
        }
        .preview-img-large { 
            max-width: 100%; 
            max-height: 640px; 
            display: block; 
            margin: 0 auto; 
        }
        .controls { padding: 15px; }
        .controls button { line-height:1.0 }
        .controls button span { font-size:1.9em }

        /* 2. The Sticky Bar (Hidden by default) */
        .sticky-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #fff;
            border-bottom: 2px solid #007bff;
            display: flex;
            align-items: center;
            padding: 0 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 10000;

            transform: translateY(-100%);
            opacity:0;
            pointer-events: none;
        }
        .sticky-bar.visible { transform: translateY(0); opacity:1 }
        /* only animate when ready */
        .sticky-bar.ready {
            /* Hidden State */
            transition: transform 0.2s ease-in-out;
        }

        #form-status-bar {
            margin-left: auto;
            color: gray;
        }
        #form-status-bar.complete {
            color: #1b5e20;
            background-color: #e8f5e9;
            border-color: #c8e6c9;
            font-weight: bold;
        }
        .sticky-bar:has(#form-status-bar.complete) {
            border-color: #52a52d;
        }

        .thumb-img { height: 45px; width: auto; border-radius: 4px; margin-right: 12px; }
        .sticky-title { font-weight: bold; color: #333; }

        /* Content spacing */
        .content { padding: 20px; max-width: 600px; margin: 0 auto; }

@media screen and (max-width: 500px) {
        .content {
                padding:20px 2px;
        }
}

/* Form Inputs */

        label { display: block; margin: 15px 0 5px; font-weight: bold; color: #555; touch-action: manipulation; user-select: none;  }
        input, textarea { 
            width: 100%; padding: 12px; margin-bottom: 10px; 
            border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; 
            font-size:1.1em;
        }

        input, select, textarea {
            /* Set this to the height of your sticky header + a bit of padding */
            scroll-margin-top: 120px;
            font-family: Georgia, Verdana, Arial, serif; /* set this as this is what used for display in main site!! */
        }

        @media screen and (max-height: 500px) and (orientation: landscape) {
        	/* only rows=5, but just to make sure */
        	textarea {
		        scroll-margin-top: 66px;
        		max-height:50svh;
        	}
            textarea:focus-within {
                 scroll-margin-top: 10px; /* actully browser likly to have hidden sticky header */
            }
        }

input:invalid, select:invalid, #contexts:invalid, .input-invalid {
    border: 1px solid #ff0000;
    background-color: #f5f5f0;
}

.field-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 5px;
}

.recent-select {
    padding: 2px 5px;
    max-width: 150px;
    border-radius: 4px;
    border: 1px solid #ccc;
    background: #f9f9f9;
}

/* suggestion-pill is used for placename ugestions on title/description */

#suggestion-pill-bar {
    width: 100%;
    display: flex;
    overflow-x: auto;
    gap: 2px;
    padding: 4px;
    background: #f4f4f4;
    height: 44px;
    align-items: center;
}
#suggestion-pill-bar.no-results {
    display: none;
}

.suggestion-pill {
    padding: 6px;
    border-radius: 20px;
    border: 1px solid #ccc;
    background: white;
    cursor: pointer;
    white-space:nowrap;
    user-select: none;
}

@media (any-pointer: coarse) {

    #suggestion-pill-bar {
        position: fixed;
        left: 0;
        z-index: 9999;
        gap: 8px;

        height: 50px; /* Slightly taller for easier tapping */

        /* Use translateZ to force hardware acceleration and smooth movement */
        transform: translateZ(0);
        will-change: transform; /* Hardware acceleration optimization */
        transition: transform 0.2s ease-out;

        touch-action: manipulation;
    }
    .suggestion-pill {
        padding: 6px 12px;
    }
}

.suggestion-pill:active {
     background: #e0e0e0;
}

.hidden { display: none }

/* contexts multi-select */

#contexts {
    width: 100%;
    padding-left: 3px; /* Gives the items room to breathe */
    font-size: 16px; /* Prevents iOS from auto-zooming on focus */
    line-height: 1.5;
    border: 1px solid #ccc;
    border-radius: 6px;
    margin-bottom: 10px;
}
#contexts option {
    font-weight: bold;
    color:#555;
}
#contexts optgroup {
    padding-top: 10px;
    padding-bottom: 5px;
    color: var(--accent);
    font-weight: normal;
}

/* subject/tag autocomplete */

#subject-input, #tag-search {
    margin-bottom:0;
}
.tag-input-container {
    background-color:white;
    border-radius:6px;
}

/* suggestion-item's are the items below subject/tag input */
.suggestion-item {
    --display: inline; /* Allows them to flow next to each other */
    padding: 6px 12px;     /* More padding for better touch targets */
    margin: 4px;
    cursor: pointer;
    touch-action: manipulation; /* Optimizes for touch, removing the "300ms tap delay" */
    user-select: none;
    white-space: nowrap;
    border-radius: 15px;   /* Rounded pill look */
    border: 1px solid #aaa;
    background: #fff;
    background-color: #f5f5f0;
    transition: background 0.2s;
}
.suggestion-item:hover {
    background: #e0e0e0;
}
.suggestion-item:active {
    background: #007bff;
    color: white;
    border-color: #0056b3;
}

.suggestion-item strong {
    font-weight: 500;
    text-decoration: underline;
    text-decoration-color: silver;
    pointer-events: none;
}

.add-new-tag {
    background-color: #e6fffa; /* Soft green */
    border: 1px dashed #38b2ac; /* Dashed border to imply 'creating' */
    color: #2c7a7b;
    font-weight: bold;
}

div#active-tags {
	line-height:35px;
}

/* tag-pill are the actual selected tag(s) */
span.tag-pill {
    padding: 6px 12px;
    margin: 4px;
	white-space: nowrap;
    font-weight: 500;

    border-radius: 15px;   /* Rounded pill look */
    border: 1px solid #aaa;
    background: #fff;
}
span.tag-pill button {
	border:none;
    color:red;
	margin-left: 6px; /* Give the 'X' some space */
	padding:0;
}

/* Compact Flag Container */
    .flag-container {
        background: #f9f9f9;
        border-radius: 8px;
        --border: 1px solid #eee;
    }

    .flag-container label {
        font-weight: bold;
        display: block;
        margin-bottom: 10px;
    }

    label.flag-item {
        display: flex;
        align-items: flex-start; /* Keeps text aligned if it wraps */
        --margin-bottom: 8px;
        cursor: pointer;
        touch-action: manipulation;
    }

    .flag-item input {
	width:inherit;
        margin-top: 4px; /* Align checkbox with the top of the text */
        margin-right: 10px;
        transform: scale(1.2); /* Slightly larger for easier tapping */
    }

    .flag-item span {
        font-weight: normal;
        line-height: 1.4;
    }

/* more general forms */

    button {
        padding: 8px 16px; border: 1px solid #007bff; background: #fff;
        color: #007bff; border-radius: 4px; cursor: pointer; font-weight: bold; touch-action: manipulation;
    }
    button:active { background: #007bff; color: #fff; }

	fieldset {
		margin-top:20px;
		border-radius:8px;
	    background-color:#f5f5f0; padding:3px;
        border: 1px solid #eee;
	}
	fieldset legend {
		padding:5px;
		margin-left:10px;
		font-weight:700;
	}
	#licence input[type=radio] {
	    width:inherit;
	}
	#licence input[type=text] {
	    background-color:white;
	}

	dialog::backdrop {
	    background: rgba(0, 0, 0, 0.5);
	}

	dialog {
	    /* Ensures it doesn't look like a standard browser alert */

	    max-height: 85vh; /* Give a bit more vertical breathing room */
	    max-width: 90vw;  /* Prevents it from hitting the screen edges on mobile */
	    width: 500px;
	    padding: 20px;
	    border-radius: 12px;
	    border: 1px solid #ccc;
	    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
	    background-color:#e4e4fc;
	}
    dialog button {
        display:block;
        width:100%;
    }

	.nowrap {
		white-space:nowrap;
	}


    /* Map */

#maparea {
    display: flex;
    flex-direction: column; /* Default: Stacked */
    align-items: center;    /* This centers the 350px map horizontally */

    margin-left: auto;      /* The "Magic" centering combo */
    margin-right: auto;
    width: fit-content;     /* Crucial: prevents the div from being 100% wide */

    gap: 10px;              /* Space between map and controls */
    justify-content: flex-start; /* Keeps them grouped together */
    align-items: flex-start;    /* Prevents stretching */
}

#map {
    max-width: min( 350px , 100% );
    width: 350px;
    margin:auto;
    aspect-ratio: 1 / 1;
    border:1px solid silver; border-radius:5px;
    flex-shrink: 0;         /* Prevents the map from squishing */
}

#maparea .controls {
    text-align:center;
    padding:0;
}

@media all and (max-width: 450px) {
    #maparea #map {
	margin:1px; /*undo auto, to allow the map to be left centered, to give more area on side to swipe the page */
    }
}

/* Switch to Row layout in Landscape */
@media (orientation: landscape) {
    #maparea {
        flex-direction: row; /* Controls move to the right */
        align-items: stretch;
    }

    #maparea .controls {
        max-width: 250px;
        width: 100%;            /* Allows it to be smaller than 200px if needed */
        flex-shrink: 1;         /* Allows controls to shrink if screen is tiny */
        word-wrap: break-word;  /* Prevents text from forcing the width wider */

        /* Optional: make controls match the map height */
        max-height: 350px;
        overflow-y: auto;
    }
}

#maparea input[type=radio], #maparea input[type=checkbox] { /* inside leaflet layer switcher */
    width:inherit;
}

#maparea input[type=text] {
    width:180px;
    font-family: sans-serif;
    color:gray;
    background-color:var(--bg);
    border:1px solid silver;
}
#maparea label {
    display:unset;
}
#maparea input.active {
        color:black;
        background-color:white;
        border:1px solid black;
}
#maparea input#photographer_gridref.active{
    border:2px solid #210b7b;
}
#maparea input#grid_reference.active{
    border:2px solid #5300ff;
}
#maparea label.active {
    background-color:yellow;
}

.easy-button-container button {
    padding:0;
}
.easy-button-container button span {
    line-height:30px;
}

.notes-bar {
    border-radius: 10px;
    padding: 10px;
    background-color: #eee;
    margin-top: 10px;
}

#orientation_message {
    background-color:pink;
}
.field-header .info-icon {
    position: relative; top:0 !important; left:10px;
}

.field-header .info-icon::after {
    left:30px; right:unset;
}

    </style>

    <!-- THIS IS A SEPERATE STYLE BLOCK, THAT GETS DUPLCIATED INTO PARENT -->
	<style id="styleforRemoteBlock">
	    #remoteEditorOverlay {
	        position: fixed; left: 0; width: 100vw; background: white;
	        z-index: 999999; display: none; flex-direction: column; overflow: hidden;
	    }
	    .remote-header { 
	        height: 48px; background: #f8f9fa; display: flex; 
	        justify-content: space-between; align-items: center; padding: 0 12px;
	        border-bottom: 1px solid #ddd;
	    }
	    .toggle-group { display: flex; background: #eee; border-radius: 6px; padding: 2px; }
	    .toggle-btn { 
	        border: none; padding: 6px 12px; font-size: 13px; border-radius: 4px; 
	        cursor: pointer; background: transparent; 
	    }
	    .toggle-btn.active { background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.2); font-weight: bold; }
	    
	    .remote-body { position:relative; flex: 1; display: flex; flex-direction: column; padding: 0px; background-color:#f8f9fa; }
	    
	    /* Input visibility controls */
	    #remoteTitleInput, #remoteDescArea { width: 100%; border: 1px solid #eee; font-size: 18px; outline: none; box-sizing: border-box; font-family: Georgia, Verdana, Arial, serif}
	    #remoteTitleInput { height: 45px; padding: 0 6px; }
	    #remoteDescArea { flex: 1; padding: 6px; resize: none; max-width:652px; }

	    .remote-suggestions { 
	        height: 50px; --background: #222; color: white; display: flex; 
	        align-items: center; gap: 10px; padding: 0 10px; overflow-x: auto; flex-shrink: 0;
	    }
	    .suggestion-pill { background: #444; color:white; padding: 6px 12px; border-radius: 4px; font-size: 13px; white-space: nowrap; }

	/* Portrait: Show both at once */
	@media (orientation: portrait) {
	    .remote-body {
	        display: flex;
	        flex-direction: column;
	        gap: 15px;
	    }
	    #remoteTitleInput { display: block !important; }
	    #remoteDescArea { display: block !important; flex: 1; }
	    
	    /* Hide the toggles in portrait as they aren't needed */
	    .toggle-group { display: none !important; }
	}

/* The warning state on the input itself */
.input-warning {
    background-color: #fff9c4 !important;
    border: 1px solid #fbc02d !important;
}

/* The Icon as a sibling */
.info-icon {
    position: absolute;
    /* Use 'right' and a top offset based on the input's position */
    right: 12px;
    background: #fbc02d;
    color: #000;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    text-align: center;
    line-height: 22px;
    font-weight: bold;
    cursor: pointer;
    z-index: 10;
    display: none;
}

/* Position specifically for title vs desc */
#titleInfo { top: 12px; }
#descInfo { bottom: 62px; } /* Adjust based on your button bar height */

.info-icon::after {
    content: attr(data-error);
    display: none;
    position: absolute;
    right: 12px;
    background: #333;
    color: #fff;
    padding: 5px 10px;
    border-radius: 4px;
    white-space: nowrap;
}
.info-icon:active::after { display: block; }

    </style>

    <link rel="stylesheet" type="text/css" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo smarty_modifier_revision("/js/mappingLeaflet.css"); ?>" />
    <link rel="stylesheet" href="<?php echo smarty_modifier_revision("/js/leaflet-search-master/src/leaflet-search.css"); ?>" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.67.0/dist/L.Control.Locate.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-geotag-photo@0.5.1/dist/Leaflet.GeotagPhoto.css" />

    <script src="<?php echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>
    <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.7.0/proj4.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4leaflet/1.0.2/proj4leaflet.min.js"></script>

    <script src="<?php echo smarty_modifier_revision("/js/Leaflet.MetricGrid.js"); ?>"></script>
    <script src="<?php echo smarty_modifier_revision("/js/mappingLeaflet.js"); ?>"></script>

    <script src="<?php echo smarty_modifier_revision("/js/L.Control.Locate.js"); ?>"></script>
    <script src="https://unpkg.com/leaflet-geotag-photo@0.5.1/dist/Leaflet.GeotagPhoto.min.js"></script>
    <script src="<?php echo smarty_modifier_revision("/js/leaflet-search-master/src/leaflet-search.js"); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet-easybutton@2/src/easy-button.js"></script>
    <script src="<?php echo smarty_modifier_revision("/js/Leaflet.GeographGeocoder.js"); ?>"></script>
    <script src="<?php echo smarty_modifier_revision("/js/Leaflet.GeographRecentUploads.js"); ?>"></script>


</head>
<body>

<div id="top-boundary"></div>

<div class="main-header" id="mainHeader">
    <div id="image-dimensions" style="padding: 10px; font-weight: bold; background: #eee;"></div>
    <img id="imgLarge" class="preview-img-large" src="" alt="Full Preview">
    <div class="controls">
        <button onclick="rotateImage(270)"><span>&#8634;</span> Rotate Left</button>
        <button onclick="rotateImage(90)">Rotate Right <span>&#8635;</span></button>
    </div>

    <div id="orientation_message" style="display:none">
	Browsers and devices handle image orientation differently. If you are seeing this warning, please rotate the image sideways (if 
	needed) and back to upright, <b>even if it looks OK to you</b>. This will ensure it is displayed correctly across all devices.
    </div>
</div>

<div id="stickyBar" class="sticky-bar">
    <img id="imgThumb" class="thumb-img" src="" alt="Thumbnail">
    <div class="sticky-title" id="displayTitle"></div>
    <div id="form-status-bar"></div>
</div>

<form method="post" action="/app/submit.php?done" name="theForm" id="theForm">
	<input type=hidden name="upload_id" value="">
	<input type=hidden name="largestsize" value="65536">
	<input type=hidden name="filename" id="filename" value="">

    <div style="text-align:center">
        <button type="button" onclick="openModal('map-modal')"
        style="background:none; border:none; color:blue; text-decoration:underline;">How to use this map &gt;</button>
    </div>

	<div id="maparea">

        <div id="map"></div>

        <div class="controls">
                <span class=nowrap><label for=photographer_gridref class="gr active" style="color:#210b7b">Camera</label>:
                        <input type="text" name="photographer_gridref" id="photographer_gridref" value="" size="12" maxlength="14"
                         pattern="^[A-Za-z]{1,2}\s*\d{1,5}\s*\d{1,5}$" title="Optional: 1-2 letters plus an even number of digits (e.g. TQ 123 456 or O 12 34)"
                         onblur="checkGridref(this)" placeholder="(Camera Location)" class="active"/></span>
                &nbsp;
                <span class=nowrap><label for=grid_reference class="gr" style="color:#5300ff;">Subject</label>:
                        <input type="text" name="grid_reference" value="" id="grid_reference" size="12" maxlength="14"
                         required pattern="^[A-Za-z]{1,2}\s*\d{1,5}\s*\d{1,5}$" title="Enter a 1 or 2 letter grid ref followed by an even number of digits (e.g., TQ 123 456 or O 12 34)"
                         onblur="checkGridref(this)" placeholder="(Subject Location)"/></span>

		<!-- TODO - open gridsquare page -->

                <div style=display:none><input type="checkbox" name="use6fig" value="1"/> <label for="use6fig">Only use 6 figures (<span class="nowrap"><a title="Explanation" href="https://www.geograph.org.uk/help/map_precision" target="_blank">Explanatioion</a><img style="padding-left:2px;" alt="New Window" title="opens in a new window" src="https://s1.geograph.org.uk/img/newwin.png" width="10" height="10"/></span>)</label></div>
            <br>

            <label for="view_direction">View</label>:
            <select id="view_direction" name="view_direction">
                    <option value="-1" style="color:gray">Direction</option>
                    <option value="0">NORTH            : 0 deg</option>
                    <option value="22" style="color:gray">North-northeast  : 22 deg</option>
                    <option value="45">Northeast        : 45 deg</option>
                    <option value="67" style="color:gray">East-northeast   : 67 deg</option>
                    <option value="90">EAST             : 90 deg</option>
                    <option value="112" style="color:gray">East-southeast   : 112 deg</option>
                    <option value="135">Southeast        : 135 deg</option>
                    <option value="157" style="color:gray">South-southeast  : 157 deg</option>
                    <option value="180">SOUTH            : 180 deg</option>
                    <option value="202" style="color:gray">South-southwest  : 202 deg</option>
                    <option value="225">Southwest        : 225 deg</option>
                    <option value="247" style="color:gray">West-southwest   : 247 deg</option>
                    <option value="270">WEST             : 270 deg</option>
                    <option value="292" style="color:gray">West-northwest   : 292 deg</option>
                    <option value="315">Northwest        : 315 deg</option>
                    <option value="337" style="color:gray">North-northwest  : 337 deg</option>
                    <option value="00">NORTH            : 0 deg</option>
             </select>
            <div id="dist_message" style="padding-left:10px"></div>

            <div class="notes-bar"><select id="notesList"></select> (Sets the <span id="activeMode">Camera</span>)</div>

        </div>
	</div>

    <div id="mapInfo" style="padding:10px;border-radius:10px; background-color:#fbfbe1; position:sticky; bottom:0; z-index:1000; text-align:center">
        If the image lacks location data, use the <strong>Locate/Pin</strong> icon to find your current position or the <strong>Search</strong> icon to find a place by name.<br><br>
        <strong>Drag the map</strong> to align the central cross-hairs with the Camera/Photographer location.<br><br>
        Tap the <strong>Grid Reference boxes</strong> to toggle between positioning the Camera and the Subject (the active selection is highlighted in white).
    </div>

    <dialog id="map-modal" onclick="closeModal('map-modal')">
        <h3>Location Instructions</h3>
        <p>To submit your image, we need both the <strong>Camera</strong> position and the <strong>Subject</strong> position.</p>

        <article>
            <h3>1. Set the Camera Location</h3>
            <p>If the location did not load automatically from your photo's EXIF data, use one of these methods:</p>
            <ul>
                <li><strong>Search:</strong> Tap the <span class="fa fa-search"></span> icon on the map to find a specific place.</li>
                <li><strong>GPS:</strong> Tap the <span class="fa fa-map-marker"></span> icon to center the map on your current position.</li>
                <li><strong>Manual:</strong> Type a Grid-Reference directly into the Camera (or Subject) location box.</li>
                <li><strong>Last:</strong> If have already submitted an image, click the <span class="fa fa-history"></span> Reset to use last submitted location.</li>
            </ul>
            <p><strong>Refine:</strong> Once the map is active, drag it until the central circle is positioned exactly over the camera location.</p>
        </article>

        <article>
            <h3>2. Set the Subject Location</h3>
            <p>Once the Camera position is set, mark the location of your subject:</p>
            <ul>
                <li><strong>Quick Method:</strong> Double-tap the subject's location on the map to instantly mark and center the point.</li>
                <li><strong>Manual Method:</strong> Tap the Subject box to toggle to "Subject" Centering then drag the map until the subject is under the center crosshair.</li>
            </ul>
            <blockquote>
                <p><strong>Tip:</strong> If your photo already has GPS data, simply double-tap the subject on the map and drag to refine if necessary.</p>
            </blockquote>
        </article>

        <article>
            <h3>Additional Controls</h3>
            <ul>
                <li><strong>Toggling:</strong> You can click either the Camera or Subject box at any time to switch which location is currently active for dragging on the map.</li>
                <li><strong>Minimum Requirements:</strong> If you cannot specify an exact location, you must enter at least a 4-figure Grid Reference in the Subject box.</li>
                <li><strong>View Direction:</strong> the direction dropdown will auto-calculate as you enter locations. Manual selection is only necessary if the camera and subject are very close together, which may affect accuracy.</li>
            </ul>
        </article>
        <button type="button" onclick="closeModal('map-modal')">Close</button>
    </dialog>

	<div class="content">
        <div class="field-header">
            <div style="display: flex; align-items: center;">
                <label>Title</label>
                <span id="titleInfo" class="info-icon" data-error="">!</span>
            </div>
            <span class="optional-label">(required)</span>
        </div>
	    <input type="text" name="title" maxlength="128" id="localTitle" placeholder="Give your photo a title" oninput="updateStickyTitle(this.value)" required>

        <div class="field-header">
            <div style="display: flex; align-items: center;">
        	    <label>Description</label>
                <span id="descInfo" class="info-icon" data-error="">!</span>
            </div>
            <span class="optional-label">(optional)</span>
        </div>
	    <textarea name="comment" id="localDesc" placeholder="optional longer description" rows="5"></textarea>

        <div id="suggestion-pill-bar" class="hidden no-results"></div>

        <label for="imagetaken">Date Taken</label>
        <div>
            <input type="date" id="imagetaken" name="imagetaken" required>
            <input type="text" id="date-text" name="date_partial"
                   placeholder="e.g. 2025 or 2025-03"
                   title="Please enter a date like 1960, 1964-03, or 2023-03-09"
                   style="display:none;" pattern="^\s*\d{4}([/ -]\d{1,2}){0,2}\s*$">

            <div id="date-controls">
                <button type="button" onclick="switchToText()">I only know approximately</button>
                <button type="button" onclick="clearDate()">I don't know the date</button>
            </div>

            <script>
            function switchToText() {
                const picker = document.getElementById('imagetaken');
                const textInput = document.getElementById('date-text');

                picker.style.display = 'none';
                picker.removeAttribute('required');

                textInput.style.display = 'block';
                textInput.setAttribute('required', 'required');
                textInput.focus();

                document.getElementById('date-controls').style.display = 'none';
            }

            function clearDate() {
                // Hide both, remove required from both
                document.getElementById('imagetaken').style.display = 'none';
                document.getElementById('imagetaken').removeAttribute('required');
                document.getElementById('date-text').style.display = 'none';
                document.getElementById('date-text').removeAttribute('required');
                document.querySelector('label[for=imagetaken]').textContent = 'Date Unknown';
                document.getElementById('date-controls').style.display = 'none';
                if (typeof updateFormProgress == 'function')
                    updateFormProgress();
            }
	    function resetDateControls(exifdate) {
		if (exifdate && exifdate > '1000-01-01') {
	            document.getElementById('imagetaken').value = exifdate.substr(0,10).replace(/:/g,'-'); //sometimes EXIF has ":"
	            //if we have a date it very unlikly to not be known!
	            document.getElementById('date-controls').style.display = 'none';
	        } else {
	            //might need to reshow them!
		    document.getElementById('imagetaken').style.display = '';
	            document.getElementById('date-controls').style.display = '';
		}
                document.getElementById('imagetaken').required = true;
		document.getElementById('date-text').style.display = 'none';
                document.getElementById('date-text').required = false;
	    }


            </script>
        </div>

        <div class="field-header">
    	    <label>Geographical Contexts</label>
            <span class="optional-label" id="context-count">(select multiple)</span>
        </div>
	    <select name="contexts[]" id="contexts" multiple size=10 required></select>

        <div class="field-header">
            <label for="subject-input">Primary Subject</label>
            <select class="recent-select" id="recent-subjects" onchange="useRecentSubject(this)">
                <option value="">Recently Used</option>
            </select>
        </div>
	    <div class="tag-input-container">
            <input type="search" name="subject" id="subject-input" placeholder="Search subjects...">
            <div id="suggestionsSubjects" class="dropdown"></div>
            <datalist id="subject-list"></datalist>
            <input type="hidden" name="subject_id" id="subject-id">
        </div>

        <div class="field-header">
            <label>Free-form Tags</label>
            <select class="recent-select" id="recent-tags" onchange="useRecentTag(this)">
                <option value="">Recently Used</option>
            </select>
        </div>
	    <div class="tag-input-container">
            <div id="active-tags"></div>
            <input type="search" id="tag-search" placeholder="Type to add tags...">
            <div id="suggestions" class="dropdown"></div>
        </div>

        <div class="field-header">
            <label>Special Flags</label>
            <span class="optional-label">(optional)</span>
        </div>
    	<div class="flag-container">
	        <label class="flag-item" for="c-drone">
    	        <input type="checkbox" name="tags[]" id="c-drone" value="from:drone">
	            <span><b>Taken from/by Drone</b><br><small>(not a handheld camera)</small></span>
	        </label>

    	    <label class="flag-item" for="c-pano">
	            <input type="checkbox" name="tags[]" id="c-pano" value="panorama" onclick="updatePanoDisplay()">
    	        <span><b>Panorama</b></span>
	        </label>

            <div id="showpano" style="display:none">
                <select name="tags[]" id="panoselect" onchange="updatePanoDisplay()">
                        <option value="">Please select type...</option>
                        <option value="panorama:wideangle">Wideangle (wider view angle than 'natural', but not full 360)</option>
                        <option value="panorama:360">360 degree (circular but not full height)</option>
                        <option value="panorama:photosphere">PhotoSphere (full 360, and full height)</option>
                </select>

                <div class=nowrap id="showvfov" style="display:none">(vfov: <input type=number step=0.01 name=vfov id=vfov placeholder=120 style=width:70px;text-align:right>degrees wide)</div>
                <div class=nowrap id="showhfov">(hfov: <input type=number step=0.01 name=hfov id=hfov placeholder=90 style=width:70px;text-align:right>degrees high)</div>

        		<button type="button" onclick="openModal('pano-modal')"
                style="background:none; border:none; color:blue; text-decoration:underline;">How to Submit Panoramas &gt;</button>

           </div>
           <script>
           function updatePanoDisplay() {
                if (document.getElementById("c-pano").checked) {
                    const select = document.getElementById("panoselect");
                    document.getElementById('showvfov').style.display = (select.value == 'panorama:wideangle')?'':'none';
                    document.getElementById('showhfov').style.display = (select.value == 'panorama:photosphere')?'none':'';
                    document.getElementById("showpano").style.display = "";
                    document.getElementById("panoselect").required = true;
                } else {
                    document.getElementById("showpano").style.display ="none";
                    document.getElementById("panoselect").required = false;
                }
           }
           </script>

<dialog id="pano-modal" onclick="closeModal('pano-modal')">
    <div style="max-height: 80vh; overflow-y: auto; padding: 10px;">
        <h3>About Panoramas & Photospheres</h3>

        <p>Panoramas are enhanced with a special viewer that allows users to rotate and zoom into the scene.</p>

        <h4>Getting the best display:</h4>
        <p>To ensure your wide-angle work displays without stretching, we use your Field of View (FOV) settings. An approximate value (to the nearest 5&deg; or 10&deg;) is sufficient.</p>

        <h4>Submission Guidelines (in particular for PhotoSpheres):</h4>
        <ul>
            <li><strong>Thumbnail:</strong> Please submit a "normal angle" image here first. If you stitched a panorama, use one of the original source images to ensure a clear thumbnail.</li>
            <li><strong>High-Res:</strong> After submission, use the "Upload a larger version" feature to add the full-resolution panorama.</li>
        </ul>

        <p><em>Note: You only need to release a 640px version initially, as the interactive viewer will replace the standard larger-view functionality.</em></p>

        <a href="/article/Panoramas-and-Photospheres-on-Geograph" target="_blank">Read more about Panoramas on Geograph</a> (New Window)
        </a>

        <br><br>
        <button type="button" onclick="closeModal('pano-modal')">Close</button>
    </div>
</dialog>

    	</div>

		<!-- SD??!? -->

        <fieldset id="licence">
            <legend>Photographer Attribution</legend>

            Who took this photo?

            <label>
                <input type="radio" name="pattrib" value="self" checked onclick="updateLicenceDiv()">
                I am the photographer.
            </label>
            <label>
                <input type="radio" name="pattrib" value="other" id="attrib_other" onclick="updateLicenceDiv(true)">
                I am submitting on behalf of someone else.
            </label>

            <div id="licence_name" style="display: none;">
                <p>By selecting this option you certify that you as the 'Geograph Account Holder',
                   act as an authorised 'Licensor' <span class="nowrap">(<a href="/help/what_is_a_licensor" target="_blank">What does this mean?</a>)</span>
                   for the photographer named below:</p>

                <label for="pattrib_name">Photographer Name</label>
                <input type="text" name="pattrib_name" id="pattrib_name"
                       pattern="^[a-zA-Z0-9\-\s\']+$"
                       title="Only letters, hyphens and apostrophes allowed">

				<p>This option should not be used to re-publish the work of others already published under a Creative Commons Licence, either on Geograph or elsewhere; such content is not appropriate for Geograph.</p>

            </div>

            <script>
            function updateLicenceDiv(isManual = false) {
                const isOther = document.getElementById('attrib_other').checked;
                const container = document.getElementById('licence_name');
                const input = document.getElementById('pattrib_name');
				const elements = document.getElementsByClassName('owner_prompt');

                // Use toggling for class/display
                container.style.display = isOther ? 'block' : 'none';

                // Cleaner attribute setting
                input.required = isOther;

				const labelText = isOther ? 'The photographer' : 'You';
				for (let i = 0; i < elements.length; i++) {
			        elements[i].textContent = labelText;
			    }

                // Auto-focus the input when it appears
                if (isManual && isOther) input.focus();
            }
            </script>
        </fieldset>

        <fieldset>
            <legend>Licensing & Rights</legend>

            By submitting, you agree to release this image and metadata under a <b>Creative Commons Attribution-ShareAlike 2.0 Licence</b>.
            <ul>
                <li><span class="owner_prompt">You</span> <b>keep the copyright</b>.
                <li>Others can share, modify, and use it <b>commercially</b> (e.g., printing/selling) provided they credit you.
                <li><b>Irrevocable</b>: Once submitted, this licence cannot be withdrawn.
            </ul>

            <p><b><a href="https://creativecommons.org/licenses/by-sa/2.0/" target="_blank" rel="noopener noreferrer">View Full Licence Deed</a></b>
				<span class="nowrap"> (Opens in new tab)</span></p>

		    <p>Because we are an open project we want to ensure our content is licensed as openly as possible and so we ask that all images are released under the Creative Commons 
            licence, including accompanying metadata. <button type="button"
                onclick="openModal('license-modal');"
                style="background:none; border:none; color:blue; text-decoration:underline;">Read More &gt;</button> </p>

            <dialog id="license-modal" onclick="closeModal('license-modal')">
                <h3>Open Licensing Explained</h3>

                <p>By using a <strong>Creative Commons Attribution-ShareAlike 2.0</strong> licence, you retain your copyright while granting the
                    public permission to use, share, and modify the image and metadata, <i>provided they credit <span class="owner_prompt">you</span></i>.</p>

                    <h4>What you are agreeing to:</h4>
                    <ul>
                        <li><strong>Commercial Use:</strong> Others may print, sell, or use the image commercially (e.g., on eBay or in publications).</li>
                        <li><strong>Derivative Works:</strong> Others may modify the image to create new works.</li>
                        <li><strong>Irrevocability:</strong> Once submitted, this licence cannot be withdrawn, as others may have already downloaded and legally used the image.</li>
                    </ul>

                <h4>Why we require this:</h4> <p>To fund the running costs of this site and create (for example) site-wide montages, we require all content to be licensed openly. 
                    This includes the <strong>metadata</strong> (location, title/description, tags, date and shared descriptions), which allows researchers and the public to 
                    discover and reuse contributions effectively.</p>

                <p>You are releasing this image at <span id="final-dimensions">[d x d]</span> specifically, the larger size (if any) wont be released.

	            <p><a href="/help/freedom" target="_blank">Open Geograph Freedom Manifesto</a> <span class="nowrap">(Opens in new tab)</span></p>

                <button type="button" onclick="closeModal('license-modal')">Close</button>
            </dialog>
        </fieldset>

	</div>

	<button type=submit class="btn btn-primary">I Agree - Submit Image</button>


	<button type=button class="btn btn-secondary" onclick="navigateTo('/app/uploaded')" style="width:100%">Return to list Without Submitting</button>


	<br><br>
</form>


    <!--- this is just a template that gets cloned, not the active suggestion bar! (should not be inside the actual form) -->
    <div id="blockForRemote" style="display:none">
        <div class="remote-header">
            <img id="remotePreview" height=50>
            <div class="toggle-group">
                <button class="toggle-btn" id="btnModeTitle">Title</button>
                <button class="toggle-btn" id="btnModeDesc">Description</button>
            </div>
            <button id="remoteCloseBtn" style="background:#007bff; color:white; border:none; padding:8px 15px; border-radius:4px;">Done</button>
        </div>
        <div class="remote-body">
            <input type="text" id="remoteTitleInput" maxlength="128" placeholder="Enter Title...">
            <textarea id="remoteDescArea" maxlength="65000" placeholder="Enter Optional Description..."></textarea>
<div class="info-icon" id="titleInfo" title="Style Hint">!</div>
<div class="info-icon" id="descInfo" title="Style Hint">!</div>

        </div>
    	<div class="remote-suggestions" id="remoteSuggBar"></div>
    </div>


<script src="<? echo smarty_modifier_revision("/js/to-title-case.js"); ?>"></script>
<script src="<? echo smarty_modifier_revision("/js/anyascii.js"); ?>"></script>

<script type="module">
        import { escapeHTML, escapeRegex, navigateTo, openModal, closeModal } from '/app/js/utils.js?<? echo filemtime('js/utils.js'); ?>';
        //so the page can use it
        window.escapeHTML = escapeHTML;
        window.escapeRegex = escapeRegex;
        window.navigateTo = navigateTo;
	window.openModal = openModal;
	window.closeModal = closeModal;
</script>

<script>
    const stickyBar = document.getElementById('stickyBar');
    const mainHeader = document.getElementById('mainHeader');
    const displayTitle = document.getElementById('displayTitle');
    const imgLarge = document.getElementById('imgLarge');
    const imgThumb = document.getElementById('imgThumb');
    const theForm = document.getElementById('theForm');

// --------------------------------

    let upload_id = null;
//    let update_data = [];
    window.max_size = 8 * 1024 * 1024; //larger files will be downsized!
    window.uploadMaxDimension = 65536;

    window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin) return;

        console.log("Received:", event.data);
        try {
            const data = JSON.parse(event.data);
//            upload_data = data;

            if (data.settings && data.settings.uploadMaxDimension) {
                window.uploadMaxDimension = parseInt(data.settings.uploadMaxDimension, 10);
                theForm.elements['largestsize'].value = window.uploadMaxDimension;
                updateDimensionsDisplay();
            }
	    if (data.settings && data.settings.darkMode) {
                // Handle Dark Mode
                document.body.classList.toggle('dark-mode', data.settings.darkMode);
            }

            if (data.transfer_id)
                resetForm(data.transfer_id);

            if (data.width && data.height) {
                currentWidth = data.width;
                currentHeight = data.height;
                updateDimensionsDisplay();
            }

            //uploaded page will send grid_reference+photographer_gridref
            if (data.grid_reference)
                document.getElementById('grid_reference').value = data.grid_reference;
            if (data.photographer_gridref) {
                document.getElementById('photographer_gridref').value = data.photographer_gridref;
                centerMap(data.photographer_gridref);
                saveMapPosition(map, 'Location from EXIF');
            }

            //but submit will send lat/long!
            if (data.lat) { //long may be exacty zero (meridian!
                setLatLong(data.lat, data.long, 'photographer_gridref','EXIF')
                saveMapPosition(map, 'Location from EXIF');
            }

            resetDateControls(data.imagetaken ?? '')

            if (data.orientation)
                orientationMessage(data.orientation);

            if (data.transfer_id)
                if (typeof updateFormProgress == 'function')
                    updateFormProgress();

            if (data.name)
                document.getElementById('filename').value = data.name;


        } catch (e) {
console.log("Error", e);
            // Handle non-JSON messages
            if (event.data.startsWith('transfer_id=')) {
                upload_id = event.data.match(/id=(\w+)/)[1];
                //todo, extract exif (geo+date+oritentiation!)
                //currently only rceive the upload_id via simple strings
                resetForm(upload_id);
            }
        }

    });

    window.addEventListener('DOMContentLoaded', function() {
        // 1. Critical Logic (must happen immediately)
        const urlParams = new URLSearchParams(window.location.search);
        const newID = urlParams.get('transfer_id');
        if (newID) {
            resetForm(newID);
        }
        //todo?
        //else setTimeout(function() { if (!update_id) navigateTo('/app/uploaded/'); }, 2500);

        // 2. Secondary Logic (decoupled)
        const runDeferredTasks = () => {
            if (!window.map) {
                loadmap();
            }

            loadContexts();
            loadSubjects();
            renderRecent('submit.subjects', 'recent-subjects');
            renderRecent('submit.tags', 'recent-tags');
            updateLicenceDiv();
            renderNotesList();
        };

        // Use requestIdleCallback with a fallback
        if ('requestIdleCallback' in window) {
            requestIdleCallback(runDeferredTasks);
        } else {
            setTimeout(runDeferredTasks, 1);
        }
    });

    function resetForm(newId) {
        updatePreview(newId); //will store it in upload_id;

    	//we starting again!
        document.getElementById("orientation_message").style.display='none';
        theForm.elements['grid_reference'].value = '';
        theForm.elements['photographer_gridref'].value = '';
        theForm.elements['view_direction'].value = -1;

    	theForm.elements['title'].value = '';
    	theForm.elements['comment'].value = '';

        theForm.elements['imagetaken'].value = ''; //todo,might need to restore the controls??

        theForm.elements['contexts[]'].value = '';
        theForm.elements['subject'].value = ''; //reset recent?
        document.getElementById("active-tags").innerHTML = '';

//        theForm.elements[''].value = '';
        //todo, other elements to reset too! including special flags!
        document.getElementById("c-drone").checked = false;
        document.getElementById("c-pano").checked = false;
        document.getElementById("panoselect").value=''; document.getElementById("panoselect").required = false;
        document.getElementById("vfov").value = '';
        document.getElementById("hfov").value = '';
        updatePanoDisplay();
        window.scrollTo({top: 0}); //incase last use was scrolled!

                if (typeof updateFormProgress == 'function')
                    updateFormProgress();
    }

    let currentWidth = 0;
    let currentHeight = 0;

    function updatePreview(newId) {
        if (newId)
            upload_id = newId; //store in the global (otherwise we using from the global as is)
        theForm.elements['upload_id'].value = upload_id;
        imgLarge.src = `/submit.php?preview=${upload_id}`;
        imgThumb.src = `/submit.php?preview=${upload_id}`;

        // Get dimensions from image load
        imgLarge.onload = function() {
            // Note: browser might show actual display dimensions, but it's a fallback
            if (!currentWidth) {
                currentWidth = this.naturalWidth;
                currentHeight = this.naturalHeight;
                updateDimensionsDisplay();
            }
        };
    }

    function updateDimensionsDisplay() {
        if (!currentWidth || !currentHeight) return;

        let finalWidth = currentWidth;
        let finalHeight = currentHeight;
        let downsized = false;

        if (window.uploadMaxDimension < 65536 && (currentWidth > window.uploadMaxDimension || currentHeight > window.uploadMaxDimension)) {
            const aspect = currentWidth / currentHeight;
            if (aspect > 1) {
                finalWidth = window.uploadMaxDimension;
                finalHeight = Math.floor(window.uploadMaxDimension / aspect);
            } else {
                finalHeight = window.uploadMaxDimension;
                finalWidth = Math.floor(window.uploadMaxDimension * aspect);
            }
            downsized = true;
        }

        const dimText = `${currentWidth} x ${currentHeight} pixels`;
        const finalDimText = `${finalWidth} x ${finalHeight} pixels`;

        document.getElementById('image-dimensions').innerHTML = `Current Size: ${dimText}` +
            (downsized ? `<br><span style="color: #d9534f;">Note: This image will be downsized to ${finalDimText} server-side.</span>` : '');

        const finalDimsEl = document.getElementById('final-dimensions');
        if (finalDimsEl) {
            finalDimsEl.textContent = finalDimText + (window.uploadMaxDimension >= 65536 ? ' (Full Resolution)' : '');
            //todo, the " the larger size (if any) wont be released" should be dynamic too!
        }
    }

// --------------------------------
// Sticky Header/Preview

    async function rotateImage(degrees, force = 0) {
        if (!upload_id || upload_id.length < 10) {
            alert("Unable to rotate, please let us know");
            return;
        }

        try {
            imgLarge.style.opacity = 0.3;
            document.querySelector('.controls').opacity = 0.3;

            // Construct the URL using URLSearchParams (safer than manual string building)
            const params = new URLSearchParams({ rotate:upload_id, degrees, force });
            const response = await fetch(`/submit.php?${params.toString()}`);
            const result = await response.json();

            if (result.width && result.upload_id) {
                imgLarge.style.opacity = 1;
                updatePreview(result.upload_id);
                document.getElementById("orientation_message").style.display='none';

            } else if (result.lossy) {
                if (confirm("This image cannot be rotated losslessly. Quality loss may occur. Continue?")) {
                    rotateImage(degrees, 1); // Retry with force=1
                } else {
                    imgLarge.style.opacity = 1;
                }
            } else {
                throw new Error("Rotation failed");
            }
        } catch (err) {
            console.error(err);
            alert("Rotation Failed, please try again. If it persists, let us know!");
        }
    }

    function orientationMessage(orientation) {
        if (orientation && orientation != 1 &&  orientation !== "1") {
            document.getElementById("orientation_message").style.display='';
        }
    }

    // Sync the sticky title with the input
    function updateStickyTitle(val) {
        displayTitle.innerText = val || "Editing Photo...";
    }

    // Change your observer options
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // threshold 0.2 means: "Is at least 20% of the image visible?"
            // We show the sticky bar when LESS than 20% is visible.
            if (entry.intersectionRatio < 0.2) {
//                stickyBar.classList.add('ready');
                stickyBar.classList.add('visible');
            } else {
                stickyBar.classList.remove('visible');
            }
        });
    }, {
        threshold: [0, 0.2, 0.5, 1.0] // Track multiple points for smoother transitions
    });
    observer.observe(imgLarge);

    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', () => {
        // Only scroll if the input is focused
        if (document.activeElement.tagName === 'TEXTAREA') {
          // Small delay to ensure the resize has finished
          setTimeout(() => {
            document.activeElement.scrollIntoView({
              behavior: 'smooth',
              block: 'start' // Pins to the top (honouring scroll-margin-top)
            });
          }, 100);
        }
      });
    }

// --------------------------------
// Tags

    const searchInput = document.getElementById('tag-search');
    const suggestions = document.getElementById('suggestions');
    const activeTagsContainer = document.getElementById('active-tags');
    let selectedTags = new Set(); // Use a Set to prevent duplicates

    let debounceTimer = null;
    searchInput.addEventListener('input', async (e) => {
        const query = e.target.value;
        if (query.length < 1) { suggestions.innerHTML = ''; return; }

        //do need debounce
        if (debounceTimer) clearTimeout(debounceTimer);

        debounceTimer = setTimeout(async function() {
    		//todo, take only the LAST component, if entered text includes a ;
            const response = await fetch(`/tags/tags.json.php?term=${encodeURIComponent(query)}&mode=ranked`);
            const results = await response.json(); // Expected: ["tag1", "tag2"]

            // Normalize results for comparison
            const normalizedResults = results.map(t => t.toLowerCase());

            let html = results.map(tag => {
                // 1. Create a Case-Insensitive Regex of the user's query
                const safeQuery = escapeRegex(query);
                const regex = new RegExp(`(${safeQuery})`, "gi");
                // 2. Replace the match with a bold version
                // $1 keeps the original casing from the database (e.g., "Road" stays "Road")
                const highlighted = escapeHTML(tag).toTitleCase().replace(regex, "<strong>$1</strong>");
                return `<div class="suggestion-item">${highlighted}</div>`;
            }).join('');

            if (query.length > 2 && !normalizedResults.includes(query.toLowerCase())) {
        		//todo, perhaps would be nice o auto-split!
                if (query.includes(';')) {
                    // 1. Split and clean each tag
                    const tagArray = query.split(/\s*;\s*/).map(t => t.trim()).filter(t => t.length > 0);
                    const cleanedTags = tagArray.map(t => cleanTag(t)).filter(t => t.length > 0);
                    if (cleanedTags.length > 0) { //could end up zero!

                        // 2. Create the data-tag string for bulk processing
                        const query_safe = escapeHTML(cleanedTags.join(';'));

                        // 3. Generate the visual display
                        const displayList = cleanedTags.map(t => `[${escapeHTML(t)}]`).join(' ');

                        html += `<div class="suggestion-item add-new-tag" data-tag="${query_safe}">+ Add all: ${displayList}</div>`;
                    }

		        } else {
                    const query_safe = escapeHTML(cleanTag(query));
                    html += `<div class="suggestion-item add-new-tag" data-tag="${query_safe}">+ Add [${query_safe}]</div>`;
                }
            }

            suggestions.innerHTML = html;
        }, 250);
    });
    // Handle clicking a suggestion
    suggestions.addEventListener('click', (e) => {
        if (e.target.classList.contains('suggestion-item')) {
            // If it's the "Add New" pill, grab the custom data attribute
            const tag = e.target.classList.contains('add-new-tag')
                ? e.target.dataset.tag
                : e.target.innerText;
            addTag(tag);
        }
    });

    searchInput.addEventListener('focus', (e) => {
        // Wait a tiny bit for the mobile keyboard to fully animate up
        setTimeout(() => {
            const rect = searchInput.getBoundingClientRect();
            const viewportHeight = window.innerHeight;

            // If the input is in the bottom 30% of the visible area
            if (rect.top > viewportHeight * 0.7) {
                let block = 'center'; // This puts it in the middle, not the top
                if (window.matchMedia("(max-height: 500px) and (orientation: landscape)").matches) {
                    block = 'start';
                }
                searchInput.scrollIntoView({
                    behavior: 'smooth',
                    block: block
                });
            }
        }, 300);
    });

    function cleanTag(text) {
        //Allows chars: A-Z a-z 0-9 _ ( ) + . & / ! ? % @ # - (plus space)

        //basic HTML injection protection
        text = text.replace(/\\/g, "").replace(/<[^>]*>/g, "").replace(/[<>]+/ig, " ");

        //clean up text, doing fairly full unicode->ascii transliteration
        text = anyAscii(text);

        //standardize brackets
        text = text.replace(/[\{\(\[<]+/g, "(").replace(/[\}\)\]>]+/g, ")");

        //hive off the prefix
        var prefix = null;
        if (text.indexOf(':') > -1) {
                var bits = text.split(/\s*:+\s*/,2);
                text = bits[1].replace(/:/g,' ');

                //prefixes have particully restricted charactor set.
                prefix = bits[0].toLowerCase().replace(/[^\w]+/," ").replace(/[ _]+/g, " ").replace(/(^\s+|\s+$)/g, "");
        }

        //special support for listin building rating
        text = text.replace(/\*/g,'(star)');

        //quotes not supported
        text = text.replace(/['"`]+/g, ""); //dont want to replace with space, because of apos

        //then remove any none supported chars (by now only have ascii left to deal with)
        text = text.replace(/[^\w()\+\.&\/!?%@#-]+/g, " ");

        //clean/collapse whitespace
        text = text.replace(/[ _\t\n\r]+/g, " ").replace(/(^\s+|\s+$)/g, "");

        //this is a well known and common issue to fix, our house style doesnt have dot after st.
        text = text.replace(/\b(st)\.+\s*/i, '$1 ');

        //just to catch odd cases were tag ends up actully blank!
        text = text.replace(/^\s*$/,'blank');

        //add the prefix again
        if (prefix)
                text = prefix+':'+text;
        return text;
    }

    function addTag(tag) {
        if (tag.includes(';')) {
            const tagArray = tag.split(/\s*;\s*/).map(t => t.trim()).filter(t => t.length > 0);
            if (tagArray.length == 0)
                return;

            for(let q=0;q<tagArray.length;q++)
                addTag(tagArray[q]);
            return;
        }

        if (!selectedTags.has(tag)) {
            selectedTags.add(tag);

            const span = document.createElement('span');
            span.className = 'tag-pill';

            // 1. Set text safely (avoids XSS and quote issues)
            span.textContent = tag + ' ';

            // 2. Create the button as a real object
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = '&#10005;';

            // 3. Attach the remove logic directly to this specific button
            btn.onclick = function() {
                selectedTags.delete(tag); // Remove from our Set
                span.remove();            // Remove the whole pill from DOM
            };

            // 4. Create the hidden input for the form POST
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tags[]';
            input.value = tag;

            span.appendChild(btn);
            span.appendChild(input);
            activeTagsContainer.appendChild(span);
        }
        searchInput.value = '';
        suggestions.innerHTML = '';
    }

// --------------------------------
// Subjects

    const subjectInput = document.getElementById('subject-input');
    const subjectList  = document.getElementById('subject-list');
    const suggestionsS = document.getElementById('suggestionsSubjects');

    async function loadSubjects() {
        const response = await fetch("/tags/subject.json.php?v=2");
        const data = await response.json();

        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.tag; // This is what the user sees/types
            option.dataset.id = item.tag_id; // Store the ID for the form
            option.dataset.count = parseInt(item.count,10);
            subjectList.appendChild(option);
        });
    }

    subjectInput.addEventListener('input', () => {
        const query = subjectInput.value.trim().toLowerCase();

        if (query.length < 1) {
            suggestionsS.innerHTML = '';
            return;
        }

        // 1. Get all options from your hidden datalist
        const options = Array.from(subjectList.options);

        // 2. Filter and Score
        let matches = options
            .map(opt => {
                const val = opt.value.toLowerCase();
                let score = 0;
                if (val === query) score = 3; // Perfect match
                else if (val.startsWith(query)) score = 2; // Priority 1: Starts with
                else if (val.includes(query)) score = 1; // Priority 2: Contains
                return { val: opt.value, id: opt.dataset.id, score };
            })
            .filter(match => match.score > 0)
            .sort((a, b) => b.score - a.score); // Higher score first

        // Optimized: If we have exactly one perfect match, clear the suggestions
        if (matches.length === 1 && matches[0].score === 3) {
            subjectInput.setCustomValidity("");
            suggestionsS.innerHTML = '';
            return;
        }

        // If there are many matches, trim!
        if (matches.length > 100) {
            // 1. Split by relevance
            const highRelevance = matches.filter(m => m.score >= 2); // StartsWith
            const lowRelevance = matches.filter(m => m.score === 1);  // Contains

            // 2. Keep ALL high relevance, but limit low relevance
            const limitedLowRelevance = lowRelevance.slice(0, 50);

            matches = [...highRelevance, ...limitedLowRelevance];

            // Add a visual indicator to the list so the user knows they should keep typing
            if (lowRelevance.length > 50) {
                matches.push({
                    val: `...and ${lowRelevance.length - 50} more. Keep typing!`,
                    id: null,
                    isHint: true
                });
            }
        }

        // 3. Render
        suggestionsS.innerHTML = matches.map(m => {
                    const regex = new RegExp(`(${query})`, "gi");
                    // 2. Replace the match with a bold version
                    // $1 keeps the original casing from the database (e.g., "Road" stays "Road")
                    const highlighted = escapeHTML(m.val).toTitleCase().replace(regex, "<strong>$1</strong>");
                    return `<div class="suggestion-item">${highlighted}</div>`;
            return `<div class="suggestion-item" data-id="${m.id}">${highlighted}</div>`
        }).join('');
    });

    // 4. Click handling: Update the input and clear dropdown
    suggestionsS.addEventListener('click', (e) => {
        const item = e.target.closest('.suggestion-item');
        if (item) {
            subjectInput.value = item.textContent;
            subjectInput.setCustomValidity("");
            document.getElementById('subject-id').value = item.dataset.id;
            suggestionsS.innerHTML = '';
        }
    });

    subjectInput.addEventListener('focus', (e) => {
        const query = subjectInput.value.trim().toLowerCase();
        if (query.length < 1) {
            subjectInput.placeholder = 'Start typing... (showing popular subjects)';
            subjectInput.setCustomValidity("");
            const options = Array.from(subjectList.options);

            const matches = options
            .map(opt => {
                const val = opt.value.toLowerCase();
                return { val: opt.value, id: opt.dataset.id, count: opt.dataset.count };
            })
            .sort((a, b) => b.count - a.count) // Higher score first
            .slice(0, 25);

            suggestionsS.innerHTML = matches.map(m => {
                return `<div class="suggestion-item" data-id="${m.id}">${escapeHTML(m.val).toTitleCase()}</div>`
            }).join('');
        } else {
            subjectInput.placeholder = 'Type to search subjects...'; //probably wont be seen, but resets the default above!
        }

        // Wait a tiny bit for the mobile keyboard to fully animate up
        setTimeout(() => {
            const rect = subjectInput.getBoundingClientRect();
            const viewportHeight = window.innerHeight;


            // If the input is in the bottom 30% of the visible area
            if (rect.top > viewportHeight * 0.7) {
                let block = 'center'; // This puts it in the middle, not the top
                if (window.matchMedia("(max-height: 500px) and (orientation: landscape)").matches) {
                    block = 'start';
                }
                subjectInput.scrollIntoView({
                    behavior: 'smooth',
                    block: block
                });
            }
        }, 300);
    });

    subjectInput.addEventListener('change', () => {
        const options = document.querySelectorAll('#subject-list option');
        const match = Array.from(options).find(o => o.value === subjectInput.value);

        //this is only added during submit (if needed), but need to clear it!
        if (match) {
            subjectInput.setCustomValidity("");
        }
    });


// ---------------------------------
// Contexts


    async function loadContexts() {
        const select = document.getElementById('contexts');
        const fragment = document.createDocumentFragment();

        // 1. Add "Recently Used"
        const recent = getRecent('submit.contexts');
        if (recent.length > 0) {
            const group = document.createElement('optgroup');
            group.label = 'Recently Used';
            recent.forEach(tag => {
                const opt = new Option(tag, tag);
                group.appendChild(opt);
            });
            fragment.appendChild(group);
        }

        // 2. Fetch Remote Data
        try {
            const response = await fetch("https://www.geograph.org.uk/tags/primary.json.php");
            const data = await response.json();

            let currentGroup = null;
            data.forEach(item => {
                if (item.grouping !== currentGroup) {
                    currentGroup = item.grouping;
                    const group = document.createElement('optgroup');
                    group.label = currentGroup;
                    fragment.appendChild(group);
                }
                fragment.lastChild.appendChild(new Option(item.tag, item.tag));
            });
        } catch (e) { console.error("Failed to load tags", e); }

        select.appendChild(fragment);

        select.addEventListener('input', e => {
            const selectedOptions = Array.from(select.options).filter(o => o.selected);
            const selectedCount = selectedOptions.length;
            if (selectedCount>6)
                 document.getElementById('context-count').textContent = `${selectedCount} is TOO MANY`;
            else
                document.getElementById('context-count').textContent = `${selectedCount} selected`;
            select.classList.toggle('input-invalid', (selectedCount == 0 || selectedCount>6));
        });
    }

    function renderRecent(storageKey, elementId) {
        const dropdown = document.getElementById(elementId);
        const items = getRecent(storageKey); // Using your existing getRecent logic

        if (!items || items.length === 0) {
            dropdown.style.display = 'none';
            // Check if we already added the optional label to prevent duplicates
            if (!dropdown.parentElement.querySelector('.optional-label')) {
                const span = document.createElement('span');
                span.className = 'optional-label';
                span.textContent = '(optional)';
                // Style it to match your "Recent" dropdown's aesthetic
                dropdown.parentElement.appendChild(span);
            }
            return;
        }

        // Clear existing
        dropdown.innerHTML = '<option value="">Recently Used</option>';
        items.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            dropdown.appendChild(opt);
        });
    }

    // When a Recent Subject is picked
    function useRecentSubject(select) {
        if (!select.value) return;
        const input = document.getElementById('subject-input');
        input.value = select.value;
        select.options[select.selectedIndex].style.color = 'silver';
        select.value = ""; // Reset dropdown
        suggestionsS.innerHTML = ''; //just in case had search open!
    }

    // When a Recent Tag is picked
    function useRecentTag(select) {
        if (!select.value) return;
        addTag(select.value); //automatically clears
        select.options[select.selectedIndex].style.color = 'silver';
        select.value = ""; // Reset dropdown
    }

    //these with the existing format used by old submission

    const getRecent = (key) => {
        const raw = localStorage.getItem(key);
        if (!raw) return [];

        let lines = [];
        try {
            // Handle the JSON double-encoding
            const decoded = JSON.parse(raw);
            lines = decoded.split('\n');
        } catch (e) {
            // Fallback for plain string format
            lines = raw.split('\n');
        }

        const entries = lines
            .map(line => {
                const [timestamp, tag] = line.split('|');
                return { tag, ts: parseInt(timestamp, 10) };
            })
            .filter(e => e.tag && !isNaN(e.ts));

        // Return tags sorted by timestamp descending
        return entries
            .sort((a, b) => b.ts - a.ts)
            .map(e => e.tag);
    };

    const saveRecent = (key, selectedTags) => {
        // 1. Get current entries as a Map (tag -> timestamp)
        const raw = localStorage.getItem(key);
        let existingEntries = {};

        try {
            const decoded = JSON.parse(raw);
            decoded.split('\n').forEach(line => {
                const [ts, tag] = line.split('|');
                if (tag) existingEntries[tag] = parseInt(ts, 10);
            });
        } catch(e) {}

        // 2. Update with new selections (overwrite/set to NOW)
        selectedTags.forEach(tag => {
            existingEntries[tag] = Date.now();
        });

        // 3. Convert back to "timestamp|tag" lines
        const lines = Object.entries(existingEntries)
            .map(([tag, ts]) => `${ts}|${tag}`);

        // 4. Double encode: join with \n, then JSON.stringify
        localStorage.setItem(key, JSON.stringify(lines.join('\n')));
    };

    // --------------------------------
    // only prototype (collecting tags validation stuff ready!)

    function validateForm(event) {
        const form = this;

        ////////////////////
        // check required

        if (localTitle.value.trim() === '') {
	    //this only happens in isSmall (as the native required didnt work!)
            event.preventDefault();
            localTitle.readOnly = false; // Temporarily unlock
            localTitle.setCustomValidity('Please fill out this Field');
            localTitle.reportValidity();
            localTitle.focus();
            return false;
        }

        const select = document.getElementById('contexts');
        const selected = Array.from(select.selectedOptions).map(o => o.value);

        if (selected.length === 0) {
            event.preventDefault();
            //this should never be needed, as should via native 'required', but included for completeness
            alert("Please select at least one Geographical Context");
            return false;
        }

        const input = document.getElementById('subject-input');
        if (input.value.length>0) { //still optional!

            const hiddenId = document.getElementById('subject-id');
            const options = document.querySelectorAll('#subject-list option');
            const valueLower = input.value.toLowerCase();

            // Find if the typed value matches a valid tag
            const match = Array.from(options).find(o => o.value.toLowerCase() === valueLower);

            if (match) {
                hiddenId.value = match.dataset.id;
                input.setCustomValidity(""); // Clear any previous error
            } else {
                event.preventDefault();
                // This triggers the browser's built-in validation bubble
                input.setCustomValidity("Please select a subject from the list");
                input.reportValidity(); // This forces the browser to show the bubble immediately
                input.focus();
                return false;
            }
        } else {
            input.setCustomValidity(""); // Clear any previous error
        }

        ////////////////////
        //success, so final cleanup..

        //context are required anyway! (so should always be present
        saveRecent('submit.contexts', selected);

        if (input.value) saveRecent('submit.subjects', [input.value]);

        // (selectedTags is the Set you used in your addTag logic)
        if (selectedTags.size > 0) {
            saveRecent('submit.tags', Array.from(selectedTags));
        }

        //save this for next time!
        if (map && saveMapPosition)
            saveMapPosition(map, 'Position of Last Submission');

        return true;
    }

    document.forms['theForm'].addEventListener('submit', validateForm);

// --------------------------------
// Map - ported from mobile submit

    var map = null;
    var issubmit = false; //we do it manually.
    var geocoder = null;
    var disableAutoUpdate = false;
    var leafletBaseKey = 'LeafletBase'; //at the moment, we dont know what grid it will be!
    var checkedonce = false;

    var static_host = <? echo json_encode($CONF['STATIC_HOST']); ?>;
	var OSAPIKey = <? echo json_encode($CONF['os_api_key'] ?? null); ?>;

    let resetButton;
    function saveMapPosition(map, label) {
        const center = map.getCenter();
        const zoom = map.getZoom();
        const position = {
            lat: center.lat,
            lng: center.lng,
            zoom: zoom
        };
        localStorage.setItem('mapLastPosition', JSON.stringify(position));
        if (label) {
            localStorage.setItem('mapLastLabel', label);
            if (resetButton) {
                // Use the button instance method to update the tooltip
                resetButton.options.title = `Reset to: ${label}`;
                // Force the title update on the button element itself
                if (resetButton.button)
                    resetButton.button.title = `Reset to: ${label}`;
            }
        } else {
            localStorage.setItem('mapLastLabel', 'unknown'); //should still set something, so not out of sync
        }
    }

    function loadmap() {
        setupBaseMap({doubleClickZoom:false, scrollWheelZoom:'center'});

// Add this guard immediately after creating the map object
// ... because map starts non-centerd, accidental dragging of the map breaks it due to uncaught exception, this guards against that!
map.on('mousedown dragstart', function(e) {
    if (!map.getCenter()) {
        // If no center is set, stop the event from bubbling
        // to the internal Leaflet handlers like _onUp
        L.DomEvent.stopPropagation(e);
        return false;
    }
});

        //this button does double duty
        // ... it can be used to 'reset' back to EXIF location (if drag around and loose their nice GPS location)
        // ... but we also save location, on final submision; so on next load (when no exif), it can be used initialize map to last submission
        const savedLabel = localStorage.getItem('mapLastLabel') ?? 'unknown';
        resetButton = L.easyButton('fa-history', function(btn, map) {
            const saved = localStorage.getItem('mapLastPosition');
            if (saved) {
                const pos = JSON.parse(saved);
                map.setView([pos.lat, pos.lng], pos.zoom);
            } else {
                alert("No saved position found.");
            }
        }, `Reset to: ${savedLabel}`).addTo(map);


        if (location.search.length>2 && location.search.indexOf('gridref=')) {
                if (match = location.search.match(/gridref=([A-Z]{1,2} ?\d{2,5} ?\d{2,5})/)) {
                        disableAutoUpdate = true; //we just centering the map, not setting an exact location!
                        centerMap(match[1]);
                }
        }

        L.geotagPhoto.crosshair({
          //      crosshairHTML: '<img alt="Center of the map; crosshair location" title="Crosshair" src="https://unpkg.com/leaflet-geotag-photo@0.5.1/images/crosshair.svg" width="100px" />'

            crosshairHTML: `
                <svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="45" fill="none" stroke="black" stroke-width="3" class="main-crosshair" stroke-opacity="0.5"/>
                    <g stroke="black" stroke-width="1" stroke-linecap="round" stroke-opacity="0.5" class="reticle-lines">
                        <line x1="50" y1="43" x2="50" y2="47" /> <line x1="50" y1="53" x2="50" y2="57" /> <line x1="43" y1="50" x2="47" y2="50" /> <line x1="53" y1="50" x2="57" y2="50" /> </g>
                </svg>`

        }).addTo(map).on('input', function (event) { //really jsut called when the map is recentered!
                if (!map._loaded) //dragging the map before setup, fails!
                        return;
                var point = this.getCrosshairLatLng(); //really just getting center of the map!
                if (point && point.lat && !disableAutoUpdate)
                        setLatLong(point.lat, point.lng);
        });

        map.on('mousedown',function() {
                disableAutoUpdate = false;
        });

        L.DomEvent.on(map._container, 'touchstart', function() {
                disableAutoUpdate = false;
        });

        map.on('dblclick',function(event) {
                if (!map._loaded) //dragging the map before setup, fails!
                        return;

                //first SWAP the active.
                disableAutoUpdate = true;
                document.querySelectorAll('#maparea input[type=text]').forEach(input => {
                    input.classList.toggle('active');
                });

                // Toggle active class on all matching labels
                document.querySelectorAll('#maparea label.gr').forEach(label => {
                    label.classList.toggle('active');
                });

                updateActiveMode();

                //then recenter the map (which feeds back to the new location box!)
                disableAutoUpdate = false;
                map.panTo(event.latlng);
        });

        map.on('dragend',function(event) {
                if (!map._loaded) //dragging the map before setup, fails!
                        return;

                if (document.forms['theForm'].use6fig && !document.forms['theForm'].use6fig.checked && !checkedonce) {
                        var z=13; //zoom level on normal web tile maps.
                        if (map.options && map.options.crs && map.options.crs.code && map.options.crs.code == "EPSG:27700") //the OS maps use a differet CRS, with differnt zooms
                                z = 7;
                        if (map.getZoom() <= z) {
                                document.forms['theForm'].use6fig.checked = true;
                                checkedonce = true;
                        }
                }
        });

       // setupMess(); //TODO!


        if (typeof setupQuota === 'function') {
            setupQuota(map, baseMaps['Modern OS - GB']);
        }

    }

        function updateActiveMode() {
            const activeLabel = document.querySelector('label.gr.active');
            if (activeLabel) {
                document.getElementById('activeMode').textContent = activeLabel.textContent;
            }
        }


    document.addEventListener('DOMContentLoaded', () => {
        const tab2 = document.getElementById('maparea');
        if (!tab2) return;

        //gather Map inputs
        const inputs = tab2.querySelectorAll('input[type=text]');
        const labels = tab2.querySelectorAll('label.gr');
        const mapInfo = document.getElementById('mapInfo');

        // Helper to clear active classes
        const clearActive = () => {
            inputs.forEach(i => i.classList.remove('active'));
            labels.forEach(l => l.classList.remove('active'));
        };

        // Input events
        inputs.forEach(input => {
            // Focus event
            input.addEventListener('focus', function() {
                //special rule, as subject may be prefilled with a 4fig GR which would cause a disconcerting jump!
                if (this.name == 'grid_reference' && this.value && this.value.match(/^[A-Z]{1,2}\s*\d{2}\s*\d{2}$/)
                    && document.getElementById('photographer_gridref').value.length > 8) {
                    this.value = document.getElementById('photographer_gridref').value;
                }
                clearActive();
                this.classList.add('active');
                if (this.previousElementSibling) {
                    this.previousElementSibling.classList.add('active');
                }
                if (this.value) centerMap(this.value);
                updateActiveMode();
            });

            // Change/Input events
            ['input', 'change', 'keyup', 'paste'].forEach(evt => {
                input.addEventListener(evt, function() {
                    window.disableAutoUpdate = true;
                    if (this.value) centerMap(this.value);
                    updateMapMarker(this, false);
                    if (mapInfo) mapInfo.style.display = 'none';
                });
            });
        });

        // Label click events
        tab2.querySelectorAll('label').forEach(label => {
            const attr = label.getAttribute('for');
            if (attr) {
                const targetInput = tab2.querySelector(`input[type=text][name="${attr}"]`);
                if (targetInput) {
                    label.addEventListener('click', (e) => {
                        e.preventDefault();
                        clearActive();
                        targetInput.classList.add('active');
                        if (targetInput.previousElementSibling) {
                            targetInput.previousElementSibling.classList.add('active');
                        }
                        if (targetInput.value) centerMap(targetInput.value);
                        updateActiveMode();
                    });
                }
            }
        });
    });

    function centerMap(gridref) {
        gridref = gridref.trim().toUpperCase().replace(/ /g,'');
        var grid=new GT_OSGB();
        var ok = false;
        if (grid.parseGridRef(gridref)) {
                ok = true;
        } else {
                grid=new GT_Irish();
                ok = grid.parseGridRef(gridref)
        }

        if (ok && gridref.length > 4) {
                if (gridref.length <= 6 && grid.eastings%1000 == 0 && grid.northings%1000 == 0) {
                        grid.eastings = grid.eastings + 500;
                        grid.northings = grid.northings + 500;
                } else if (gridref.length <= 8 && grid.eastings%100 == 0 && grid.northings%100 == 0) {
                        grid.eastings = grid.eastings + 50;
                        grid.northings = grid.northings + 50;
                } else if (gridref.length <= 10 && grid.eastings%10 == 0 && grid.northings%10 == 0) {
                        grid.eastings = grid.eastings + 5;
                        grid.northings = grid.northings + 5;
                }

                //convert to a wgs84 coordinate
                wgs84 = grid.getWGS84(true);

                if (!map)
                        loadmap();
                var point = new L.LatLng(wgs84.latitude,wgs84.longitude);
                var z = map.getZoom();

                if (!z || z < 13)
                        map.setView(point,15);
                else
                        map.setView(point);
        }
    }

    function setLatLong(lat,long,element,source) {
        //console.log('setLatLong',lat,long,element,source);
        if (!lat || !long) {
                return;
        }
        if (!map)
                loadmap();

        let z;
        if (map && element) {  //only call if specifying a element. If no element, it probably just a map drag!
            z = map.getZoom();
            if (!z || z < 13) {
                    map.setView([lat,long],15);
            } else {
                    map.panTo([lat,long]);
            }
        }

        wgs84=new GT_WGS84();
        wgs84.setDegrees(lat, long);

        var grid = false
        if (wgs84.isIreland2()) {
            grid=wgs84.getIrish(true);
        } else if (wgs84.isGreatBritain()) {
            grid=wgs84.getOSGB();
        }
        if (grid) {
            let precision = 4; //default 8fig GR
            if (!z) //might already fetched
                z = map.getZoom();
            if (source === 'EXIF') precision = 5;
            else if (map.options.crs?.code === 'EPSG:27700') {
                //OS maps use differnt projection
                if (z < 7) precision = 3;
                else if (z > 9) precision = 5;
            } else {
                if (z < 7) precision = 1;
                else if (z < 10) precision = 2;
                else if (z < 14) precision = 3;
                else if (z > 17) precision = 5;
            }

            gridref = grid.getGridRef(precision); //.replace(/ /g,''); -- actully lets show spaced GRs!

            if (!element) {
      		    if (document.getElementById('photographer_gridref')?.classList.contains('active')) {
        	        element = 'photographer_gridref';
        	    } else if (document.getElementById('grid_reference')?.classList.contains('active')) {
        	        element = 'grid_reference';
        	    }
            }

            if (element) {
                document.forms['theForm'].elements[element].value = gridref;
                if (element == 'photographer_gridref' && !marker2) { //updateMapMarker WILL create subject marker, but not photographer marker?
                    createPMarker([lat,long]);
                }
                updateMapMarker(document.forms['theForm'].elements[element],false);
            }
            if (source) {
        		const exifElement = document.getElementById('exiflocation');
                if (exifElement) {
                    exifElement.textContent = "Location from " + source + ": " + gridref;
        	        exifElement.style.display = '';
        	    }
            }
            const mapInfo = document.getElementById('mapInfo');
            if (mapInfo) {
                mapInfo.style.display = 'none';
            }
        }
    }

    function checkGridref(that) {
        //todo! (is being called, so dont remove)

        /*
        const val = el.value.trim();
        if (!val && !el.required) return; // Ignore empty optional fields

        // 1. Basic cleaning
        const clean = val.replace(/\s+/g, '').toUpperCase();
        const match = clean.match(/^([A-Z]{1,2})(\d+)$/);

        if (match && match[2].length % 2 === 0) {
            // 2. It's valid! Now format the input box for the user
            const letters = match[1];
            const digits = match[2];
            const half = digits.length / 2;
            el.value = `${letters} ${digits.substring(0, half)} ${digits.substring(half)}`;

            // 3. Update the Map & Reset Button label
            // If this is the main subject, we definitely want to save this "Good Position"
            if (el.id === 'grid_reference') {
                // convertToLatLng is your own logic for the grid shift
                const coords = convertToLatLng(el.value);
                map.setView(coords, 16);
                saveMapPosition(map, 'Subject: ' + el.value);
            }
        } else if (val !== "") {
            // Optional: style the box red if they typed garbage
            el.style.borderColor = 'red';
        }
        */
    }

function renderNotesList() {
    const select = document.getElementById('notesList');
    select.innerHTML = '<option value="">Select a note to jump to...</option>';

    const storage = JSON.parse(localStorage.getItem('savedNotes') || '[]');
    if (!storage.length) {
        select.parentElement.style.display = 'none';
        return;
    }

    storage.forEach((item, index) => {
        const option = document.createElement('option');
        // 1. Store the coords in the value so we can easily parse them later
        option.value = item.coords;
        // 2. Make the display text human-readable
        option.textContent = `${item.timestamp} | ${item.note.substring(0, 30)}${item.note.length>30?'...':''}`;
        select.appendChild(option);
    });

    // 3. Attach the change listener
    select.onchange = (e) => {
        const coordsStr = e.target.value;
        if (!coordsStr) return;

        // Assuming coords are stored as "lat,lng" string
        const [lat, lng] = coordsStr.trim().split(/\s*,\s*/).map(Number);

        if (map && !isNaN(lat) && !isNaN(lng)) {
            map.setView([lat, lng], 13); // Center the Leaflet map
        }
        select.selectedIndex=0;
    };
}


// ---------------------
// The title and description get a full screen editor, mainly to make he suggestionsBar visible, but really helps on small screns, where the text area might be partially obscured or scorlled out of view. 


    // used in both modes
    const localTitle = document.getElementById('localTitle'); // <input> in iframe
    const localDesc = document.getElementById('localDesc');   // <textarea> in iframe

    const isSmall = window.matchMedia("(any-pointer: coarse) and (max-width: 900px) and (max-height: 900px)").matches;

    //used by both modes (gets set to right context)
    let suggBar = null;
    let searchRoot = null;

    //used by isSmall, defined here to for scoping
    let titleInp, descArea, btnTitle, btnDesc, currentMode;

    //and defined for working with local bar

    // A unified way to get the active element, regardless of device
    //used by useSuggection
    function getActiveEditor() {
        if (isSmall) {
            return (currentMode === 'title') ? titleInp : descArea;
        }
        return lastFocusedElement;
    }

    if (isSmall) {
    	localTitle.readOnly = true;
	localTitle.classList.toggle('input-invalid', localTitle.value == ''); //the browser doesnt show real :invalid on readonly, so add fake one

        localDesc.readOnly = true;

    	// --- Configuration & Setup ---
    	const parentDoc = window.parent.document;
    	const parentWin = window.parent;
    	searchRoot = parentDoc;

    	// 1. Get the templates from the current iframe document
    	const styleTemplate = document.getElementById("styleforRemoteBlock");
    	const overlayTemplate = document.getElementById("blockForRemote");

    	// 2. Check if already injected in the parent
    	let remoteOverlay = parentDoc.getElementById('remoteEditorOverlay');

    	if (!remoteOverlay) {
    	    // Inject the CSS (Cloned from iframe to parent head)
    	    const newStyle = styleTemplate.cloneNode(true);
    	    newStyle.id = 'remoteEditorStyles';
    	    parentDoc.head.appendChild(newStyle);

    	    // Inject the HTML (Cloned from iframe to parent body)
    	    remoteOverlay = overlayTemplate.cloneNode(true);
    	    remoteOverlay.id = 'remoteEditorOverlay';
    	    remoteOverlay.style.display = 'none'; // Ensure it's hidden
    	    parentDoc.body.appendChild(remoteOverlay);
    	}

    	// DESTROY the original template (prevent ID conflicts when standalone)
    	overlayTemplate.remove();

    	// 3. UI Logic and State Management
    	// grab references to the overlay (newly created or reused)
    	titleInp = parentDoc.getElementById('remoteTitleInput');
    	descArea = parentDoc.getElementById('remoteDescArea');
    	btnTitle = parentDoc.getElementById('btnModeTitle');
    	btnDesc = parentDoc.getElementById('btnModeDesc');

    	//just to start, will be set correctly on focus!
    	currentMode = 'title'; // 'title' or 'desc'

    	// store a singleton reference to the local function
    	if (!parentWin.updateRemoteLayout) {
    	    parentWin.updateRemoteLayout = function() {
    		    if (parentWin.visualViewport && remoteOverlay.style.display === 'flex') {
    		        const vv = parentWin.visualViewport;
    		        remoteOverlay.style.height = `${vv.height}px`;
    		        remoteOverlay.style.top = `${vv.offsetTop}px`;
    		        parentWin.scrollTo(0, 0);
    		    }
    	    }
    	    parentWin.currentMode = currentMode;
    	}

    	// these are inline functions as reference local varaibles
    	const openOverlay = (mode) => {
    	    // Sync iframe data to parent overlay
    	    titleInp.value = localTitle.value;
    	    descArea.value = localDesc.value;
    	    remoteOverlay.style.display = 'flex';
    	    setMode(mode);
    	    parentWin.visualViewport.addEventListener('resize', parentWin.updateRemoteLayout);
    	    parentWin.visualViewport.addEventListener('scroll', parentWin.updateRemoteLayout);
    	    parentWin.updateRemoteLayout();

            initalizePlacenames();
            parentDoc.getElementById('remotePreview').src = imgLarge.src;
    	};

    	localTitle.addEventListener('click', () => openOverlay('title'));
    	localDesc.addEventListener('click', () => openOverlay('desc'));

    	//needs to overwrite it
    	parentDoc.getElementById('remoteCloseBtn').onclick = () => {
    	    // Sync data back to iframe
    	    localTitle.value = titleInp.value;
    	    localDesc.value = descArea.value;

	    //sync with local validation
	    updateStickyTitle(titleInp.value);
	    localTitle.classList.toggle('input-invalid', localTitle.value == ''); //the browser doesnt show real :invalid, so add fake one
            //we also need to add remove this once edited!
	    if (localTitle.value != '')
		localTitle.setCustomValidity("");
            updateFormProgress();

    	    remoteOverlay.style.display = 'none';

    		//need to make sure to remove this, so the next iframe can add its own
    	    parentWin.visualViewport.removeEventListener('resize', parentWin.updateRemoteLayout);
    	    parentWin.visualViewport.removeEventListener('scroll', parentWin.updateRemoteLayout);
    	};

    	suggBar = parentDoc.getElementById('remoteSuggBar');

    	if (!suggBar.dataset.listenerAttached) {
    		// Event Listeners for switching modes inside the overlay (only needed once)
    		btnTitle.onclick = () => setMode('title');
    		btnDesc.onclick = () => setMode('desc');

    		// need to also set the modes (for portrait, when both bisible!)
    		titleInp.onfocus = () => setMode('title');
    		descArea.onfocus = () => setMode('desc');

    		//these event handlers only reference content directly in the remote overlay, so only need adding once
    		suggBar.addEventListener('click', useSuggection);

    		// 1. Attach listeners to the remote elements
    		// This should be done right after they are created in the parentDoc
    		titleInp.addEventListener('input', (e) => handleInput(e));
    		descArea.addEventListener('input', (e) => handleInput(e));

    		suggBar.dataset.listenerAttached = "true"; // Flag it as "already handled"
    	}

    } else { //not isSmall, so large

    	// we if no remote frame, we now need to work on the 'local' suggestion bar
    	suggBar = document.getElementById('suggestion-pill-bar');
    	searchRoot = document;

    	[localTitle, localDesc].forEach(el => {
    		el.addEventListener('input', handleInput);
        	el.addEventListener('focus', handleFocus);
        	el.addEventListener('blur', handleBlur);
    	});

        suggBar.addEventListener('click', useSuggection);
    }

    // ---------------------------------------
    // and now the actual fucntions

    function setMode(mode) {
        currentMode = mode;
        if (mode === 'title') {
    	    // If we are in portrait, don't bother hiding/showing, just focus
    	    if (!window.matchMedia("(orientation: portrait)").matches) {
    	        titleInp.style.display = 'block';
            	descArea.style.display = 'none';
        	}
            btnTitle.classList.add('active');
            btnDesc.classList.remove('active');
            titleInp.focus();
        } else {
        	if (!window.matchMedia("(orientation: portrait)").matches) {
               	titleInp.style.display = 'none';
               	descArea.style.display = 'block';
        	}
            btnTitle.classList.remove('active');
            btnDesc.classList.add('active');
            descArea.focus();
        }
    }

    function handleInput(e) {
        // Get the word currently being typed (last word before cursor)
        const val = e.target.value;
        const cursorPosition = e.target.selectionStart;

        // Slice text up to cursor and grab the last word
        const textUpToCursor = val.slice(0, cursorPosition);
        const words = textUpToCursor.split(/\s+/);
        const lastWord = words[words.length - 1].toLowerCase();

        filterSuggestions(lastWord);

        validateStyle(e.target, e.target.tagName === 'TEXTAREA'?'desc':'title');

    }

    function filterSuggestions(query) {
        // Crucial: Use parentDoc because the buttons aren't in the iframe!
        const pills = searchRoot.querySelectorAll('.suggestion-pill');
        pills.forEach(pill => {
            const name = pill.textContent.toLowerCase();
            const title = pill.getAttribute('title') ? pill.getAttribute('title').toLowerCase() : "";
            // Match against the button text OR the title attribute (Village, Road, etc.)
            const isVisible = query === "" || name.includes(query); // || title.includes(query);
            pill.style.display = isVisible ? 'inline-block' : 'none';
        });
    }

    function useSuggection(e) {
        e.preventDefault();

        // Ensure we clicked a button (or something inside a button)
        const btn = e.target.closest('.suggestion-pill');
        if (!btn) return;

        // 1. Identify which input is currently "active" in your UI
        const activeEl = getActiveEditor();

        insertAtCursor(activeEl, btn.textContent);
        filterSuggestions(''); //remove filter
    }

    // Toggle visibility based on focus
    let blurTimer = null;
    function handleFocus(e) {
        initalizePlacenames();

        //we DONT check no-results here, as may still be loading, the no-results will keep it hidden, even if 'hidden' class is removed

        lastFocusedElement = e.target;
        suggBar.classList.remove('hidden');
        if (blurTimer) clearTimeout(blurTimer); //otherwise the timer might still hide when switching!
    }

    function handleBlur(e) {
        // Delay blur to allow clicking a pill before the bar disappears
        blurTimer = setTimeout(() => {
		//if just clicking a suggestion, dont hide the bar (useSuggestion will refocus it anyway!)
            if (!suggBar.contains(document.activeElement)) {
                suggBar.classList.add('hidden');
            }
            blurTimer = null;
        }, 200);
    }

// ---------------------

let loadedPos = { eastings: null, northings: null, ri: null };
let isFetching = false;


function initalizePlacenames() {
    // Only fetch if we have valid coordinates from your map logic
    if (typeof eastings1 !== 'undefined' && typeof northings1 !== 'undefined' && eastings1 > 0 && northings1 > 0) {
        //alas no global reference to the grid is kept!
        const ri=document.getElementById('grid_reference').value.match(/^[A-Z]{2}/i)?1:2;
        loadPlaceNames(eastings1, northings1, ri);
    } else if (typeof eastings2 !== 'undefined' && typeof northings2 !== 'undefined' && eastings2 > 0 && northings2 > 0) {
        const ri=document.getElementById('photographer_gridref').value.match(/^[A-Z]{2}/i)?1:2;
        loadPlaceNames(eastings2, northings2, ri);
    }
}

async function loadPlaceNames(eastings, northings, ri) {
  // Don't re-fetch if we've already loaded this exact spot
  if (isFetching || (eastings === loadedPos.eastings && northings === loadedPos.northings && ri == loadedPos.ri)) {
    return;
  }

  isFetching = true;
  loadedPos = { eastings, northings, ri };

  suggBar.innerHTML = 'Loading...';

  try {
    const script_name = (ri==2)?"ie_open_data.json.php":"os_open_names.json.php";
    const response = await fetch(`/stuff/${script_name}?e=${eastings}&n=${northings}`);
    const data = await response.json();

    isFetching = false;
    suggBar.innerHTML = ''; // Clear loading

    if (data?.rows?.length) {
      for (const item of data.rows) {
        for (const key of ['name1', 'name2', 'name', 'irish']) {
          if (!item[key]) continue;

          const name = item[key];
          const pill = document.createElement('button');
          pill.className = 'suggestion-pill';
          pill.type = 'button';
          pill.textContent = name;
          pill.title = item.local_type ?? item.town_type ?? '';

          suggBar.appendChild(pill);
        }
      }
      suggBar.classList.remove('no-results');
    } else {
      suggBar.classList.add('no-results');
//      suggBar.textContent = 'No nearby places found.';
    }
  } catch (err) {
    suggBar.classList.add('no-results');
//    suggBar.textContent = 'Failed to load places.';
  }
  isFetching = false;
}

function insertAtCursor(el, textToInsert) {

    // Validate that it's an input or textarea
    if (!el || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA')) {
        console.warn('No valid input focused');
        return;
    }

    const cursorPosition = el.selectionStart;
    const textBeforeCursor = el.value.slice(0, cursorPosition);

    // 1. Find the start of the current "partial word"
    // We search backwards for the last space (or newline)
    const lastSpaceIndex = textBeforeCursor.lastIndexOf(' ');
    const lastNewlineIndex = textBeforeCursor.lastIndexOf('\n');
    const lastBreakIndex = Math.max(lastSpaceIndex, lastNewlineIndex);
    const wordStart = lastBreakIndex === -1 ? 0 : lastBreakIndex + 1;

    // 2. Build the new value:
    // Everything before the partial word + the full suggestion + rest of text
    const textAfterCursor = el.value.slice(el.selectionEnd);
    el.value = el.value.slice(0, wordStart) + textToInsert + ' ' + textAfterCursor;

    // 3. Update cursor position (placed after the inserted word and the space we added)
    const newCursorPos = wordStart + textToInsert.length + 1;
    el.setSelectionRange(newCursorPos, newCursorPos);

    el.focus();
}

function validateStyle(el, fieldName) {
    const v = el.value.trim();
    if (v.length <= 1) {
        setValidationUI(el, fieldName, true);
        return;
    }
    let titleValue = (isSmall)?titleInp.value:localTitle.value;
    let error = null;
    if (/^[a-z]/.test(v)) error = 'Start with a capital letter';
    else if (v.length > 4 && (v.toUpperCase() === v || v.toLowerCase() === v)) error = 'Avoid ALL CAPS or all lowercase';
    else if (fieldName === 'title' && v.endsWith('.') && !v.endsWith('...')) error = 'Titles should not end with a full stop';
    //todo need to detect if description is duplicate of title
    else if (fieldName === 'desc' && v.toLowerCase().replace(/\.+$/, '') == titleValue.trim().toLowerCase()) error = 'Should not duplicate Title';
    //else if (fieldName === 'desc' && v.length > 0 && !/[.!?]$/.test(v)) error = 'Comments should end with punctuation';

    setValidationUI(el, fieldName, !error, error);
}

function setValidationUI(el, fieldName, isValid, message = '') {
    const iconId = fieldName == 'title' ? 'titleInfo' : 'descInfo';
    const icon = searchRoot.getElementById(iconId);

    if (!isValid) {
        el.classList.add('input-warning');
        if (icon) {
            icon.style.display = 'block';
            icon.dataset.error = message; //for the CSS popup
           // icon.onclick = () => alert(message); // Simple alert, or a custom toast
        }
    } else {
        el.classList.remove('input-warning');
        if (icon) icon.style.display = 'none';
    }
}

// ---------------------

function updateFormProgress() {
    const form = document.forms['theForm'];
    const statusDisplay = document.getElementById('form-status-bar'); // Your display element
    
    let completed = 0;
    let totalRelevant = 0;

    Array.from(form.elements).forEach(el => {
        // 1. Define what counts as a "Value"
        let hasValue = false;
        let isTracked = false;

        // 1. Identify "Tracked" elements
        const isTextish = ['text', 'number', 'date', 'textarea'].includes(el.type) || el.tagName === 'TEXTAREA';
        const isSingleSelect = el.type === 'select-one' && !el.name.startsWith('recent-'); //not the recent selectors
        const isSearch = el.type === 'search' && el.id === 'subject-input'; //only the Subject one, NOT tags
        const isCheckbox = el.type === 'checkbox';
        const isMultiSelect = el.type === 'select-multiple';
        const isTrackedHidden = el.type === 'hidden' && el.name === 'tags[]'; //only dynamic tags

        // 2. Determine "Value" based on type
        if (isTextish || isSearch || isTrackedHidden) {
            isTracked = true;
            hasValue = el.value.trim().length > 0;
        } else if (isSingleSelect) {
            isTracked = true;
            hasValue = el.value.length > 0 && el.value !== "-1"; //direction uses -1 for none, as 0 is North
        } else if (isCheckbox) {
            isTracked = true;
            hasValue = el.checked;
        } else if (isMultiSelect) {
            isTracked = true;
            // Check if at least one option is selected
            hasValue = Array.from(el.options).some(opt => opt.selected && opt.value !== "");
        }

        // 2. Apply your "Required vs Optional" logic
        if (isTracked) {
            const isRequired = el.hasAttribute('required');
            // Only count in the total if it's required OR currently has a value
            if (isRequired || hasValue) {
                totalRelevant++;
                if (hasValue) completed++;
            }
        }
    });

    statusDisplay.textContent = `Progress: ${completed}/${totalRelevant}`;
    if (completed === totalRelevant && totalRelevant > 0) {
        statusDisplay.classList.add('complete');
    } else {
        statusDisplay.classList.remove('complete');
    }
}
const myForm = document.forms['theForm'];

// 'input' catches typing in text/search/textarea
myForm.addEventListener('input', updateFormProgress);

// 'change' catches select dropdowns and checkbox toggles
myForm.addEventListener('change', updateFormProgress);

// Initial run to catch pre-filled data (EXIF, etc.)
updateFormProgress();

</script>

<script src="/js/Leaflet.map-limits.js?<? echo filemtime('../js/Leaflet.map-limits.js'); ?>"></script>

</body>
</html>
