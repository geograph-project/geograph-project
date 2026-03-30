<?php

require_once('geograph/global.inc.php');
require_once('geograph/uploadmanager.class.php');

init_session();

$USER->mustHavePerm('basic');

customNoCacheHeader();

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (!empty($_GET['mail'])) {
	$subject = "[Geograph] Link to process your submissions";
	$body = <<< END
Open this link:
https://www.geograph.org.uk/viewer/processor.php
To process your submissions. This is a one-off task, but can rerun it periodically to process new submissions.

On a computer, you can leave this running in a tab until it finishes.

Any problems contact as at {$CONF['contact_email']}
END;

	$r = mail_wrapper($USER->email, $subject, $body, "From: Geograph <noreply@geograph.org.uk>");

	if ($r) print "Email sent;<hr>";
}

$user_id = intval($USER->user_id);

$count = $db->getOne("select count(*) from gridimage_search gi left join gridimage_hash using (gridimage_id) where gi.user_id = $user_id and gridimage_hash.gridimage_id is null");

//$count = $db->getOne("select count(*) from gridimage gi left join gridimage_hash using (gridimage_id) where gi.user_id = $user_id and gridimage_hash.gridimage_id is null and moderation_status = 'pending'");

$count = 1000;

if (!empty($count) && empty($_GET['ignore'])) {
	$seconds = intval($count * 20 / 50);
	if ($seconds < 360) {
        	$seconds += 10; //just to will be a bit of extra overhead
	        $time = "$seconds seconds";
	} elseif ($seconds < 60*60) {
        	$seconds += 100;
	        $time = ceil($seconds/60)." minutes";
	} else {
        	$seconds += 1000;
	        $time = "<b>".ceil($seconds/60/60)." hours</b> - you can leave it processing overnight";
	}
	?>

	<div style="max-width:940px">

	<p>You have <b><? echo number_format($count, 0); ?></b> images awaiting processing. We use "perceptual hashing" to visually match your submissions across different 
	resolutions and identify duplicates.</p>


    <h2>One-Time Archive Scan</h2

    <p>Status: <strong><?php echo $count; ?> images to process.</strong> (Estimated time: <strong><?php echo $time; ?></strong>)</p>

    <?php if ($count <= 30) { ?>
        <div id="inline-processor">
            <p>Since this is a small batch, you can run it right here:</p>

            <a href="/viewer/processor.php?inner=1" target="processor"
               onclick="document.getElementById('proces').style.display=''; this.style.display='none'; return true;">
               &#9654; Start Processing Now
            </a>
            <iframe src="about:blank" name="processor" id="proces" style="display:none" width="100%" height="200" frameborder="1"></iframe>

            <p><small>Once finished, <a href="?">click here to refresh</a>.</small></p>
        </div>

    <?php } else { ?>
        <div id="external-processor">
            <p><strong>Desktop Recommended:</strong> This is a processor-intensive task. To ensure it finishes quickly and doesn't "sleep" on your mobile device, we recommend running this on a computer.</p>

            <ul>
                <li>
                    <a href="/viewer/processor.php" target="_blank"><strong>Launch Processor in New Tab</strong></a>
                    <br><small>Note: Please open only one window.</small>
                </li>
                <li>
			<? if (!empty($r)) { ?>
			    <a href="?ignore=1">If want can continue to the app now</a>
			<? } else { ?>
	                    <a href="?mail=true"><strong>Email me a link to complete this on Desktop</strong></a>
			<? } ?>
                </li>
            </ul>

            <p>Already finished? <a href="?">Click here to refresh</a>.</p>
        </div>

	<h3>How it works</h3>
	<ul>
	    <li><b>Keep the tab open:</b> The processor will continue in the background, though it may run faster if the tab is active.</li>
	    <li><b>Performance:</b> Expect a rate of roughly 50 images every 20 seconds.</li>
	    <li><b>Local Processing:</b> Images are downloaded and hashed on your computer; the resulting hashes are then saved to our servers.</li>
	    <li><b>Stopping/Restarting:</b> You can safely close the processor at any time. To resume, simply reopen the link. If the process appears to stall, refresh the page.</li>
	    <li><b>Maintenance:</b> This is a one-off process for your existing catalog. In the future, you will only need to process new submissions.</li>
	</ul>

    <?php } ?>

	<p><strong>Want to skip this?</strong> You can <a href="?ignore=1">proceed to the app</a> now, but unprocessed images will not be identified. 
	<em>Warning: Skipping this step may delay new images from appearing in the app for up to 24 hours. For the best experience, wait for the processor to complete.</em></p>

	<?
	exit;
}


################################################

