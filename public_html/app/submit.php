<?php

require_once('geograph/global.inc.php');
require_once('geograph/uploadmanager.class.php');

init_session();

//equivilent to dieUnderHighLoad();
if (!empty($CONF['readonly'])) {
       include __DIR__."/offline-readonly.inc.php";
}

$USER->mustHavePerm('basic');

function failMessage($text, $um = null) {
    if (!empty($um) && !empty($um->existing))
        $existing = intval($um->existing);

    include __DIR__."/submit-fail.inc.php";
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

    // Check if the AI marker exists and matches the submitted subject
    if (!empty($_POST['subjectmarker']) && preg_match('/^'.preg_quote($_POST['subject'], '/').'[\*~]$/', $_POST['subjectmarker']))
        // The user didn't tamper with the input; keep the AI marker tag
        $_POST['tags'][] = "subject:".$_POST['subjectmarker'];
    elseif (!empty($_POST['subject']))
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
    if (!empty($_POST['snippets'])) {
        if (is_array($_POST['snippets'])) {
            $um->setSnippets($_POST['snippets']);
        }
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
        if ($rc == "") { //empty string is success!

            //clear user profile
            $ab=floor($USER->user_id/10000);
            $smarty = new GeographPage;
            $smarty->clear_cache(null, "user$ab|{$USER->user_id}");

            $need_larger = false;
            foreach($um->tags as $tag) {
                if (preg_match('/^panorama:/',$tag)) //todo && $_POST['largestsize'][$key] == '640' ??
                     $need_larger = 1;
            }
            include __DIR__."/submit-success.inc.php";

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
            --secondary: #e9ecef;
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
        .btn-secondary { background: var(--secondary); color: #333333; width: 100%; box-sizing: border-box; }


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

    -webkit-touch-callout: none; /* Prevents iOS context menu */
    -webkit-user-select: none;   /* Prevents selection */
    user-select: none;
    touch-action: pan-y;         /* Allows vertical scrolling, but helps prevent horizontal 'back' gestures */

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
.long-distance {
	text-decoration: line-through;
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

/* subject/tag/SD autocomplete */

.tag-input-container {
    background-color:white;
    border-radius:6px;
}
.tag-input-container input {
    margin-bottom:0;
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
    overflow:hidden;
    text-overflow: ellipsis;
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

div.active-tags {
	line-height:35px;
}

/* tag-pill are the actual selected tag(s) */
span.tag-pill {
    padding: 6px 12px;
    margin: 4px;
    white-space: nowrap;
    font-weight: 500;
    border-radius: 15px;
    background: #d4e5bd;
}
span.tag-pill button {
    border:none;
    color:red;
    margin-left: 6px;
    padding:0;
    background-color:transparent;
    font-size:1.1em; /* for better vertical centering */
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
        padding: 8px 16px; border: 0; background: #d8def9;
        color: #007bff; border-radius: 14px; cursor: pointer; font-weight: bold; touch-action: manipulation;
    }
    button:active { background: #007bff; color: #fff; }

    button.help-link {
        background-color:#e4ffe4;
        text-decoration:none;
        border:0;
        color:black;
    }

	fieldset {
		margin-top:20px;
		border-radius:8px;
	    background-color:#f5f5f0; padding:3px;
        border: 0;
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
        backdrop-filter: blur(3px)
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
    aspect-ratio: 1 / 1;
    max-height: 90svh;
    border:1px solid silver; border-radius:5px;
}

#maparea .controls {
    text-align:center;
    padding:0;
}

#maparea .map-wrapper {
    margin:auto;
    position:relative;
    max-width: min( 350px , calc( 98vw - 30px ) );
}
#maparea .map-edge-guard {
    display:none;
}


@media all and (max-width: 450px) {
    #maparea .map-wrapper {
	margin:1px; /*undo auto, to allow the map to be left centered, to give more area on side to swipe the page */
    }

    #maparea .map-edge-guard {
	display:block;
        position: absolute;
        top:0;bottom:0;right:0;width:18px;margin-right:-3px;z-index:10000;
        background:transparent;touch-action: pan-y;
	transition: background 0.2s ease;
    }
    #maparea .map-edge-guard:active {
	background: rgba(0, 0, 0, 0.08);
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
#maparea input#photographer_gridref.active {
    border:1px solid #210b7b;
}
#maparea input#grid_reference.active {
    border:1px solid #5300ff;
}
#maparea label.active {
    background-color:yellow;
}
#dist_message {
    padding-left:10px;
    min-height:22px;
    color:brown;
}

/* Hide button by default (Desktop/Mouse) */
#toggleBtn {
  display: none;
}

/* Show only if the primary input is a touch screen */
@media (pointer: coarse) {
  #toggleBtn {
    display: inline-block;
  }
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
    padding:6px;
    font-size:1.1em;
    margin-bottom: 100vh;
}
.field-header .info-icon {
    position: relative; top:0 !important; left:10px;
}

.field-header .info-icon::after {
    left:30px; right:unset;
}

/* -- used nearby --------------------------------------- */

/* Container and Backdrop */
dialog#tag-selector-modal {
    padding: 0;
    border: none;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    max-width: 500px;
    width: 90vw;
    max-height: 90vh;
}

/* Header & Footer Layout */
dialog#tag-selector-modal .tag-modal-header,
dialog#tag-selector-modal .tag-modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
    white-space: nowrap;
}

