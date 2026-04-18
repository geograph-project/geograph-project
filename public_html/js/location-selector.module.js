// Import dependencies if they are in separate files,
// or define them here if they are internal helpers.
const escapeRegex = (str) => str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

/**
 * Modern Geolocation Handler
 * Updates an input value with Lat/Long
 */
export function handleGeolocation(element_id, options = {}) {
    const locInput = document.getElementById(element_id);
    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser");
        return;
    }

    const originalPlaceholder = locInput.placeholder || "placename, lat/long, etc.";
    locInput.placeholder = "Locating...";

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const { latitude, longitude } = position.coords;
            const latFixed = latitude.toFixed(6);
            const lngFixed = longitude.toFixed(6);

            if (options.convert) {
                // Safety check: ensure the legacy global function exists
                if (typeof window.wgs2gridref === 'function') {
                    const gridref = window.wgs2gridref(latitude, longitude, 8);
                    locInput.value = `${gridref} / from ${latFixed},${lngFixed}`;
                } else {
                    console.warn("wgs2gridref not found, falling back to coordinates.");
                    locInput.value = `${latFixed},${lngFixed}`;
                }
            } else {
                locInput.value = `${latFixed},${lngFixed}`;
            }

            locInput.placeholder = originalPlaceholder;
            // Trigger a change event so other listeners know the value updated
            locInput.dispatchEvent(new Event('change'));
        },
        (error) => {
            locInput.placeholder = "Location access denied";
            alert("Could not get your location. Please type it manually.");
        },
        { enableHighAccuracy: true, timeout: 5000 }
    );
}

/**
 * Autocomplete Logic
 */
export function setupPlaceAutocomplete(element_id, options = {}) {
    const { maplink = false, regions = null, onSelect = null } = options;
    const input = document.getElementById(element_id);
    const list = document.getElementById(options.listId || 'results-list');

    const renderItem = (item, term) => {
        const safeTerm = escapeRegex(term);
        const re = new RegExp(`(${safeTerm})`, 'gi');
        const label = item.label.replace(re, '<b>$1</b>');
        const displaygr = (item.gr && !item.label.includes(item.gr) && item.title) ? item.gr : '';
        const sub = (item.title || item.gr || '').replace(re, '<b>$1</b>');
        return `
            <li data-value="${item.value}" data-gr="${item.gr || ''}" data-label="${item.label}">
		<div class="main-info">
	                <span class="label">${label}</span>
			<span class="gridref">${displaygr}</span>
            	</div>
                <span class="locality">${sub}</span>
            </li>`;
    };

    const updateSuggestions = async () => {
        const term = input.value.trim();
        let html = '';

        if (term.length < 2) {
            if (regions) {
                regions.forEach(item => {
                    html += renderItem({ label: item, value: item }, term);
                });
                list.innerHTML = html;
                list.style.display = 'block';
            } else {
                list.style.display = 'none';
            }
            return;
        }

	//todo, debounce!

        try {
            const response = await fetch(`/finder/places.json.php?q=${encodeURIComponent(term)}&new=2`);
            const data = await response.json();

            if (maplink) {
                html += `<li data-action="map" style="color: blue; font-weight:bold;">&#128269; View Results on a map...</li>`;
            }

            if (data?.items) {
                sortGazetter(data.items, term).forEach(item => {
                    html += renderItem({
                        label: item.name1+(item.name2?` (${item.name2})`:''),
                        value: item.name1.includes(item.gridref)?item.name1:`${item.gridref} ${item.name1}`,
                        gr: item.gridref, //todo would be to create 6fig GR rather than using the 4fig one provided
                        title: ((item.county == item.country)?item.country:`${item.county}, ${item.country}`)+(item.type?` (${item.type})`:'')
                    }, term);
                });
            }

            list.innerHTML = html;
            list.style.display = 'block';
        } catch (e) { console.error("Autocomplete error:", e); }
    };

    // Attach the same logic to both events
    input.addEventListener('input', updateSuggestions);
    input.addEventListener('focus', updateSuggestions);

    list.addEventListener('click', (e) => {
        const li = e.target.closest('li');
        if (!li) return;

        if (li.dataset.action === "map") {
            openPlaceSearch(input.value, (name, gr) => {
                input.value = `${gr} ${name}`;
                input.dispatchEvent(new Event('change'));
                list.style.display = 'none';
            });
        } else {
            input.value = li.dataset.value;
            input.dispatchEvent(new Event('change'));
            list.style.display = 'none';
            if (onSelect) onSelect(li.dataset);
        }
    });

    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !list.contains(e.target)) {
            list.style.display = 'none';
        }
    });
}

/**
 * Iframe Modal Search
 */
