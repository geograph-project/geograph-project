// Local self-contained escapeHtml helper to ensure no runtime errors
function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) {
        return '';
    }
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

document.addEventListener('DOMContentLoaded', function() {
    // Current application parameters
    const CURRENT_LABEL = window.CURRENT_LABEL || 'Sea Arches';
    const API_BASE = "/curated/editor/curate.json.php";

    // Application state
    let state = {
        curatedMap: new Map(), // gridimage_id -> curated state record
        rawCandidates: [],     // Array of raw search result objects
        unassignedQueue: [],   // Sequential unassigned queue
        shortlistedList: [],   // Array of shortlisted items
        currentUnassignedIndex: 0,
        currentHotNotIndex: 0,
        selectedCandidateId: null, // currently highlighted card in Column 1
        totalFound: 0,          // total matching candidates found in raw search
        knownFeatures: []      // Array of known feature items
    };

    // DOM Elements Cache
    const el = {
        tabs: document.querySelectorAll('.nav-btn'),
        panes: document.querySelectorAll('.tab-pane'),
        rawSearchInput: document.getElementById('raw-search-input'),
        rawSearchBtn: document.getElementById('raw-search-btn'),
        rawCandidatesList: document.getElementById('raw-candidates-list'),
        shortlistedList: document.getElementById('shortlisted-list'),
        unassignedSeqView: document.getElementById('unassigned-seq-view'),
        hotNotSeqView: document.getElementById('hot-not-seq-view'),
        unassignedBadge: document.getElementById('unassigned-badge'),
        hotNotBadge: document.getElementById('hot-not-badge'),
        refreshReportBtn: document.getElementById('refresh-report-btn'),
        reportTableBody: document.getElementById('report-table-body'),
        aiVectorCheckbox: document.getElementById('ai-vector-checkbox'),
        aiVectorModel: document.getElementById('ai-vector-model'),
        clearShortlistBtn: document.getElementById('clear-shortlist-btn'),
        mapShowRaw: document.getElementById('map-show-raw'),
        mapShowShortlisted: document.getElementById('map-show-shortlisted'),
        mapShowConfirmed: document.getElementById('map-show-confirmed'),
        mapShowOutliers: document.getElementById('map-show-outliers'),
        mapMarkerCount: document.getElementById('map-marker-count'),
        mapLiveUpdate: document.getElementById('map-live-update'),
        mapLiveUpdateContainer: document.getElementById('map-live-update-container'),
        mapShowFeatures: document.getElementById('map-show-features'),
        mapShowFeaturesContainer: document.getElementById('map-show-features-container')
    };

    function updateLiveUpdateVisibility() {
        if (!el.mapLiveUpdateContainer) return;
        if (el.aiVectorCheckbox && el.aiVectorCheckbox.checked) {
            el.mapLiveUpdateContainer.style.display = 'none';
            if (el.mapLiveUpdate) el.mapLiveUpdate.checked = false;
        } else {
            el.mapLiveUpdateContainer.style.display = 'inline-block';
        }
    }

    // Toggle AI Vector Model Selection Visibility
    if (el.aiVectorCheckbox) {
        el.aiVectorCheckbox.addEventListener('change', function() {
            if (this.checked) {
		//stick with one model for now
                //el.aiVectorModel.style.display = 'inline-block';
            } else {
                el.aiVectorModel.style.display = 'none';
            }
            updateLiveUpdateVisibility();
        });
    }

    // Clear Shortlist Action
    if (el.clearShortlistBtn) {
        el.clearShortlistBtn.addEventListener('click', async function() {
            if (confirm("Are you sure you want to clear all suggested/shortlisted items? This will reset them to raw.")) {
                const shortlists = Array.from(state.curatedMap.values()).filter(x => x.active === 1);
                for (const item of shortlists) {
                    await updateCurationState(item.id, 0); // Exclude or remove
                }
                await fetchCurationState();
                renderAllViews();
            }
        });
    }

    // Handle Tab switching
    el.tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            el.tabs.forEach(t => t.classList.remove('active'));
            el.panes.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            const targetPane = document.getElementById('tab-' + this.dataset.tab);
            if (targetPane) {
                targetPane.classList.add('active');
            }

            // Perform tab-specific initializations
            if (this.dataset.tab === 'spatial-map') {
                initOrUpdateMap();
            } else if (this.dataset.tab === 'reports') {
                refreshReport();
            }
        });
    });

    // Keyboard Shortcuts Listener
    document.addEventListener('keydown', function(event) {
        // Bypass shortcuts when user is typing in forms
        const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        if (activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select') {
            return;
        }

        // Action Keys
        if (event.code === 'KeyA') {
            // Shortlist Highlighted candidate
            if (state.selectedCandidateId) {
                event.preventDefault();
                shortlistImage(state.selectedCandidateId);
            }
        } else if (event.code === 'KeyX') {
            // Exclude highlighted candidate or current review slideshow image
            const activeTab = document.querySelector('.nav-btn.active') ? document.querySelector('.nav-btn.active').dataset.tab : '';
            if (activeTab === 'triage' && state.selectedCandidateId) {
                event.preventDefault();
                excludeImage(state.selectedCandidateId);
            } else if (activeTab === 'hot-not') {
                event.preventDefault();
                const currentShort = getShortlistedItems()[state.currentHotNotIndex];
                if (currentShort) {
                    excludeImage(currentShort.id);
                }
            }
        } else if (event.code === 'KeyF') {
            // Focus first feature field in Column 2
            event.preventDefault();
            const firstFeatureInput = document.querySelector('.feature-resolver-input');
            if (firstFeatureInput) {
                firstFeatureInput.focus();
            }
        } else if (event.code === 'ArrowRight') {
            // Next item in queues
            const activeTab = document.querySelector('.nav-btn.active') ? document.querySelector('.nav-btn.active').dataset.tab : '';
            if (activeTab === 'hot-not') {
                event.preventDefault();
                skipHotNotImage();
            } else if (activeTab === 'unassigned-triage') {
                event.preventDefault();
                skipUnassignedImage();
            }
        } else if (!event.ctrlKey && !event.metaKey && (event.code === 'KeyC' || event.code === 'Enter')) {
            // Confirm/Save
            const activeTab = document.querySelector('.nav-btn.active') ? document.querySelector('.nav-btn.active').dataset.tab : '';
            if (activeTab === 'hot-not') {
                event.preventDefault();
                const currentShort = getShortlistedItems()[state.currentHotNotIndex];
                if (currentShort) {
                    confirmShortlistedImage(currentShort.id);
                }
            } else if (activeTab === 'unassigned-triage') {
                event.preventDefault();
                saveUnassignedImage();
            }
        }
    });

    // Helper: Local Storage for Skipped Images (Review queue)
    function getSkippedIds() {
        return JSON.parse(localStorage.getItem('skipped_curation_' + CURRENT_LABEL) || '[]');
    }

    function addSkippedId(id) {
        const skipped = getSkippedIds();
        if (!skipped.includes(id)) {
            skipped.push(id);
            localStorage.setItem('skipped_curation_' + CURRENT_LABEL, JSON.stringify(skipped));
        }
    }

    // Call REST API to fetch curated state
    async function fetchCurationState() {
        try {
            const res = await fetch(`${API_BASE}?action=get_curation_summary&label=${encodeURIComponent(CURRENT_LABEL)}`);
            const data = await res.json();
            if (data.curated) {
                state.curatedMap.clear();
                data.curated.forEach(item => {
                    state.curatedMap.set(item.id, item);
                });
            }
        } catch (e) {
            console.error('Error fetching curation summary', e);
        }
    }

    async function fetchKnownFeatures() {
        const FEATURE_TYPE_ID = window.FEATURE_TYPE_ID;
        if (!FEATURE_TYPE_ID) return;
        try {
            const res = await fetch(`${API_BASE}?action=get_features&feature_type_id=${FEATURE_TYPE_ID}&label=${encodeURIComponent(CURRENT_LABEL)}`);
            const data = await res.json();
            if (data.features) {
                state.knownFeatures = data.features;
            }
        } catch (e) {
            console.error('Error fetching known features', e);
        }
    }

    // Call REST API to update curation state
    async function updateCurationState(gridimage_id, active, feature = '', region = '') {
        try {
            const formData = new FormData();
            formData.append('action', 'save_state');
            formData.append('label', CURRENT_LABEL);
            formData.append('gridimage_id', gridimage_id);
            formData.append('active', active);
            formData.append('feature', feature);
            formData.append('region', region);

            const res = await fetch(API_BASE, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.error) {
                alert(data.error);
                return false;
            }
            return true;
        } catch (e) {
            console.error('Error saving curation state', e);
            return false;
        }
    }

    // Query Sphinx / API for Raw Candidate Images
    async function performRawSearch() {
        if (!el.rawSearchInput) return;
        const query = el.rawSearchInput.value.trim();
        if (!query) return;

        el.rawCandidatesList.innerHTML = '<div class="loading-placeholder">Searching candidates...</div>';

        // Prepare lists of excluded/confirmed IDs to pass to Sphinx `not in` query
        const excludedOrConfirmedIds = Array.from(state.curatedMap.values())
            .filter(item => item.active === 0 || item.active === 2)
            .map(item => item.id);

        let url = '';
        let params = {
            select: 'id,user_id,realname,grid_reference,title,hash,wgs84_lat,wgs84_long',
            utf: '1',
            limit: '60'
        };

        if (el.aiVectorCheckbox && el.aiVectorCheckbox.checked) {
            url = '/api-facetql-vector.php';
            params['label'] = query;
            params['model'] = el.aiVectorModel.value;
            // Note: api-facetql-vector.php does not support 'where' exclusions, so they will be filtered client-side.
        } else {
            url = '/api-facetql.php';

            // Check if the search query is a specific ID or comma-separated list of IDs
		    if (query.match(/^(id:)?\d+(,\d+)*$/i)) {
                // Parse requested IDs and remove any that are already excluded/confirmed
                const rawIds = query.replace(/^id:/i, '').split(',').map(id => id.trim());
                const validRequestedIds = rawIds.filter(id => !excludedOrConfirmedIds.includes(Number(id)) && !excludedOrConfirmedIds.includes(id));

                if (validRequestedIds.length > 0) {
                    params['where'] = `id in (${validRequestedIds.join(',')})`;
                } else {
                    // All requested IDs are excluded; match impossible condition to return 0 results
                    params['where'] = "id in (0)";
                }
            } else {
                params['match'] = query;
                // Inject ID exclusion clause so Sphinx filters out already curated items!
                if (excludedOrConfirmedIds.length > 0) {
                    params['where'] = `id not in (${excludedOrConfirmedIds.join(',')})`;
                }
            }
        }

        try {
            const fullUrl = `${url}?${new URLSearchParams(params).toString()}`;
            const res = await fetch(fullUrl);
            const data = await res.json();

            if (data.meta) {
                state.totalFound = parseInt(data.meta.total_found || data.meta.total || 0);
            } else {
                state.totalFound = 0;
            }

            if (data.rows && data.rows.length > 0) {
                // Map API rows into structured candidate array, converting coordinates
                state.rawCandidates = data.rows.map(row => {
                    return {
                        id: parseInt(row.id),
                        user_id: parseInt(row.user_id),
                        realname: row.realname,
                        title: row.title,
                        hash: row.hash,
                        lat: rad2deg(row.wgs84_lat ?? 0),
                        lng: rad2deg(row.wgs84_long ?? 0)
                    };
                });
            } else {
                state.rawCandidates = [];
            }

            renderRawCandidates();
        } catch (e) {
            console.error('Error fetching raw candidates', e);
            el.rawCandidatesList.innerHTML = '<div class="loading-placeholder error">Error searching candidates. Please try again.</div>';
        }
    }

    // Action: Shortlist Image (status=1)
    async function shortlistImage(id) {
        // Find in raw candidates if not already curated
        const raw = state.rawCandidates.find(item => item.id === id);
        if (raw) {
            const ok = await updateCurationState(id, 1, '', '', raw.title);
            if (ok) {
                // Update local state
                state.curatedMap.set(id, {
                    id: id,
                    active: 1,
                    feature: '',
                    region: '',
                    title: raw.title,
                    hash: raw.hash,
                    lat: raw.lat,
                    lng: raw.lng
                });

                // Clear highlighted selection
                if (state.selectedCandidateId === id) {
                    state.selectedCandidateId = null;
                }

                // Remove from rawCandidates array
                state.rawCandidates = state.rawCandidates.filter(item => item.id !== id);

                renderAllViews();
            }
        }
    }

    // Action: Exclude Image (status=0)
    async function excludeImage(id) {
        const raw = state.rawCandidates.find(item => item.id === id) || state.curatedMap.get(id);
        if (raw) {
            const ok = await updateCurationState(id, 0, '', '');
            if (ok) {
                state.curatedMap.set(id, {
                    id: id,
                    active: 0,
                    feature: '',
                    region: '',
                    title: raw.title || '',
                    hash: raw.hash,
                    lat: raw.lat,
                    lng: raw.lng
                });

                if (state.selectedCandidateId === id) {
                    state.selectedCandidateId = null;
                }

                state.rawCandidates = state.rawCandidates.filter(item => item.id !== id);

                renderAllViews();
            }
        }
    }

    // Action: Confirm Suggested Image
    async function confirmShortlistedImage(id, featureName = '') {
        const item = state.curatedMap.get(id);
        if (item) {
            const finalFeature = featureName || item.feature;
            if (!finalFeature) {
                alert("Please enter or select a feature name first!");
                return;
            }

            const ok = await updateCurationState(id, 2, finalFeature, item.region);
            if (ok) {
                item.active = 2;
                item.feature = finalFeature;
                state.curatedMap.set(id, item);

                renderAllViews();
            }
        }
    }

    // Render Lists helper
    function getShortlistedItems() {
        return Array.from(state.curatedMap.values()).filter(item => item.active === 1);
    }

    function getConfirmedItems() {
        return Array.from(state.curatedMap.values()).filter(item => item.active === 2);
    }

    function getExcludedItems() {
        return Array.from(state.curatedMap.values()).filter(item => item.active === 0);
    }

    function getUnassignedItems() {
        return Array.from(state.curatedMap.values()).filter(item => (item.active === 1 || item.active === 2) && item.feature === '');
    }

    // Render Raw Candidates (Left Column)
    function renderRawCandidates() {
        const container = el.rawCandidatesList;
        if (!container) return;
        container.innerHTML = '';

        // Filter out any candidates already in curation map (or marked status 0, 2)
        const visibleCandidates = state.rawCandidates.filter(item => {
            const cur = state.curatedMap.get(item.id);
            return !cur || (cur.active !== 0 && cur.active !== 2 && cur.active !== 1);
        });

        if (visibleCandidates.length === 0) {
            container.innerHTML = '<div class="loading-placeholder">No raw candidates found. Change search terms or mark items above.</div>';
            return;
        }

        visibleCandidates.forEach(item => {
            const card = document.createElement('div');
            card.className = `image-card ${state.selectedCandidateId === item.id ? 'selected-card' : ''}`;
            card.dataset.id = item.id;
            card.draggable = true;

            const url = getGeographUrl(item.id, item.hash, 'med');

            card.innerHTML = `
                <div class="image-thumbnail-wrapper">
                    <img src="${url}" crossorigin onerror="retryCross(this)" loading="lazy">
                </div>
                <div class="image-details">
                    <h4 class="image-title"><a href="https://www.geograph.org.uk/photo/${item.id}" target="_blank">${escapeHtml(item.title)}</a></h4>
                    <div class="image-meta">by ${escapeHtml(item.realname)} (ID: ${item.id})</div>
                    <div class="image-actions">
                        <button class="btn btn-sm btn-primary add-shortlist-btn">Shortlist</button>
                        <button class="btn btn-sm btn-danger exclude-btn">Exclude</button>
                    </div>
                </div>
            `;

            // Event Listeners for Card Triage
            card.addEventListener('click', function() {
                document.querySelectorAll('.raw-candidates-col .image-card').forEach(c => c.classList.remove('selected-card'));
                card.classList.add('selected-card');
                state.selectedCandidateId = item.id;
            });

            card.addEventListener('dblclick', function(e) {
                e.preventDefault();
                shortlistImage(item.id);
            });

            card.querySelector('.add-shortlist-btn').addEventListener('click', function(e) {
                e.stopPropagation();
                shortlistImage(item.id);
            });

            card.querySelector('.exclude-btn').addEventListener('click', function(e) {
                e.stopPropagation();
                excludeImage(item.id);
            });

            // Drag and Drop
            card.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('text/plain', item.id);
                card.style.opacity = '0.5';
            });

            card.addEventListener('dragend', function() {
                card.style.opacity = '1';
            });

            container.appendChild(card);
        });
    }

    // Render Shortlist (Right Column)
    function renderShortlisted() {
        const container = el.shortlistedList;
        if (!container) return;
        container.innerHTML = '';

        const items = getShortlistedItems();
        if (el.hotNotBadge) el.hotNotBadge.textContent = items.length;

        if (items.length === 0) {
            container.innerHTML = '<div class="loading-placeholder">No shortlisted candidates. Drag items here or double-click to shortlist them.</div>';
            return;
        }

        items.forEach(item => {
            const card = document.createElement('div');
            card.className = 'image-card';
            card.dataset.id = item.id;

            const url = getGeographUrl(item.id, item.hash, 'med');

            card.innerHTML = `
                <div class="image-thumbnail-wrapper">
                    <img src="${url}" loading="lazy">
                </div>
                <div class="image-details">
                    <h4 class="image-title"><a href="https://www.geograph.org.uk/photo/${item.id}" target="_blank">${escapeHtml(item.title)}</a></h4>
                    <div class="resolver-input-group" style="display:flex; gap: 8px; margin-top:5px;">
                        <input type="text" class="form-control feature-resolver-input" style="flex:1; padding:5px;" value="${escapeHtml(item.feature)}" placeholder="Assign Feature/Place Name...">
                        <button class="btn btn-sm btn-success confirm-feature-btn">Confirm</button>
                    </div>
                    <div class="proximity-assist" id="proximity-assist-${item.id}">
                        <span class="proximity-label">Proximity Suggestions:</span>
                        <div class="proximity-tags" id="proximity-tags-${item.id}">
                            <span style="font-size:11px; color:#999;">Computing nearest...</span>
                        </div>
                    </div>
                    <div class="image-actions" style="margin-top: 10px;">
                        <button class="btn btn-sm btn-danger remove-curate-btn">Exclude</button>
                    </div>
                </div>
            `;
setTimeout(function() {
            // Proximity Suggestions computation
            fetchProximitySuggestions(item.lat, item.lng, `proximity-tags-${item.id}`, card);
}, 10);
            // Inputs / Actions listeners
            const input = card.querySelector('.feature-resolver-input');
            input.addEventListener('change', function() {
                item.feature = this.value;
                state.curatedMap.set(item.id, item);
            });

            card.querySelector('.confirm-feature-btn').addEventListener('click', function() {
                confirmShortlistedImage(item.id, input.value);
            });

            card.querySelector('.remove-curate-btn').addEventListener('click', function() {
                excludeImage(item.id);
            });

            container.appendChild(card);
        });
    }

    // Proximity Suggestions Fetching & Rendering
    async function fetchProximitySuggestions(lat, lng, containerId, cardEl) {
        const container = document.getElementById(containerId);
        if (!container) return;

        try {
            const res = await fetch(`${API_BASE}?action=get_proximity_suggestions&label=${encodeURIComponent(CURRENT_LABEL)}&lat=${lat}&lng=${lng}&feature_type_id=${window.FEATURE_TYPE_ID || ''}`);
            const data = await res.json();

            if (data.suggestions && data.suggestions.length > 0) {
                container.innerHTML = '';
                data.suggestions.forEach(s => {
                    const tag = document.createElement('span');
                    tag.className = 'proximity-tag';
                    tag.textContent = s.feature;
                    tag.title = `${Math.round(s.dist)} meters`;
                    tag.addEventListener('click', function() {
                        const input = cardEl.querySelector('.feature-resolver-input');
                        if (input) {
                            input.value = s.feature;
                            input.dispatchEvent(new Event('change'));
                        }
                    });
                    container.appendChild(tag);
                });
            } else {
                container.innerHTML = '<span style="font-size:11px; color:#999;">None nearby</span>';
            }
        } catch (e) {
            container.innerHTML = '<span style="font-size:11px; color:#f00;">Error</span>';
        }
    }

    // Drag-over Shortlist Column
    if (el.shortlistedList) {
        el.shortlistedList.addEventListener('dragover', function(e) {
            e.preventDefault();
        });

        el.shortlistedList.addEventListener('drop', function(e) {
            e.preventDefault();
            const id = parseInt(e.dataTransfer.getData('text/plain'));
            if (id) {
                shortlistImage(id);
            }
        });
    }

    // Render Unassigned Sequential View (Tab 3)
    function renderUnassignedSequential() {
        const container = el.unassignedSeqView;
        if (!container) return;
        const unassigned = getUnassignedItems();
        if (el.unassignedBadge) el.unassignedBadge.textContent = unassigned.length;

        if (unassigned.length === 0) {
            container.innerHTML = '<div class="sequential-placeholder">No unassigned images found. Add candidates to the shortlist and feature-resolver to curate features!</div>';
            return;
        }

        if (state.currentUnassignedIndex >= unassigned.length) {
            state.currentUnassignedIndex = unassigned.length - 1;
        }
        if (state.currentUnassignedIndex < 0) {
            state.currentUnassignedIndex = 0;
        }

        const item = unassigned[state.currentUnassignedIndex];
        const url = getGeographUrl(item.id, item.hash, 'full');

        container.innerHTML = `
            <div class="sequential-card">
                <div class="sequential-left">
                    <div class="sequential-img-wrapper">
                        <img src="${url}" crossorigin onerror="retryCross(this)">
                    </div>
                </div>
                <div class="sequential-right">
                    <div class="sequential-details">
                        <div class="queue-progress" style="font-weight:bold; margin-bottom: 10px;">Queue Progress: Image ${state.currentUnassignedIndex + 1} of ${unassigned.length}</div>
                        <h3>${escapeHtml(item.title)}</h3>
			<a href="https://www.geograph.org.uk/photo/${item.id}" target="_blank">View Photo Page</a>
                        <p style="font-size:13px; color:#666;">Coordinates: ${item.lat.toFixed(6)}, ${item.lng.toFixed(6)}</p>

                        <div style="margin-top:20px;">
                            <label style="font-weight:bold; display:block; margin-bottom:8px;">Assign Feature Name (F):</label>
                            <input type="text" id="seq-feature-input" class="form-control feature-resolver-input" style="width:100%; padding:10px; font-size:15px;" value="${escapeHtml(item.feature)}" placeholder="{enter name here}">
                        </div>

                        <div class="proximity-assist" style="margin-top: 15px;">
                            <span class="proximity-label">Proximity suggestions (click to use):</span>
                            <div class="proximity-tags" id="seq-proximity-tags">
                                <span style="font-size:11px; color:#999;">Computing nearest...</span>
                            </div>
                        </div>
                    </div>
                    <div class="sequential-actions">
                        <button class="btn btn-success" id="seq-save-btn">Save & Confirm (C)</button>
                        <button class="btn btn-danger" id="seq-exclude-btn">Exclude (X)</button>
                        <button class="btn btn-secondary" id="seq-skip-btn">Skip (ArrowRight)</button>
                    </div>
                </div>
            </div>
        `;

        fetchProximitySuggestions(item.lat, item.lng, 'seq-proximity-tags', container);

        // Buttons listeners
        document.getElementById('seq-save-btn').addEventListener('click', saveUnassignedImage);
        document.getElementById('seq-exclude-btn').addEventListener('click', () => excludeImage(item.id));
        document.getElementById('seq-skip-btn').addEventListener('click', skipUnassignedImage);

        // Focus the field
        const seqInput = document.getElementById('seq-feature-input');
        if (seqInput) {
            seqInput.focus();
        }
    }

    function saveUnassignedImage() {
        const unassigned = getUnassignedItems();
        const item = unassigned[state.currentUnassignedIndex];
        if (item) {
            const val = document.getElementById('seq-feature-input').value.trim();
            if (!val) {
                alert("Please specify a feature name!");
                return;
            }
            confirmShortlistedImage(item.id, val);
            state.currentUnassignedIndex++;
        }
    }

    function skipUnassignedImage() {
        const unassigned = getUnassignedItems();
        state.currentUnassignedIndex = (state.currentUnassignedIndex + 1) % unassigned.length;
        renderUnassignedSequential();
    }

    // Render "Hot or Not" Sequential Review Queue (Tab 4)
    function renderHotNotSlideshow() {
        const container = el.hotNotSeqView;
        if (!container) return;
        const shortlisted = getShortlistedItems();
        const skipped = getSkippedIds();

        // Filter out locally skipped items
        const activeQueue = shortlisted.filter(item => !skipped.includes(item.id));

        if (activeQueue.length === 0) {
            container.innerHTML = '<div class="sequential-placeholder">No shortlisted items left to review. Suggest/Shortlist some images first!</div>';
            return;
        }

        if (state.currentHotNotIndex >= activeQueue.length) {
            state.currentHotNotIndex = activeQueue.length - 1;
        }
        if (state.currentHotNotIndex < 0) {
            state.currentHotNotIndex = 0;
        }

        const item = activeQueue[state.currentHotNotIndex];
        const url = getGeographUrl(item.id, item.hash, 'full');

        container.innerHTML = `
            <div class="sequential-card">
                <div class="sequential-left">
                    <div class="sequential-img-wrapper">
                        <img src="${url}" crossorigin onerror="retryCross(this)">
                    </div>
                </div>
                <div class="sequential-right">
                    <div class="sequential-details">
                        <div class="queue-progress" style="font-weight:bold; margin-bottom: 10px;">Review Progress: Image ${state.currentHotNotIndex + 1} of ${activeQueue.length}</div>
                        <h3><a href="https://www.geograph.org.uk/photo/${item.id}" target="_blank">${escapeHtml(item.title)}</a></h3>
                        <p style="font-size:14px; margin-top:20px; line-height: 1.6;">
                            This image was shortlisted/suggested by users for the target label <b>${escapeHtml(CURRENT_LABEL)}</b>.<br>
                            Review the details and confirm if this is a correct, valid example.
                        </p>
                    </div>
                    <div class="sequential-actions">
                        <button class="btn btn-success btn-lg" id="hot-confirm-btn" style="padding:15px 30px; font-size:16px;">Confirm Suggestion (C)</button>
                        <button class="btn btn-danger btn-lg" id="hot-exclude-btn" style="padding:15px 30px; font-size:16px;">Reject/Exclude (X)</button>
                        <button class="btn btn-secondary btn-lg" id="hot-skip-btn" style="padding:15px 30px; font-size:16px;">Skip (ArrowRight)</button>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('hot-confirm-btn').addEventListener('click', () => {
            confirmShortlistedImage(item.id, item.feature || '[Unassigned]');
        });
        document.getElementById('hot-exclude-btn').addEventListener('click', () => excludeImage(item.id));
        document.getElementById('hot-skip-btn').addEventListener('click', skipHotNotImage);
    }

    function skipHotNotImage() {
        const shortlisted = getShortlistedItems();
        const skipped = getSkippedIds();
        const activeQueue = shortlisted.filter(item => !skipped.includes(item.id));
        if (activeQueue.length > 0) {
            const item = activeQueue[state.currentHotNotIndex];
            addSkippedId(item.id);
            state.currentHotNotIndex = state.currentHotNotIndex % activeQueue.length;
            renderAllViews();
        }
    }

    // Render Curation Reports (Tab 5)
    async function refreshReport() {
        if (!el.reportTableBody) return;
        el.reportTableBody.innerHTML = '<tr><td colspan="3" class="text-center">Generating progress report...</td></tr>';
        try {
            const res = await fetch(`${API_BASE}?action=get_report&label=${encodeURIComponent(CURRENT_LABEL)}`);
            const data = await res.json();

            if (data.report && data.report.length > 0) {
                el.reportTableBody.innerHTML = '';
                data.report.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="font-weight:600;">${escapeHtml(row.feature)}</td>
                        <td><span class="badge" style="background-color: var(--success-color);">${row.total_curated}</span></td>
                        <td><span style="color:green; font-weight:bold;">Active Curation</span></td>
                    `;
                    el.reportTableBody.appendChild(tr);
                });
            } else {
                el.reportTableBody.innerHTML = '<tr><td colspan="3" class="text-center">No curated features for this label yet.</td></tr>';
            }
        } catch (e) {
            el.reportTableBody.innerHTML = '<tr><td colspan="3" class="text-center" style="color:red;">Error loading report.</td></tr>';
        }
    }

    if (el.refreshReportBtn) {
        el.refreshReportBtn.addEventListener('click', refreshReport);
    }

    // Leaflet Map Curation Object
    let map = null;
    let markerLayerGroup = null;
    let isFirstMapLoad = true;
    let tempAddMarker = null;

    function initOrUpdateMap() {
        const mapContainer = document.getElementById('curation-map');
        if (!mapContainer) return;

        if (!map) {
            // Set up Leaflet map container
            map = L.map('curation-map').setView([54.0, -2.5], 6); // default UK center

            // Standard OSM tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            markerLayerGroup = L.layerGroup().addTo(map);

            // Show Features layer toggle container if FEATURE_TYPE_ID is present
            if (window.FEATURE_TYPE_ID && el.mapShowFeaturesContainer) {
                el.mapShowFeaturesContainer.style.display = 'inline-block';
            }

            // Listen to checkbox filter changes
            const mapFilters = [el.mapShowRaw, el.mapShowShortlisted, el.mapShowConfirmed, el.mapShowOutliers, el.mapShowFeatures];
            mapFilters.forEach(f => {
                if (f) f.addEventListener('change', renderMapMarkers);
            });

            // Set up manual Show All / Fit Bounds button
            const fitBtn = document.getElementById('map-fit-bounds-btn');
            if (fitBtn) {
                fitBtn.addEventListener('click', fitMapToMarkers);
            }

            // Set up Leaflet map moveend event for Live Update feature
            map.on('moveend', handleMapMoveEnd);

            // Setup new pin placement click listener
            if (window.FEATURE_TYPE_ID) {
                map.on('click', handleMapClick);
            }
        }

        // Delay invalidation so container displays properly
        setTimeout(() => {
            map.invalidateSize();
            renderMapMarkers();
        }, 100);
    }

    async function handleMapClick(e) {
        // Ignore map click if clicking on a marker
        if (e.originalEvent && e.originalEvent.target && e.originalEvent.target.classList.contains('leaflet-marker-icon')) {
            return;
        }

        if (tempAddMarker) {
            map.removeLayer(tempAddMarker);
        }

        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        // Fetch nearby curated images within 1km
        let nearbyImages = [];
        try {
            const res = await fetch(`${API_BASE}?action=get_nearby_curated_images&label=${encodeURIComponent(CURRENT_LABEL)}&lat=${lat}&lng=${lng}`);
            const data = await res.json();
            nearbyImages = data.images || [];
        } catch (err) {
            console.error('Error fetching nearby curated images', err);
        }

        // Build the dropdown options
        let optionsHtml = '<option value="">-- No Image Selected --</option>';
        nearbyImages.forEach(img => {
            optionsHtml += `<option value="${img.id}">${escapeHtml(img.title)} (ID: ${img.id}, Dist: ${Math.round(img.dist)}m)</option>`;
        });

        // Use standard Leaflet marker for the new pin
        tempAddMarker = L.marker([lat, lng]).addTo(map);

        const popupContent = `
            <div class="add-feature-popup" style="min-width: 250px;">
                <h4 style="margin: 0 0 10px 0; color: var(--primary-color);">Add New Known Feature</h4>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; font-weight:bold; margin-bottom:4px;">Feature Name:</label>
                    <input type="text" id="new-feat-name" class="form-control" style="width:100%; padding:5px; box-sizing:border-box;" placeholder="Enter name...">
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-weight:bold; margin-bottom:4px;">Select Image (within 1km):</label>
                    <select id="new-feat-image" class="form-select" style="width:100%; padding:5px; box-sizing:border-box;">
                        ${optionsHtml}
                    </select>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button class="btn btn-sm btn-secondary" id="btn-cancel-add-feat">Cancel</button>
                    <button class="btn btn-sm btn-success" id="btn-save-add-feat">Create</button>
                </div>
            </div>
        `;

        tempAddMarker.bindPopup(popupContent).openPopup();

        tempAddMarker.on('popupopen', function() {
            // Cancel button
            document.getElementById('btn-cancel-add-feat').addEventListener('click', function() {
                if (tempAddMarker) {
                    map.removeLayer(tempAddMarker);
                    tempAddMarker = null;
                }
            });

            // Save/Create button
            document.getElementById('btn-save-add-feat').addEventListener('click', async function() {
                const name = document.getElementById('new-feat-name').value.trim();
                const gridimage_id = document.getElementById('new-feat-image').value;

                if (!name && !gridimage_id) {
                    alert('Please enter a feature name or select an image!');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'insert_feature');
                formData.append('label', CURRENT_LABEL);
                formData.append('feature_type_id', window.FEATURE_TYPE_ID);
                formData.append('name', name);
                formData.append('wgs84_lat', lat);
                formData.append('wgs84_long', lng);
                formData.append('gridimage_id', gridimage_id || '');

                try {
                    const res = await fetch(API_BASE, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.error) {
                        alert(data.error);
                    } else {
                        if (tempAddMarker) {
                            map.removeLayer(tempAddMarker);
                            tempAddMarker = null;
                        }
                        await fetchKnownFeatures();
                        renderMapMarkers();
                    }
                } catch (err) {
                    console.error('Error inserting feature', err);
                    alert('An error occurred while creating the feature.');
                }
            });
        });

        tempAddMarker.on('popupclose', function() {
            setTimeout(() => {
                if (tempAddMarker && !tempAddMarker.isPopupOpen()) {
                    map.removeLayer(tempAddMarker);
                    tempAddMarker = null;
                }
            }, 100);
        });
    }

    function bindFeatureEditPopup(marker, feat) {
        const uniq = feat.id;

        const initialPopupContent = `
            <div class="edit-feature-popup" id="edit-feat-container-${uniq}" style="min-width: 280px;">
                <h4 style="margin: 0 0 10px 0; color: var(--primary-color);">Edit Known Feature</h4>
                <div style="margin-bottom: 10px;">
                    <label style="display:block; font-weight:bold; margin-bottom:4px;">Feature Name:</label>
                    <input type="text" id="edit-feat-name-${uniq}" class="form-control" style="width:100%; padding:5px; box-sizing:border-box;" value="${escapeHtml(feat.name)}">
                </div>

                <div id="edit-feat-selected-img-wrapper-${uniq}" style="margin-bottom: 10px; display: none; text-align: center;">
                    <div style="font-weight:bold; margin-bottom:4px; text-align:left;">Assigned Image:</div>
                    <img id="edit-feat-selected-img-${uniq}" class="map-popup-image" style="width: 100%; height: 120px; object-fit: contain; margin-bottom: 5px; background: #eee; border-radius: 4px;">
                    <button class="btn btn-sm btn-danger" id="edit-feat-unassign-btn-${uniq}" style="width: 100%;">Unassign Image</button>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-weight:bold; margin-bottom:4px;">Select Image (within 1km):</label>
                    <select id="edit-feat-image-select-${uniq}" class="form-select" style="width:100%; padding:5px; box-sizing:border-box;">
                        <option value="">Loading nearby images...</option>
                    </select>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button class="btn btn-sm btn-secondary" id="edit-feat-cancel-btn-${uniq}">Cancel</button>
                    <button class="btn btn-sm btn-success" id="edit-feat-save-btn-${uniq}">Save</button>
                </div>
            </div>
        `;

        marker.bindPopup(initialPopupContent);

        marker.on('popupopen', async function() {
            const imgWrapper = document.getElementById(`edit-feat-selected-img-wrapper-${uniq}`);
            const imgEl = document.getElementById(`edit-feat-selected-img-${uniq}`);
            const unassignBtn = document.getElementById(`edit-feat-unassign-btn-${uniq}`);

            let currentGridimageId = feat.gridimage_id;

            function updateAssignedImageDisplay() {
                if (currentGridimageId && currentGridimageId > 0) {
                    imgWrapper.style.display = 'block';
                    if (feat.gridimage_hash) {
                        imgEl.src = getGeographUrl(currentGridimageId, feat.gridimage_hash, 'med');
                    } else {
                        imgEl.src = `https://www.geograph.org.uk/photo/${currentGridimageId}`;
                    }
                } else {
                    imgWrapper.style.display = 'none';
                    imgEl.src = '';
                }
            }

            updateAssignedImageDisplay();

            if (unassignBtn) {
                unassignBtn.addEventListener('click', function() {
                    currentGridimageId = null;
                    updateAssignedImageDisplay();
                    const selectEl = document.getElementById(`edit-feat-image-select-${uniq}`);
                    if (selectEl) {
                        selectEl.value = "";
                    }
                });
            }

            // Fetch nearby curated images within 1km
            let nearbyImages = [];
            try {
                const res = await fetch(`${API_BASE}?action=get_nearby_curated_images&label=${encodeURIComponent(CURRENT_LABEL)}&lat=${feat.lat}&lng=${feat.lng}`);
                const data = await res.json();
                nearbyImages = data.images || [];
            } catch (err) {
                console.error('Error fetching nearby curated images', err);
            }

            // Populate select dropdown
            const selectEl = document.getElementById(`edit-feat-image-select-${uniq}`);
            if (selectEl) {
                let optionsHtml = '<option value="">-- No Image Selected --</option>';
                nearbyImages.forEach(img => {
                    const isSelected = img.id === currentGridimageId ? 'selected' : '';
                    optionsHtml += `<option value="${img.id}" ${isSelected}>${escapeHtml(img.title)} (ID: ${img.id}, Dist: ${Math.round(img.dist)}m)</option>`;
                });
                selectEl.innerHTML = optionsHtml;

                selectEl.addEventListener('change', function() {
                    const selectedVal = this.value;
                    if (selectedVal) {
                        currentGridimageId = parseInt(selectedVal);
                        const selectedImg = nearbyImages.find(img => img.id === currentGridimageId);
                        if (selectedImg) {
                            feat.gridimage_hash = selectedImg.hash;
                        }
                    } else {
                        currentGridimageId = null;
                    }
                    updateAssignedImageDisplay();
                });
            }

            // Cancel Button
            const cancelBtn = document.getElementById(`edit-feat-cancel-btn-${uniq}`);
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    marker.closePopup();
                });
            }

            // Save Button
            const saveBtn = document.getElementById(`edit-feat-save-btn-${uniq}`);
            if (saveBtn) {
                saveBtn.addEventListener('click', async function() {
                    const newName = document.getElementById(`edit-feat-name-${uniq}`).value.trim();

                    const formData = new FormData();
                    formData.append('action', 'update_feature');
                    formData.append('label', CURRENT_LABEL);
                    formData.append('feature_item_id', feat.id);
                    formData.append('name', newName);
                    formData.append('gridimage_id', currentGridimageId !== null ? currentGridimageId : 'null');

                    try {
                        const res = await fetch(API_BASE, {
                            method: 'POST',
                            body: formData
                        });
                        const data = await res.json();
                        if (data.error) {
                            alert(data.error);
                        } else {
                            feat.name = newName;
                            feat.gridimage_id = currentGridimageId;
                            if (currentGridimageId) {
                                const selectedImg = nearbyImages.find(img => img.id === currentGridimageId);
                                if (selectedImg) {
                                    feat.gridimage_hash = selectedImg.hash;
                                    feat.gridimage_title = selectedImg.title;
                                }
                            } else {
                                feat.gridimage_hash = null;
                                feat.gridimage_title = null;
                            }
                            marker.closePopup();
                            bindFeatureEditPopup(marker, feat);
                        }
                    } catch (err) {
                        console.error('Error updating feature', err);
                        alert('An error occurred while saving the feature.');
                    }
                });
            }
        });
    }

    function renderMapMarkers() {
        if (!map || !markerLayerGroup) return;

        markerLayerGroup.clearLayers();
        const bounds = L.latLngBounds();
        let markerCount = 0;

        const showRaw = el.mapShowRaw ? el.mapShowRaw.checked : true;
        const showShortlisted = el.mapShowShortlisted ? el.mapShowShortlisted.checked : true;
        const showConfirmed = el.mapShowConfirmed ? el.mapShowConfirmed.checked : true;
        const showOutliersOnly = el.mapShowOutliers ? el.mapShowOutliers.checked : false;

        // Collect confirmed items coordinates first for outlier calculation
        const confirmedItems = getConfirmedItems();

        // 1. Plot Confirmed (Green)
        if (showConfirmed) {
            confirmedItems.forEach(item => {
                if (item.lat && item.lng) {
                    const marker = L.marker([item.lat, item.lng], {
                        icon: L.divIcon({
                            className: 'pin pin-green',
                            html: '<div class="pin-inner"></div>'
                        })
                    });

                    // Popup with details & Feature Expansion option
                    const popupHtml = `
                        <div>
                            <img class="map-popup-image" src="${getGeographUrl(item.id, item.hash, 'med')}" loading="lazy">
                            <div class="map-popup-title">${escapeHtml(item.title)}</div>
                            <div style="font-size:12px; margin-bottom:5px;">Feature: <b>${escapeHtml(item.feature)}</b></div>
                            <button class="btn btn-sm btn-primary expand-search-btn" data-lat="${item.lat}" data-lng="${item.lng}">Expand Feature Search (2km)</button>
                        </div>
                    `;
                    marker.bindPopup(popupHtml);

                    marker.on('popupopen', function() {
                        const btn = document.querySelector('.expand-search-btn');
                        if (btn) {
                            btn.addEventListener('click', function() {
                                const lat = parseFloat(this.dataset.lat);
                                const lng = parseFloat(this.dataset.lng);
                                runFeatureExpansion(lat, lng);
                            });
                        }
                    });

                    markerLayerGroup.addLayer(marker);
                    bounds.extend([item.lat, item.lng]);
                    markerCount++;
                }
            });
        }

        // 2. Plot Shortlisted (Yellow)
        if (showShortlisted) {
            getShortlistedItems().forEach(item => {
                if (item.lat && item.lng) {
                    const marker = L.marker([item.lat, item.lng], {
                        icon: L.divIcon({
                            className: 'pin pin-yellow',
                            html: '<div class="pin-inner"></div>'
                        })
                    });

                    const popupHtml = `
                        <div>
                            <img class="map-popup-image" src="${getGeographUrl(item.id, item.hash, 'med')}" loading="lazy">
                            <div class="map-popup-title">${escapeHtml(item.title)}</div>
                            <div style="margin-top:5px; display:flex; gap:5px;">
                                <button class="btn btn-sm btn-success map-confirm-btn" data-id="${item.id}">Confirm</button>
                                <button class="btn btn-sm btn-danger map-exclude-btn" data-id="${item.id}">Exclude</button>
                            </div>
                        </div>
                    `;
                    marker.bindPopup(popupHtml);

                    marker.on('popupopen', function() {
                        document.querySelector('.map-confirm-btn').addEventListener('click', function() {
                            confirmShortlistedImage(parseInt(this.dataset.id), '[Assigned from Map]');
                            map.closePopup();
                        });
                        document.querySelector('.map-exclude-btn').addEventListener('click', function() {
                            excludeImage(parseInt(this.dataset.id));
                            map.closePopup();
                        });
                    });

                    markerLayerGroup.addLayer(marker);
                    bounds.extend([item.lat, item.lng]);
                    markerCount++;
                }
            });
        }

        // 3. Plot Raw Candidates (Red)
        if (showRaw) {
            // Filter out any candidates already in curation map (or marked status 0, 2)
            const rawItems = state.rawCandidates.filter(item => {
                const cur = state.curatedMap.get(item.id);
                return !cur || (cur.active !== 0 && cur.active !== 2 && cur.active !== 1);
            });

            rawItems.forEach(item => {
                if (item.lat && item.lng) {
                    // Check if outlier (distance > 1km to any confirmed item)
                    let isOutlier = true;
                    if (confirmedItems.length > 0) {
                        for (const confirmed of confirmedItems) {
                            const d = computeDistance(item.lat, item.lng, confirmed.lat, confirmed.lng);
                            if (d <= 1) {
                                isOutlier = false;
                                break;
                            }
                        }
                    } else {
                        isOutlier = false; // No clusters yet to be an outlier from
                    }

                    if (showOutliersOnly && !isOutlier) {
                        return; // Skip drawing
                    }

                    const markerClass = `pin pin-red ${isOutlier ? 'pin-outlier' : ''}`;

                    const marker = L.marker([item.lat, item.lng], {
                        icon: L.divIcon({
                            className: markerClass,
                            html: '<div class="pin-inner"></div>'
                        })
                    });

                    const popupHtml = `
                        <div>
                            <img class="map-popup-image" src="${getGeographUrl(item.id, item.hash, 'med')}" loading="lazy">
                            <div class="map-popup-title">${escapeHtml(item.title)}</div>
                            ${isOutlier ? '<div style="color:magenta; font-weight:bold; margin-bottom:5px;">Potential Outlier (>1km)</div>' : ''}
                            <div style="margin-top:5px; display:flex; gap:5px;">
                                <button class="btn btn-sm btn-primary map-shortlist-btn" data-id="${item.id}">Shortlist</button>
                                <button class="btn btn-sm btn-danger map-exclude-btn" data-id="${item.id}">Exclude</button>
                            </div>
                        </div>
                    `;
                    marker.bindPopup(popupHtml);

                    marker.on('popupopen', function() {
                        document.querySelector('.map-shortlist-btn').addEventListener('click', function() {
                            shortlistImage(parseInt(this.dataset.id));
                            map.closePopup();
                        });
                        document.querySelector('.map-exclude-btn').addEventListener('click', function() {
                            excludeImage(parseInt(this.dataset.id));
                            map.closePopup();
                        });
                    });

                    markerLayerGroup.addLayer(marker);
                    bounds.extend([item.lat, item.lng]);
                    markerCount++;
                }
            });
        }

        // 4. Plot Known Features (Default Pins, Blue/standard)
        const showFeatures = el.mapShowFeatures ? el.mapShowFeatures.checked : true;
        if (window.FEATURE_TYPE_ID && showFeatures) {
            state.knownFeatures.forEach(feat => {
                if (feat.lat && feat.lng) {
                    // Use the default Leaflet pin by NOT passing custom divIcon/icon
                    const marker = L.marker([feat.lat, feat.lng], {
                        draggable: true
                    });

                    let originalLatLng = L.latLng(feat.lat, feat.lng);

                    marker.on('dragend', function(e) {
                        const newLatLng = marker.getLatLng();

                        const confirmPopupContent = `
                            <div class="confirm-move-popup" style="min-width: 200px;">
                                <h5 style="margin: 0 0 10px 0;">Move Known Feature?</h5>
                                <p style="font-size: 13px; margin: 0 0 12px 0;">Do you want to move <b>${escapeHtml(feat.name || '[Unnamed]')}</b> to this new location?</p>
                                <div style="display:flex; gap:10px; justify-content:flex-end;">
                                    <button class="btn btn-sm btn-secondary" id="btn-cancel-move-${feat.id}">Cancel</button>
                                    <button class="btn btn-sm btn-primary" id="btn-confirm-move-${feat.id}">Save Location</button>
                                </div>
                            </div>
                        `;

                        marker.bindPopup(confirmPopupContent).openPopup();

                        setTimeout(() => {
                            const btnCancel = document.getElementById(`btn-cancel-move-${feat.id}`);
                            const btnConfirm = document.getElementById(`btn-confirm-move-${feat.id}`);

                            if (btnCancel) {
                                btnCancel.addEventListener('click', function() {
                                    marker.setLatLng(originalLatLng);
                                    marker.closePopup();
                                    bindFeatureEditPopup(marker, feat);
                                });
                            }

                            if (btnConfirm) {
                                btnConfirm.addEventListener('click', async function() {
                                    const formData = new FormData();
                                    formData.append('action', 'update_feature_coords');
                                    formData.append('label', CURRENT_LABEL);
                                    formData.append('feature_item_id', feat.id);
                                    formData.append('wgs84_lat', newLatLng.lat);
                                    formData.append('wgs84_long', newLatLng.lng);

                                    try {
                                        const res = await fetch(API_BASE, {
                                            method: 'POST',
                                            body: formData
                                        });
                                        const data = await res.json();
                                        if (data.error) {
                                            alert(data.error);
                                            marker.setLatLng(originalLatLng);
                                        } else {
                                            originalLatLng = newLatLng;
                                            feat.lat = newLatLng.lat;
                                            feat.lng = newLatLng.lng;
                                            bindFeatureEditPopup(marker, feat);
                                            marker.closePopup();
                                        }
                                    } catch (err) {
                                        console.error('Error updating feature coords', err);
                                        alert('An error occurred while moving the feature.');
                                        marker.setLatLng(originalLatLng);
                                    }
                                });
                            }
                        }, 50);
                    });

                    bindFeatureEditPopup(marker, feat);

                    markerLayerGroup.addLayer(marker);
                    bounds.extend([feat.lat, feat.lng]);
                    markerCount++;
                }
            });
        }

        if (el.mapMarkerCount) el.mapMarkerCount.textContent = markerCount;

        // Only call fitBounds automatically the very first time markers are loaded to avoid losing user's focus/zoom
        if (isFirstMapLoad && markerCount > 0 && bounds.isValid()) {
            map.fitBounds(bounds, { maxZoom: 14, padding: [20, 20] });
            isFirstMapLoad = false;
        }
    }

    // Explicitly fit map to markers on user click
    function fitMapToMarkers() {
        if (!map) return;
        const bounds = L.latLngBounds();
        let markerCount = 0;

        getConfirmedItems().forEach(item => {
            if (item.lat && item.lng) {
                bounds.extend([item.lat, item.lng]);
                markerCount++;
            }
        });

        getShortlistedItems().forEach(item => {
            if (item.lat && item.lng) {
                bounds.extend([item.lat, item.lng]);
                markerCount++;
            }
        });

        state.rawCandidates.forEach(item => {
            if (item.lat && item.lng) {
                bounds.extend([item.lat, item.lng]);
                markerCount++;
            }
        });

        if (markerCount > 0 && bounds.isValid()) {
            map.fitBounds(bounds, { maxZoom: 14, padding: [20, 20] });
        }
    }

    // Handle Map MoveEnd to fetch additional images inside the current viewport
    async function handleMapMoveEnd() {
        if (!map) return;
        if (!el.mapLiveUpdate || !el.mapLiveUpdate.checked) return;
        if (el.aiVectorCheckbox && el.aiVectorCheckbox.checked) return;

        // Only load if there are more results than currently loaded in state.rawCandidates
        if (state.totalFound <= state.rawCandidates.length) {
            console.log("Map Live Update skipped: all matching candidates already loaded.");
            return;
        }

        const query = el.rawSearchInput.value.trim();
        if (!query) return;

        const bounds = map.getBounds();
        const olbounds = bounds.toBBoxString();

        // Prepare lists of excluded/confirmed/shortlisted IDs to pass to Sphinx `not in` query
        const excludedOrConfirmedIds = Array.from(state.curatedMap.values())
            .filter(item => item.active === 0 || item.active === 2)
            .map(item => item.id);

        let params = {
            select: 'id,user_id,realname,grid_reference,title,hash,wgs84_lat,wgs84_long',
            utf: '1',
            limit: '150',
            match: query,
            order: 'sequence asc',
            olbounds: olbounds
        };

        if (excludedOrConfirmedIds.length > 0) {
            params['where'] = `id not in (${excludedOrConfirmedIds.join(',')})`;
        }

        try {
            const fullUrl = `/api-facetql.php?${new URLSearchParams(params).toString()}`;
            const res = await fetch(fullUrl);
            const data = await res.json();

            if (data.rows && data.rows.length > 0) {
                const existingIds = new Set(state.rawCandidates.map(c => c.id));
                let addedCount = 0;

                data.rows.forEach(row => {
                    const id = parseInt(row.id);
                    if (!existingIds.has(id)) {
                        state.rawCandidates.push({
                            id: id,
                            user_id: parseInt(row.user_id),
                            realname: row.realname,
                            title: row.title,
                            hash: row.hash,
                            lat: rad2deg(row.wgs84_lat ?? 0),
                            lng: rad2deg(row.wgs84_long ?? 0)
                        });
                        existingIds.add(id);
                        addedCount++;
                    }
                });

                if (addedCount > 0) {
                    console.log(`Live update loaded ${addedCount} new candidates in view.`);
                    // Refresh both triage and map views
                    renderRawCandidates();
                    renderMapMarkers();
                }
            }
        } catch (e) {
            console.error('Error in map live update', e);
        }
    }

    // Simple distance calculation in km between coordinates
    function computeDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth's radius in km
        const dLat = deg2rad(lat2 - lat1);
        const dLon = deg2rad(lon2 - lon1);
        const a =
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    function deg2rad(deg) {
        return deg * (Math.PI/180);
    }

    // Feature Expansion: Radius query around confirmed item
    function runFeatureExpansion(lat, lng) {
        // Construct radius parameter (latitude, longitude, distance in meters)
        const geoParam = `${lat},${lng},2000`; // 2km radius

        // Populate search box with spatial parameter and trigger search
        if (el.rawSearchInput) {
            el.rawSearchInput.value = `${CURRENT_LABEL}`;
        }
        if (el.aiVectorCheckbox) {
            el.aiVectorCheckbox.checked = false; // standard search for spatial precision
        }
        if (el.aiVectorModel) {
            el.aiVectorModel.style.display = 'none';
        }

        // Direct search function
        if (el.rawSearchBtn) {
            el.rawSearchBtn.click();
        }

        // Switch back to Triage Workstation tab
        const triageTab = document.querySelector('.nav-btn[data-tab="triage"]');
        if (triageTab) triageTab.click();
    }

    // Render All Active Views Helper
    function renderAllViews() {
        renderRawCandidates();
        renderShortlisted();
        renderUnassignedSequential();
        renderHotNotSlideshow();

        const mapContainer = document.getElementById('curation-map');
	if (isVisible(mapContainer))
            renderMapMarkers();
    }

    // Global Search trigger
    if (el.rawSearchBtn) {
        el.rawSearchBtn.addEventListener('click', performRawSearch);
    }
    if (el.rawSearchInput) {
        el.rawSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performRawSearch();
            }
        });
    }

    // Initial Workstation Load
    (async function init() {
        await fetchCurationState();
        if (window.FEATURE_TYPE_ID) {
            await fetchKnownFeatures();
        }
        renderAllViews();
        // Run default search on active label on startup
        performRawSearch();
    })();

});

function isVisible(element) {
  if (!element) return false;

  // Handles elements, parents with display: none, and elements not in the DOM
  return !!(
    element.offsetWidth ||
    element.offsetHeight ||
    element.getClientRects().length
  );
}


