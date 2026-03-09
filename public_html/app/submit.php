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

#contexts {
    width: 100%;
    padding-left: 3px; /* Gives the items room to breathe */
    font-size: 16px; /* Prevents iOS from auto-zooming on focus */
    line-height: 1.5;
    border: 1px solid #ccc;
    border-radius: 6px;
    background-color: var(--bg);
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

.suggestion-item {
    --display: inline; /* Allows them to flow next to each other */
    padding: 6px 12px;     /* More padding for better touch targets */
    margin: 4px;
    cursor: pointer;
    white-space: nowrap;
    border-radius: 15px;   /* Rounded pill look */
    border: 1px solid #aaa;
    background: #fff;
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

span.tag-pill {
    padding: 6px 12px;
    margin: 4px;
	white-space: nowrap;

    border-radius: 15px;   /* Rounded pill look */
    border: 1px solid #aaa;
    background: #fff;
}
div#active-tags {
	line-height:35px;
}
span.tag-pill button {
	border:none;
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
	    <label>Title</label>
	    <input type="text" name="title" placeholder="Give your photo a title" oninput="updateStickyTitle(this.value)">

	    <label>Description (optional)</label>
	    <textarea name="comment" placeholder="optional longer description" rows="4"></textarea>

	    <label>Date Photo Taken</label>
	    <input type="date" name="imagetaken">

	    <label>Geographical Contexts (select multiple)</label>
	    <select name="contexts[]" id="contexts" multiple size=10></select>

	    <label>Primary Subject (optional)</label>
	    <input type="text" id="subject-input" list="subject-list" placeholder="Start typing...">
            <datalist id="subject-list"></datalist>
            <input type="hidden" name="subject_id" id="subject-id">

	    <label>Tags (optional)</label>
	    <div class="tag-input-container">
                <div id="active-tags"></div> <input type="text" id="tag-search" placeholder="Type to add tags...">
                <div id="suggestions" class="dropdown"></div>
            </div>

        <label>Special Flags (optional)</label>
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

        loadContexts();
        loadSubjects();
    });

    function resetForm(newId) {
        updatePreview(newId); //will store it in upload_id;

    	//we starting again!
    	mainForm.elements['title'].value = '';
    	mainForm.elements['comment'].value = '';
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

console.log(result);

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

            let html = results.map(tag => `<div class="suggestion-item">${tag}</div>`).join('');
            if (query.length > 2 && !normalizedResults.includes(query.toLowerCase()))
                html += `<div class="suggestion-item add-new-tag" data-tag="${query}">+ Add "${query}"</div>`;

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

//these with the existing format used by old submissioN

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

function validateForm(form) {

    const select = document.getElementById('contexts');
    const selected = Array.from(select.selectedOptions).map(o => o.value);

    if (selected.length === 0) {
        alert("Please select at least one Geographical Context");
        return false;
    }

        const input = document.getElementById('subject-input');
        const hiddenId = document.getElementById('subject-id');
        const options = document.querySelectorAll('#subject-list option');
        
        // Find if the typed value matches a valid tag
        const match = Array.from(options).find(o => o.value === input.value);
        
        if (match) {
            hiddenId.value = match.dataset.id;
            return true;
        } else {
            alert("Please select a subject from the provided list.");
            input.focus();
            return false;
        }


    saveRecent('submit.contexts', selected);
     //save subject saveRecent('submit.subjects', [input.value]);
     //save tags saveRecent('submit.tags', tags);
    return true;
}

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

</script>

</body>
</html>