export function openPlaceSearch(query, callback) {
    // Reference the top-level parent window (but CAN be self if not a iframe!)

    const targetWindow = window.parent || window;
    const targetDoc = targetWindow.document;

    const backdrop = targetDoc.createElement('div');
    backdrop.className = "modal-backdrop-geo";
    backdrop.style.cssText = "position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); z-index:100000; display:flex; align-items:center; justify-content:center; backdrop-filter:blur(4px);";

    const modal = targetDoc.createElement('div');
    modal.style.cssText = "width:90vw; height:90vh; max-height:700px; background:white; border-radius:8px; position:relative; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,0.5);";

    const iframe = targetDoc.createElement('iframe');
    iframe.src = `/finder/place.php?inner&loc=${encodeURIComponent(query)}`;
    iframe.style.cssText = "width:100%; height:100%; border:none;";

    const closeBtn = targetDoc.createElement('button');
    closeBtn.innerHTML = "&times;";
    closeBtn.style.cssText = "position:absolute; top:10px; right:10px; z-index:100001; background:#fff; border:1px solid #ccc; border-radius:50%; width:34px; height:34px; cursor:pointer; font-size:24px; display:flex; align-items:center; justify-content:center;";

    modal.appendChild(closeBtn);
    modal.appendChild(iframe);
    backdrop.appendChild(modal);
    targetDoc.body.appendChild(backdrop);

    const destroyModal = () => {
        targetWindow.removeEventListener('message', messageHandler);
        if (targetDoc.body.contains(backdrop)) targetDoc.body.removeChild(backdrop);
    };

    const messageHandler = (event) => {
        if (event.data && event.data.type === 'PLACE_SELECTED') {
            destroyModal();
            callback(event.data.name, event.data.gr, event.data.lat, event.data.lng);
        }
    };

    targetWindow.addEventListener('message', messageHandler);
    closeBtn.onclick = destroyModal;
    backdrop.onclick = (e) => { if (e.target === backdrop) destroyModal(); };


        // Inside openPlaceSearch on the Parent Page
        if (targetWindow.visualViewport) {
            var resizeHandler = function() {
                var vv = targetWindow.visualViewport;

                // 1. Calculate the real visible height
                // We subtract a little (e.g., 20px) if we want the modal to not touch the keyboard
                var availableHeight = vv.height;

                // 2. Adjust the backdrop or modal container
                // If we want the modal to remain centered in the VISIBLE area:
                backdrop.style.height = availableHeight + "px";
                backdrop.style.top = vv.offsetTop + "px";

                // 3. Update the modal height so it doesn't get cut off
                // We make the modal 90% of the CURRENT visual height
                modal.style.height = (availableHeight * 0.96) + "px";

                // 4. Notify the iframe to redraw the map
                if (iframe.contentWindow) {
                    iframe.contentWindow.postMessage({ type: 'VIEWPORT_RESIZE' }, '*');
                }
            };

            targetWindow.visualViewport.addEventListener('resize', resizeHandler);
            targetWindow.visualViewport.addEventListener('scroll', resizeHandler);

            // Initial call to set size
            resizeHandler();
        }

}


/*
<input type="search" id="loc" placeholder="Search for a place...">
<button id="get-loc-btn">Find my Location</button>

<div class="autocomplete-container">
    <ul id="results-list" class="results-list"></ul>
</div>

<script type="module">
    import { handleGeolocation, setupPlaceAutocomplete } from '/js/location-utils.js';

    // 1. Initialize Autocomplete
    setupPlaceAutocomplete('loc', {
        maplink: true,
        regions: ['London', 'Manchester', 'Scotland'],
        onSelect: (data) => {
            console.log("Selected:", data.label);
            // If you want to auto-submit the form:
            // document.getElementById('search-form').submit();
        }
    });

    // 2. Bind the "Find my Location" button
    document.getElementById('get-loc-btn').addEventListener('click', (e) => {
        e.preventDefault();
        handleGeolocation('loc');
    });
</script>

*/

//spectivially the new=2 / spatial_index data
function sortGazetter(results, query) {
    const q = query.toLowerCase();

    // 1. Define Type Priorities
    const getTypePriority = (type) => {
        const t = (type || "").toLowerCase();
        if (t === 'town' || t === 'city' || t === 'town_1' || t === 'town_2') return 1;
        if (t === 'suburban area' || t === 'district' || t === 'town_10') return 100;
        return 10;
    };

    // 2. Determine Match Rank on both names!
    const getMatchRank = (n1, n2) => {
	const name1 = (n1 || "").toLowerCase();
        const name2 = (n2 || "").toLowerCase();
        if (name1 === q || name2 === q) return 1;
        if (name1.startsWith(q) || name2.startsWith(q)) return 2;
        if (name1.includes(q) || name2.includes(q)) return 3;
        return 4;
    };

    return results.sort((a, b) => {
        // Compare Match Ranks (Exact > Starts With > Contains)
	// specifically avoiding the gridref on name1 (doesnt exist on name2)
        const rankA = getMatchRank(a.name1.split('/')[0], a.name2);
        const rankB = getMatchRank(b.name1.split('/')[0], b.name2);
        if (rankA !== rankB) { return rankA - rankB; }

        // If ranks match, compare Type Priorities
        const typeA = getTypePriority(a.type);
        const typeB = getTypePriority(b.type);
        if (typeA !== typeB) { return typeA - typeB; }

        // Final tie-breaker: Alphabetical
        return a.name1.localeCompare(b.name1);
    });
}