dialog#tag-selector-modal .tag-modal-header {
    padding: 12px 20px;
    border-bottom: 1px solid #ddd;
}
dialog#tag-selector-modal .tag-modal-header button {
    width:100px; color:red;
}

dialog#tag-selector-modal .tag-modal-footer {
    border-top: 1px solid #ddd;
    padding: 0px 8px;
}

/* Scrollable List Area */
dialog#tag-selector-modal .tag-list-scroll {
    overflow-y: auto;
    max-height: 60vh;
    background: #fff;
}

/* Individual Row Styling */
dialog#tag-selector-modal .tag-item-row {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
    font-weight: normal;
}
@media screen and (max-width: 450px) {
    dialog#tag-selector-modal .tag-item-row {
        padding: 5px 5px;
    }
}

dialog#tag-selector-modal .tag-item-row:hover {
    background-color: #f0f7ff;
}

/* Typography & Badges inside rows */
dialog#tag-selector-modal .tag-dist {
    font-family: monospace;
    color: #666;
    flex-shrink: 0;
    margin-right:6px;
}

dialog#tag-selector-modal .tag-attr-type {
    background: #eee;
    border-radius: 8px;
    margin-left:2px;
    margin-right: 8px;
    color: #555;
    text-align: center;
}

dialog#tag-selector-modal .tag-label-text {
    flex-grow: 1;
    font-weight:600;
}
dialog#tag-selector-modal .tag-label-text span {
    font-weight:normal; color:gray;
}

dialog#tag-selector-modal .tag-count {
    text-align:right;
    width:30px;
    color:silver;
}

/* Checkbox/Radio spacing */
dialog#tag-selector-modal input[type="checkbox"],
dialog#tag-selector-modal input[type="radio"] {
    margin-right: 12px;
    transform: scale(1.1);
    width:inherit;
    margin-bottom:0;
}

/* The Floating Note */
dialog#tag-selector-modal .tag-modal-floating-note {
    position: absolute;
    /* Positions it above the modal box */
    bottom: calc(100% + 15px); 
    left: 50%;
    transform: translateX(-50%);
    
    /* Visual styling */
    background: rgba(0, 0, 0, 0.85); /* Dark background */
    color: #fff;
    padding: 10px 18px;
    border-radius: 20px;
    font-size: 0.85rem;
    width: 280px; /* Constrain width so it looks like a bubble */
    text-align: center;
    line-height: 1.4;
    box-shadow: 0 4px 15px rgba(0,0,0,0.4);
    pointer-events: none; /* So it doesn't block clicks to the backdrop */
    z-index: 10;
}

/* Add a little 'tail' to the bubble (optional) */
dialog#tag-selector-modal .tag-modal-floating-note::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -8px;
    border-width: 8px;
    border-style: solid;
    border-color: rgba(0, 0, 0, 0.85) transparent transparent transparent;
}

/* Ensure the dialog itself doesn't 'clip' the floating note */
dialog#tag-selector-modal {
    overflow: visible; /* CRITICAL: allows content to hang outside the box */
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

    <script>
        window.user_id = <? echo intval($USER->user_id); ?>;
    </script>

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

<? if (!empty($CONF['submission_message'])) {
       include __DIR__."/offline-message.inc.php";
} ?>

<div id="top-boundary"></div>

<div class="main-header" id="mainHeader">
    <div id="image-dimensions" style="padding: 10px; font-weight: bold; background: #eee;"></div>
    <img id="imgLarge" class="preview-img-large" src="" alt="Full Preview">
    <div class="controls">
        <button onclick="rotateImage(270)"><span>&#8634;</span> Rotate Left</button>
        <button onclick="rotateImage(90)">Rotate Right <span>&#8635;</span></button>
    </div>

    <div id="orientation_message" style="display:none">
	&#9888; Browsers and devices handle image orientation differently. &#9888;<br> If you are seeing this warning, please rotate the image sideways
	and back to upright, <b class=nowrap">even if it looks OK to you initially</b>. This will ensure it is displayed correctly across all devices.
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
        <button type="button" onclick="openModal('map-modal')" class="help-link">How to use this map &#9432;</button>
    </div>

	<div id="maparea">

	<div class="map-wrapper">
	        <div id="map"></div>
		<div class="map-edge-guard"></div>
	</div>


        <div class="controls">
<button type=button
    id="toggleBtn" 
    onclick="toggleLock()" 
    style="padding: 8px 12px; cursor: pointer; align-items: center; float:right"
    title="Toggle Edit"
  >
    <span id="btnIcon">&#9000;</span>
  </button>

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

