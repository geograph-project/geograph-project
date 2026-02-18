<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Place Sample Tester</title>
    <style>
        :root { --primary: #2563eb; --bg: #f1f5f9; }
        :root { --primary: #2563eb; --bg: black; }
        body { font-family: sans-serif; background: var(--bg); padding: 0; margin:0; }

        .controls {
            background: #000066; color: white; padding: 20px; border-radius: 8px;
            display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .control-group { display: flex; flex-direction: column; gap: 5px; }
        .sort-item { display: inline-flex; align-items: center; gap: 5px; margin-right: 10px; }

	/* Gray out the label and change cursor when the checkbox inside it is disabled */
	.filter-item:has(input:disabled) {
	    color: #94a3b8; /* A muted gray */
	    cursor: not-allowed;
	    opacity: 0.6;
	}
	label:has(input:checked) {
            color:yellow;
        }


        #grid {
            display: grid; grid-template-columns: repeat(auto-fill, 224px);
            gap: 6px; justify-content: center;
        }

        .thumb-card {
            width: 224px; height: 224px; position: relative;
            background: #ddd; border-radius: 10px; overflow: hidden;
        }
        .thumb-card img { width: 100%; height: 100%; object-fit: cover; }

        /* Visual cue for backfilled items */
        .thumb-card.backfilled-- { opacity: 0.7; border: 2px dashed #94a3b8; }
        .thumb-card.backfilled::after {
            content: "*"; position: absolute; top: 5px; left: 5px;
            --background: rgba(255,255,255,0.8); font-size: 10px; padding: 2px 4px; border-radius: 2px;
        }

        .title-overlay {
            position: absolute; bottom: 0; right: 0;
            background: rgba(0, 0, 0, 0.7); color: white;
            padding: 2px 3px; font-size: 0.9em;

	    background-image: linear-gradient(rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.3));
	    font-weight: bold; color:black;
	    text-shadow: 0px 0px 3px #ffffff;
	    border-top-left-radius: 10px;
	    text-align:right;
        }

	.year-overlay {
	    position: absolute;
	    top: 0;
	    right: 0;
	    /* Deep navy with 0.85 alpha */
	    background: rgba(0, 0, 102, 0.55);
	    color: white;
	    padding: 2px 6px;
	    font-size: 0.8em;
	    font-weight: bold;
	    border-bottom-left-radius: 4px;
	}

        .stats { margin-bottom: 15px; font-weight: bold; color: #475569; }
    </style>
</head>
<body>

    <div class="controls">
        <div class="control-group">
            <label>Sample</label>
            <select id="fileSelector">
                <option value="">Select...</option>
                <option value="sample-tester.json.php?ddev=1" title="original selection based on 50k gazetter (misses part of center)">Birmingham (Course)</option>
                <option value="sample-tester.json.php?ddev=1&fine=1" title="Just the city core, using OS Open Names">Bham City Center</option>
                <option value="sample-tester.json.php?ddev=1&corrected=1" selected title="OS Open Names, but tweaked to prefer Center">Enhanced Selection</option>
                <option value="sample-tester.json.php?ddev=1&precise=1" title="Using Exact Boundary from OS Built Up Area data (using a slightly simplified polygon)">Exact Built Up Area</option>
                <option value="sample-tester.json.php?ddev=1&large=1" title="Whole of birmingham + Sandwell county just to include everyting (from 50k squares)">Birmingham Metro Area</option>
                <option value="sample-tester.json.php?ddev=1&curated=1">-- Mining Imagry Test</option>
            </select>
        </div>

        <div class="control-group">
            <label>Target Sample Size (1-75)</label>
            <input type="number" id="sampleSize" value="10" min="1" max="75">
        </div>

        <div class="control-group">
            <label>Priority Distance Filter (m)</label>
            <input type="number" id="distFilter" value="500">
        </div>

        <div class="control-group">
            <label>Content Filters <attr title="We prioritize images that meet these filters. If there aren't enough matches, we'll
intelligently 'backfill' the grid with the best available alternatives to ensure you
always have a complete sample.">*</attr></label>
<div>
            <label class="sort-item">
                <input type="checkbox" class=filter-cb" id="geoFilter" onclick="document.getElementById('aiOverride').disabled = !this.checked">
                Geograph only
            </label>
	    <label class="sort-item">
	        <input type="checkbox" class=filter-cb" id="aiOverride" disabled>
        	AI Override
	    </label>
	    <label class="-item">
	        <input type="checkbox" class=filter-cb" id="techFilter">
	        Quality
	    </label>
</div>
	</div>

        <div class="control-group">
            <label>Sort Criteria (select multiple)</label>
            <div>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="score desc"> Score</label>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="sequence asc" checked> Diversity</label>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="centroid_dist asc"> Distance</label>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="fused desc"> Fusion</label>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="v_bayesian desc"> Scenicness</label>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="v_bayesian asc"> Urban</label>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="aesthetic asc"> Grit</label>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="aesthetic desc"> Aesthetic</label>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="technical desc"> Technical</label>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="baysian desc"> Gallery</label>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="imagetaken desc"> Recent</label>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="imagetaken asc"> Historic</label>
		<label class="sort-item"><input type="checkbox" class="sort-cb" value="date_sequence asc"> Though the Ages</label>
                <label class="sort-item"><input type="checkbox" class="sort-cb" value="centroid_dist desc"> Surrounds</label>
                <label class="sort-item" style=color:gray><input type="checkbox" class="sort-cb" value="bearing_deg asc"> Bearing</label>
                <label class="sort-item" style=color:gray><input type="checkbox" class="sort-cb" value="sequence desc"> Random</label>
                <!--label class="sort-item" style=color:gray><input type="checkbox" class="sort-cb" value="sds desc"> Content</label-->
                <label class="sort-item" style=color:gray><input type="checkbox" class="sort-cb" value="technical asc"> Low</label>
            </div>
        </div>
    </div>

    <div id="stats" class="stats"></div>
    <div id="grid"></div>

<script>
    let rawData = [];

    const els = {
        file: document.getElementById('fileSelector'),
        grid: document.getElementById('grid'),
        size: document.getElementById('sampleSize'),
        dist: document.getElementById('distFilter'),
        geo: document.getElementById('geoFilter'),
	useai: document.getElementById('aiOverride'),
	techhigh: document.getElementById('techFilter'),
        stats: document.getElementById('stats')
    };

    // Listeners
    els.file.addEventListener('change', loadData);
    els.size.addEventListener('input', render);
    els.dist.addEventListener('input', render);
    els.geo.addEventListener('input', render);
    els.useai.addEventListener('input', render);
    els.techhigh.addEventListener('input', render);
    document.querySelectorAll('.sort-cb').forEach(cb => cb.addEventListener('change', render));

    async function loadData() {
        if (!els.file.value) return;
        try {
            els.stats.innerText = `LOADING! Please Wait`;

            const res = await fetch(els.file.value);
            rawData = await res.json();
	    rawData.forEach((item) => {
	            // Convert dates to timestamps for math (0 for invalid dates)
			//ie if want to sort by the real date, not a numberic sequence
        	    const date = new Date(item.imagetaken.replace(/-00/g, '-01'));
	            item._ts = (item.imagetaken.startsWith('0000') || isNaN(date)) ? 0 : date.getTime();
	    });
            render();
        } catch (e) { alert("Failed to load JSON. Ensure you are running on a local server.");  console.log(e); }
    }

    function calculateRRF(items, activeSortConfigs) {
        if (items.length === 0) return [];
        if (activeSortConfigs.length === 0) {
            return [...items].sort((a, b) => a.sequence - b.sequence);
        }

        const k = 60;
        const rankMaps = activeSortConfigs.map(cfg => {
		const sorted = [...items].sort((a, b) => {
		    const valA = a[cfg.field] || "";
		    const valB = b[cfg.field] || "";
		    if (cfg.dir === 'asc') {
		        return valA < valB ? -1 : valA > valB ? 1 : 0;
		    } else {
		        return valA > valB ? -1 : valA < valB ? 1 : 0;
		    }
		});

            const map = new Map();
            sorted.forEach((item, idx) => map.set(item.gridimage_id, idx + 1));
            return map;
        });

        return items.map(item => {
            let score = 0;
            rankMaps.forEach(map => score += 1 / (k + map.get(item.gridimage_id)));
            return { ...item, _rrf: score };
        }).sort((a, b) => b._rrf - a._rrf);
    }

    function render() {
        if (!rawData.length) return;

        const limit = Math.min(Math.max(parseInt(els.size.value) || 1, 1), 75);
        const distThreshold = parseInt(els.dist.value) || 99999;
        const onlyGeo = els.geo.checked;
        const useAI = els.useai.checked;
        const techHigh = els.techhigh.checked;

        const activeSorts = Array.from(document.querySelectorAll('.sort-cb:checked')).map(cb => {
            const [field, dir] = cb.value.split(' ');
            return { field, dir };
        });

        const isHistoricSort = activeSorts.some(s => s.field === 'imagetaken' && s.dir === 'asc');

        // 1. Split data into "In-Bounds" and "Out-of-Bounds"
        const inBounds = []; //const just means the it always array, the elements an change!
        const outOfBounds = [];

        rawData.forEach(originalItem => {
            // Clone the item so we don't permanently mess with the rawData
            let item = { ...originalItem };
            const aiClasses = (item.ai_types || "").split(',').map(t => t.trim());

            // APPLY EXPERIMENTAL AI LOGIC
            if (useAI) {
                if (aiClasses.includes('Inside') || aiClasses.includes('Close Look') || aiClasses.includes('Aerial')) {
                    item.moderation_status = 'accepted';
                }
                // The 'Undo' experiment:
                if (aiClasses.includes('Geograph') && !aiClasses.includes('Cross Far')) {
                    item.moderation_status = 'geograph';
                }
            }

            const matchesDist = item.centroid_dist < distThreshold;
            const matchesGeo = !onlyGeo || (item.moderation_status === 'geograph');
            const passesTech = !techHigh || (item.technical >= 5);

            // 2. Date Validation for Historic Sort
            // If Historic sort is active, images with '0000-00-00' are disqualified from inBounds
            const hasValidDate = item.imagetaken && item.imagetaken !== '0000-00-00';
            const failDateCheck = isHistoricSort && !hasValidDate;

            // Priority bucket vs Backfill bucket
            if (matchesDist && matchesGeo && !failDateCheck && passesTech) {
                inBounds.push(item);
            } else {
                outOfBounds.push(item);
            }
        });

        // 2. Rank both sets using RRF independently
        const rankedIn = calculateRRF(inBounds, activeSorts);
        const rankedOut = calculateRRF(outOfBounds, activeSorts);

        // 3. Combine: Take as many as possible from rankedIn, then backfill from rankedOut
        const finalSelection = rankedIn.slice(0, limit);
        const needed = limit - finalSelection.length;

        let displayList = [...finalSelection];
        if (needed > 0) {
            const backfill = rankedOut.slice(0, needed).map(item => ({ ...item, isBackfill: true }));
            displayList = displayList.concat(backfill);
        }

        //resort diveristy
        const isdiversitySort = activeSorts.some(s => s.field === 'date_sequence' && s.dir === 'asc');
	if (isdiversitySort) {
	    // This ensures that even if RRF picked them for diversity,
	    // they appear in a "progression through time" on the page.
	    displayList.sort((a, b) => {
	        // Keep 0000 at the end, otherwise sort by timestamp
	        if (a._ts === 0) return 1;
	        if (b._ts === 0) return -1;
	        return a._ts - b._ts;
	    });
	}

        // 4. Render UI
        els.stats.innerText = `Filtered: ${rankedIn.length} | Backfilled: ${displayList.length - rankedIn.slice(0, limit).length} | Total Shown: ${displayList.length}`;

        //todo, for now hardcoded, but will be dynamic!
	const placename = 'Birmingham';
	/* would need logic to deal with bilingual, etc, would be done upfront...
	// Example: North Tolsta or Gaelic equivalent
	// We use (?: ) to group without "capturing"
	const placename = '(?:North Tolsta)|(?:Tolastadh bho Thuath)';

	// Example: Evanton or Baile Eoghain / Baile-Eoghain
	const placename = '(?:Evanton)|(?:Baile[ -]Eoghain)';
	*/

		    // 1. Directions to catch "North Birmingham" or "East Birmingham"
		    const directions = '(?:north|south|east|west|greater|central)?\\s*';

		    // 2. Separators: matches commas, dashes, or the word "in"
		    // We EXCLUDE "of" to protect "University of Birmingham"
		    const separators = ':\\s*|,\\s*|\\s-\\s*|\\sin\\s+|\\snear\\s+';

	els.grid.innerHTML = displayList.map(item => {
	    // Extract year, check if it's valid
	    const year = item.imagetaken ? item.imagetaken.substring(0, 4) : '0000';
	    const yearDisplay = (year !== '0000')
	        ? `<div class="year-overlay" title="${item.imagetakenString}">${year}</div>`
	        : '';

	    let displayTitle = item.title;

            if (!displayTitle) {
		displayTitle = "unknown"; //shouldnt happen, but in case JSON had null due to encoding error

	    } else if (placename || year) {
		    // 3. The Pattern: (Separator) + (Optional Direction) + (Place or Year)
		    const cleanPattern = new RegExp(`(?:${separators})(?:${directions})(?:${placename}|${year})+\.?$`, 'gi');

		    displayTitle = displayTitle.replace(cleanPattern, '').trim();

		    // 4. Special "Dangling" cases: "Title 1985" or "Title in 1992"
		    if (year !== '0000') {
		        displayTitle = displayTitle.replace(new RegExp(`\\s+(in\\s+)?${year}$`), '').trim();
		    }

                    // If we (accidently) stripped everything, revert to the original title
		    if (displayTitle.length === 0) displayTitle = item.title;
	    }

	    return `
	        <a href="https://www.geograph.org.uk/photo/${item.gridimage_id}" class="thumb-card ${item.isBackfill ? 'backfilled' : ''}">
	            <img src="${item.thumbnail}" alt="${item.title}" loading="lazy" title=" by ${item.realname}">
	            ${yearDisplay}
	            <div class="title-overlay" title="${item.title} by ${item.realname}">${displayTitle}</div>
	        </a>
	    `;
	}).join('');

    }
    document.addEventListener('DOMContentLoaded', loadData);

function triggerChaos() {
    const checkboxes = document.querySelectorAll('.sort-cb, .filter-cb');
    
    checkboxes.forEach(cb => {
        // Give it a 30% chance to be checked to avoid "over-cluttering" the logic
        cb.checked = Math.random() < 0.3;
    });

    // Trigger the render function to see the result immediately
    render();
}

</script>
<p style=color:silver><button onclick="triggerChaos()" class="chaos-btn">Shuffle</button> - tick a random selection of filters and sort(s)</p>

</body>
</html>
