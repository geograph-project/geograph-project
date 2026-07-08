<?php

require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm('basic');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Tag & Snippet Test Console</title>

    <script src="<?php echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>
    <link rel="stylesheet" href="unified-tag-test.css?<? echo filemtime('unified-tag-test.css'); ?>">

</head>
<body>

    <h2>Unified Tag & Snippet Test Console</h2>

    <p style="color:gray">This page tests <b>4 different backend engines</b>, handling both context-driven auto-suggestions and reactive "as-you-type" queries. While they all share 
    share behavioral overlaps, they are powered by separate specialized implementations (each with their own combination of available filters/options).</p>

    <div class="workspace">
        <div class="panel">
            <div class="form-group">
                <select id="endpointToggle">
                    <option value="snippet" selected>Shared Descriptions / Snippets (/snippets.json.php)</option>
                    <option value="tag">Tags (/tags/tags.json.php)</option>
                </select>
            </div>

            <div id="tagSrcGroup" class="form-group conditional" style="display: none;">
                <label for="tagSrc">Semantic Filter</label>
                <select id="tagSrc">
                    <option value="">(None)</option>
                    <option value="tag">Tag</option>
                    <option value="subject">Subject</option>
                    <option value="type">Type</option>
                    <option value="top">Top</option>
                    <option value="bucket">Bucket</option>
                    <option value="named">Named</option>
                </select>
            </div>

            <div id="snippetModeGroup" class="form-group conditional">
                <label for="snippetMode">Snippet Search Mode (as type search only)</label>
                <select id="snippetMode">
                    <option value="ranked">Ranked Match (Default)</option>
                    <option value="prefix">Include Prefixes (Test 1)</option>
                    <option value="nearby">Used Nearby Only</option>
                    <option value="nearbyplus">Used Nearby Priority (Test 2)</option>
                    <option value="prefixplus" selected>Used Nearby Priority + Prefix (NEW default)</option>
                    <option value="alpha">Alphabetical sorting</option>
                </select>
            </div>

            <label>Algorithmic Options (may not apply in all modes)</label>
            <div class="options-grid">
                <label class="checkbox-label for-tag for-snippet"><input type="checkbox" id="opt_vector" checked> Semantic</label>
                <label class="checkbox-label for-tag"><input type="checkbox" id="opt_filter" checked> Filter</label>
                <label class="checkbox-label for-tag"><input type="checkbox" id="opt_rerank"> Rerank</label>
                <label class="checkbox-label for-tag for-snippet"><input type="checkbox" id="opt_idf"> IDF</label>
                <label class="checkbox-label for-tag for-snippet"><input type="checkbox" id="opt_nearby"> Used-Nearby</label>
                <label class="checkbox-label for-snippet"><input type="checkbox" id="opt_dist"> GeoDist</label>
                <label class="checkbox-label for-snippet hidden"><input type="checkbox" id="opt_legacy"> No Boost</label>
                <label class="checkbox-label for-snippet"><input type="checkbox" id="opt_rrf"> RRF</label>
                <label class="checkbox-label for-snippet"><input type="checkbox" id="opt_arf"> aRRF</label>
            </div>

            <div class="form-group" style="background: #ebf8ff; padding: 4px; border-radius: 4px; border: 1px solid #bee3f8; margin-top:16px">
		<div style="float:right"><button id="prevBtn">&lt;</button> <button id="nextBtn">&gt;</button></div>
                <label for="recentSubmissions">Recent Submissions Loader</label>
                <select id="recentSubmissions">
                    <option value="">Select a recent submission to preview</option>
                </select>
            </div>

            <div class="form-group">
                <label for="image_id">Image ID</label>
                <input type="text" id="image_id" placeholder="e.g. 123456">
            </div>

            <div class="form-group">
                <label for="grid_reference">Grid Reference</label>
                <input type="text" id="grid_reference" placeholder="e.g. TQ 396 400">
            </div>

            <div class="form-group">
                <label for="title">Title Context</label>
                <input type="text" id="title" placeholder="Contextual title...">
            </div>

            <div class="form-group">
                <label for="description">Description Context</label>
                <textarea id="description" placeholder="Contextual descriptive body text..."></textarea>
            </div>

        </div>

        <div class="panel">
            <div class="search-container">
                <label for="queryInput">As-You-Type Interactive Query</label>
                <input type="text" id="queryInput" placeholder="Type search terms here to execute targeted search matches..." autocomplete="off">
            </div>

            <div id="suggestionsSection">
                <div class="results-section-header">Auto-Suggestions (Based on Form Context)</div>
                <div id="suggestionsWrapper" class="results-list">
                    <div class="status-msg">Fill out form data above to generate contextual auto-suggestions.</div>
                </div>
            </div>

            <div class="results-section-header hidden">As-You-Type Matches</div>
            <div id="asYouTypeWrapper" class="results-list">
                <div class="status-msg">Type query tokens above to trigger targeted engine matches.</div>
            </div>
        </div>

    </div>

    <script>
        // --- CORE APPLICATION STATE ENGINE ---
        const CONFIG = {
            endpoints: {
                tag: '/tags/tags.json.php',
                snippet: '/snippets.json.php'
            }
        };

        // UI Element Registries
        const el = {
            endpointToggle: document.getElementById('endpointToggle'),
            tagSrcGroup: document.getElementById('tagSrcGroup'),
            snippetModeGroup: document.getElementById('snippetModeGroup'),
            tagSrc: document.getElementById('tagSrc'),
            snippetMode: document.getElementById('snippetMode'),
            
            recentSubmissions: document.getElementById('recentSubmissions'),
            imageId: document.getElementById('image_id'),
            gridRef: document.getElementById('grid_reference'),
            title: document.getElementById('title'),
            description: document.getElementById('description'),
            
            optVector: document.getElementById('opt_vector'),
            optFilter: document.getElementById('opt_filter'),
            optNearby: document.getElementById('opt_nearby'),
            optRerank: document.getElementById('opt_rerank'),
            optIdf: document.getElementById('opt_idf'),
            optLegacy: document.getElementById('opt_legacy'),
            optRrf: document.getElementById('opt_rrf'),
            optArf: document.getElementById('opt_arf'),
            optDist: document.getElementById('opt_dist'),
            
            queryInput: document.getElementById('queryInput'),
            suggestionsWrapper: document.getElementById('suggestionsWrapper'),
            suggestionsSection: document.getElementById('suggestionsSection'),
            asYouTypeWrapper: document.getElementById('asYouTypeWrapper')
        };

        let debounceTimer = null;
        window.user_id = 42; // System context fallback value

        // --- SUBMISSION SOURCE CONTEXT INGESTION ---
        async function initRecentSubmissions() {
            try {
                const response = await fetch('/app/submissions-dev.json.php?images=100');
                if (!response.ok) throw new Error('API downstream fault');
                const submissions = await response.json();
                
                submissions.forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub.gridimage_id || '';

                    let sids = [];
                    if (sub.snippets)
                        sids = sub.snippets.split(/,/);

                    option.textContent = `${sids.length?`[${sids.length}] `:''}${sub.title || 'Untitled'} (${sub.submitted || 'Date Unknown'})`;

                    // Native key/value dataset serialization
                    option.dataset.title = (sub.title || '');
                    option.dataset.description = sub.comment || '';
                    option.dataset.gridref = sub.grid_reference || '';
                    option.dataset.snippet_ids = sub.sids;
                    el.recentSubmissions.appendChild(option);
                });
            } catch (err) {
                console.warn('Unable to load contextual submissions library:', err);
                el.recentSubmissions.innerHTML = '<option value="">Failed to auto-load background submissions</option>';
            }
        }

        el.recentSubmissions.addEventListener('change', (e) => {
            const selected = e.target.options[e.target.selectedIndex];
            if (!selected.value) return;

            el.imageId.value = selected.value;
            el.title.value = selected.dataset.title;
            el.description.value = selected.dataset.description;
            el.gridRef.value = selected.dataset.gridref;

            triggerSystemEvaluation();
        });

