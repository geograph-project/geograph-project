<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vector Similarity Filtering Demo</title>
    <style>
        :root {
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: #dee2e6;
            --primary-color: #0d6efd;
            --text-color: #212529;
            --text-muted: #6c757d;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Controls Section */
        .controls-panel {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
            border: 1px solid var(--border-color);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            align-items: end;
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        .input-group label {
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        input[type="text"] {
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
        }

        .slider-group {
            display: flex;
            flex-direction: column;
        }

        .slider-header {
            display: flex;
            justify-content: space-between;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }

        input[type="range"] {
            width: 100%;
            cursor: pointer;
        }

        button {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        button:hover {
            opacity: 0.9;
        }

        button:disabled {
            background-color: var(--text-muted);
            cursor: not-allowed;
        }

        /* Status Bar */
        .status-bar {
            margin-bottom: 16px;
            font-size: 0.95rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Results Grid */
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }

        .image-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.15s ease-in-out;
        }

        .image-card:hover {
            transform: translateY(-2px);
        }

        .image-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background-color: #eee;
        }

        .card-body {
            padding: 12px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0 0 6px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-meta {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .badge {
            align-self: flex-start;
            margin-top: auto;
            background: #e9ecef;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            font-family: monospace;
        }

        .inverted-slider {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 4px;
            outline: none;
            /* 
               The Magic: 
               We start with gray (background) up to the current percentage, 
               and then transition to blue (active) for the remainder up to max.
            */
            background: linear-gradient(to right, #ccc 0%, #ccc var(--pct, 50%), #007bff var(--pct, 50%), #007bff 100%);
            margin-top:10px;
        }

        /* Styling the thumb (the draggable knob) */
	/* ... actully dont want to change this
        .inverted-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #007bff;
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 0 2px rgba(0,0,0,0.4);
        }

        .inverted-slider::-moz-range-thumb {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #007bff;
            cursor: pointer;
            border: 2px solid #fff;
            box-shadow: 0 0 2px rgba(0,0,0,0.4);
        }*/

    </style>
	<script src="/js/vector.class.js?<?php echo filemtime("../js/vector.class.js"); ?>"></script>
	<script src="/js/geograph-api-libs.js?<?php echo filemtime("../js/geograph-api-libs.js"); ?>"></script>
</head>
<body>

<div class="container">
    <div class="controls-panel">
        <form id="searchForm" class="form-grid">
            <!-- Primary Keyword Search Input -->
            <div class="input-group">
                <label for="keywordInput">1. Keyword Search (Initial Dataset)</label>
                <input type="text" id="keywordInput" value="Lake" placeholder="e.g., lake, castle, forest">
            </div>

            <!-- Vector Filter Input -->
            <div class="input-group">
	            <label for="filterInput">2. Similarity Filter</label>
                <input type="text" id="filterInput" value="Mountain" placeholder="e.g., Mountain Landscape">
            </div>

            <!-- Distance Threshold Slider -->
            <div class="slider-group">
                <div class="slider-header">
                    <span>Threshold</span>
                    <label style=" cursor: pointer;">( <input type="checkbox" id="invertCbx"> Invert)</label>
                    <span id="thresholdVal">0.80</span>
                </div>
                <input type="range" id="thresholdSlider" min="0" max="1" step="0.001" value="0.8">
                <span id="distMsg"></span>
            </div>



            <!-- Action Button -->
            <div>
                <button type="submit" id="searchBtn">Search &amp; Filter</button>
            </div>

            <div>
		<label><input type=checkbox id="recentCbx"> Recent First</label>
            </div>


        </form>
    </div>

    <div class="status-bar" id="statusMsg">Ready to search. Enter keywords above to load initial dataset.</div>

    <div class="results-grid" id="resultsGrid"></div>
</div>

<script>
    // Global Application State
    let allImages = [];
    let anchorVec = null;
    let isServerSideKNN = false;
    let datasetMinDist = null;
    let datasetMaxDist = null;
    const model = 'pe';

    // UI Elements
    const searchForm = document.getElementById('searchForm');
    const keywordInput = document.getElementById('keywordInput');
    const filterInput = document.getElementById('filterInput');
    const thresholdSlider = document.getElementById('thresholdSlider');
    const thresholdVal = document.getElementById('thresholdVal');
    const statusMsg = document.getElementById('statusMsg');
    const distMsg = document.getElementById('distMsg');
    const resultsGrid = document.getElementById('resultsGrid');
    const searchBtn = document.getElementById('searchBtn');
    const invertCbx = document.getElementById('invertCbx');
    const recentCbx = document.getElementById('recentCbx');

    // 1. Fetch Initial Keyword Results
    async function fetchResults(query) {
        const params = new URLSearchParams({
            match: query,
            select: 'id,hash,grid_reference,realname,title,image_vector,place',
            long: 1,
            utf: 1,
            limit: 100,
            model: model
        });
	if (recentCbx.checked)
		params.set('order','id desc');

        try {
            const response = await fetch(`/api-facetql-vector.php?${params.toString()}`);
            const data = await response.json();
            return data.rows || [];
        } catch (error) {
            console.error("Error fetching images:", error);
            return [];
        }
    }

    // 1b. Fetch Server-Side Pure Similarity Results (No keywords)
    async function fetchSimilarityResults(label) {
        const params = new URLSearchParams({
            label: label,
            select: 'id,hash,grid_reference,realname,title', // No image_vector requested (k is still provided regardless!)
            long: 1,
            utf: 1,
            limit: 100,
            model: model
        });
        if (recentCbx.checked)
            params.set('order','id desc');

        try {
            const response = await fetch(`/api-facetql-vector.php?${params.toString()}`);
            const data = await response.json();
            return data.rows || [];
        } catch (error) {
            console.error("Error fetching similarity images:", error);
            return [];
        }
    }

    // 2. Fetch Vector Embedding for Second Filter Label
    async function fetchLabelVector(label) {
        if (!label || !label.trim()) return null;
        const cleanLabel = label.trim();
        
        const params = new URLSearchParams({
            labels: cleanLabel,
            model: model
        });

        try {
            const response = await fetch(`/finder/label-vectors.json.php?${params.toString()}`);
            const result = await response.json();
            
            if (result && result[cleanLabel]) {
                // Decode and normalize vector array
                return new EmbeddingVector(result[cleanLabel]).normalize();
            }
        } catch (error) {
            console.error("Error fetching label vector:", error);
        }
        return null;
    }

    // 3. Process API Rows into State
    function readRows(rows) {
        allImages = []; // Reset previous dataset
        rows.forEach(image => {
            if (image.image_vector || image.k) {
                try {
                    const imageUrl = getGeographUrl(image.id, image.hash, 'med');
         		    const imgData = {
                        id: image.id,
                        hash: image.hash,
                        gridref: image.grid_reference,
                        title: escapeHTML(image.title) || 'Untitled',
                        realname: escapeHTML(image.realname) || 'Unknown',
                        thumb: imageUrl,
                        vector: null,
                        k: image.k !== undefined ? parseFloat(image.k) : null // Read distance direct from server
                    };
         		    if (image.image_vector)
	                    imgData.vector = new EmbeddingVector(image.image_vector).normalize();
                    allImages.push(imgData);
                } catch (e) {
                    console.error("Error parsing vector for image ID " + image.id, e);
                }
            }
        });
    }

    // 4. Render Images based on Threshold (Preserving Original Order)
    function renderImages() {
        resultsGrid.innerHTML = '';
        const threshold = parseFloat(thresholdSlider.value);
        let shownCount = 0;

			    const min = parseFloat(thresholdSlider.min)*0.95;
			    const max = parseFloat(thresholdSlider.max)*1.05;
			    const range = max - min;

        allImages.forEach(image => {
            let dist = null;
            let show = true;

            // Compute distance if a filter vector exists
            if (anchorVec) {
                dist = image.vector.distance(anchorVec);
            } else {
                dist = image.k;
            }

            if (dist !== null) {
                if (invertCbx.checked) {
	                if (dist < threshold) { show = false; }
       	        } else {
	                if (dist > threshold) { show = false; }
                }
            }

            if (show) {
                shownCount++;
                const card = document.createElement('div');
                card.className = 'image-card';

			let distBadge = ''; //`<div class="badge">No Filter Applied</div>`;

			if (dist !== null && range > 0) {
			    // Prevent division by zero if min and max happen to be identical
			    let  distPerc = (1 - ((dist - min) / range)) * 100;

			    // Clamp between 0 and 100 just in case floating point math wobbles
			    distPerc = Math.max(0, Math.min(100, distPerc));

			    distBadge = `<div class="badge">Dist: ${dist.toFixed(3)} (~${distPerc.toFixed(1)}%)</div>`;
			}

                card.innerHTML = `
                    <a href="/photo/${image.id}" title="${image.gridref} :: ${image.title} - click to view" target="_blank">
                        <img src="${image.thumb}" alt="Geograph image ${image.id}" loading="lazy">
                    </a>
                    <div class="card-body">
                        <h4 class="card-title" title="${image.title}">${image.title}</h4>
                        <div class="card-meta">By: ${image.realname}</div>
                        ${distBadge}
                    </div>
                `;
                resultsGrid.appendChild(card);
            }
        });

        // Update UI status
        if (allImages.length === 0) {
            statusMsg.textContent = "No results found.";
        } else if (anchorVec || isServerSideKNN) {
            const relationSymbol = invertCbx.checked ? '>' : '<';
            const filterTypeDesc = invertCbx.checked ? 'further than' : 'closer than';

            statusMsg.textContent = `Showing ${shownCount} of ${allImages.length} images (Filtered by distance ${relationSymbol} ${threshold.toFixed(3)}).`;
        } else {
            statusMsg.textContent = `Showing all ${allImages.length} images (No vector filter active).`;
        }
    }

    // Event Listener: Form Submission (Fetch Data & Apply Filter)
    searchForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const keyword = keywordInput.value.trim();
        const filterQuery = filterInput.value.trim() || keyword;

        if (!keyword && !filterQuery) return;

        searchBtn.disabled = true;
        searchBtn.textContent = "Loading...";
        statusMsg.textContent = "Fetching images and computing vectors...";

        // Reset distance bounds
        datasetMinDist = null;
        datasetMaxDist = null;
    	thresholdSlider.min = 0;
	    thresholdSlider.max = 1;

        try {
            let rows = [];

            if (filterQuery && !keyword) {
                // PURE KNN SIMILARITY (Server calculates distances, no client anchor vector required)
                isServerSideKNN = true;
                anchorVec = null;
                rows = await fetchSimilarityResults(filterQuery);
            } else {
                // HYBRID OR KEYWORD ONLY (Client-side distance calculation)
                isServerSideKNN = false;
                const [fetchedRows, vector] = await Promise.all([
                    fetchResults(keyword),
                    fetchLabelVector(filterQuery || keyword)
                ]);
                rows = fetchedRows;
                anchorVec = vector;
            }

            readRows(rows);

            // Calculate min/max distances across the newly loaded dataset if vector filtering is active
            if (allImages.length > 0 && (anchorVec || isServerSideKNN)) {
                let min = Infinity;
                let max = -Infinity;

                allImages.forEach(image => {
                    const d = isServerSideKNN ? image.k : (image.vector ? image.vector.distance(anchorVec) : null);
                    if (d < min) min = d;
                    if (d > max) max = d;
                });

                datasetMinDist = min;
                datasetMaxDist = max;

                const minStr = datasetMinDist !== null ? datasetMinDist.toFixed(4) : 'N/A';
                const maxStr = datasetMaxDist !== null ? datasetMaxDist.toFixed(4) : 'N/A';
                distMsg.innerHTML = `<span style="font-size: 0.85rem; margin-top: 4px; display: inline-block;">Range &mdash; Min: <strong>${minStr}</strong>, Max: <strong>${maxStr}</strong></span>`;
                let threshold = parseFloat(thresholdSlider.value);
                if (threshold > datasetMaxDist || threshold < datasetMinDist) {
                    thresholdSlider.value = (datasetMinDist+datasetMinDist+datasetMaxDist)/3;
                    thresholdVal.textContent = parseFloat(thresholdSlider.value).toFixed(3);

                    thresholdSlider.style.setProperty('--pct', '33%');

                }
                //think best to set this after, not sure if how setting value directly is affected by min/max
                thresholdSlider.min = datasetMinDist;
                thresholdSlider.max = datasetMaxDist;
                thresholdSlider.step = 0.001; //needs more granility now zoomed in. 
            }

            renderImages();
        } catch (err) {
            statusMsg.textContent = "An error occurred while fetching results.";
            console.error(err);
        } finally {
            searchBtn.disabled = false;
            searchBtn.textContent = "Search & Filter";
        }
    });

    // Event Listener: Live Slider Adjustment (No API calls needed!)
    thresholdSlider.addEventListener('input', (e) => {
        thresholdVal.textContent = parseFloat(e.target.value).toFixed(3);

	updateSlider(thresholdSlider); //update the variable for the inverted styling

        // Only re-render if we already have data loaded
        if (allImages.length > 0) {
            renderImages();
        }
    });

	invertCbx.addEventListener('change', () => {
            thresholdSlider.classList.toggle('inverted-slider',invertCbx.checked);
	    updateSlider(thresholdSlider);

	    if (allImages.length > 0) {
	        renderImages();
	    }
	});

        function updateSlider(slider) {
            // Calculate percentage based on min, max, and current value
            const min = slider.min || 0;
            const max = slider.max || 100;
            const pct = ((slider.value - min) / (max - min)) * 100;
            
            // Pass the percentage to the CSS variable
            slider.style.setProperty('--pct', pct + '%');
        }
       function escapeHTML(str) {
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        }


</script>

</body>
</html>
