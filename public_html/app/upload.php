<?php

require_once('geograph/global.inc.php');
require_once('geograph/uploadmanager.class.php');

init_session();

$USER->mustHavePerm('basic');

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($_POST['email'])) { //aovid a login request!
	// Get the raw POST data
	$input = file_get_contents('php://input');
	$data = json_decode($input, true);

	$uploadmanager=new UploadManager;

	$response = [];
	if (isset($data['image'])) {
	    $response['ok']  = $uploadmanager->processDataURL($data['image'], $data['name'] ?? null);
        if ($response['ok']) {
            $response['upload_id'] = $uploadmanager->upload_id;
            if (!empty($uploadmanager->original_width)) {
                //ideally want size of the largest, not the preview;
                $response['width'] = $uploadmanager->original_width;
                $response['height'] = $uploadmanager->original_height;
            } else {
                $response['width'] = $uploadmanager->upload_width;
                $response['height'] = $uploadmanager->upload_height;
            }
		} else {
			$response['error'] = $uploadmanager->errormsg;
		}
	}
	outputJSON($response);
	exit;
}

###############################

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWA Image Uploader</title>
    <style>
        :root {
            --primary: #007AFF;
            --success: #28a745;
            --bg: #f8f9fa;
            --content-text: #000000;
            --card-faded: #666666; /* Slightly softer than #333 for better hierarchy */

            --accent: #6c757d;
            --input-bg: #ffffff;
            --input-placeholder: #999999;


            /* Light Mode Secondary */
            --secondary-bg: #e9ecef;
            --secondary-text: #333333;
            --secondary-hover: #dee2e6; /* Slightly darker for interaction */
            --danger-bg: #ffc0cb;       /* Your 'pink' */
            --danger-text: #333333;

        }
        body.dark-mode {
                --bg: #121212;
            --content-text: #e0e0e0;
            --card-faded: #a0a0a0;     /* Light grey to stand out on dark cards */

            /* New: Dark Mode Inputs */
            --input-bg: #2c2c2c;       /* Slightly lighter than card-bg to "lift" the input */
            --input-placeholder: #757575;

            /* Dark Mode Secondary */
            --secondary-bg: #333333;    /* Dark grey to sit quietly on the card */
            --secondary-text: #e0e0e0;  /* Off-white text */
            --secondary-hover: #444444; /* Slightly lighter for interaction */
            --danger-bg: #442727;       /* Deep wine/maroon background */
            --danger-text: #ff8a8a;     /* Soft red/pink text */
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg);
            color: var(--content-text);
            margin: 0; padding: 2px;
            display: flex; justify-content: center;
        }
        .card { --background: white; padding: 24px 0; border-radius: 20px; --box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 100%; max-width: 450px; text-align: center; }

        /* Custom Buttons */
        .btn { padding: 14px 28px; border-radius: 12px; border: none; cursor: pointer; touch-action: manipulation; user-select: none; font-weight: 600; transition: all 0.2s; display: inline-block; margin: 8px 0; font-size: 16px; }
        .btn-select { background: var(--primary); color: white; width: 100%; box-sizing: border-box; }
        .btn-upload { background: var(--primary); color: white; width: 100%; }
        .btn-upload:disabled { background: var(--secondary-bg); color: var(--secondary-text); cursor: not-allowed; }
        .btn-secondary { background: var(--secondary-bg); color: var(--secondary-text); width: 100%; }
	.btn-help { background-color:#8ddf8d; }


        /* Progress Bar */
        .progress-container { width: 100%; height: 6px; background: #eee; border-radius: 10px; margin: 10px 0; overflow: hidden; display: none; }
        .progress-bar { width: 0%; height: 100%; background: var(--success); transition: width 0.3s; }

        /* Views */
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 20px; }
        .hero-view { margin-top: 20px; position: relative; border-radius: 15px; overflow: hidden; }
        .hero-view img { width: 100%; max-height: 350px; object-fit: cover; display: block; }

        /* Image Item States */
        .img-wrapper { position: relative; aspect-ratio: 1; border-radius: 12px; overflow: hidden; background: #eee; }
        .img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: 0.4s; }
        .remove-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.2); display: flex; justify-content: center; align-items: center; opacity: 0.8; transition: 0.2s; cursor: pointer; }
        .img-wrapper:hover .remove-overlay { opacity: 1; }
        .remove-icon { background: white; color: red; border-radius: 50%; width: 24px; height: 24px; line-height: 24px; font-weight: bold; }

        .warning { position: absolute; top: 5px; left: 5px; background: #ffcc00; color: #000; padding: 2px 6px; border-radius: 4px; font-weight: bold; pointer-events: none; }

        /* Uploaded State */
        .uploaded img { opacity: 0.3; filter: grayscale(100%); }
        .uploaded::after { content: "\2713"; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 32px; color: var(--success); text-shadow: 0 2px 4px rgba(0,0,0,0.1); }

    	/* need to specifically fade to dark! */
	    body.dark-mode .uploaded img {
    		background-color: var(--bg);
	    	filter: grayscale(100%) brightness(0.2);
    		opacity: 0.8;
	    }

        .settings { margin-top: 25px; padding-top: 15px; border-top: 1px solid #eee; text-align: left; }
        .settings label { cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--accent); }
        .hidden { display: none; }

        #missing-btn { margin-top:20px; opacity:0.9; }

        dialog::backdrop {
            background: rgba(0, 0, 0, 0.5);
	        backdrop-filter: blur(3px);
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
            background-color: var(--input-bg);
		    color: var(--content-text);
        }

        dialog button {
            display:block;
            width:100%;
        }

    </style>
</head>
<body>

<div class="card">
    <label for="file-input" class="btn btn-select" id="select-label">Choose Image(s)</label>
    <input type="file" id="file-input" accept="image/jpeg, image/heic" multiple hidden>

    <div id="display-area"></div>

    <div class="progress-container" id="progress-cont">
        <div class="progress-bar" id="progress-fill"></div>
    </div>

	<button id="missing-btn" onclick="openModal('geo-modal')"  class="btn btn-help hidden" type="button">Why am I seeing a 'Missing Geo' error?</button>

    <button id="upload-btn" class="btn btn-upload hidden">Start Upload</button>

    <div id="post-upload-actions" class="hidden">
        <button class="btn btn-upload" onclick="navigateTo('/app/uploaded')">Proceed to Submission</button>
        <div id="multi-actions">
            <button class="btn btn-secondary" id="btnFirst">Submit First Image</button>
            <button class="btn btn-secondary hidden" id="btnLast">Submit Last Image</button>
        </div>
        <div id="single-actions" class="hidden">
            <button class="btn btn-secondary" id="btnSingle">Submit Image Now</button>
        </div>
    </div>

    <div class="settings hidden">
        <label>
            <input type="checkbox" id="auto-proceed">
            Proceed automatically after single upload completes
        </label>
    </div>


	<br><br>
    <div class="settings2">
        <label><input type="radio" name=ctype id="image" onclick="setAcceptValue(this)" value="image/jpeg, image/heic" checked>Image Selector</label>
        <label><input type="radio" name=ctype id="file" onclick="setAcceptValue(this)" value="*">File Selector</label>
    </div>
	<script>
		function setAcceptValue(that) {
			//seems most robust by setAttribute
			document.getElementById("file-input").setAttribute("accept", that.value);
		}
	</script>

	<p>If location data is missing, try the File Selector method. While navigating to your images this way can be a bit more involved, it's often more reliable for reading GPS data.
	 If you aren't having issues, feel free to stick with the simpler Image Selector.</p>

	<div>
		Or can try our: 
		<button type=button onclick="navigateTo('/app/chooser')">Enhanced Image Browser (Beta)</button>
	</div>

</div>


<dialog id="geo-modal" onclick="closeModal('geo-modal')">
	<p>If you believe the image should have location data (e.g., it was taken on a GPS-enabled device), your browser may be stripping the
	metadata to prevent accidental location sharing.</p>

	<p>This unfortunately happening before the image reaches this page, and is outside of our control.</p>

	<h4>Is there a workaround for mobile devices?</h4>

	<p>While there isn't a universal fix, some Android/Samsung users find success by clicking <strong>'Browse'</strong> and selecting the image
	from the <strong>'Recent'</strong> list rather than the Gallery. This often allows the file to retain its location data when it reaches the
	upload process. </p>

	<h4>Is there a more reliable way to capture the location?</h4>

	<p>Using our <strong>'Take Photo'</strong> page allows you to take images that save the coordinates directly into the filename. This
	prevents the browser from stripping the data and ensures the photo is quickly placed on the map. </p>

        <button type="button" class="btn" onclick="closeModal('geo-modal')">Close</button>
</dialog>


<script src="https://cdn.jsdelivr.net/npm/exifr@7.1.3/dist/lite.umd.js"></script>
<script src="<?php echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>
<script src="<?php echo smarty_modifier_revision("/js/Geograph.MediaDatabase.class.js"); ?>"></script>
<script src="<?php echo smarty_modifier_revision("/js/submission_utils.js"); ?>"></script>
<script src="<?php echo smarty_modifier_revision("/viewer/ExifRestorer.js"); ?>"></script>

<script>
    window.max_size = 8 * 1024 * 1024; //larger files will be downsized!
    window.uploadMaxDimension = 65536; // Default to effectively unlimited
</script>

    <script type="module">
	import { navigateTo, setupSettingsListener, openModal, closeModal } from '/app/js/utils.js?<? echo filemtime('js/utils.js'); ?>';
	window.navigateTo = navigateTo;
        window.openModal = openModal;
        window.closeModal = closeModal;
        setupSettingsListener();
    </script>

<script>
    const fileInput = document.getElementById('file-input');
    const selectLabel = document.getElementById('select-label');
    const displayArea = document.getElementById('display-area');
    const uploadBtn = document.getElementById('upload-btn');
    const postActions = document.getElementById('post-upload-actions');
    const progressCont = document.getElementById('progress-cont');
    const progressFill = document.getElementById('progress-fill');
    const autoProceedCheck = document.getElementById('auto-proceed');

    let fileQueue = [];

    // Persist Preference
    autoProceedCheck.checked = localStorage.getItem('autoProceed') === 'true';
    autoProceedCheck.onchange = () => localStorage.setItem('autoProceed', autoProceedCheck.checked);

    fileInput.addEventListener('change', handleFiles);

    async function handleFiles(e) {
        const files = Array.from(e.target.files);
        if (!files.length) return;

        // Check for the limit (prevent overload!)
        if (files.length > 25) {
            alert("You selected " + files.length + " files. Only the first 25 will be processed.");
            // 2. Slice to the allowed limit
            files = files.slice(0, 25);
        }

        fileQueue = files.map(file => ({
            id: 'img_' + Math.random().toString(36).substr(2, 9),
            file: file,
            dataUri: null
        }));

        document.querySelector('.settings').classList.toggle('hidden', files.length>1);

        renderUI();
    }

async function renderUI() {
    displayArea.innerHTML = '';
    selectLabel.style.opacity = 0.5;
    selectLabel.textContent = "Choose different image(s)";
    postActions.classList.add('hidden');
    progressCont.style.display = 'none';

    if (fileQueue.length === 0) {
        uploadBtn.classList.add('hidden');
        return;
    }

    uploadBtn.classList.remove('hidden');
    uploadBtn.disabled = true; // Disable until processing finishes
    uploadBtn.innerText = "Processing images...";

    const existing = document.getElementById('messageDiv');
    if (existing) existing.remove();

    // Process all images in the queue
    await Promise.all(fileQueue.map(processItem));

    // Render based on count
    let missingGeo = 0;
    if (fileQueue.length === 1) {
        const item = fileQueue[0];
        if(!item.exifData.hasGeo) missingGeo++;
        displayArea.innerHTML = `
            <div class="hero-view" id="wrapper-${item.id}">
                <img src="${item.isHeic?'/app/assets/heic-placeholder.png':item.dataUri}">
                ${item.exifData.hasGeo ? '' : '<div class="warning">Missing Geo-tags</div>'}
                ${(item.exifData.orientation && item.exifData.orientation !== 1) ? '<div class="warning">Needs Rotating</div>' : ''}
            </div>`;
    } else {
        const grid = document.createElement('div');
        grid.className = 'grid';
        for (const item of fileQueue) {
            const div = document.createElement('div');
            div.className = 'img-wrapper';
            div.id = `wrapper-${item.id}`;
            if(!item.exifData.hasGeo) missingGeo++;
            div.innerHTML = `
                <img src="${item.isHeic?'/app/assets/heic-placeholder.png':item.dataUri}">
                ${item.exifData.hasGeo ? '' : '<div class="warning">Missing Geo</div>'}
                ${(item.exifData.orientation && item.exifData.orientation !== 1) ? '<div class="warning">Needs Rotating</div>' : ''}
                <div class="remove-overlay" onclick="removeItem('${item.id}')">
                    <span class="remove-icon">&#10005;</span>
                </div>`;
            grid.appendChild(div);
        }
        displayArea.appendChild(grid);
    }

    uploadBtn.disabled = false;
    uploadBtn.innerText = `Upload ${fileQueue.length} ${fileQueue.length === 1 ? 'Image' : 'Images'}`;

    document.getElementById('missing-btn').classList.toggle('hidden', !missingGeo);
}


    function removeItem(id) {
        fileQueue = fileQueue.filter(f => f.id !== id);
        renderUI();
    }

    uploadBtn.onclick = async () => {
        uploadBtn.disabled = true;
        progressCont.style.display = 'block';

        // Can't remove them anymore once upload begun
        document.querySelectorAll('.remove-overlay').forEach(overlay => {
            overlay.style.display = 'none';
        });

        // 1. Hide all overlays once
        document.querySelectorAll('#display-area .remove-overlay').forEach(el => el.style.display = 'none');

        // 2. Iterate by modifying the original queue
        let i = 0;
        const fileQueueLength = fileQueue.length; //capture at start
        let lastResult = null;
        let lastItem = null;
        let uploadedCount = 0;

        while (i < fileQueue.length) {
            const item = fileQueue[i];

            // UI Update: Progress Bar
            uploadBtn.innerText = `Uploading ${uploadedCount + 1} / ${fileQueueLength}...`;
//            progressFill.style.width = ((uploadedCount + 1) / fileQueueLength) * 100 + '%';

            // Sequential POST request
            const result = await sendToPHP(item.dataUri, item.file.name, (percent) => {

                // 1. Update Button Text: Show which file and its specific %
                uploadBtn.textContent = `Uploading ${uploadedCount + 1} / ${fileQueueLength} (${percent}% done)`;

                // 2. Update Progress Bar: Smooth movement across the whole batch
                const smoothWidth = ((i + (percent / 100)) / fileQueue.length) * 100;
                progressFill.style.width = smoothWidth + '%';
            }, item.exifData);

            if (result && result.success) {
                // SUCCESS: Remove from queue and mark visually
                document.getElementById(`wrapper-${item.id}`).classList.add('uploaded');

                uploadedCount++;
                lastResult = result;
                lastItem = item;

                // Add the UploadID to buttons
                if (fileQueueLength === 1) {
                    addIdtoBtn('btnSingle', result.upload_id, result.width, result.height, item.exifData, item.file.name);
                } else if (uploadedCount === 1) {
                    addIdtoBtn('btnFirst', result.upload_id, result.width, result.height, item.exifData, item.file.name);
                } else if (uploadedCount === fileQueueLength) {
                    addIdtoBtn('btnLast', result.upload_id, result.width, result.height, item.exifData, item.file.name);
                }

                upload_id = result.upload_id;
                // Remove from array so it's gone if we click upload again
                fileQueue.splice(i, 1);
            } else {
                // FAILURE: Keep in queue, move to next
                alert(`Failed to upload ${item.file.name}. It will remain in the queue.`);
                document.getElementById(`wrapper-${item.id}`).style.border = "2px solid red";
                i++;
            }
        }

        // 3. Reset UI state
        uploadBtn.disabled = false;
        progressCont.style.display = 'none';

        // All submitted OK!
        if (fileQueue.length === 0) {
            selectLabel.style.opacity = 0.8;
            selectLabel.textContent = "Choose more image(s)";

            uploadBtn.classList.add('hidden');
            if (fileQueueLength === 1 && autoProceedCheck.checked && document.visibilityState === 'visible') {
                navigateTo('/app/submit',{message: JSON.stringify({
                    transfer_id: upload_id,
                    width: lastResult.width,
                    height: lastResult.height,
                    lat: lastItem.exifData?.lat,
                    long: lastItem.exifData?.long,
		    name: lastItem.file?.name,
                    imagetaken: lastItem.exifData?.date,
                    orientation: lastItem.exifData?.orientation
                })});
	        postActions.classList.add('hidden');
  	        displayArea.innerHTML = '';
            } else {
                postActions.classList.remove('hidden');
                document.getElementById('multi-actions').classList.toggle('hidden', fileQueueLength === 1);
                document.getElementById('single-actions').classList.toggle('hidden', fileQueueLength !== 1);
            }
        } else {
            // If queue not empty, change button text to indicate retry
            uploadBtn.innerText = `Retry ${fileQueue.length} Failed Uploads`;
        }
    };

function addIdtoBtn(btnId, upload_id, width, height, exifData, name) {
	//want to overite any existing click (from a previous call!)
        document.getElementById(btnId).onclick = function() {
		navigateTo('/app/submit',{message: JSON.stringify({
            transfer_id: upload_id,
            width: width,
            height: height,
            lat: exifData?.lat,
            long: exifData?.long,
            name: name,
            imagetaken: exifData?.date,
            orientation: exifData?.orientation
        })});
		postActions.classList.add('hidden');
		displayArea.innerHTML = '';
	};
}


</script>

</body>
</html>