// Helper function to handle the cycling logic
function cycleSelect(selectEl, direction) {
    const options = selectEl.options;
    let newIndex = selectEl.selectedIndex;

    // Loop to find the next valid option (safeguard against infinite loops)
    for (let i = 0; i < options.length; i++) {
        // Move index forward or backward, wrapping around the ends
        newIndex = (newIndex + direction + options.length) % options.length;

        // Skip if the option has an empty value (e.g., placeholder)
        if (options[newIndex].value !== "") {
            selectEl.selectedIndex = newIndex;
            
            // Trigger the existing 'change' event listener
            selectEl.dispatchEvent(new Event('change'));
            break;
        }
    }
}

document.getElementById('prevBtn').addEventListener('click', () => {
    cycleSelect(el.recentSubmissions, -1);
});

document.getElementById('nextBtn').addEventListener('click', () => {
    cycleSelect(el.recentSubmissions, 1);
});


/////////////////////////////////////////////////////////////////

        // --- API URL ROUTER FACTORIES ---
        function assembleTagParameters(queryValue, isAutoSuggest = false) {
            const params = new URLSearchParams();

        //1. TAG Suggestions
            if (isAutoSuggest) {
                const combinedContext = `${el.title.value} ${el.description.value}`.replace(/\s+/g, ' ').trim();
                params.append('q', combinedContext);

                if (el.optVector.checked) {
                    params.append('vector', '1');
                } else {
                    params.append('idf', '1');
                }

        //2. TAG Search
            } else {
                params.append('q', queryValue);

                if (el.optIdf.checked) params.append('idf', '1'); //can technically enable idf mode explicity for as-type!

                if (el.optVector.checked) {
                    params.append('vector', '1');
                    if (el.tagSrc.value) params.append('src', el.tagSrc.value);
                } else {
                    //we dont currently support most general modes, but should add nearby (as a HARD filter)
                    if (el.optNearby.checked && el.gridRef.value.trim()) {
                        params.append('mode', 'nearby');
                        params.append('gr', el.gridRef.value.trim());
                    }
                }
            }

            if (el.optFilter.checked) params.append('filter', '1');
            if (el.optRerank.checked) params.append('rerank', '1');

            params.append('limit', '60');
            return params;
        }