<script>
function toggleLock() {
  const input1 = document.getElementById('photographer_gridref');
  const input2 = document.getElementById('grid_reference');
  const icon = document.getElementById('btnIcon');

  if (input1.hasAttribute('readonly')) {
    // UNLOCK: Transition to Keyboard
    input1.removeAttribute('readonly');
    input2.removeAttribute('readonly');
    input1.setAttribute('inputmode', 'text'); // Allow keyboard
    input2.setAttribute('inputmode', 'text');
    icon.innerHTML = '&#128274;'; // Lock entity (to signify button is to 'relock')

    // Move cursor to end
    if (input1.classList.contains('active')) {
	    const val = input1.value;
	    input1.focus();
        input1.value = '';
        input1.value = val;

    } else if (input2.classList.contains('active')) {
	    const val = input2.value;
	    input2.focus();
        input2.value = '';
        input2.value = val;
    }

  } else {
    // LOCK
    input1.setAttribute('readonly', 'true');
    input2.setAttribute('readonly', 'true');
    input1.setAttribute('inputmode', 'none'); // Hide keyboard
    input2.setAttribute('inputmode', 'none');
    icon.innerHTML = '&#9000;'; // Keyboard entity (to signifcan can unlock)
    if (input1.classList.contains('active')) {
        input1.focus(); //still focus it to keep focus on the map (otherwise focus may jump to date/title box!)
    } else if (input2.classList.contains('active')) {
        input2.focus();
    }
  }
}
</script>

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
            <div id="dist_message"></div>

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
                <p><strong>Quick Tip:</strong> If your photo already has GPS data, all need to do is simply double-tap the subject on the map and drag to refine if necessary.</p>
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
	    <textarea name="comment" id="localDesc" placeholder="optional longer description" rows="5" style="field-sizing: content; min-height: 80px;"></textarea>

        <div id="suggestion-pill-bar" class="hidden no-results"></div>


        <div class="field-header">
            <label>Shared Descriptions</label> <span class="optional-label">(optional)</span>
            <select class="recent-select" id="recent-snippets" onchange="useRecentSnippet(this)">
                <option value="">Recently Used</option>
            </select>
        </div>
	    <div class="tag-input-container">
            <div id="active-snippets" class="active-tags"></div>
            <input type="search" id="snippet-search" placeholder="Type to search descriptions...">
            <div id="snippet-suggestions" class="dropdown"></div>
        </div>

	  <button type="button" onclick="openCreateSnippetModal()" class="btn btn-secondary" style="width: auto; display: inline-block; margin: 0 0 0 10px; padding: 10px 20px;">Create New Shared Description</button>
	  <button type="button" onclick="openModal('tag-modal')" class="help-link">What do all these fields mean? &#9432;</button>


<dialog id="tag-modal" onclick="closeModal('tag-modal')">
    <div style="max-height: 80vh; overflow-y: auto; padding: 10px;">
	    <section>
	        <h2>Title</h2>
	        <p>Short title for the image. Aim to be descriptive; you don't need lots of details. This is a good place to mention the name of the place if it is relevant to the image.</p>
	    </section>

	    <section>
	        <h2>Description</h2>
	        <p>Optionally, you can provide more details. Go into as much detail as you want here; for example, you can provide more information about the place, link to other images, and/or provide links to other websites with more info.</p>
	    </section>

	    <section>
	        <h2>Shared Descriptions</h2>
	        <p>Contributors can write descriptions that can be attached to multiple images. You might find someone has already written such a description for the place or subject you photographed. You are welcome to attach SDs created by others to your image.</p>

    		<p>Note: at this time, it's not possible to edit SDs via the App. Use the main website to edit a SD.</p>
	    </section>

	    <section>
	        <h2>Date Taken</h2>
	        <p>We do ask for the date the photo was taken if at all possible. If you really don't know, or can only provide the approximate year/month, then that is fine too.</p>
	    </section>

	    <blockquote style="background-color:#d4e5bd;">
	        <strong>Tip:</strong> Use <strong>'View Nearby Tags'</strong> to see tags and shared descriptions used nearby. These are simply images that happen to be in the vicinity, so while many suggestions won't be correct, you might find some interesting ones you can use.
	    </blockquote>

	    <section>
	        <h2>Geographical Context</h2>
	        <p>Select a few relevant tags from the list that describe the general environment where the image was taken and what it depicts. You aren't expected to be perfectly accurate here; just pick the ones that feel relevant.</p>
		<p>Tip: Some Context might be highlighted in blue, these are suggestions of possible labels for the specific image. You don't have to follow the suggestions, they are just aiming to provide a starting point.
	    </section>

	    <section>
	        <h2>Subject</h2>
	        <p>We provide a list of tags intended to denote the <strong>primary</strong> subject of the photo (as opposed to everything the image covers). Use the dedicated Subject search to find a possible tag for the main subject.</p>
		<p>Tip: When first click the Subject search box, may see a short list of automatic suggestions. We might have found a likly subject for your image, not the top suggestion may not be the best, look though all ten suggestions for the best one.
	    </section>

	    <section>
	        <h2>Tags</h2>
	        <p>Here you can provide as much detail as you can for the image. This is entirely optional, but it may make your image much more findable.</p>
	    </section>

        <section>
            <h2>Special Flags</h2>
            <p>We ask that images taken by drone be specifically marked. Additionally, if the image is a wide-angle panorama, you can flag it as such. This enables a special viewer on the photo page, allowing users to pan and zoom into high-resolution images. We allow panoramas up to 12MB in size with unlimited resolution.</p>
	    <p>Note: Its recommended, to first submit a normal viewing angle (eg a single shot from a stitched panaorama) first as the main image. Then add the full panorama as a 'Larger Upload' aftewards (link will be provided after submission).
        </section>

        <br><br>
        <button type="button" onclick="closeModal('pano-modal')">Close</button>
    </div>
