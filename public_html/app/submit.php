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

        .btn { padding: 14px 28px; border-radius: 12px; border: none; cursor: pointer; font-weight: 600; transition: all 0.2s; display: inline-block; margin: 8px 0; font-size: 16px; }
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
        .controls button { font-size:1.1em }

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

    input, select, textarea {
        /* Set this to the height of your sticky header + a bit of padding */
        scroll-margin-top: 120px; 
    }

input:invalid, select:invalid, #contexts:invalid {
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
    font-size: 0.8em;
    padding: 2px 5px;
    max-width: 150px;
    border-radius: 4px;
    border: 1px solid #ccc;
    background: #f9f9f9;
}

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

#tag-search {
    margin-bottom:0;
}
.tag-input-container {
    background-color:white;
    border-radius:6px;
}
.suggestion-item {
    --display: inline; /* Allows them to flow next to each other */
    padding: 6px 12px;     /* More padding for better touch targets */
    margin: 4px;
    cursor: pointer;
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

/* Add an active state for mobile tapping */
.suggestion-item:active {
    background: #007bff;
    color: white;
    border-color: #0056b3;
}
.suggestion-item strong {
    font-weight: 500;
    text-decoration: underline;
    text-decoration-color: silver;
}

span.tag-pill {
    padding: 6px 12px;
    margin: 4px;
	white-space: nowrap;
    font-weight: 500;

    border-radius: 15px;   /* Rounded pill look */
    border: 1px solid #aaa;
    background: #fff;
}
div#active-tags {
	line-height:35px;
}
span.tag-pill button {
	border:none;
    color:red;
	margin-left: 6px; /* Give the 'X' some space */
	padding:0;
}
.add-new-tag {
    background-color: #e6fffa; /* Soft green */
    border: 1px dashed #38b2ac; /* Dashed border to imply 'creating' */
    color: #2c7a7b;
    font-weight: bold;
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

	fieldset {
		margin-top:20px;
		border-radius:8px;
	    background-color:#f5f5f0; padding:3px;
	}
	fieldset legend {
		padding:5px;
		margin-left:10px;
		font-weight:700;
		font-size:1.05em;
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
	    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
	}
	#license-modal {
	    max-height: 85vh; /* Give a bit more vertical breathing room */
	    max-width: 90vw;  /* Prevents it from hitting the screen edges on mobile */
	    width: 500px;
	    padding: 20px;
	    border-radius: 12px;
	    border: 1px solid #ccc;
	    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
	}
	.nowrap {
		white-space:nowrap;
	}
    </style>
</head>
<body>

<div id="top-boundary"></div>