/////////////////////////////////////////////////////////////////

        function assembleSnippetParameters(queryValue, isAutoSuggest = false) {
            const params = new URLSearchParams();


        //3. Snippet Suggestions
            if (isAutoSuggest) {
                const combinedContext = `${el.title.value} ${el.description.value}`.replace(/\s+/g, ' ').trim();
                params.append('term', combinedContext);

                if (el.optVector.checked) {
                    params.append('vector', '1');
                } else if (el.optNearby.checked && el.gridRef.value.trim()) {
                    params.append('mode', 'nearby');
                    params.set('term', el.gridRef.value.trim());
                } else {
                    params.append('idf', '1');
                }

                if (el.optLegacy.checked) params.append('legacy', '1');

        //4. Snippet Search
            } else {
                if (el.optIdf.checked) params.append('idf', '1'); //can technically enable idf mode explicity for as-type!

                params.append('term', queryValue);
                params.append('mode', el.snippetMode.value || 'ranked');

                if (el.optVector.checked) params.append('vector', '1');
            }

            if (el.gridRef.value.trim()) {
                params.append('gr', el.gridRef.value.trim());
            }
            if (el.imageId.value.trim()) {
                //we can't send 'gridimage_id' as that just lists tags for that id
                    //but with time, we hope to be able to use ID to lookup image for semantic context but possibly by adding [id:__] to samantic query
                //params.append('image_id', el.imageId.value.trim());
            }

            return params;
        }

