<?

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
		} else {
			$response['error'] = $uploadmanager->errormsg;
		}
	}
	outputJSON($response);
	exit;
}

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

        /* Custom Buttons */
        .btn { padding: 14px 28px; border-radius: 12px; border: none; cursor: pointer; font-weight: 600; transition: all 0.2s; display: inline-block; margin: 8px 0; font-size: 16px; }
        .btn-select { background: var(--primary); color: white; width: 100%; box-sizing: border-box; }
        .btn-upload { background: #1a1a1a; color: white; width: 100%; }
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
            <button class="btn btn-secondary" id="btnFirst">Submit First Image</button>
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
    const displayArea = document.getElementById('display-area');
    const uploadBtn = document.getElementById('upload-btn');
    const postActions = document.getElementById('post-upload-actions');
    const progressCont = document.getElementById('progress-cont');
    const progressFill = document.getElementById('progress-fill');
    const autoProceedCheck = document.getElementById('auto-proceed');

    let fileQueue = [];
    const max_size = 8 * 1024 * 1024; //larger files will be downsized!

    // Persist Preference
    autoProceedCheck.checked = localStorage.getItem('autoProceed') === 'true';
    autoProceedCheck.onchange = () => localStorage.setItem('autoProceed', autoProceedCheck.checked);

    fileInput.addEventListener('change', handleFiles);

    async function handleFiles(e) {
        const files = Array.from(e.target.files);
        if (!files.length) return;

        fileQueue = files.map(file => ({
            id: 'img_' + Math.random().toString(36).substr(2, 9),
            file: file,
            dataUri: null
        }));

        renderUI();
    }

    async function renderUI() {
        displayArea.innerHTML = '';
        postActions.classList.add('hidden');
        progressCont.style.display = 'none';

        if (fileQueue.length === 0) {
            uploadBtn.classList.add('hidden');
            return;
        }

        uploadBtn.classList.remove('hidden');
        uploadBtn.disabled = false;
        uploadBtn.innerText = `Upload ${fileQueue.length} ${fileQueue.length === 1 ? 'Image' : 'Files'}`;

        if (fileQueue.length === 1) {
            const dataUri = await toBase64(fileQueue[0].file);
            fileQueue[0].dataUri = dataUri;
            displayArea.innerHTML = `
                <div class="hero-view" id="wrapper-${fileQueue[0].id}">
                    <img src="${dataUri}">
                </div>`;
        } else {
            const grid = document.createElement('div');
            grid.className = 'grid';
            for (const item of fileQueue) {
                const dataUri = await toBase64(item.file);
                item.dataUri = dataUri;
                const div = document.createElement('div');
                div.className = 'img-wrapper';
                div.id = `wrapper-${item.id}`;
                div.innerHTML = `
                    <img src="${dataUri}">
                    <div class="remove-overlay" onclick="removeItem('${item.id}')">
                        <span class="remove-icon">&#10005;</span>
                    </div>`;
                grid.appendChild(div);
            }
            displayArea.appendChild(grid);
        }
    }

    function removeItem(id) {
        fileQueue = fileQueue.filter(f => f.id !== id);
        renderUI();
    }

    uploadBtn.onclick = async () => {
        uploadBtn.disabled = true;
        progressCont.style.display = 'block';

        for (let i = 0; i < fileQueue.length; i++) {
            const item = fileQueue[i];

        // --- UPDATED: RESIZING STEP ---
        // This will now use your worker logic and wait until it's finished
        let finalDataUri = item.dataUri;
        if (item.file.size > max_size) {
            uploadBtn.innerText = `Resizing ${item.file.name}...`;
            finalDataUri = await prepareImageForUpload(item.file, max_size);

	    setTimeout(function() {
		//resize left a message we need to hide!
		const element = document.getElementById('messageDiv');
                if (element)
			element.style.display = 'none';
	    }, 2000);
        }
        // ------------------------------

            // Can't remove it anymore!
	    const child = document.getElementById(`wrapper-${item.id}`).querySelector('.remove-overlay');
	    if (child) {
                child.style.display = 'none';
            }

            // UI Update: Progress Bar
            const percent = ((i + 1) / fileQueue.length) * 100;
            progressFill.style.width = percent + '%';
            uploadBtn.innerText = `Uploading ${i + 1}/${fileQueue.length}...`;

            // Sequential POST request
            const result = await sendToPHP(item.dataUri);

            if (result && result.success) {
                document.getElementById(`wrapper-${item.id}`).classList.add('uploaded');
		//todo, attach the result.upload_id to the 'continue' buttons!
		if (i == 0 && fileQueue.length == 1) {
			//want to overite any existing click (from a previous call!)
	                document.getElementById('btnSingle').onclick = function() {
				navigateTo('/app/submit',{message: 'transfer_id='+result.upload_id});
			};
		} else if (i == 0) {
	                document.getElementById('btnFirst').onclick = function() {
				navigateTo('/app/submit',{message: 'transfer_id='+result.upload_id});
			};
		} else if (i == fileQueue.length-1) {
	                document.getElementById('btnLast').onclick = function() {
				navigateTo('/app/submit',{message: 'transfer_id='+result.upload_id});
			};
		}

		//todo, delete from fileQueue (so if retry failures, doesnt resubmit!)
            } // else alert??
        }

        uploadBtn.classList.add('hidden');
        progressCont.style.display = 'none';

        // Post-Upload Logic
        if (fileQueue.length === 1 && autoProceedCheck.checked) {
            submitChoice('only');
        } else {
            postActions.classList.remove('hidden');
            document.getElementById('multi-actions').classList.toggle('hidden', fileQueue.length === 1);
            document.getElementById('single-actions').classList.toggle('hidden', fileQueue.length !== 1);
        }
    };

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
            return { success: true, id: result.upload_id };
        } else {
            console.error('Upload failed:', result.error);
            return { success: false, error: result.error };
        }

    } catch (e) {
        console.error('Fetch error:', e);
        return { success: false, error: e.message };
    }
}

    function submitChoice(type) {
        let finalImage;
        if (type === 'first') finalImage = fileQueue[0];
        if (type === 'last') finalImage = fileQueue[fileQueue.length - 1];
        if (type === 'only') finalImage = fileQueue[0];

        // Replace this with your actual navigation or final submission logic
        console.log("Final Selection:", finalImage.file.name);
        alert(`Finalized: ${finalImage.file.name}. Redirecting...`);
    }

/**
 * Wraps your existing legacy library logic into a Promise-based flow
 */
async function prepareImageForUpload(file, max_size) {
    // 1. If file is small enough, skip resizing
    if (file.size <= max_size) {
        return await toBase64(file);
    }

    // 2. Return a Promise that resolves when the legacy callback is fired
    return new Promise((resolve, reject) => {
        // resizeFileWorker automatically detects environment and calls 
        // resizeFile as a fallback if the worker API isn't supported.
        resizeFileWorker(file, max_size, (dataUrl, finalSize) => {
            // This is the callback from your legacy library
            resolve(dataUrl);
        });
    });
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
