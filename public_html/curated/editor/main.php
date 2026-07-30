<?php
/**
 * main.php - Visual and Spatial Curation Triage Workstation
 */

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;
$USER->mustHavePerm("basic");
customNoCacheHeader();

$label = $_GET['label'] ?? 'Sea Arches';

$smarty->display('_std_begin.tpl');
?>

<div class="curator-workspace">
    <!-- Workspace Header -->
    <div class="workspace-header">
        <div class="header-main">
            <h1>Spatial Curation Workstation</h1>
            <div class="label-info">
                Active Label: <span class="badge" id="active-label-badge"><?php echo htmlentities($label); ?></span>
            </div>
        </div>
        <div class="workspace-nav">
            <button class="nav-btn active" data-tab="triage">Triage Workstation</button>
            <button class="nav-btn" data-tab="spatial-map">Spatial Map</button>
            <button class="nav-btn" data-tab="unassigned-triage">Unassigned Queue <span class="badge-count" id="unassigned-badge">0</span></button>
            <button class="nav-btn" data-tab="hot-not">Review Queue <span class="badge-count" id="hot-not-badge">0</span></button>
            <button class="nav-btn" data-tab="reports">Reports & Progress</button>
        </div>
    </div>

    <!-- Tab Contents -->
    <div class="tab-contents-container">

        <!-- TAB 1: TRIAGE WORKSTATION -->
        <div class="tab-pane active" id="tab-triage">
            <div class="triage-columns">
                <!-- Column 1: Raw Search Results -->
                <div class="triage-col raw-candidates-col">
                    <div class="col-header">
                        <h2>1. Raw Candidates (Sphinx Engine)</h2>
                        <div class="search-form">
                            <input type="text" id="raw-search-input" value="<?php echo htmlentities($label); ?>" placeholder="Search query...">
                            <button class="btn btn-primary" id="raw-search-btn">Search</button>
                        </div>
                        <div class="search-options">
                            <label><input type="checkbox" id="ai-vector-checkbox"> AI Vector Search</label>
                            <select id="ai-vector-model" class="form-select" style="display:none;">
                                <option value="clip">CLIP Model</option>
                                <option value="pe">Perception Encoder</option>
                            </select>
                        </div>
                    </div>
                    <div class="results-list" id="raw-candidates-list">
                        <div class="loading-placeholder">Enter a search query or click Search to fetch candidates.</div>
                    </div>
                </div>

                <!-- Column 2: Pending Shortlist / Feature Assignment -->
                <div class="triage-col shortlist-col">
                    <div class="col-header">
                        <h2>2. Pending Shortlist / Feature Resolver</h2>
                        <div class="bulk-actions" style="margin-bottom: 10px;">
                            <button class="btn btn-secondary btn-sm" id="clear-shortlist-btn">Clear Shortlist</button>
                        </div>
                    </div>
                    <div class="results-list" id="shortlisted-list">
                        <div class="loading-placeholder">No shortlisted candidates. Drag items here or double-click to shortlist them.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: SPATIAL MAP -->
        <div class="tab-pane" id="tab-spatial-map">
            <div class="map-controls" style="display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap; background-color: var(--header-bg); padding: 10px; border-radius: 4px; border: 1px solid var(--border-color);">
                <div class="filter-group" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <label><input type="checkbox" id="map-show-raw" checked> Show Raw Candidates (Red)</label>
                    <label><input type="checkbox" id="map-show-shortlisted" checked> Show Shortlisted (Yellow)</label>
                    <label><input type="checkbox" id="map-show-confirmed" checked> Show Confirmed (Green)</label>
                    <label><input type="checkbox" id="map-show-outliers"> Highlight Outliers Only (>10km away)</label>
                    <label id="map-live-update-container" style="color: var(--accent-color); font-weight: bold;"><input type="checkbox" id="map-live-update"> Live Update (OS Bounds)</label>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button class="btn btn-secondary btn-sm" id="map-fit-bounds-btn">Show All (Fit to Markers)</button>
                    <div class="map-stats" style="font-weight: 600;">
                        Total Map Markers: <span id="map-marker-count">0</span>
                    </div>
                </div>
            </div>
            <div id="curation-map" style="height: 650px; width: 100%; border-radius: 8px; border: 1px solid #ccc; margin-top: 10px;"></div>
        </div>

        <!-- TAB 3: UNASSIGNED FEATURE TRIAGE -->
        <div class="tab-pane" id="tab-unassigned-triage">
            <div class="triage-sequential-container" id="unassigned-seq-view">
                <div class="sequential-placeholder">No unassigned images found. Add candidates to the shortlist and feature-resolver to curate features!</div>
            </div>
        </div>

        <!-- TAB 4: HOT OR NOT REVIEW QUEUE -->
        <div class="tab-pane" id="tab-hot-not">
            <div class="triage-sequential-container" id="hot-not-seq-view">
                <div class="sequential-placeholder">No pending suggestions for review. Shortlist candidates first to load them here!</div>
            </div>
        </div>

        <!-- TAB 5: REPORTS -->
        <div class="tab-pane" id="tab-reports">
            <div class="report-container">
                <h2>Curation Reporting: <?php echo htmlentities($label); ?></h2>
                <div class="report-actions">
                    <button class="btn btn-secondary" id="refresh-report-btn">Refresh Report</button>
                </div>
                <div class="report-table-wrapper" style="margin-top: 20px;">
                    <table class="report-table" id="report-table">
                        <thead>
                            <tr>
                                <th>Feature / Place Name</th>
                                <th>Total Confirmed Photos (active=2)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="report-table-body">
                            <tr>
                                <td colspan="3" class="text-center">Click Refresh to generate the report.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Leaflet CSS & JS dependencies -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Curation App stylesheet -->
<link rel="stylesheet" href="<?php echo smarty_modifier_revision("/curated/editor/styles.css"); ?>">

<!-- Geograph and Curation dependencies -->
<script src="<?php echo smarty_modifier_revision("/js/geograph-api-libs.js"); ?>"></script>
<script src="<?php echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>

<!-- Inline variables declaration -->
<script>
    window.CURRENT_LABEL = <?php echo json_encode($label); ?>;
</script>

<!-- Curation App scripts -->
<script src="<?php echo smarty_modifier_revision("/curated/editor/javascript.js"); ?>"></script>

<?php
$smarty->display('_std_end.tpl');
?>
