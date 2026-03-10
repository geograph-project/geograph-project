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
	        $response['ok']  = $uploadmanager->processDataURL($data['image']);
        	if ($response['ok']) {
                	$response['upload_id'] = $uploadmanager->upload_id;
                        $response['width'] = $uploadmanager->upload_width;
                        $response['height'] = $uploadmanager->upload_height;
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
            --accent: #6c757d;
        }
        body { font-family: -apple-system, system-ui, sans-serif; background: var(--bg); margin: 0; padding: 20px; display: flex; justify-content: center; }
        .card { background: white; padding: 24px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 100%; max-width: 450px; text-align: center; }

@media screen and (max-width: 500px) {
        body {
                padding:20px 2px;
        }
	.card {
		padding:20px 2px;
	}
}

        /* Custom Buttons */
        .btn { padding: 14px 28px; border-radius: 12px; border: none; cursor: pointer; font-weight: 600; transition: all 0.2s; display: inline-block; margin: 8px 0; font-size: 16px; }
        .btn-select { background: var(--primary); color: white; width: 100%; box-sizing: border-box; }
        .btn-upload { background: var(--primary); color: white; width: 100%; }
        .btn-upload:disabled { background: #ccc; cursor: not-allowed; }
        .btn-secondary { background: #e9ecef; color: #333; width: 100%; }

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

        .settings { margin-top: 25px; padding-top: 15px; border-top: 1px solid #eee; text-align: left; }
        .settings label { cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px; color: var(--accent); }
        .hidden { display: none; }
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

    <button id="upload-btn" class="btn btn-upload hidden">Start Upload</button>

    <div id="post-upload-actions" class="hidden">
        <div id="multi-actions">
            <button class="btn btn-upload" id="btnFirst">Submit First Image</button>
            <button class="btn btn-secondary" id="btnLast">Submit Last Image</button>
        </div>
        <div id="single-actions" class="hidden">
            <button class="btn btn-upload" id="btnSingle">Submit Image Now</button>
        </div>
    </div>

    <div class="settings">
        <label>
            <input type="checkbox" id="auto-proceed">
            Proceed directly after single upload
        </label>
    </div>
</div>

<!-- note we are using a local 'patched' version of exif.js, that deals with specific bugs -->
<script type="text/javascript" src="/viewer/exif.js"></script>
<script type="text/javascript" src="/js/submission_utils.js"></script>
<script type="text/javascript" src="/viewer/ExifRestorer.js"></script>

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
    const max_size = 8 * 1024 * 1024; //larger files will be downsized!
    let uploadMaxDimension = 65536; // Default to effectively unlimited

    window.addEventListener('message', (event) => {
        if (event.origin !== window.location.origin) return;
        try {
            const data = JSON.parse(event.data);
            if (data.settings && data.settings.uploadMaxDimension) {
                uploadMaxDimension = parseInt(data.settings.uploadMaxDimension, 10);
                console.log('Updated uploadMaxDimension to:', uploadMaxDimension);
            }
        } catch (e) {
            // Not JSON or other message type
        }
    });

    // Persist Preference
    autoProceedCheck.checked = localStorage.getItem('autoProceed') === 'true';
    autoProceedCheck.onchange = () => localStorage.setItem('autoProceed', autoProceedCheck.checked);

    fileInput.addEventListener('change', handleFiles);

    function toDecimal(number) {
        if (!number || !number.length) return null;
        const convert = (val) => (typeof val === 'object' && val.denominator) ? val.numerator / val.denominator : val;
        return convert(number[0]) + (convert(number[1]) / 60.0) + (convert(number[2]) / 3600.0);
    }

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

        renderUI();
    }

    // Helper to process a single item
    async function processItem(item) {
        const isHeic = item.file.name.toLowerCase().endsWith('.heic') || item.file.type === 'image/heic';

        // 1. Analyze EXIF
        const exifData = await new Promise(resolve => EXIF.getData(item.file, function() {
            const lat = EXIF.getTag(this, 'GPSLatitude');
            const long = EXIF.getTag(this, 'GPSLongitude');
            const date = EXIF.getTag(this, 'DateTimeOriginal') || EXIF.getTag(this, 'DateTime');
            const orientation = EXIF.getTag(this, 'Orientation');

            let decimalLat = null;
            let decimalLong = null;

            if (lat && long) {
                decimalLat = toDecimal(lat);
                decimalLong = toDecimal(long);

                if (EXIF.getTag(this, 'GPSLatitudeRef') === 'S') decimalLat *= -1;
                if (EXIF.getTag(this, 'GPSLongitudeRef') === 'W') decimalLong *= -1;
                // Handle 0-360 range if necessary
                if (decimalLong > 180) decimalLong -= 360.0;
            }

            resolve({
                hasGeo: !!(lat && long),
                lat: decimalLat,
                long: decimalLong,
                date: date,
                orientation: orientation
            });
        }));

        // 2. Resize if necessary
        let dataUri = await new Promise(async (resolve) => {
            let needsResize = (item.file.size > max_size);

            if (!needsResize && uploadMaxDimension < 65536) {
                // Also check dimensions
                const dimensions = await new Promise(res => {
                    const img = new Image();
                    img.onload = () => {
                        const dims = {w: img.width, h: img.height};
                        URL.revokeObjectURL(img.src);
                        res(dims);
                    };
                    img.onerror = () => {
                        URL.revokeObjectURL(img.src);
                        res(null);
                    };
                    img.src = URL.createObjectURL(item.file);
                });
                if (dimensions && (dimensions.w > uploadMaxDimension || dimensions.h > uploadMaxDimension)) {
                    needsResize = true;
                }
            }

            if (needsResize && !isHeic) {
                resizeFileWorker(item.file, max_size, (url) => {
                    const finished = document.getElementById('messageDiv');
                    if (finished) finished.remove();
                    resolve(url);
                }, uploadMaxDimension);
            } else {
                const reader = new FileReader();
                reader.onload = (e) => resolve(e.target.result);
                reader.readAsDataURL(item.file);
            }
        });

        item.dataUri = dataUri;
        item.isHeic = isHeic;
        return { item, exifData };
    }

async function renderUI() {
    displayArea.innerHTML = '';
    selectLabel.style.opacity = 0.5;
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
    const processedItems = await Promise.all(fileQueue.map(processItem));

    // Store EXIF data back to fileQueue
    processedItems.forEach(({ item, exifData }) => {
        const queueItem = fileQueue.find(f => f.id === item.id);
        if (queueItem) queueItem.exifData = exifData;
    });

    // Render based on count
    if (processedItems.length === 1) {
        const { item, exifData } = processedItems[0];
        displayArea.innerHTML = `
            <div class="hero-view" id="wrapper-${item.id}">
                <img src="${item.isHeic?'/app/assets/heic-placeholder.png':item.dataUri}">
                ${exifData.hasGeo ? '' : '<div class="warning">Missing Geo-tags</div>'}
                ${(exifData.orientation && exifData.orientation !== 1) ? '<div class="warning">Needs Rotating</div>' : ''}
            </div>`;
    } else {
        const grid = document.createElement('div');
        grid.className = 'grid';
        for (const { item, exifData } of processedItems) {
            const div = document.createElement('div');
            div.className = 'img-wrapper';
            div.id = `wrapper-${item.id}`;
            div.innerHTML = `
                <img src="${item.isHeic?'/app/assets/heic-placeholder.png':item.dataUri}">
                ${exifData.hasGeo ? '' : '<div class="warning">Missing Geo</div>'}
                ${(exifData.orientation && exifData.orientation !== 1) ? '<div class="warning">Needs Rotating</div>' : ''}
                <div class="remove-overlay" onclick="removeItem('${item.id}')">
                    <span class="remove-icon">&#10005;</span>
                </div>`;
            grid.appendChild(div);
        }
        displayArea.appendChild(grid);
    }

    uploadBtn.disabled = false;
    uploadBtn.innerText = `Upload ${fileQueue.length} ${fileQueue.length === 1 ? 'Image' : 'Images'}`;
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
            progressFill.style.width = ((uploadedCount + 1) / fileQueueLength) * 100 + '%';

            // Sequential POST request
            const result = await sendToPHP(item.dataUri);
            if (result && result.success) {
                // SUCCESS: Remove from queue and mark visually
                document.getElementById(`wrapper-${item.id}`).classList.add('uploaded');

                uploadedCount++;
                lastResult = result;
                lastItem = item;

                // Add the UploadID to buttons
                if (fileQueueLength === 1) {
                    addIdtoBtn('btnSingle', result.upload_id, result.width, result.height, item.exifData);
                } else if (uploadedCount === 1) {
                    addIdtoBtn('btnFirst', result.upload_id, result.width, result.height, item.exifData);
                } else if (uploadedCount === fileQueueLength) {
                    addIdtoBtn('btnLast', result.upload_id, result.width, result.height, item.exifData);
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
            selectLabel.style.opacity = 0.7;
            uploadBtn.classList.add('hidden');
            if (fileQueueLength === 1 && autoProceedCheck.checked && document.visibilityState === 'visible') {
                navigateTo('/app/submit',{message: JSON.stringify({
                    transfer_id: upload_id,
                    width: lastResult.width,
                    height: lastResult.height,
                    lat: lastItem.exifData?.lat,
                    long: lastItem.exifData?.long,
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

function addIdtoBtn(btnId, upload_id, width, height, exifData) {
	//want to overite any existing click (from a previous call!)
        document.getElementById(btnId).onclick = function() {
		navigateTo('/app/submit',{message: JSON.stringify({
            transfer_id: upload_id,
            width: width,
            height: height,
            lat: exifData?.lat,
            long: exifData?.long,
            imagetaken: exifData?.date,
            orientation: exifData?.orientation
        })});
		postActions.classList.add('hidden');
		displayArea.innerHTML = '';
	};
}


async function sendToPHP(dataUri) {
    try {
        const response = await fetch('upload.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ image: dataUri })
        });

        // 1. Always check HTTP status first
        if (!response.ok) throw new Error('Network response was not ok');

        // 2. Parse the JSON returned by your PHP script
        const result = await response.json();

        // 3. Handle your custom application-level success/error
        if (result.ok) {
            console.log('Upload successful! ID:', result.upload_id);
            return { success: true, upload_id: result.upload_id, width: result.width, height: result.height };
        } else {
            console.error('Upload failed:', result.error);
            return { success: false, error: result.error };
        }

    } catch (e) {
        console.error('Fetch error:', e);
        return { success: false, error: e.message };
    }
}

    function toBase64(file) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
        });
    }

function navigateTo(path, options) {
    const isInsideIframe = window.self !== window.top;
    const target = isInsideIframe ? window.parent : window;

    const event = new CustomEvent('request-navigation', {
        detail: { path, options },
        bubbles: true
    });
    target.dispatchEvent(event);
}

</script>

</body>
</html>