/////////////////////////////////////////////////////////////////

        // --- CENTRAL API DISPATCHER ---
        async function executeSearchDispatch(isAutoSuggest, queryValue, targetContainer) {
            const targetEngine = el.endpointToggle.value;
            let params, url;

            if (targetEngine === 'tag') {
                if (isAutoSuggest && !el.title.value.trim() && !el.description.value.trim()) {
                    targetContainer.innerHTML = '<div class="status-msg">Provide Title/Description context data to run Auto-Suggestions.</div>';
                    return;
                }
                params = assembleTagParameters(queryValue, isAutoSuggest);
                url = `${CONFIG.endpoints.tag}?${params.toString()}`;
            } else {
                if (isAutoSuggest && !el.title.value.trim() && !el.description.value.trim()) {
                    targetContainer.innerHTML = '<div class="status-msg">Provide Title/Description context data to run Auto-Suggestions.</div>';
                    return;
                }
                params = assembleSnippetParameters(queryValue, isAutoSuggest);
                url = `${CONFIG.endpoints.snippet}?${params.toString()}`;
            }

            targetContainer.innerHTML = '<div class="status-msg">Querying Engine Endpoint...</div>';

            try {
                let data;

                //for now harcoded to snippet!
                if (el.optRrf.checked && isAutoSuggest && targetEngine === 'snippet') {

                    //need to unroll this so only add the required options. 
                    const combinedContext = `${el.title.value} ${el.description.value}`.replace(/\s+/g, ' ').trim();
                    const params = new URLSearchParams();
                    params.append('term', combinedContext);

                    // Define your placeholder endpoint URLs
                    let urls = [];
                    if (el.optVector.checked)
                        urls.push(`${CONFIG.endpoints.snippet}?${params.toString()}&vector=1`);
                    if (el.optIdf.checked)
                        urls.push(`${CONFIG.endpoints.snippet}?${params.toString()}&idf=1`);
                    if (el.optNearby.checked && el.gridRef.value.trim())
                        urls.push(`${CONFIG.endpoints.snippet}?mode=nearby&q=${encodeURIComponent(el.gridRef.value)}`);

                    // Guard clause: if no endpoints are selected, handle gracefully
                    if (urls.length === 0) {
                        data = [];
                    } else {
                        // 1. Dispatch all fetches concurrently
                        const responses = await Promise.all(urls.map(url => fetch(url)));

                        // 2. Validate all HTTP responses
                        responses.forEach((res, index) => {
                            if (!res.ok) throw new Error(`HTTP Error on Stream ${index + 1}: ${res.status}`);
                        });

                        // 3. Parse all JSON payloads concurrently
                        const allLists = await Promise.all(responses.map(res => res.json()));

                        // 3.5. Add another virtual Distance sorted list!
                        let geoCoordinates = null;
                        if (el.gridRef.value && el.gridRef.value.match(/([A-Z]{1,2})\s*(\d{2,})\s*(\d{2,})/i)) {
                            geoCoordinates = GT_WGS84.parseGridRef(el.gridRef.value);
                        }

                        if (el.optDist.checked && geoCoordinates && geoCoordinates.status === 'OK') {
                            const virtualMap = new Map();

                            // 1. Gather all unique items with valid coordinates across all fetched streams
                            allLists.forEach(list => {
                                if (!Array.isArray(list)) return;
                                list.forEach(row => {
                                    const itemId = row.snippet_id || row.id;
                                    if (!itemId || virtualMap.has(itemId)) return;

                                    const rowLat = parseFloat(row.wgs84_lat);
                                    const rowLon = parseFloat(row.wgs84_long);
                                    if (!isNaN(rowLat) && !isNaN(rowLon) && rowLat > 0) {
                                        // Clone the row to prevent mutating the original objects by reference
                                        const clonedRow = { ...row };

                                        clonedRow.geodist = calculateHaversineKM(geoCoordinates.latitude, geoCoordinates.longitude, radToDeg(rowLat), radToDeg(rowLon) );
                                        virtualMap.set(itemId, clonedRow);
                                    }
                                });
                            });

                            // 2. Convert the Map values to an array and sort ascending (closest first = highest rank)
                            const distanceSortedList = Array.from(virtualMap.values())
                                .sort((a, b) => a.geodist - b.geodist);

                            // 3. Push this newly ranked list into your collection for RRF processing
                            if (distanceSortedList.length > 0) {
                                allLists.push(distanceSortedList);
                            }
                        }

console.log("Length: ", allLists.length,  allLists);

                        // 4. Spread the array of arrays into your existing RRF function
			if (el.optArf.checked) {
				//Using Average Reciprocal Rank (ARR) is typically the most robust solution for non-overlapping lists because it isolates performance to the streams where the data actually exists.
                        	data = arrfCompose(...allLists);
			} else {
                        	data = rrfCompose(...allLists);
			}
                    }

                } else {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error(`HTTP System Error Code: ${response.status}`);
                    data = await response.json();
                }

                renderEnginePayload(data, targetEngine, targetContainer, isAutoSuggest);
            } catch (err) {
                targetContainer.innerHTML = `<div class="error-msg">Execution Fault: ${err.message}</div>`;
            }
        }