</dialog>



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

        <br><br>
        <button type=button class="btn btn-secondary" onclick="showNearbyTagsModalWrapper();" style="background-color:#d4e5bd;">Tags Used Nearby</button>

        <div class="field-header">
    	    <label>Geographical Contexts</label>
            <span class="optional-label" id="context-count">(select multiple)</span>
        </div>
	    <select name="contexts[]" id="contexts" multiple size=10 required></select>

        <div class="field-header">
            <label for="subject-input">Primary Subject</label> <span class="optional-label">(optional)</span>
            <select class="recent-select" id="recent-subjects" onchange="useRecentSubject(this)">
                <option value="">Recently Used</option>
            </select>
        </div>
	    <div class="tag-input-container">
            <input type="search" name="subject" id="subject-input" placeholder="Search subjects...">
            <div id="subject-suggestions" class="dropdown"></div>
            <datalist id="subject-list"></datalist>
            <input type="hidden" name="subject_id" id="subject-id">
        </div>

        <div class="field-header">
            <label>Free-form Tags</label> <span class="optional-label">(optional)</span>
            <select class="recent-select" id="recent-tags" onchange="useRecentTag(this)">
                <option value="">Recently Used</option>
            </select>
        </div>
	    <div class="tag-input-container">
            <div id="active-tags" class="active-tags"></div>
            <input type="search" id="tag-search" placeholder="Type to add tags...">
            <div id="tag-suggestions" class="dropdown"></div>
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

        		<button type="button" onclick="openModal('pano-modal')" class="help-link">How to Submit Panoramas &#9432;</button>

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
            licence, including accompanying metadata. <button type="button" class="help-link"
                onclick="openModal('license-modal');">Read More &#9432;</button> </p>

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

                <p>You are releasing this image at <span id="final-dimensions">[d x d]</span>.

	            <p><a href="/help/freedom" target="_blank">Open Geograph Freedom Manifesto</a> <span class="nowrap">(Opens in new tab)</span></p>

                <button type="button" onclick="closeModal('license-modal')">Close</button>
            </dialog>
        </fieldset>

	</div>

	<button type=submit class="btn btn-primary" id="submit_button">I Agree - Submit Image</button>


	<button type=button class="btn btn-secondary" onclick="navigateTo('/app/uploaded')" style="width:100%">Return to list Without Submitting</button>


	<br><br>
</form>

<dialog id="create-snippet-modal">
    <div style="max-height: 80vh; overflow-y: auto;">
        <h3>Create New Shared Description</h3>
        <form id="create-snippet-form">
            <label for="snippet-title">Title</label>
            <input type="text" id="snippet-title" name="title" maxlength="64" required placeholder="Short descriptive title">

            <label for="snippet-comment">Description</label>
            <textarea id="snippet-comment" name="comment" maxlength="16384" required rows="7" placeholder="Detailed description..."></textarea>

            <label for="snippet-gridref">Grid Reference <span style="font-weight:normal">(if SD is for a specific location)</span></label>
            <input type="text" id="snippet-gridref" name="grid_reference" pattern="^[A-Za-z]{1,2}\s*\d{1,5}\s*\d{1,5}$" placeholder="e.g. TQ 123 456">

            <label class="flag-item" for="snippet-nogr">
                <input type="checkbox" id="snippet-nogr" name="nogr" value="1" onchange="toggleSnippetGridRef(this.checked)">
                <span><b>Do not Attach Location</b></span>
            </label>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Create</button>
                <button type="button" onclick="closeModal('create-snippet-modal')" class="btn btn-secondary" style="flex: 1;">Cancel</button>
            </div>
        </form>
    </div>
</dialog>

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
        import { escapeHTML, escapeRegex, navigateTo, openModal, closeModal, updateAppState } from '/app/js/utils.js?<? echo filemtime('js/utils.js'); ?>';
        //so the page can use it
        window.escapeHTML = escapeHTML;
        window.escapeRegex = escapeRegex;
        window.navigateTo = navigateTo;
	window.openModal = openModal;
	window.closeModal = closeModal;
	window.updateAppState = updateAppState;
</script>

