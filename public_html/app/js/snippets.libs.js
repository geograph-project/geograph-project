
    const snippetInput = document.getElementById('snippet-search');
    const suggestionsSnippets = document.getElementById('snippet-suggestions');
    const activeSnippetContainer = document.getElementById('active-snippets');
    let selectedSnippets = {}; // Use a Object to store titles

//    let debounceTimer = null;
    snippetInput.addEventListener('input', async (e) => {
        let query = e.target.value;

        if (debounceTimer) clearTimeout(debounceTimer);

    	let extra = '';
    	if (query == '.') {
    		//this is just intended as messy protottype!
    		snippetInput.value = '';
    		extra = "&idf=2"; //new suggestions system
    		var title = document.getElementById('localTitle').value;
    		var description = document.getElementById('localDesc').value;
    		query = `${title} ${description}`.replace(/\s+/g, ' ').trim();

        } else if (query.length < 1) { suggestionsSnippets.innerHTML = ''; return; }

        debounceTimer = setTimeout(async function() {
            let html = '';
            let normalizedResults = [];

            let conv;
            let mode = 'prefix';
    	    if (document.getElementById('grid_reference')) {
                const gridRef = document.getElementById('grid_reference').value;
                const match = gridRef.match(/([A-Z]{1,2})\s*(\d{2,})\s*(\d{2,})/i);
                if (match) {
                    let grid = (match[1].length === 2) ? new GT_OSGB() : new GT_Irish();
                    grid.parseGridRef(match[1]+match[2]+match[3]);
                    conv = grid.getWGS84(true);
                 //         if (!conv || conv.status != 'OK') //conversion could fail! (although unlikly)
                   //                 throw new Error(`Unable to convert`);

                    //for nearby
                    mode = 'prefixplus'; //plus means to priortize used-nearby results
                    extra += `&gr=${encodeURIComponent(grid.getGridRef(2).replace(/ /g,''))}`;

			//just want the plain one for debug!
                    extra += `&subject=${encodeURIComponent(gridRef)}`;
                }
            }

            try {
                const response = await fetch(`/snippets.json.php?term=${encodeURIComponent(query)}&mode=${mode}${extra}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const results = await response.json();

                if (results.length === 0) {
                    const query_safe = escapeHTML(cleanTag(query)); //this is creating a tag!

                    suggestionsSnippets.innerHTML = `
                        <div class="suggestion-item error-text">No matching descriptions found</div>
            			${(query_safe.length > 2 && query_safe !== 'blank') ? `
                        <div class="suggestion-item add-new-tag" data-tag="${query_safe}">
                            <span class="plus-icon">+</span> Add [<strong>${query_safe}</strong>] as a Tag
                        </div>`:''}
                    `;
                    return;
                }

                const safeQuery = escapeRegex(query);
                const regex = new RegExp(`(${safeQuery})(?!(?:[^&;]+;))`, "gi");
                html = results.map(row => {
                    var className = '';

if (extra && conv && conv.status == 'OK') {
        const itemLatRad = parseFloat(row.wgs84_lat);
        const itemLonRad = parseFloat(row.wgs84_long);
        if (itemLatRad !== 0 && itemLonRad !== 0) {
            // Convert API radians to degrees
            const itemLat = radToDeg(itemLatRad);
            const itemLon = radToDeg(itemLonRad);
            // Calculate distance from user
            const dist = getDistanceKM(conv.latitude, conv.longitude, itemLat, itemLon);

            if (dist > 20)
                className = "long-distance";
        }
}

                    const highlighted = escapeHTML(row.title).replace(regex, "<strong>$1</strong>");

                    const previewBtn = `<a href="/snippet/${row.snippet_id || row.id}" target="_blank" class="preview-link" title="Preview" style="text-decoration:none; margin-left:5px;">&#x1f441;</a>`;

                    const credit = (row.user_id == window.user_id || !row.realname)?'':`by ${escapeHTML(row.realname)}`;

                    return `<div class="suggestion-item ${className}" data-snippet_id="${row.snippet_id || row.id}" data-title="${escapeHTML(row.title)}">${highlighted} ${previewBtn} ${credit}</div>`;
                }).join('');

            } catch (error) {
                console.error("SD fetch failed:", error);
                // Inform the user that suggestions are currently unavailable
                html = `<div class="suggestion-item error-text"><em>Suggestions unavailable</em></div>`;
            }

            suggestionsSnippets.innerHTML = html;
        }, 250);
    });

    // Handle clicking a suggestion
    suggestionsSnippets.addEventListener('click', (e) => {
        if (e.target.classList.contains('suggestion-item')) {
            if (e.target.dataset.tag) {
		addTag(e.target.dataset.tag);
            } else if (e.target.dataset.snippet_id) {
                addSnippet(e.target.dataset.snippet_id, e.target.dataset.title);
            }
            snippetInput.value = ''; //its been added as tag instead!
        }
    });

    snippetInput.addEventListener('focus', (e) => {
        // Wait a tiny bit for the mobile keyboard to fully animate up
        setTimeout(() => {
            const rect = snippetInput.getBoundingClientRect();
            const viewportHeight = window.innerHeight;

            // If the input is in the bottom 30% of the visible area
            if (rect.top > viewportHeight * 0.7) {
                let block = 'center'; // This puts it in the middle, not the top
                if (window.matchMedia("(max-height: 500px) and (orientation: landscape)").matches) {
                    block = 'start';
                }
                snippetInput.scrollIntoView({
                    behavior: 'smooth',
                    block: block
                });
            }
        }, 300);
    });

    function addSnippet(snippet_id, title) {

        if (!selectedSnippets[snippet_id]) {
            selectedSnippets[snippet_id] = title;

            const span = document.createElement('span');
            span.className = 'tag-pill';

            // 1. Set text safely (avoids XSS and quote issues)
            span.textContent = title + ' ';

            // 2. Create the button as a real object
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = '&#10005;';

            // 3. Attach the remove logic directly to this specific button
            btn.onclick = function() {
                delete selectedSnippets[snippet_id]; // Remove from our Set
                span.remove();            // Remove the whole pill from DOM
            };

            // 4. Create the hidden input for the form POST
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'snippets[]';
            input.value = snippet_id;

            span.appendChild(btn);
            span.appendChild(input);
            activeSnippetContainer.appendChild(span);

            if (typeof updateFormProgress == 'function')
                updateFormProgress();
        }
        snippetInput.value = '';
        suggestionsSnippets.innerHTML = '';
    }

// Radians to Degrees
function radToDeg(rad) {
    return rad * (180 / Math.PI);
}

// Haversine Formula to calculate distance in KM
function getDistanceKM(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}