<div class="main-header" id="mainHeader">
    <img id="imgLarge" class="preview-img-large" src="" alt="Full Preview">
    <div class="controls">
        <button onclick="rotateImage(270)">&#8634; Rotate Left</button>
        <button onclick="rotateImage(90)">Rotate Right &#8635;</button>
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
	<input type=hidden name="upload_id" value="">

	<div id="map">
		map placeholder!
	</div>

	<div class="content">
        <div class="field-header">
    	    <label>Title</label>
            <span class="optional-label">(required)</span>
        </div>
	    <input type="text" name="title" placeholder="Give your photo a title" oninput="updateStickyTitle(this.value)" required>

        <div class="field-header">
    	    <label>Description (optional)</label>
            <span class="optional-label">(optional)</span>
        </div>
	    <textarea name="comment" placeholder="optional longer description" rows="5"></textarea>

        <fieldset>
            <legend>Date Taken</legend>
            <input type="date" id="date-picker" name="imagetaken" required>
            <input type="text" id="date-text" name="date_partial"
                   placeholder="e.g. 2025 or 2025-03"
                   title="Please enter a date like 1960, 1964-03, or 2023-03-09"
                   style="display:none;" pattern="^\s*\d{4}([/ -]\d{1,2}){0,2}\s*$">

            <div id="date-controls">
                <button type="button" onclick="switchToText()">I only know the approx Year/Month</button>
                <button type="button" onclick="clearDate()">I don't know the date</button>
            </div>

            <script>
            function switchToText() {
                const picker = document.getElementById('date-picker');
                const textInput = document.getElementById('date-text');

                picker.style.display = 'none';
                picker.removeAttribute('required');

                textInput.style.display = 'block';
                textInput.setAttribute('required', 'required');
                textInput.focus();

                document.getElementById('date-controls').innerHTML = '';
            }

            function clearDate() {
                // Hide both, remove required from both
                document.getElementById('date-picker').style.display = 'none';
                document.getElementById('date-picker').removeAttribute('required');
                document.getElementById('date-text').style.display = 'none';
                document.getElementById('date-text').removeAttribute('required');
                document.getElementById('date-controls').innerHTML = '<p>Date unknown</p>';
            }
            </script>

        </fieldset>

        <div class="field-header">
    	    <label>Geographical Contexts</label>
            <span class="optional-label">(select multiple)</span>
        </div>
	    <select name="contexts[]" id="contexts" multiple size=10 required></select>

        <div class="field-header">
            <label for="subject-input">Primary Subject</label>
            <select class="recent-select" id="recent-subjects" onchange="useRecentSubject(this)">
                <option value="">Recently Used</option>
            </select>
        </div>
        <input type="text" id="subject-input" list="subject-list" placeholder="Search subjects...">
        <datalist id="subject-list"></datalist>
        <input type="hidden" name="subject_id" id="subject-id">

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

                <div class=nowrap id="showvfov" style="display:none">(vfov: <input type=number step=0.01 name=vfov placeholder=120 style=width:70px;text-align:right>degrees wide)</div>
                <div class=nowrap id="showhfov">(hfov: <input type=number step=0.01 name=hfov placeholder=90 style=width:70px;text-align:right>degrees high)</div>
           </div>
           <script>
           function updatePanoDisplay() {
                if (document.getElementById("c-pano").checked) {
                    const select = document.getElementById("panoselect");
                    document.getElementById('showvfov').style.display = (select.value == 'panorama:wideangle')?'':'none';
                    document.getElementById('showhfov').style.display = (select.value == 'panorama:photosphere')?'none':'';
                    document.getElementById("showpano").style.display = "";
                } else {
                    document.getElementById("showpano").style.display ="none";
                }
           }
           </script>

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
                onclick="document.getElementById('license-modal').showModal();document.getElementById('license-modal').scrollTop = 0;"
                style="background:none; border:none; color:blue; text-decoration:underline; cursor:pointer;">Read More &gt;</button> </p>

            <dialog id="license-modal" onclick="document.getElementById('license-modal').close()">
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

                <p>You are releasing this image at [d x d] specifically, the larger size (if any) wont be released.

	            <p><a href="/help/freedom" target="_blank">Open Geograph Freedom Manifesto</a> <span class="nowrap">(Opens in new tab)</span></p>

                <button type="button" style="display:block;width:100%;" onclick="document.getElementById('license-modal').close()">Close</button>
            </dialog>
        </fieldset>

	</div>

	<button type=submit class="btn btn-primary">I Agree - Submit Image</button>


	<br><br>
</form>

<script>
    const stickyBar = document.getElementById('stickyBar');
    const mainHeader = document.getElementById('mainHeader');
    const displayTitle = document.getElementById('displayTitle');
    const imgLarge = document.getElementById('imgLarge');
    const imgThumb = document.getElementById('imgThumb');
    const mainForm = document.getElementById('mainForm');