/////////////////////////////////////////////////////////////////

        // --- DOM RENDER ENGINE ---
        function renderEnginePayload(items, mode, container, isAutoSuggest) {
            container.innerHTML = '';

            if (!items || items.length === 0) {
                container.innerHTML = '<div class="status-msg">No structured records returned from search engine matrix.</div>';
                return;
            }

            // Client-side geospatial boundary computations for Snippet Engines
            let geoCoordinates = null;
            if (mode === 'snippet' && el.gridRef.value.trim()) {
                const match = el.gridRef.value.match(/([A-Z]{1,2})\s*(\d{2,})\s*(\d{2,})/i);
                if (match) {
                    geoCoordinates = GT_WGS84.parseGridRef(el.gridRef.value);
                }
            }

            items.forEach(row => {
                const rowEl = document.createElement('div');
                rowEl.className = 'result-row';

                const mainSection = document.createElement('div');
                mainSection.className = 'result-main';

                const metricsSection = document.createElement('div');
                metricsSection.className = 'result-metrics';

                if (mode === 'tag') {
                    // Tag Serialization UI Layout Structure
                    const prefix = row.prefix ? `<strong>${escapeHTML(row.prefix)}:</strong> ` : '';
                    const value = escapeHTML(row.tag || row.title || '');
                    
                    mainSection.innerHTML = `<div class="result-title">${prefix}${value}</div>`;
                    
                    if (row.src) {
                        //mainSection.innerHTML += `<div class="result-meta">Source Provider: ${escapeHTML(row.src)}</div>`;
                    }

                    if (typeof row.distance === 'number') {
                        metricsSection.textContent = ((1-row.distance)*100).toFixed(4)+"%";
                    }

                } else if (mode === 'snippet') {
                    // Snippet Serialization UI Layout Structure
                    const titleStr = escapeHTML(row.title || '');
                    const previewUrl = `https://www.geograph.org.uk/snippet/${row.snippet_id || row.id || ''}`;
                    const eyeIcon = `&#x1f441;`;
                    
                    mainSection.innerHTML = `
                        <div class="result-title">
                            ${titleStr} 
                            <a href="${previewUrl}" target="_blank" class="preview-link" title="Preview">${eyeIcon}</a>
                        </div>
                    `;

                    let metaString = '';
                    if (row.user_id && row.user_id != window.user_id && row.realname) {
                        metaString += `by ${escapeHTML(row.realname)} `;
                    }
                    mainSection.innerHTML += `<div class="result-meta">${metaString}</div>`;

                    // Handle geospatial distance variations across indices
                    if (isAutoSuggest && geoCoordinates && geoCoordinates.status === 'OK') {
                        const rowLat = parseFloat(row.wgs84_lat);
                        const rowLon = parseFloat(row.wgs84_long);
                        
                        if (rowLat && rowLon) {
                            const distanceKM = calculateHaversineKM(geoCoordinates.latitude, geoCoordinates.longitude, radToDeg(rowLat), radToDeg(rowLon));
                            metricsSection.innerHTML = `<div>${distanceKM.toFixed(2)} km</div>`;
                            
                            if (distanceKM > 20) {
                                rowEl.classList.add('long-distance');
                            }
                        }
                    }
                    
                    if (typeof row.distance === 'number') {
                        metricsSection.innerHTML += `<div>${((1-row.distance)*100).toFixed(1)}%</div>`;
                    }
                }

                rowEl.appendChild(mainSection);
                rowEl.appendChild(metricsSection);
                container.appendChild(rowEl);
            });
        }


///////////////////////////////////////////////////////////////////

/**
 * Combines multiple result lists using Reciprocal Rank Fusion (RRF)
 * @param {...Array} lists - An arbitrary number of item arrays to fuse
 * @returns {Array} - The single, sorted, fused array of items
 */
function rrfCompose(...lists) {
    const k = 60; // Standard RRF constant parameter
    const itemMap = new Map();

    // Iterate through each list passed to the function
    lists.forEach((list) => {
        if (!Array.isArray(list)) return;

        list.forEach((item, index) => {
            // Safely identify the item using either id property from your UI block
            const itemId = item.snippet_id || item.id;
            if (!itemId) return; // Skip if it doesn't have an ID

            // 1-based rank calculation
            const rank = index + 1;
            const scoreContribution = 1 / (k + rank);

            if (itemMap.has(itemId)) {
                // Item seen in a previous list; accumulate score
                const existing = itemMap.get(itemId);
                existing.rrfScore += scoreContribution;
                // Merge properties just in case metadata differs slightly
                existing.item = { ...existing.item, ...item };
            } else {
                // New item found
                itemMap.set(itemId, {
                    rrfScore: scoreContribution,
                    item: { ...item }
                });
            }
        });
    });

    // Convert the map to an array, sort by highest RRF score, and map back to the original item format
    return Array.from(itemMap.values())
        .sort((a, b) => b.rrfScore - a.rrfScore)
        .map(entry => entry.item);
}