<script src="<? echo smarty_modifier_revision("/js/Geograph.MediaDatabase.class.js"); ?>"></script>
<script src="<? echo smarty_modifier_revision("/app/js/used-nearby.libs.js"); ?>"></script>
<script src="<? echo smarty_modifier_revision("/app/js/suggestions.libs.js"); ?>"></script>
<script src="<? echo smarty_modifier_revision("/app/js/contexts.libs.js"); ?>"></script>
<script src="<? echo smarty_modifier_revision("/app/js/subjects.libs.js"); ?>"></script>
<script src="<? echo smarty_modifier_revision("/app/js/tags.libs.js"); ?>"></script>
<script src="<? echo smarty_modifier_revision("/app/js/snippets.libs.js"); ?>"></script>
<script src="<? echo smarty_modifier_revision("/app/js/recents.libs.js"); ?>"></script>

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

    let dbHistory;

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
            } else {
        		currentWidth = null; //so it can be autoloaded from image!
    	    }

            ////////////////////////////

            let hasGeo = false;
            //uploaded page will send grid_reference+photographer_gridref
            if (data.grid_reference)
                document.getElementById('grid_reference').value = data.grid_reference;
            if (data.photographer_gridref) {
                document.getElementById('photographer_gridref').value = data.photographer_gridref;
                centerMap(data.photographer_gridref);
                saveMapPosition(map, 'Location from EXIF');
                hasGeo = true;
            }

            //but submit will send lat/long!
            if (data.lat) { //long may be exacty zero (meridian!
                setLatLong(data.lat, data.long, 'photographer_gridref','EXIF')
                saveMapPosition(map, 'Location from EXIF');
                hasGeo = true;
            }

            let warningBox = document.getElementById('guessed_location');
            if (warningBox) warningBox.remove();

            //if there is no geolocation, see if we can guestimate from media database
            if (!hasGeo && data.imagetaken) {
                if (!dbHistory) dbHistory = new MediaDatabase();

                //we are not an async function so not using await, also dont want to hold this function up!
                dbHistory.findApproximateLocationByExifDate(data.imagetaken).then(result => {
                    if (result && result.lat) {
                        setLatLong(result.lat, result.long, 'photographer_gridref', 'Nearest / '+result.filename)
                        saveMapPosition(map, 'Location from Nearest Image');

                        if (result.diffSeconds > 10) { //within 10 seconds it likly it was just matched against the same image! (just that lcoation was stripped during upload)
                            warningBox = document.createElement('div');
                            warningBox.id = 'guessed_location';
                            warningBox.style.padding = '20px';
                            warningBox.style.backgroundColor = '#fbfbe1';
                            warningBox.style.textAlign = 'center';
                            warningBox.textContent = "The location has been estimated from an image ("+result.filename+") taken about the same time. Please check the circle is correctly located.";
                            document.getElementById('maparea')?.before(warningBox);
                        }
                    }
                });
            }

            ////////////////////////////

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

            loadContexts(); //note it has it own inbuilt 'recent' render!
            loadSubjects();

            renderRecent('submit.subjects', 'recent-subjects');
            renderRecent('submit.tags', 'recent-tags');
            renderRecent('submit.snippets', 'recent-snippets');

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
        document.getElementById("active-snippets").innerHTML = '';

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

//////////////////////
    const standardSrc = `/submit.php?preview=${upload_id}`;
    const peekSrc = `/app/peek.jpg.php?preview=${upload_id}`;

    //need to reset from previous (particully after a rotate event!)
    //but NOT reset currentWidth!
	imgLarge.style.width = null;
    imgLarge.style.height = null;

    // Set initial source
    imgLarge.src = standardSrc;

    // Define the "Show Detail" action
    const showDetail = (e) => {
        imgLarge.src = peekSrc;
        imgLarge.style.objectFit = 'cover';
    };

    // Define the "Restore Original" action
    const hideDetail = () => {
        imgLarge.src = standardSrc;
    };

    // Desktop Events
    imgLarge.onmousedown = showDetail;
    imgLarge.onmouseup = hideDetail;
    imgLarge.onmouseleave = hideDetail; // Restore if they drag the mouse off

    // Mobile/Touch Events
    imgLarge.ontouchstart = showDetail;
    imgLarge.ontouchend = hideDetail;
    imgLarge.oncontextmenu = (e) => e.preventDefault(); //turns out still need to surpress!

