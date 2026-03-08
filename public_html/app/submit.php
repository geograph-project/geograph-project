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
            z-index: 1000;
            
            /* Hidden State */
            transform: translateY(-100%);
            transition: transform 0.2s ease-in-out;
        }
        .sticky-bar.visible { transform: translateY(0); }
        
        .thumb-img { height: 45px; width: auto; border-radius: 4px; margin-right: 12px; }
        .sticky-title { font-weight: bold; font-size: 0.9em; color: #333; }

        /* Map */

	#map {
		max-width: min( 350px , 100% );
		margin: 6px auto;
		aspect-ratio: 1 / 1;

		border:1px solid black; border-radius:2px;
		background-color:cyan;
	}

        /* Content spacing */
        .content { padding: 20px; max-width: 600px; margin: 0 auto; }

@media screen and (max-width: 500px) {
        .content {
                padding:20px 2px;
        }
}

        label { display: block; margin: 15px 0 5px; font-weight: bold; color: #555; }
        input, textarea { 
            width: 100%; padding: 12px; margin-bottom: 10px; 
            border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; 
        }

/* Compact Flag Container */
    .flag-container {
        background: #f9f9f9;
        border-radius: 8px;
        border: 1px solid #eee;
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
    }

    .flag-item input {
	width:inherit;
        margin-top: 4px; /* Align checkbox with the top of the text */
        margin-right: 10px;
        transform: scale(1.2); /* Slightly larger for easier tapping */
    }

    .flag-item span {
        font-weight: normal;
        font-size: 0.95em;
        line-height: 1.4;
    }


        button {
            padding: 8px 16px; border: 1px solid #007bff; background: #fff;
            color: #007bff; border-radius: 4px; cursor: pointer; font-weight: bold;
        }
        button:active { background: #007bff; color: #fff; }
    </style>
</head>
<body>

<div id="top-boundary"></div>

<div class="main-header" id="mainHeader">
    <img id="imgLarge" class="preview-img-large" src="" alt="Full Preview">
    <div class="controls">
        <button onclick="rotate(-90)">&#8634; Rotate Left</button>
        <button onclick="rotate(90)">Rotate Right &#8635;</button>
    </div>

    <div style="display:none">
	Warning: <b>This image has EXIF 'Orientation' flag set.</b>

        It's highly recommended to use the rotation function to reorientate the image, this resets the flag which prevents potential display 
        issues, as not all Browsers etc will honor the flag.<br><br> So please rotate the image, even if it actully displays 
        <i>correctly</i> in the preview! Rotate it sideways, and then <i>back</i> until displays correctly again.<br>Your browser might be 
        ignoring the flag which is why the preview appears ok to you!<br><br>
    </div>
</div>

<div id="stickyBar" class="sticky-bar">
    <img id="imgThumb" class="thumb-img" src="" alt="Thumbnail">
    <div class="sticky-title" id="displayTitle"></div>
</div>

<form method="post" name="mainForm" id="mainForm">
	<input type=hidden name="transfer_id" value="">

	<div id="map">
		map placeholder!
	</div>

	<div class="content">
	    <label>Title</label>
	    <input type="text" name="title" placeholder="Give your photo a title" oninput="updateStickyTitle(this.value)">

	    <label>Description (optional)</label>
	    <textarea name="comment" placeholder="optional longer description" rows="4"></textarea>

	    <label>Date Photo Taken</label>
	    <input type="date" name="imagetaken">

	    <label>Primary Geographical Context</label>
	    <select name="context" multiple></select>

	    <label>Primary Subject (optional)</label>
	    <input type="search" name="subject">

	    <label>Tags (optional)</label>
	    <input type="search" name="tags[]">

        <label>Special Flags</label>
    	<div class="flag-container">
	        <label class="flag-item" for="c-drone">
    	        <input type="checkbox" name="tags[]" id="c-drone" value="from:drone">
	            <span><b>Taken from/by Drone</b><br><small>(not a handheld camera)</small></span>
	        </label>

    	    <label class="flag-item" for="c-pano">
	            <input type="checkbox" name="tags[]" id="c-pano" value="panorama">
    	        <span><b>Panorama</b></span>
	        </label>
    	</div>

		<!-- SD??!? -->

   	    <label>Licence</label>
   	    <div id="licence" style="height:150px; color:gray">
            placeholder!
	    </div>

	</div>

	<button type=submit disabled class="btn btn-primary">Submit Image</button>

</form>

<script>
    const stickyBar = document.getElementById('stickyBar');
    const mainHeader = document.getElementById('mainHeader');
    const displayTitle = document.getElementById('displayTitle');
    const imgLarge = document.getElementById('imgLarge');
    const imgThumb = document.getElementById('imgThumb');
    const mainForm = document.getElementById('mainForm');

// --------------------------------

    let transfer_id = null;
    let filename = null; //we can receive the original filename

    window.addEventListener('message', (event) => {
        // Basic security check: if (event.origin !== "http://yourdomain.com") return;

        console.log("Received:", event.data);
        if (event.data.startsWith('transfer_id=')) {
            transfer_id = event.data.match(/id=(\w+)/)[1];
    	//todo, extract exif (geo+date+oritentiation+filename!)
            resetForm();
        }
    });

    window.addEventListener('DOMContentLoaded', function() {
    	//note sure if will use URL Params, but very useful during testing!
        const urlParams = new URLSearchParams(window.location.search);
        if (newID = urlParams.get('transfer_id')) {
        	transfer_id = newID;
        	resetForm();
        }
        //todo?
        //else setTimeout(function() { if (!transfer_id) navigateTo('/app/uploaded/'); }, 2500);
    });

	//assumes transfer_id set!
    function resetForm() {
    	mainForm.elements['transfer_id'].value = transfer_id;
    	imgLarge.src = `/submit.php?preview=${transfer_id}`;
    	imgThumb.src = `/submit.php?preview=${transfer_id}`;
    	if (filename)
    		displayTitle.textContent = filename;

    	//we starting again!
    	mainForm.elements['title'].value = '';
    	mainForm.elements['comment'].value = '';
    	//todo, we need to set position (from EXIF)
    	//todo, we need to set date!!? (from EXIF)
    }

// --------------------------------

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
                stickyBar.classList.add('visible');
            } else {
                stickyBar.classList.remove('visible');
            }
        });
    }, {
        threshold: [0, 0.2, 0.5, 1.0] // Track multiple points for smoother transitions
    });
    observer.observe(imgLarge);

    // Rotation Logic (Applied to both images)   -- TEMPROARY!
    let rotation = 0;
    function rotate(deg) {
        rotation = (rotation + deg) % 360;
        const transformValue = `rotate(${rotation}deg)`;
        document.getElementById('imgLarge').style.transform = transformValue;
        document.getElementById('imgThumb').style.transform = transformValue;
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