//todo, check user_stat, if no images, pointless even trying to do custmization
$hashesUrl = $CONF['API_HOST']."/viewer/hashes.json.php";
$hashesUrl = "/viewer/hashes.json.php";
$token=new Token;
$token->setValue("id", $USER->user_id);
$hashesUrl .= "?t=".$token->getToken();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NanoGallery Pro</title>
    <script src="https://cdn.jsdelivr.net/npm/exifr@7.1.3/dist/lite.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js"></script>
    <script src="https://unpkg.com/imagehash-web/dist/imagehash-web.min.js"></script>
    <script src="<?php echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>

    <script src="<?php echo smarty_modifier_revision("/js/submission_utils.js"); ?>"></script>
    <script src="<?php echo smarty_modifier_revision("/viewer/ExifRestorer.js"); ?>"></script>

    <script>
        let user_id = <? echo intval($USER->user_id); ?>;
        let hashes_url = <? echo json_encode($hashesUrl); ?>;
        let hashes_url2 = "/viewer/hashes-tmp.json.php";

        window.max_size = 8 * 1024 * 1024; //larger files will be downsized!
        window.uploadMaxDimension = 65536; // Default to effectively unlimited (will be updated by the settings listener!)
    </script>

    <script type="module">
        import { navigateTo, setupSettingsListener } from '/app/js/utils.js?<? echo filemtime('js/utils.js'); ?>';
        window.navigateTo = navigateTo;
        setupSettingsListener();
    </script>

    <style>
        :root { --bg: #000; --card: #1a1a1a; --text: #eee; --accent: #007AFF; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 0; }
        
        /* Header & Nav */
        header { sticky: top; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 0.5px solid #333; z-index: 100; position: sticky; top: 0; }
        .stats { font-size: 12px; color: #888; }
        
        /* Controls */
        .toolbar { padding: 10px; display: flex; gap: 8px; background: #111; scroll-margin-top: 60px; }
        button { background: #2c2c2e; color: white; border: none; padding: 8px 14px; border-radius: 8px; font-weight: 500; cursor: pointer; }
        button.primary { background: var(--accent); }

        /* Gallery Layout */
        #gallery-root { padding: 10px; }
        .day-section { margin-bottom: 20px; }
        .day-header { font-size: 14px; font-weight: 600; margin: 0 0 10px 5px; color: #aaa; text-transform: uppercase; letter-spacing: 0.5px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .day-header:hover { color: #fff; }
        
        .thumb-strip { display: flex; gap: 4px; overflow-x: auto; scroll-snap-type: x proximity; -webkit-overflow-scrolling: touch; }
        .thumb-strip.wrapped { flex-wrap: wrap; overflow-x: hidden; }
        .thumb-wrapper { flex: 0 0 100px; width: 100px; height: 100px; background: #222; border-radius: 4px; overflow: hidden; scroll-snap-align: start; position: relative; }
        .thumb-wrapper img { width: 100%; height: 100%; object-fit: contain; transition: opacity 0.3s; }

        /* Details Mode */
        .details-list { display: flex; flex-direction: column; gap: 10px; overflow-x: hidden; }
        .details-item { display: flex; gap: 15px; background: #1a1a1a; padding: 10px; border-radius: 8px; align-items: flex-start; }
        .details-thumb { width: 100px; height: 100px; flex-shrink: 0; background: #222; border-radius: 4px; overflow: hidden; position: relative; }
        .details-thumb img { width: 100%; height: 100%; object-fit: contain; }
        .details-info { flex-grow: 1; font-size: 13px; color: #ccc; overflow: hidden; }
        .details-info div { margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .details-info strong { color: #fff; font-size: 14px; display: block; margin-bottom: 6px; }
        .details-info .label { color: #888; font-size: 11px; text-transform: uppercase; margin-right: 5px; }

        /* Modal */
        .modal { position: fixed; inset: 0; background: rgba(0,0,0,0.9); display: none; flex-direction: column; padding: 20px; z-index: 1000; }
        .modal.active { display: flex; }
        .modal-content { background: var(--card); border-radius: 12px; padding: 20px; max-height: 80vh; overflow-y: auto; }
        .folder-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #333; }
        .remove-btn { color: #ff3b30; font-size: 12px; }

        .already-submitted {background-color:#073902; filter: grayscale(0.5); opacity: 0.8; }

        .already-submitted::after { content: "\2713"; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 32px; color: var(--success); text-shadow: 0 2px 4px rgba(0,0,0,0.1); pointer-events: none; }
        .exact-match {}
        .close-match {}

        .uploaded { background-color:#073902; position: relative; }
        .uploaded::after { content: "\2713"; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 32px; color: #0000ff; text-shadow: 0 2px 4px rgba(0,0,0,0.1); pointer-events: none; }
        .uploaded img { filter: grayscale(0.5);  opacity: 0.4 };

    .muted { display: none !important; } /* Hard hide from UI */

        .hidden { display: none; }
    </style>
</head>
<body>

<header>
    <div>
        <strong>Local Folders</strong>
        <div id="file-count" class="stats">Initializing...</div>
    </div>
    <button onclick="toggleModal('settings-modal')">Settings</button>
</header>

<div class="toolbar">
    <select id="date-filter" onchange="setDateFilter(this.value)">
        <option value="all">All Time</option>
        <option value="0">Today</option>
        <option value="3" selected>Last 3 Days</option>
        <option value="7">Last 7 Days</option>
        <option value="30">Last 30 Days</option>
    </select>

    <button id="sort-btn" onclick="toggleSort()">Newest First</button>

    <button id="view-btn" onclick="toggleViewMode()">View: Gallery</button>

    <button id="reauth-btn" class="hidden primary" onclick="reauthAll()">Unlock Folders</button>
</div>

<script>
let dateFilter = 'all';
let viewMode = 'gallery'; // 'gallery' or 'details'
let focusedGroupId = null;

function setDateFilter(val) {
    dateFilter = val;
    renderFullGallery();
}

function toggleViewMode() {
    viewMode = (viewMode === 'gallery') ? 'details' : 'gallery';
    document.getElementById('view-btn').innerText = `View: ${viewMode.charAt(0).toUpperCase() + viewMode.slice(1)}`;
    renderFullGallery();
}
</script>

<div id="gallery-root"></div>

<div id="settings-modal" class="modal">
    <div class="modal-content">
        <h3>Monitored Folders</h3>
        <div id="folder-list"></div>
        <hr style="border: 0.5px solid #333; margin: 20px 0;">
        <button class="primary" style="width:100%" onclick="startFolderScan()">+ Add New Folder</button>
        <button style="width:100%; margin-top:10px;" onclick="toggleModal('settings-modal')">Close</button>

        <div style="margin-top: 50px; border-top: 1px solid #333;">
            <button style="width:100%; margin-bottom: 8px;" onclick="reFetchHashes()">
                Update Hashes
            </button>
            <button style="width:100%; margin-bottom: 8px;" onclick="resetMutedImages()">
                Restore Muted Images
            </button>
            <button style="width:100%; background: #441111; color: #ff6666;" onclick="wipeAllData()">
                Forget All Folders & Cache
            </button>
        </div>

    </div>
</div>

<div id="action-modal" class="modal" onclick="closeActionModal(event)">
    <div class="modal-content" style="max-width: 350px; text-align: center;" onclick="event.stopPropagation()">
        <img id="modal-preview" style="width: 100%; border-radius: 8px; margin-bottom: 15px;">
        <div id="modal-title" style="font-weight: bold; margin-bottom: 5px; word-wrap: break-word;">Image Actions</div>
        <div id="modal-status" style="font-size: 12px; color: #888; margin-bottom: 20px;"></div>

        <div style="display: flex; flex-direction: column; gap: 10px;">
            <button id="btn-upload" class="primary" style="display:none">Upload Now (in background)</button>
            <button id="btn-view" style="display:none">View Online</button>
            <button id="btn-mute" style="color: #ff3b30;">Mute (Hide)</button>
            <button onclick="closeActionModal()">Close</button>
        </div>
    </div>
</div>


<div id="help-modal" class="modal" onclick="closeActionModal(event)">

	<h1>Enhanced Image Browser (Beta)</h1>

	<p>This page provides an alternative way to browse and select photos from your device for upload to Geograph.</p>

	<p>Instead of using your device's standard file picker one photo at a time, you can grant the app access to specific folders. This creates a customized local gallery optimized for 
	Geograph contributors.</p>

	<h2>Why use the Enhanced Browser?</h2>
	<ul>

	    <li><strong>Duplicate Detection:</strong> We automatically track which images have already been submitted (including those uploaded before you used this app), so you can 
	    see at a glance what still needs to be shared.</li>

	    <li><strong>Smarter Sorting:</strong> Unlike a standard list of thumbnails, you can group your local photos by date or Grid Square.</li>

	    <li><strong>Preserve Location Data:</strong> This method helps sidestep browser "privacy" filters that often strip GPS data. By browsing folders this way, we can more reliably 
	    read the original coordinates to help place your photo on the map.</li>

	</ul>

	<h2>How it works</h2>

	<p>Once you've added a folder, the app remembers it for future visits. Simply click "Unlock Folders" to refresh the view and scan for new shots. You still have full control - 
	nothing is uploaded until you manually select an image for submission.</p>

	<h2>Privacy & Data</h2>
	<p>We take your privacy seriously. While this tool "scans" your folders to help you organize them:</p>
	<ul>
	    <li><strong>On-Device Processing:</strong> Extraction of location data and the creation of thumbnails happens locally on your device.</li>
	    <li><strong>No Hidden Syncing:</strong> These details (and the photos themselves) never leave your device without your permission.</li>
	    <li><strong>Manual Upload Only:</strong> Only the specific images you choose to submit are ever sent to the Geograph servers.</li>
	</ul>

	<h2>One-Time Archive Scan (Beta)</h2>
	<p>To help the app identify which of your photos are already on Geograph, we need to generate a digital "fingerprint" (perceptual hash) of your existing submission history.</p>
	<ul>

	    <li><strong>Desktop Recommended:</strong> This is a processor-intensive task. While you can run it on mobile (Wi-Fi recommended), it is much faster on a desktop computer 
	    where the browser won't "sleep" and pause the process.</li>

	    <li><strong>Background Running:</strong> On a computer, you can leave this running in a tab until it finishes.</li>
	</ul>

	<p><a href="#">[Send me an email with a link to complete this on a Desktop]</a></p>

</div>



<script>
let db;
let sortOrder = 'desc'; // 'desc' = newest first
let isScanning = false;

const initDB = () => {
    return new Promise((resolve) => {
        const request = indexedDB.open('GalleryDB', 2);
        request.onupgradeneeded = (e) => {
            const db = e.target.result;
            if(!db.objectStoreNames.contains('folders')) db.createObjectStore('folders', { keyPath: 'path' });
            if(!db.objectStoreNames.contains('images')) {
                const s = db.createObjectStore('images', { keyPath: 'fileId' });
                s.createIndex('day', 'day', { unique: false });
                s.createIndex('date', 'date', { unique: false });
            }
        };
        request.onsuccess = (e) => { db = e.target.result; resolve(); };
    });
};

////////////////////////////////////////////
// main scan process

async function startFolderScan() {
    if (isScanning) {
        alert("scanning currently in progress, please wait before adding another folder");
        return;
    }
    try {
        const dirHandle = await window.showDirectoryPicker();
        
        // 1. Setup Folder Record
        await updateStore('folders', { path: dirHandle.name, handle: dirHandle, count: 0 });
        await refreshFolderList();

        const safePath = btoa(dirHandle.name).replace(/=/g, '');
        const counterEl = document.getElementById(`count-${safePath}`);
        if (counterEl) counterEl.innerText = 'Scanning folder...';

        // 2. THE CRAWL: Fast collection of File objects
        const fileQueue = [];
        await fastCrawl(dirHandle, dirHandle.name, fileQueue);

        if (fileQueue.length === 0) {
            if (counterEl) counterEl.innerText = 'No new images found.';
            return;
        }

        if (counterEl) counterEl.innerText = `Processing ${fileQueue.length} images...`;
//TODO, think best to chose close the folder popup
toggleModal('settings-modal');
//let it scan, and update the main stats!

        // 3. THE PROCESS: Thumbnails & EXIF
        processFileQueue(fileQueue, dirHandle.name);

    } catch (e) {
        console.error("Scan failed", e);
        if (e.name === 'NotAllowedError') {
            alert("Security timeout. Mobile Chrome requires you to stay on the page while it starts the scan.");
        }
    }
}

//simple version, ro rescan an exicting directory, for reauth. Still needs to use fastCrawl
async function scanDirectory(dirHandle) {
    const fileQueue = [];
    const targetPath = dirHandle.name;
    const opts = { mode: 'read' };

    try {
        // 1. Check/Request Permission
        if ((await dirHandle.queryPermission(opts)) !== 'granted') {
            if ((await dirHandle.requestPermission(opts)) !== 'granted') {
                console.error("Access denied to " + targetPath);
                return;
            }
        }

        // 2. Identify the counter element for feedback
        const counterEl = document.getElementById(`file-count`);
        if (counterEl) counterEl.innerText = 'Re-scanning...';

        // 3. Run the fast collection
        await fastCrawl(dirHandle, targetPath, fileQueue);

        if (fileQueue.length > 0) {
            // 4. Fire and forget the processing so the UI stays responsive
            processFileQueue(fileQueue, targetPath);
        } else {
            if (counterEl) counterEl.innerText = 'Up to date.';
        }
    } catch (err) {
        console.error("Scan Directory Error:", err);
    }
}


//this is intended to quickly gather files, for later processing. the permission expires if dont use it quickly!
async function fastCrawl(dirHandle, path, fileQueue) {
    for await (const entry of dirHandle.values()) {
        if (entry.kind === 'file' && /\.(jpe?g|png|webp|avif|heic|heif)$/i.test(entry.name)) {
            const fileId = `${path}/${entry.name}`;
            const existing = await getFromStore('images', fileId);

            if (!existing) {
                try {
                    // CRITICAL: Get the File object NOW while we have permission
                    const file = await entry.getFile();

                    if (file.size === 0) {
                        console.warn(`Skipping 0-byte file during crawl: ${entry.name}`);
                        continue;
                    }

                    fileQueue.push({ file, handle: entry, fileId });
                } catch (err) {
                    console.error("Failed to grab file early:", entry.name);
                }
            }
        } else if (entry.kind === 'directory') {
            await fastCrawl(entry, `${path}/${entry.name}`, fileQueue);
        }
    }
}



async function processFileQueue(fileQueue, rootPath) {
    isScanning = true;
    const safePath = btoa(rootPath).replace(/=/g, '');
    let folderRecord = await getFromStore('folders', rootPath);
    let currentCount = folderRecord?.count || 0;
    let fileCount = fileQueue.length + currentCount; //want what the total will be!

		//todo, convert to use processFile, which already does the exif logic, and will also do downsizing to create a dataUri, but maybe best defered anyway!
    for (const item of fileQueue) {
        // We already have the 'item.file' object, no more 'getFile()' calls needed!
        try {
            let fileToProcess = item.file;
            const isHeic = fileToProcess.name.toLowerCase().endsWith('.heic') ||
                           fileToProcess.name.toLowerCase().endsWith('.heif');

            let gridref = "Unknown Location"; //overwitten from exif or filename!
            let date = new Date(item.file.lastModified); //note may be overritten from exif

            //////////////////////////////////////

            // exifr handles HEIC natively,so we use the original file in case meta data was lost in conversion
            try {
                const meta = await exifr.parse(item.file, { translateKeys: true, translateValues: false });
                const exifDate = meta.DateTimeOriginal || meta.CreateDate || meta.ModifyDate || meta.DateTime;
                if (exifDate) {
                    date = new Date(exifDate);
                }

                if (Number.isFinite(meta?.latitude) && Number.isFinite(meta?.longitude)) {
                    const wgs84 = new GT_WGS84();
                    wgs84.setDegrees(meta.latitude, meta.longitude);
                    let grid = wgs84.isIreland2() ? wgs84.getIrish(true) : (wgs84.isGreatBritain() ? wgs84.getOSGB() : null);
                    if (grid) gridref = grid.getGridRef(2).replace(/ /g,'');
                }

            } catch (exifErr) {
                console.warn(`Metadata skipped for ${item.file.name}:`, exifErr.message);
            }

            //////////////////////////////////////

            const match = item.file.name.match(/_([A-Z]{1,2})(\d{4,10})\./i);
            if (match && (gridref == "Unknown Location" || match[2].length > 6)) {
                let grid = (match[1].length === 2) ? new GT_OSGB() : new GT_Irish();
                grid.parseGridRef(match[1]+match[2]);
                if (grid && grid.status == 'OK')
                    gridref = grid.getGridRef(2).replace(/\s/g, ''); //get a 4fig GR specifically
            }

            //////////////////////////////////////

            // --- HEIC TO JPEG CONVERSION (THUMBNAIL ONLY) ---
            if (isHeic) {
                try {
                    // convert to a lightweight blob for thumbnailing
                    const converted = await heic2any({
                        blob: fileToProcess,
                        toType: "image/jpeg",
                        quality: 0.7 // Lower quality for speed, we only need a thumb
                    });
                    // heic2any can return an array if the HEIC is an animation/burst
                    fileToProcess = Array.isArray(converted) ? converted[0] : converted;
                } catch (heicErr) {
                    console.error("HEIC conversion failed:", heicErr);
                }
            }

            // Use the converted fileToProcess (which is now a JPEG if it was HEIC)
            const thumbBlob = await createThumbnail(fileToProcess);

            // --- GENERATE PHASH ---
            // Create an image element to feed the phash library
            const hash = await new Promise(resolve => {
                const img = new Image();
                img.onload = async () => {
                    const h = await phash(img, 8);
                    resolve(h.toHexString());

                    URL.revokeObjectURL(img.src); // Clean up memory immediately
                };
                img.src = URL.createObjectURL(thumbBlob);
            });

            //////////////////////////////////////

            const imgData = {
                fileId: item.fileId,
                day: date.toISOString().split('T')[0],
                date: date.getTime(),
                gridref: gridref,
                thumb: thumbBlob,
                phash: hash,
                file: item.file // Store the file for future uploads (handle wont work later!)
            };

            await updateStore('images', imgData);
            currentCount++;

            // Update UI
            updateStats((currentCount/fileCount * 100).toFixed(1));
            appendToUI(imgData); //just add one image, without redrawing!

            if (currentCount % 10 === 0) {
                folderRecord.count = currentCount;
                await updateStore('folders', folderRecord);
            }
        } catch (err) {
            console.error("Processing error:", err);
        }
    }
    folderRecord.count = currentCount;
    updateStore('folders', folderRecord);
    isScanning = false;
    updateStats();
}

/////////////////////////////////////////////
// Render Functions

function createThumbElement(img) {
    const div = document.createElement('div');
    const safeId = btoa(img.fileId).replace(/=/g,'');
    div.id = `wrapper-${safeId}`;
    div.className = 'thumb-wrapper';

    if (img.uploadStatus) {
        if (img.uploadStatus.info.id) div.classList.add('already-submitted');
        if (img.uploadStatus.info.gid || img.uploadStatus.info.filename) div.classList.add('uploaded');
        div.classList.add(`${img.uploadStatus.match}-match`);
    }

    const url = URL.createObjectURL(img.thumb);
    div.innerHTML = `<img src="${url}" loading="lazy" onload="window.URL.revokeObjectURL(this.src)">`;
    div.onclick = () => openActionModal(img);
    return div;
}

function createDetailsElement(img) {
    const div = document.createElement('div');
    div.className = 'details-item';
    const safeId = btoa(img.fileId).replace(/=/g,'');
    div.id = `details-${safeId}`;

    const url = URL.createObjectURL(img.thumb);
    const filename = img.fileId.split('/').pop();
    const folder = img.fileId.substring(0, img.fileId.lastIndexOf('/'));

    div.innerHTML = `
        <div class="details-thumb">
            <img src="${url}" onload="window.URL.revokeObjectURL(this.src)">
        </div>
        <div class="details-info">
            <strong>${filename}</strong>
            <div><span class="label">Folder:</span> ${folder}</div>
            <div><span class="label">Date:</span> ${formatDate(img.date)}</div>
            <div><span class="label">Gridref:</span> ${img.gridref}</div>
            <div><span class="label">Size:</span> ${formatSize(img.file.size)}</div>
        </div>
    `;

    div.querySelector('.details-thumb').onclick = () => openActionModal(img);

    if (img.uploadStatus) {
        if (img.uploadStatus.info.id) div.classList.add('already-submitted');
        if (img.uploadStatus.info.gid || img.uploadStatus.info.filename) div.classList.add('uploaded');
    }

    return div;
}

function formatSize(bytes) {
    if (!bytes) return "0 B";
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

function formatDate(ts) {
    const d = new Date(ts);
    return d.toLocaleString([], { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function focusGroup(groupId) {
    focusedGroupId = (focusedGroupId === groupId) ? null : groupId;
    renderFullGallery();
}

async function renderFullGallery() {
    const root = document.getElementById('gallery-root');
    let images = await getAllFromStore('images');
    images = images.filter(img => !img.muted);

    // Apply Date Filter
    if (dateFilter !== 'all') {
        const now = new Date();
        now.setHours(0,0,0,0);
        const limit = new Date(now);
        limit.setDate(now.getDate() - parseInt(dateFilter));
        images = images.filter(img => img.date >= limit.getTime());
    }

    if (images.length === 0) {
        root.innerHTML = '<div id="drop-zone">No images found for this filter.</div>';
        updateStats();
        return;
    }

    // Sort images by date
    images.sort((a, b) => sortOrder === 'desc' ? b.date - a.date : a.date - b.date);

    // Sequential Grouping
    const groups = [];
    if (images.length > 0) {
        let currentGroup = {
            id: `${images[0].day}-${images[0].gridref}`,
            day: images[0].day,
            gridref: images[0].gridref,
            items: [images[0]]
        };
        for (let i = 1; i < images.length; i++) {
            const img = images[i];
            if (img.day !== currentGroup.day || img.gridref !== currentGroup.gridref) {
                groups.push(currentGroup);
                currentGroup = {
                    id: `${img.day}-${img.gridref}-${i}`, // Add index to ensure uniqueness if visited twice
                    day: img.day,
                    gridref: img.gridref,
                    items: [img]
                };
            } else {
                currentGroup.items.push(img);
            }
        }
        groups.push(currentGroup);
    }

    root.innerHTML = '';

    if (focusedGroupId && viewMode === 'gallery') {
        const backBtn = document.createElement('button');
        backBtn.innerText = "< Show All Groups";
        backBtn.style.marginBottom = "15px";
        backBtn.onclick = () => { focusedGroupId = null; renderFullGallery(); };
        root.appendChild(backBtn);
    }

    groups.forEach(group => {
        if (focusedGroupId && focusedGroupId !== group.id && viewMode === 'gallery') return;

        const sec = document.createElement('div');
        sec.className = 'day-section';

        const isFocused = focusedGroupId === group.id;

        sec.innerHTML = `
            <div class="day-header" onclick="focusGroup('${group.id}')">
                <span>${group.day} - ${group.gridref} (${group.items.length})</span>
                ${(viewMode === 'gallery' && !isFocused && group.items.length > 3) ? '<span style="font-size:10px; color:var(--accent)">EXPAND</span>' : ''}
            </div>
            <div class="thumb-strip ${isFocused ? 'wrapped' : ''} ${viewMode === 'details' ? 'details-list' : ''}"></div>
        `;

        const container = sec.querySelector('.thumb-strip');
        group.items.forEach(img => {
            if (viewMode === 'gallery') {
                container.appendChild(createThumbElement(img));
            } else {
                container.appendChild(createDetailsElement(img));
            }
        });
        root.appendChild(sec);
    });

    updateStats();
}

//function to render just one image - adding to current gallery, without full reload
//now just thottled to avoid too many redraws during scan (because dont know what group to add it to!)
let redrawTimeout;
function appendToUI(img) {
    if (redrawTimeout) return;
    redrawTimeout = setTimeout(() => {
        renderFullGallery();
        redrawTimeout = null;
    }, 5000);
}

async function toggleSort() {
    sortOrder = sortOrder === 'desc' ? 'asc' : 'desc';
    document.getElementById('sort-btn').innerText = sortOrder === 'desc' ? 'Newest First' : 'Oldest First';
    renderFullGallery();
}

async function refreshFolderList() {
    const list = document.getElementById('folder-list');
    const folders = await getAllFromStore('folders');
    list.innerHTML = folders.length ? '' : '<p style="color:#666; padding:10px;">No folders added</p>';

    folders.forEach(f => {
        const item = document.createElement('div');
        item.className = 'folder-item';
	const safePath = btoa(f.path).replace(/=/g, ''); // Base64 to ensure valid ID
        // Show the count (default to 0 if not yet set)
        const count = f.count || 0;
        item.innerHTML = `
            <div style="display:flex; flex-direction:column;">
                <span style="font-weight:500;">${f.path}</span>
                <span id="count-${safePath}" style="font-size:11px; color:var(--accent);">${count} images indexed</span>
            </div>
            <button class="remove-btn" onclick="removeFolder('${f.path}')">Remove</button>
        `;
        list.appendChild(item);
    });
}

async function removeFolder(path) {
    if (!confirm(`Stop monitoring "${path}" and clear its thumbnails?`)) return;

    const tx = db.transaction(['folders', 'images'], 'readwrite');
    const folderStore = tx.objectStore('folders');
    const imageStore = tx.objectStore('images');

    // 1. Remove the folder from the monitored list
    folderStore.delete(path);

    // 2. Remove all images belonging to this folder
    // We use a IDBKeyRange to find all keys starting with "FolderName/"
    const range = IDBKeyRange.bound(path + "/", path + "/\uffff");
    const cursorRequest = imageStore.openCursor(range);

    cursorRequest.onsuccess = (e) => {
        const cursor = e.target.result;
        if (cursor) {
            cursor.delete();
            cursor.continue();
        }
    };

    tx.oncomplete = () => {
        console.log(`Cleaned up all cached data for: ${path}`);
        refreshFolderList();
        renderFullGallery();
    };
}

function toggleModal(id) {
    document.getElementById(id).classList.toggle('active');
    if(id === 'settings-modal') refreshFolderList();
}

let imagesUploaded = 0; // Increments when a file starts
let imagesFinished = 0; // Increments when a file completes

async function updateStats(percent) {
    const imgs = await getAllFromStore('images');
    let statusText = `${imgs.length} images indexed`;

    if (imagesUploaded > 0) {
        if (imagesFinished < imagesUploaded) {
            // Currently uploading
            statusText += `, Uploading: ${imagesFinished}/${imagesUploaded} complete`;
        } else {
            // All finished for this session
            statusText += `, ${imagesFinished} images uploaded successfully`;
        }
    }
    if (percent)
        statusText += `, ${percent}%`;

    const el = document.getElementById('file-count');
    if (el) el.innerText = statusText;
}

////////////////////////////////////////////////////////////////
// checking for duplicates

let remoteHashes = {}; // Global store for comparison

function reFetchHashes() {
    syncRemoteHashes(hashes_url, hashes_url2);
}

async function syncRemoteHashes(hashesUrl, hashesUrl2) {
    try {
        const response = await fetch(hashesUrl);
        const data = await response.json();

        remoteHashes = {};

        //1. Fetch Submitted Hashes
        // Skip header row, build lookup map
        for(let i = 1; i < data.length; i++) {
            const [id, src, hashStr, grid, date, title] = data[i];
            if (hashStr) {
                remoteHashes[hashStr] = { id, title };
            }
        }

        //2. Fetch Uploaded Hashes
        if (hashesUrl2) {
            const response2 = await fetch(hashesUrl2);
            const data2 = await response2.json();

            // Skip header row, build lookup map
            for(let i = 1; i < data2.length; i++) {
                const [gid,hashStr,transfer_id,uploaded,gridref] = data2[i];
                if (hashStr && !remoteHashes[hashStr]) { //we DONT overwrite, as dont want to remove full record!
                    remoteHashes[hashStr] = { gid, transfer_id };
                }
            }
        }

        await runDuplicateCheck();
        renderFullGallery(); // Refresh UI with new status
    } catch (e) {
        console.error("Hash sync failed", e);
    }
}


async function runDuplicateCheck() {
    const images = await getAllFromStore('images');
    const remoteKeys = Object.keys(remoteHashes);

    // --- Local Storage Retrieval ---
    let localHistory = [];
    try {
        const stored = localStorage.getItem('upload_history');
        localHistory = stored ? JSON.parse(stored) : [];
    } catch (e) {
        localHistory = [];
    }
    // -------------------------------

    for (let img of images) {
        // 1. Check Local History first (Filename match)
        // We look for any record where the filename matches img.file.name
        const localMatch = localHistory.find(record => record.filename === img.file?.name);
        if (localMatch) {
            img.uploadStatus = {
                match: 'local',
                info: localMatch
            };
            await updateStore('images', img);

            continue;
        }

        if (!img.phash) continue;

        // 1. Exact match
        if (remoteHashes[img.phash]) {
            img.uploadStatus = { match: 'exact', info: remoteHashes[img.phash] };
        } else {
            // 2. Hamming distance for close matches (max 8 bits difference)
            let bestDist = Infinity;
            let bestMatch = null;

            const currentHash = ImageHash.fromHexString(img.phash);

            for (let rHashStr of remoteKeys) {
                const other = ImageHash.fromHexString(rHashStr);
                const dist = currentHash.hammingDistance(other);
                if (dist < bestDist) {
                    bestDist = dist;
                    bestMatch = rHashStr;
                }
            }

            if (bestDist <= 8) {
                img.uploadStatus = {
                    match: 'close',
                    dist: bestDist,
                    info: remoteHashes[bestMatch]
                };
            } else {
                img.uploadStatus = null; // Clean/New
            }
        }
        await updateStore('images', img);
    }
}

let currentActiveImg = null; // Track what we are looking at

function closeActionModal(e) {
    document.getElementById('action-modal').classList.remove('active');
}

async function openActionModal(img) {
    currentActiveImg = img;
    const modal = document.getElementById('action-modal');
    const name = document.getElementById('modal-title');
    const preview = document.getElementById('modal-preview');
    const status = document.getElementById('modal-status');
    const btnUpload = document.getElementById('btn-upload');
    const btnView = document.getElementById('btn-view');
    const btnMute = document.getElementById('btn-mute');

    // 1. Setup Preview
    preview.src = URL.createObjectURL(img.thumb);
    name.innerText = img.fileId.replaceAll('/', '/\u200B') || img.file.name;
    status.innerText = ( img.gridref || "No Location") + " | " + (img.day || "No Date");

    modal.classList.add('active');

    // 2. Logic for "Already Submitted"
    if (img.uploadStatus) {
        btnView.style.display = 'none';
        btnUpload.style.display = 'none';
        if (img.uploadStatus.info.id) {
            btnView.style.display = 'block';
            btnView.onclick = () => window.open(`https://www.geograph.org.uk/photo/${img.uploadStatus.info.id}`, '_blank');
            btnView.textContent = `View [[${img.uploadStatus.info.id}]] Online`;
            status.innerText += ` - Already on Geograph (${img.uploadStatus.info.title})`

        } else if (img.uploadStatus.info.filename) {
            status.innerText += ` - Uploaded recently on this device`;

        } else { //if (img.uploadStatus.info.gid) {
            //todo, could navigateTo('/app/submit') //But would need to supply the full exif data (which we dont have)
            status.innerText += " - Already Uploaded";
        }
    } else {
        btnUpload.style.display = 'block';
        btnView.style.display = 'none';
        btnUpload.onclick = () => triggerUpload(img);
    }

    // 3. Logic for Muting
    btnMute.onclick = async () => {
        if (confirm("Permanently hide this image from the gallery?")) {
            img.muted = true;
            await updateStore('images', img);
            const el = document.getElementById(`wrapper-${btoa(img.fileId).replace(/=/g,'')}`);
            if (el) el.classList.add('muted');
            closeActionModal();
        }
    };
}

async function triggerUpload(img) {
    // 1. Immediate UI Feedback
    const safeId = btoa(img.fileId).replace(/=/g,'');
    const el = document.getElementById(`wrapper-${safeId}`);
    if (el) el.classList.add('uploaded');
    closeActionModal();
    imagesUploaded++;
    updateStats();

    try {
        // 2. Background Processing

console.log(img);
            //convert to 'file' to dateUri, BUT, use our resize handler!

            //does resize - if needed, as well as fetching Exif data!
            //we COULD put the newly generated filename into file.name, and processItem would read it, but better to just use the saved lat/long directly
            const item = await processItem(img); //expects a .file, which we happen to have!

            const result = await sendToPHP(item.dataUri, img.file.name, (percent) => {
                // Update the button text to show progress
                //uploadBtn.textContent = `Uploading... ${percent}%`;
                updateStats(percent);
            });
            if (result && result.success) {
                img.uploadStatus = { match: 'exact', info: { gid: 1, transfer_id:result.upload_id, title: 'Just Uploaded' } };
                await updateStore('images', img);
                imagesFinished++;
                updateStats();


                // SUCCESS: Allow direct submission

                /* we could setup a submit button, but dont have the all the exif data yet, item.exifData should be good though!
                document.getElementById('submitBtn').onclick = function() {
                    navigateTo('/app/submit',{message: JSON.stringify({
                        transfer_id: result.upload_id,
                        width: result.width,
                        height: result.height,
                        lat: latestCoords.lat, //use saved coordinates, as dont trust exif!
                        long: latestCoords.lng,
                        // Priority: 1. EXIF date from file, 2. Formatted capture time
                        imagetaken: item.exifData?.date || fallbackExifDate,
                        orientation: item.exifData?.orientation
                    })});
                }*/
            }
            return;

    } catch (err) {
        console.error("Upload failed", err);
    }
}

////////////////////////////////////////////////////////////////
// general functions and events

/**
 * Unsets the 'muted' flag on every image in the database
 */
async function resetMutedImages() {
    if (!confirm("Are you sure you want to show all hidden/muted images again?")) return;

    const tx = db.transaction('images', 'readwrite');
    const store = tx.objectStore('images');
    const request = store.openCursor();

    request.onsuccess = (e) => {
        const cursor = e.target.result;
        if (cursor) {
            const data = cursor.value;
            if (data.muted) {
                delete data.muted; // Remove the muted property
                cursor.update(data);
            }
            cursor.continue();
        }
    };

    tx.oncomplete = () => {
        alert("All images restored. Refreshing gallery...");
        renderFullGallery();
    };
}

/**
 * Nukes the IndexedDB and refreshes the page
 */
async function wipeAllData() {
    const confirmation = confirm("WARNING: This will delete ALL monitored folders, cached thumbnails (in the app, not from your device).\n\nAre you absolutely sure?");
    if (!confirmation) return;

    // We use a specific IDB method to delete the entire database
    const deleteReq = indexedDB.deleteDatabase('GalleryDB');

    deleteReq.onsuccess = () => {
        alert("Database wiped successfully. The app will now reload.");
        //window.location.reload();
        window.history.go(0);
    };

    deleteReq.onerror = () => {
        alert("Could not delete database. You may need to close other tabs.");
    };

    deleteReq.onblocked = () => {
        alert("Database deletion blocked. Please close all other tabs of this app and try again.");
    };
}

async function createThumbnail(file) {
    const bitmap = await createImageBitmap(file);
    const canvas = document.createElement('canvas');
    const MAX = 320;
    let w = bitmap.width, h = bitmap.height;
    const aspect = w / h;
    if (w > h && aspect < 4.8) { h *= MAX/w; w = MAX; } else { w *= MAX/h; h = MAX; }
    canvas.width = w; canvas.height = h;
    canvas.getContext('2d').drawImage(bitmap, 0, 0, w, h);
    return new Promise(r => canvas.toBlob(r, 'image/webp', 0.6));
}

function updateStore(s, d) { return new Promise(r => { const t = db.transaction(s, 'readwrite'); t.objectStore(s).put(d); t.oncomplete = r; }); }
function getFromStore(s, id) { return new Promise(r => { db.transaction(s).objectStore(s).get(id).onsuccess = e => r(e.target.result); }); }
function getAllFromStore(s) { return new Promise(r => { db.transaction(s).objectStore(s).getAll().onsuccess = e => r(e.target.result); }); }

async function reauthAll() {
    const folders = await getAllFromStore('folders');
    for (const f of folders) {
        if ((await f.handle.queryPermission()) === 'granted' || (await f.handle.requestPermission()) === 'granted') {
            scanDirectory(f.handle);
        }
    }
    document.getElementById('reauth-btn').classList.add('hidden');
}

window.onload = async () => {
    await initDB();
    const folders = await getAllFromStore('folders');
    if (folders.length > 0) document.getElementById('reauth-btn').classList.remove('hidden');
    renderFullGallery();
};
</script>
<a href="javascript:(function () {var script=document.createElement('script');script.src='//cdn.jsdelivr.net/npm/eruda';document.body.appendChild(script); script.onload = function () { eruda.init() } })();">dev</a>


</body>
</html>