function arrfCompose(...lists) {
    const k = 60; 
    const itemMap = new Map();

    // 1. Accumulate scores and track appearances
    lists.forEach((list) => {
        if (!Array.isArray(list)) return;

        list.forEach((item, index) => {
            const itemId = item.snippet_id || item.id;
            if (!itemId) return;

            const rank = index + 1;
            const scoreContribution = 1 / (k + rank);

            if (itemMap.has(itemId)) {
                const entry = itemMap.get(itemId);
                entry.rrfScoreSum += scoreContribution;
                entry.appearanceCount += 1; // Track how many lists it appeared in
                entry.item = { ...entry.item, ...item };
            } else {
                itemMap.set(itemId, {
                    rrfScoreSum: scoreContribution,
                    appearanceCount: 1,
                    item: { ...item }
                });
            }
        });
    });

    // 2. Convert to array and compute the Average Reciprocal Rank
    return Array.from(itemMap.values())
        .map(entry => {
            return {
                // Divide the accumulated score by the number of lists it actually appeared in
                finalScore: entry.rrfScoreSum / entry.appearanceCount,
                item: entry.item
            };
        })
        // Sort by the normalized average score descending
        .sort((a, b) => b.finalScore - a.finalScore)
        .map(entry => entry.item);
}

///////////////////////////////////////////////////////////////////

        // --- CONTROLLER EVENT ROUTERS ---
function triggerSystemEvaluation() {
    const query = el.queryInput.value.trim();

    if (query.length >= 1) {
        // Active typing state: Hide context auto-suggestions completely
        document.getElementById('suggestionsSection').classList.add('hidden');
        
        executeSearchDispatch(false, query, el.asYouTypeWrapper);
    } else {
        // Empty query state: Bring back context auto-suggestions and refresh them
        document.getElementById('suggestionsSection').classList.remove('hidden');
        el.asYouTypeWrapper.innerHTML = '<div class="status-msg">Type query tokens above to trigger targeted engine matches.</div>';
        
        executeSearchDispatch(true, null, el.suggestionsWrapper);
    }
}

        // Input monitoring across inputs
        [el.imageId, el.gridRef, el.title, el.description, el.tagSrc, el.snippetMode,
         el.optVector, el.optFilter, el.optNearby, el.optDist, el.optRerank, el.optIdf, el.optRrf, el.optArf, el.optLegacy].forEach(input => {
            input.addEventListener('change', triggerSystemEvaluation);
            if(input.type === 'text' || input.tagName === 'TEXTAREA') {
                input.addEventListener('input', () => {
                    if (debounceTimer) clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(triggerSystemEvaluation, 300);
                });
            }
        });

        // Dynamic Sub-interface Mode View State Controller
        el.endpointToggle.addEventListener('change', (e) => {

            // Set a data attribute on the body to drive the CSS rules natively
            document.body.setAttribute('data-active-engine', e.target.value);

            if (e.target.value === 'tag') {
                el.tagSrcGroup.style.display = 'block';
                el.snippetModeGroup.style.display = 'none';
            } else {
                el.tagSrcGroup.style.display = 'none';
                el.snippetModeGroup.style.display = 'block';
            }
            triggerSystemEvaluation();
        });

        // Dedicated As-You-Type input field handler
        el.queryInput.addEventListener('input', () => {
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                triggerSystemEvaluation();
            }, 450);
        });

        // --- MATH & SECURITY HELPERS ---
        function escapeHTML(str) {
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        }
        function radToDeg(rad) { return rad * (180 / Math.PI); }
        
        function calculateHaversineKM(lat1, lon1, lat2, lon2) {
            const R = 6371; 
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = 
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        // System Kickstart Initialization 
        initRecentSubmissions();
        document.body.setAttribute('data-active-engine', 'snippet');
    </script>
</body>
</html>