// --------------------------------

    let upload_id = null;
    let filename = null; //we can receive the original filename

    window.addEventListener('message', (event) => {
        // Basic security check: if (event.origin !== "http://yourdomain.com") return;

        console.log("Received:", event.data);
        if (event.data.startsWith('transfer_id=')) {
            upload_id = event.data.match(/id=(\w+)/)[1];
    	//todo, extract exif (geo+date+oritentiation+filename!)
            resetForm(upload_id);
        }
    });

    window.addEventListener('DOMContentLoaded', function() {
    	//note sure if will use URL Params, but very useful during testing!
        const urlParams = new URLSearchParams(window.location.search);
        if (newID = urlParams.get('transfer_id')) {
        	resetForm(newID);
        }
        //todo?
        //else setTimeout(function() { if (!update_id) navigateTo('/app/uploaded/'); }, 2500);

        loadContexts(); //loads it own recent
        loadSubjects();
        renderRecent('submit.subjects', 'recent-subjects');
        renderRecent('submit.tags', 'recent-tags');
        updateLicenceDiv();
    });

    function resetForm(newId) {
        updatePreview(newId); //will store it in upload_id;

    	//we starting again!
    	mainForm.elements['title'].value = '';
    	mainForm.elements['comment'].value = '';
        //todo, other elements to reset too!
    	//todo, we need to set position (from EXIF)
    	//todo, we need to set date!!? (from EXIF)
    }

    function updatePreview(newId) {
        if (newId)
            upload_id = newId; //store in the global (otherwise we using from the global as is)
        mainForm.elements['upload_id'].value = upload_id;
        imgLarge.src = `/submit.php?preview=${upload_id}`;
        imgThumb.src = `/submit.php?preview=${upload_id}`;
    }

// --------------------------------
// Sticky Header/Preview

    async function rotateImage(degrees, force = 0) {
        const form = document.forms['theForm'];

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

            const response = await fetch(`/tags/tags.json.php?term=${encodeURIComponent(query)}&mode=ranked`);
            const results = await response.json(); // Expected: ["tag1", "tag2"]

            // Normalize results for comparison
            const normalizedResults = results.map(t => t.toLowerCase());

            let html = results.map(tag => {
                // 1. Create a Case-Insensitive Regex of the user's query
                const regex = new RegExp(`(${query})`, "gi");
                // 2. Replace the match with a bold version
                // $1 keeps the original casing from the database (e.g., "Road" stays "Road")
                const highlighted = escapeHTML(tag).replace(regex, "<strong>$1</strong>");
                return `<div class="suggestion-item">${highlighted}</div>`;
            }).join('');

            if (query.length > 2 && !normalizedResults.includes(query.toLowerCase())) {
                const query_safe = escapeHTML(query);
                html += `<div class="suggestion-item add-new-tag" data-tag="${query_safe}">+ Add "${query_safe}"</div>`;
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
                searchInput.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center' // This puts it in the middle, not the top
                });
            }
        }, 300);
    });

    function addTag(tag) {
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
// Contexts & Subjects

    const subjectInput = document.getElementById('subject-input');

    async function loadSubjects() {
        const response = await fetch("/tags/subject.json.php");
        const data = await response.json();
        const list = document.getElementById('subject-list');

        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.tag; // This is what the user sees/types
            option.dataset.id = item.tag_id; // Store the ID for the form
            list.appendChild(option);
        });
    }

    subjectInput.addEventListener('change', () => {
        const options = document.querySelectorAll('#subject-list option');
        const match = Array.from(options).find(o => o.value === subjectInput.value);

        //this is only added during submit (if needed), but need to clear it!
        if (match) {
            subjectInput.setCustomValidity("");
        }
    });

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

        // Clear existing (except first "Recent..." option)
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
    }

    // When a Recent Tag is picked
    function useRecentTag(select) {
        if (!select.value) return;
        addTag(select.value);
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

        // check required

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

            // Find if the typed value matches a valid tag
            const match = Array.from(options).find(o => o.value === input.value);

console.log(input,match);

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

        return true;
    }

    document.forms['mainForm'].addEventListener('submit', validateForm);


// --------------------------------
// General App Stuff

function navigateTo(path, options) {
    const isInsideIframe = window.self !== window.top;
    const target = isInsideIframe ? window.parent : window;

    const event = new CustomEvent('request-navigation', {
        detail: { path, options },
        bubbles: true
    });
    target.dispatchEvent(event);
}

function escapeHTML(str) {
    const p = document.createElement('p');
    p.textContent = str;
    return p.innerHTML;
}


</script>

</body>
</html>