//////////////////////

        // Get dimensions from image load
        imgLarge.onload = function() {

    		if (imgLarge.src.includes('submit.php')) {
		    	// Once the full preview loads, lock its rendered dimensions. Because of exif Rotation, can't just use naturalWidth!
                const rect = imgLarge.getBoundingClientRect();
                if (rect.width) { //during resume might not have a visible box.
                    imgLarge.style.width = rect.width + 'px';
                    imgLarge.style.height = rect.height + 'px';
                }
	    	}

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
            finalDimsEl.textContent = finalDimText + (window.uploadMaxDimension >= 65536 ? ' (at Full Resolution)' : ' specifically, any larger sizes wont be released');
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
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();

            if (result.width && result.upload_id) {
                imgLarge.style.opacity = 1;
                updatePreview(result.upload_id);

                //should be updating these too. but the main reason to scroll into view, snapp the page to top. Otherwise can move down the page!
                document.getElementById('image-dimensions').scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
                });

	            //And need to uplodate the App, so resume works!
                updateAppState({upload_id: result.upload_id});

                //no longer relevent
                document.getElementById("orientation_message").style.display='none';

            } else if (result.lossy) {
                if (confirm("This image cannot be rotated losslessly. A small amount of quality loss may occur at the edges. Continue?")) {
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
    // Check the form is ready to submit. Mostly use standard 'required' but have some custom valdiation too.

    async function validateForm(event) {
        const form = this;

	    // This stops the browser from leaving the page while we 'await'
        event.preventDefault();

        ////////////////////
        // check required

        if (localTitle.value.trim() === '') {
	        //this only happens in isSmall (as the native required didnt work!)
            localTitle.readOnly = false; // Temporarily unlock
            localTitle.setCustomValidity('Please fill out this Field');
            localTitle.reportValidity();
            localTitle.focus();
            return false;
        }

        const select = document.getElementById('contexts');
        const selected = Array.from(select.selectedOptions).map(o => o.value);

        if (selected.length === 0) {
            //this should never be needed, as should via native 'required', but included for completeness
            alert("Please select at least one Geographical Context");
            return false;
        }

        const input = document.getElementById('subject-input');
        if (input.value.length>0) { //still optional!

            const hiddenId = document.getElementById('subject-id');
            const options = document.querySelectorAll('#subject-list option');
            if (options.length) {
                const valueLower = input.value.toLowerCase().replace(/\*$/,''); //need to remove the AI marker

                // Find if the typed value matches a valid tag
                const match = Array.from(options).find(o => o.value.toLowerCase() === valueLower);

                if (match) {
                    hiddenId.value = match.dataset.id;
                    input.setCustomValidity(""); // Clear any previous error
                } else {
                    // This triggers the browser's built-in validation bubble
                    input.setCustomValidity("Please select a subject from the list");
                    input.reportValidity(); // This forces the browser to show the bubble immediately
                    input.focus();
                    return false;
                }
            } else {
                //if the subject list failed to load we cant validate anything.
                //for now, will just have to let the form continue.
            }
        } else {
            input.setCustomValidity(""); // Clear any previous error
        }

        ////////////////////
        //ah, should check we actully online still!

    	let online = await checkOnline(form);
	    if (!online)
		    // The checkOnline function already showed the error message,
    		return false;

        ////////////////////
        //success, so final cleanup..

        //context are required anyway! (so should always be present)
        saveRecent('submit.contexts', selected);

        if (input.value) saveRecent('submit.subjects', [input.value]);

        // (selectedTags is the Set you used in your addTag logic)
        if (selectedTags.size > 0) {
            saveRecent('submit.tags', Array.from(selectedTags));
        }

        //selectedSnippets is a now an Object to contain titles
        const snippetsToSave = Object.entries(selectedSnippets).map(([id, title]) => { return `${id}:::${title}`; });
        if (snippetsToSave.length > 0) {
            saveRecent('submit.snippets', snippetsToSave);
        }

        //save this for next time!
        if (map && saveMapPosition)
            saveMapPosition(map, 'Position of Last Submission');

        ////////////////////
   	    // We use form.submit() instead of triggering another 'submit' event
    	// to avoid an infinite loop.
	    form.submit();

        return true;
    }

    document.forms['theForm'].addEventListener('submit', validateForm);

    async function checkOnline(formElement) {
        const submitBtn = formElement.querySelector('[type="submit"]');
        const originalText = submitBtn.innerText;

        // 1. Visual Feedback
        submitBtn.disabled = true;
        submitBtn.innerText = "Checking connection...";

        try {
            // 2. Ping your new Status API (with a short timeout)
            const controller = new AbortController();
            const id = setTimeout(() => controller.abort(), 5000); // 5 sec timeout

            const response = await fetch('/app/status.json.php', {
                cache: 'no-store',
                signal: controller.signal
            });
            clearTimeout(id);

            if (response.ok) { //.status = 'online'
                // SERVER IS ONLINE - Allow the standard POST to happen
                return true;
            } else {
           	    const data = await response.json();

        		//if json fails to parse (eg it a 502/504 from cloudflare, then falls though to the catch block

                // SERVER IS IN MAINTENANCE (503)
        	    //we dont care about .message which informs about upcoming maintence, instead we care about if the is actully in readonly mode
                if (data.status === "readonly") {
    	            showSubmissionError(submitBtn, "Site is currently offline for maintenance. Wait a few minutes and try clicking again.");

    	        //if the within maintaince, but writable, allow the form to continue!
    	        } else if (data.status === "maintenance") {
        		    return true;
        	    } else {
    	    	    // 3. If we got JSON but no 'status' field, it might be a 3rd party error (Cloudflare/Proxy)
            	    throw new Error("Invalid Status Format");
        	    }
            }
        } catch (error) {
            // NETWORK IS DOWN (No internet or DNS failure)
            showSubmissionError(submitBtn, "Connection failed. Please check your internet and click 'I Agree' again. Your data is safe in this form.");
        }

        // Reset button if we didn't submit
        submitBtn.disabled = false;
        submitBtn.innerText = originalText;
        return false; // Prevent the form from submitting
    }

    function showSubmissionError(submitBtn, message) {
    	let errorBox = document.getElementById('submission_error');
    	if (!errorBox) {
            errorBox = document.createElement('div');
            errorBox.id = 'submission_error';
            errorBox.style.padding = '20px';
            errorBox.style.backgroundColor = '#fbfbe1';
            submitBtn.before(errorBox);
    	}
    	errorBox.textContent = message;
    }

// --------------------------------
// Map - ported from mobile submit

    var map = null;
    var issubmit = false; //we do it manually.
    var geocoder = null;
    var disableAutoUpdate = false;
    var leafletBaseKey = 'LeafletBase'; //at the moment, we dont know what grid it will be!
    var checkedonce = false;
    var crosshair;
    var disableTimer;
    var isTouchingMap = false;

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

        crosshair = L.geotagPhoto.crosshair({
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

                //if a element is focused, it will get blurred, so need to cancle the timer
                if (disableTimer) clearTimeout(disableTimer);

                //but also if map is "disabled", ignore the click
                if (!map.dragging.enabled())
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

        //also need to prevent interactions with map from deactiving it!
        const mapContainer = map.getContainer();
        ['mousedown', 'touchstart'].forEach(type => {
            mapContainer.addEventListener(type, (e) => {
                isTouchingMap = true;
                if (disableTimer) clearTimeout(disableTimer);

                if (map._notifying) return;

                const isLocked = mapContainer.classList.contains('map-locked');

                // We check if the user hit a marker or control.
                // If they hit the background tiles, show the hint.
                const isInteractive = e.target.closest('.leaflet-interactive') ||
                                     e.target.closest('.leaflet-control');

                if (isLocked && !isInteractive) {
                    map._notifying = true;
                    map_notify('Select either Camera or Subject', 'rgb(0,0,0,0.3); backdrop-filter: blur(4px);');
                    setTimeout(() => { map._notifying = false; }, 10000);
                }

            }, true); // <--- This 'true' is the magic capture flag
        });
        window.addEventListener('mouseup', () => {
            // Small delay so the blur timer can finish its check first
            setTimeout(() => { isTouchingMap = false; }, 300);
        }, true);

        if (typeof setupQuota === 'function') {
            setupQuota(map, baseMaps['Modern OS - GB']);
        }

    }

    function map_notify(text, color) {
        const msg = L.DomUtil.create('div', '', map.getContainer());
        msg.style.cssText = `position:absolute; top:70px; left:50%; transform:translateX(-50%); background:${color}; color:white; padding:8px 15px; border-radius:4px; z-index:1000; font-family:sans-serif; font-size:13px; pointer-events:none; box-shadow:0 2px 5px rgba(0,0,0,0.3); transition:opacity 1s;`;
        msg.innerHTML = text;
        setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 1000);
        }, 3000);
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

        // A central function to handle all state changes
        function enableMap(active) {
            const method = active ? 'enable' : 'disable';

            // 1. Core Handlers
            map.dragging[method]();
            map.touchZoom[method]();
            map.scrollWheelZoom[method]();
            //not, we DONT enable doubleclickzoom here! (as have own handler!)
            //note, deliberately NOT disabling zooming by the control/buttons, that is 'safe' as doesnt recenter the map

            // 2. Locate Control
            // If we are disabling, stop following immediately (unlikly but could of been using locate to set camera!)
            if (!active && locateControl) {
                //we dont just blindly call stopFollowing, as it will enable the location, if not already on
                if (locateControl._active &&
                   ((typeof locateControl._isFollowing === 'function' && locateControl._isFollowing()) || locateControl._following)) {
                    locateControl.stopFollowing();
                }
            }

            // 3. Visual & Performance Feedback
            const mapContainer = map.getContainer();
            if (!active) {
                //mapContainer.style.touchAction = 'pan-y'; // Allow page scroll
                mapContainer.classList.add('map-locked');
                crosshair.removeFrom(map);
            } else {
                //mapContainer.style.touchAction = 'none'; // Map takes control
                mapContainer.classList.remove('map-locked');
                crosshair.addTo(map);
            }
            const notesBar = document.querySelector('.notes-bar');
            if (notesBar) notesBar.classList.toggle('hidden', !active);
        }

        const localTitle = document.getElementById('localTitle');
        if (localTitle) {
            //this is tricky, they could have set positions by never actully giving focus either <input>, so we also need to catch them when they just moved onto the title
            localTitle.addEventListener('pointerdown', function() {
                if (map.dragging.enabled()) {
                    enableMap(false);
                    clearActive();
                }
            });
        }
        //these are considered part of the map, and so SHOULDNT disable the map eithr!
        document.querySelectorAll('#maparea select').forEach(select => {
            select.addEventListener('focus', function() {
                if (disableTimer) clearTimeout(disableTimer);
                //if (!map.dragging.enabled()) {
                //    enableMap(true); --actully shouldnt do that without knowing which one to enable! I guess could pick one
                //}
            });
        });

        // Check if primary input is touch (coarse)
        const isTouch = window.matchMedia('(pointer: coarse)').matches;

        // Input events
        inputs.forEach(input => {
            if (isTouch) {
                // Only restrict if it's a touch device.
                // Setting these to prevent the keyboard poping up and moving things around. In theory they dont really need keybaord, so just gets in way.
                // but we provide a dedicated unlock, so they can remove these if needed.
                input.setAttribute('readonly', 'true');
                input.setAttribute('inputmode', 'none');
            }

            // Focus event
            input.addEventListener('focus', function() {
                if (disableTimer) clearTimeout(disableTimer);

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

                updateActiveMode(); //actully updates the visual prompt
                if (!map.dragging.enabled()) {
                    enableMap(true);
                }
            });
            // Blur event
    	    input.addEventListener('blur', function() {
                //dont want to disable map, if the just SWITCHING to other mode
                disableTimer = setTimeout(function() {
                    if (!isTouchingMap) {
                        enableMap(false);
                        clearActive();
                    }
                }, 300);
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

        // Note we no longer add click handers for labels. The goal was to prevent the keyboard poping up. But we do that more reliably with readonly attribute now.
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
    	//if (!parentWin.updateRemoteLayout) {
    	    parentWin.updateRemoteLayout = function() {
    		    if (parentWin.visualViewport && remoteOverlay.style.display === 'flex') {
    		        const vv = parentWin.visualViewport;
    		        remoteOverlay.style.height = `${vv.height}px`;
    		        remoteOverlay.style.top = `${vv.offsetTop}px`;
    		        parentWin.scrollTo(0, 0);
    		    }
    	    }
    	    parentWin.currentMode = currentMode;
    	//}

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

            // Only launch suggestions if it's the title field, has a meaningful length, and the function exists
            if (localTitle.value.trim().length > 5 && typeof fetchAndProcessSuggestions !== 'undefined') {
                fetchAndProcessSuggestions(upload_id, localTitle.value.trim()); // no need to await
            }

    		//need to make sure to remove this, so the next iframe can add its own
    	    parentWin.visualViewport.removeEventListener('resize', parentWin.updateRemoteLayout);
    	    parentWin.visualViewport.removeEventListener('scroll', parentWin.updateRemoteLayout);
    	};

    	suggBar = parentDoc.getElementById('remoteSuggBar');

    	//if (!suggBar.dataset.listenerAttached) { --- actully maybe this doesnt work!

    		// Event Listeners for switching modes inside the overlay
    		btnTitle.onclick = () => setMode('title');
    		btnDesc.onclick = () => setMode('desc');

    		// need to also set the modes (for portrait, when both bisible!)
    		titleInp.onfocus = () => setMode('title');
    		descArea.onfocus = () => setMode('desc');

		//note we directly setting onclick rather than addEventListener, to make sure clear previous one
    		suggBar.onclick = useSuggection;

    		// 1. Attach listeners to the remote elements
    		// This should be done right after they are created in the parentDoc
    		titleInp.oninput = handleInput;
    		descArea.oninput = handleInput;

    	//	suggBar.dataset.listenerAttached = "true"; // Flag it as "already handled"
    	//}

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
        // Only launch suggestions if it's the title field, has a meaningful length, and the function exists
        if (e.target.id == 'localTitle' && e.target.value.trim().length > 5 && typeof fetchAndProcessSuggestions !== 'undefined') {
            fetchAndProcessSuggestions(upload_id, e.target.value.trim()); // no need to await
        }

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

  const done = {};

  // Helper to create the pill buttons
  const createPill = (name, title = '') => {
    if (done[name])
	return;
    done[name] = true;
    const pill = document.createElement('button');
    pill.className = 'suggestion-pill';
    pill.type = 'button';
    pill.textContent = name;
    pill.title = title;
    if (title == 'building' || title == 'amenity' || title == 'shop') {
        suggBar.prepend(pill);
    } else {
        suggBar.appendChild(pill);
    }
  };

  suggBar.innerHTML = '';

  try {
    // 1. Process Local Gazetteer Results
    try {
      const script_name = (ri == 2) ? "ie_open_data.json.php" : "os_open_names.json.php";
      const localResp = await fetch(`/stuff/${script_name}?e=${eastings}&n=${northings}`);
      if (!localResp.ok) {
        throw new Error(`HTTP error! status: ${localResp.status}`);
      }
      const localData = await localResp.json();

      if (localData?.rows?.length) {
        for (const item of localData.rows) {
          for (const key of ['name1', 'name2', 'name', 'irish']) {
            if (item[key]) createPill(item[key], item.local_type ?? item.town_type ?? '');
          }
        }
      }
    } catch (localErr) {
      console.error("Local Fetch failed", localErr);
    }

    // 2. Fetch OSM Nominatim fallback/supplement
    try {
      let grid = (ri == 1) ? new GT_OSGB() : new GT_Irish();
      grid.setGridCoordinates(eastings, northings);
      let conv = grid.getWGS84(true);
      if (!conv || conv.status != 'OK') //conversion could fail! (although unlikly)
	throw new Error(`Unable to convert`);

      const osmUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${conv.latitude}&lon=${conv.longitude}&zoom=18`;
      const osmResp = await fetch(osmUrl, {
        headers: { 'User-Agent': 'GeographApp/1.2 +https://www.geograph.org.uk/' }
      });
      if (!osmResp.ok) {
        throw new Error(`HTTP error! status: ${osmResp.status}`);
      }
      const osmData = await osmResp.json();

      //if (osmData.display_name) { --- actully want to look at parts
      if (osmData.address) {
          for (const key of ['amenity', 'shop', 'building', 'road', 'suburb', 'town', 'county']) {
              if (osmData.address[key]) createPill(osmData.address[key], key);
          }
      }
      if (osmData.name) //possible duplicates one of the address components, but we deduplicate anyway
          createPill(osmData.name, 'OpenStreetMap');

    } catch (osmErr) {
      console.error("OSM Fetch failed", osmErr);
    }

    // UI state check
    if (suggBar.children.length > 0) {
      suggBar.classList.remove('no-results');
    } else {
      suggBar.classList.add('no-results');
    }

  } catch (err) {
    suggBar.classList.add('no-results');
    console.error(err);
  } finally {
    isFetching = false;
  }
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

    window.openCreateSnippetModal = function() {
        const mainGR = document.getElementById('grid_reference').value;
        document.getElementById('snippet-gridref').value = mainGR;
        document.getElementById('snippet-gridref').disabled = false;
        document.getElementById('snippet-nogr').checked = false;
        document.getElementById('create-snippet-form').reset();
        document.getElementById('snippet-gridref').value = mainGR; // Reset clears it, so set again
        openModal('create-snippet-modal');
    };

    window.toggleSnippetGridRef = function(nogr) {
        document.getElementById('snippet-gridref').disabled = nogr;
    };

    document.getElementById('create-snippet-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('create', 'true');

        try {
            const response = await fetch('/submit_snippet.php?json=1', {
                method: 'POST',
                body: formData
            });
            if (!response.ok) throw new Error('Network response was not ok');
            const result = await response.json();
            if (result.error) {
                alert('Error: ' + result.error);
            } else {
                addSnippet(result.id, result.title);
                closeModal('create-snippet-modal');
            }
        } catch (error) {
            console.error('Error creating snippet:', error);
            alert('Failed to create shared description. Please try again.');
        }
    });

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
        const isTrackedHidden = el.type === 'hidden' && (el.name === 'tags[]' || el.name === 'snippets[]'); //only dynamic tags

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
