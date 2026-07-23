<?php
// ============================================================================
// CONFIGURATION & DATABASE CONNECTION
// ============================================================================

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("director");

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


// ============================================================================
// NAVIGATION SETUP
// ============================================================================

$nav_items = [
    'bots.php'         => 'Identifiable User-Agents',
    'bots-api.php'     => 'API Analyzer',
    'bots-stealth.php' => 'Stealth Crawlers (coming-soon)',
    'bots-images.php'  => 'Image Harvesting (coming-soon)',
];

$current_page = basename($_SERVER['PHP_SELF']);

// ============================================================================
// INPUT HANDLING & STATE MANAGEMENT
// ============================================================================

$filter_key_status = $_GET['key_status'] ?? 'all'; // 'all', 'has_key', 'no_key'
$filter_device     = $_GET['device'] ?? '';
$filter_apikey     = $_GET['apikey'] ?? '';
$filter_ident      = $_GET['ident'] ?? '';
$sort_order        = $_GET['sort'] ?? 'recent'; 
$view_mode         = $_GET['view_mode'] ?? 'apikey'; // 'apikey' or 'ident'
$page_size         = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$filter_min_hits = isset($_GET['min_hits']) ? (int)$_GET['min_hits'] : 0;
$days            = isset($_GET['days']) ? (int)$_GET['days'] : 0;

if (!in_array($page_size, [10, 100, 1000, 10000])) {
    $page_size = 10;
}

function makeUrl($modifications) {
    $current = $_GET;
    foreach ($modifications as $k => $v) {
        if ($v === null) {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    return '?' . http_build_query($current);
}

// ============================================================================
// BASE WHERE CLAUSE 
// ============================================================================
$base_wheres = ["1=1"];
/*
if ($filter_key_status === 'has_key') {
    $base_wheres[] = "apikey IS NOT NULL AND apikey != ''";
} elseif ($filter_key_status === 'no_key') {
    $base_wheres[] = "(apikey IS NULL OR apikey = '')";
}*/

if ($view_mode === 'ident') {
    if ($filter_device !== '') {
        $base_wheres[] = "device = " . $db->qstr($filter_device);
    } elseif (isset($_GET['device'])) {
        $base_wheres[] = "(device = '')"; //NOT null, because that will be all the unclassified stuff!
    }
}

if ($filter_apikey !== '') {
    $base_wheres[] = "apikey = " . $db->qstr($filter_apikey);
}

if ($filter_ident !== '') {
    $base_wheres[] = "ident = " . $db->qstr($filter_ident);
}

// Having clause to filter out clusters/keys with few hits
$having_clause = "";
if ($filter_min_hits > 0) {
    $having_clause = " HAVING total_hits >= " . (int)$filter_min_hits;
}

// ============================================================================
// FETCH SIDEBAR COUNTS (Dynamic pill counts for labels)
// ============================================================================
$device_counts = [];

if ($view_mode === 'ident') {
    // Fetch distinct devices with counts based on current filters (except device itself)
    $dev_wheres = ["device IS NOT NULL"]; //NOT null, because that will be all the unclassified stuff!

    $dev_res = $db->Execute("SELECT device, COUNT(*) as cnt FROM api_all_time WHERE " . implode(" AND ", $dev_wheres) . " GROUP BY device");
    if ($dev_res) {
        while ($row = $dev_res->FetchRow()) {
            $device_counts[$row['device'] ?? 'NULL'] = $row['cnt'];
        }
    }
}

// ============================================================================
// DETERMINING SORT ORDER
// ============================================================================
switch ($sort_order) {
    case 'key_age':
	if ($view_mode === 'apikey') {
	        $orderby = "ORDER BY COALESCE(k.crt_timestamp,first_hour) DESC";
        	break;
	}
	//otherwise falls though... 
    case 'normalized_week':
        $orderby = "ORDER BY (SUM(hits) / GREATEST(1, TIMESTAMPDIFF(HOUR, MIN(first_hour), MAX(last_hour)))) * 168 DESC";
        break;
    case 'new_activity':
        $orderby = "ORDER BY MIN(first_hour) DESC";
        break;
    case 'avg_usage':
        $orderby = "ORDER BY SUM(hits)/SUM(hours) DESC";
        break;
    case 'most_active':
        $orderby = "ORDER BY SUM(hits) DESC";
        break;
    case 'recent':
    default:
        $orderby = "ORDER BY MAX(last_hour) DESC";
        break;
}

// ============================================================================
// DATA FETCH: MAIN QUERY
// ============================================================================
$where_clause = " WHERE " . implode(" AND ", $base_wheres);

if ($view_mode === 'ident') {
    // Mode A: Cluster missing keys by user-agent/ip/referer compound footprint
    $main_sql = "SELECT
                    ident, fragment,
                    SUM(hits) AS total_hits,
                    SUM(hours) AS total_hours,
                    AVG(hits/hours) AS avg_usage,
                    MIN(first_hour) AS earliest_seen,
                    MAX(last_hour) AS latest_seen,
                    (SUM(hits) / GREATEST(1, TIMESTAMPDIFF(HOUR, MIN(first_hour), MAX(last_hour)))) * 168 AS hits_per_week
                 FROM api_all_time
                 $where_clause AND ident IS NOT NULL
                 GROUP BY COALESCE(fragment,ident)
		 $having_clause
                 $orderby
                 LIMIT $page_size";

	if (!empty($days)) {
        	//make a fake table as though it only loking at last 2 days
	        $main_sql = "WITH api_all_time AS (
        	        SELECT a.id,ident,fragment,SUM(h.hits) AS hits, COUNT(*) AS hours,
                	        min(hour) AS first_hour, max(hour) as last_hour, device
	                FROM api_all_time a INNER JOIN api_by_hour h USING (ident)
        	        WHERE hour > date(date_sub(now(),interval $days day)) AND h.ident IS NOT NULL GROUP BY ident
	        )
        	$main_sql";
	}

} else {
    // Mode B: Group by API Key
    $main_sql = "SELECT
                    apikey,
		    DATEDIFF(NOW(), k.crt_timestamp) AS key_age_days,
                    SUM(hits) AS total_hits,
                    SUM(hours) AS total_hours,
                    AVG(hits/hours) AS avg_usage,
                    MIN(first_hour) AS earliest_seen,
                    MAX(last_hour) AS latest_seen,
                    (SUM(hits) / GREATEST(1, TIMESTAMPDIFF(HOUR, MIN(first_hour), MAX(last_hour)))) * 168 AS hits_per_week
                 FROM api_all_time
		 LEFT JOIN apikeys k USING (apikey)
                 $where_clause AND apikey IS NOT NULL
                 GROUP BY apikey
		 $having_clause
                 $orderby
                 LIMIT $page_size";

	if (!empty($days)) {
        	//make a fake table as though it only loking at last 2 days
	        $main_sql = "WITH api_all_time AS (
        	        SELECT a.id,apikey,SUM(h.hits) AS hits, COUNT(*) AS hours,
                	        min(hour) AS first_hour, max(hour) as last_hour
	                FROM api_all_time a INNER JOIN api_by_hour h USING (apikey)
        	        WHERE hour > date(date_sub(now(),interval $days day)) AND h.apikey IS NOT NULL GROUP BY apikey
	        )
        	$main_sql";
	}
}


$results = $db->Execute($main_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API Traffic Analyzer</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 20px; background-color: #f8f9fa; color: #333; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        .control-panel { background: #fff; border: 1px solid #e1e4e6; padding: 15px; border-radius: 6px; margin-bottom: 2px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .control-group { margin-bottom: 12px; display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
        .control-label { font-weight: bold; min-width: 140px; font-size: 0.9rem; }
        .pill { display: inline-block; padding: 4px 10px; background: #e9ecef; border: 1px solid #ced4da; text-decoration: none; color: #495057; border-radius: 16px; font-size: 0.85rem; transition: all 0.2s; }
        .pill:hover { background: #dee2e6; }
        .pill.active { background: #007bff; color: #fff; border-color: #007bff; }
        .review-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #e1e4e6; table-layout: fixed; }
        .review-table th, .review-table td { padding: 8px 11px; border-bottom: 1px solid #e1e4e6; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; }
        .review-table th { background-color: #f1f3f5; color: #495057; font-weight: 600; }
        .review-table tr:hover { background-color: #f8f9fa; }
        .badge { background: #e2e8f0; color: #4a5568; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-weight: 500; white-space:nowrap }
        .text-muted { color: #6c757d; font-size: 0.85rem; }
        .ident-string { font-family: monospace; font-size: 0.8rem; word-break: break-all; }

        /* Disclaimer Card Styling */
        .disclaimer-card { background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #d97706; padding: 15px; border-radius: 6px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .disclaimer-title { font-weight: bold; color: #b45309; font-size: 0.95rem; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .disclaimer-text { font-size: 0.85rem; color: #78350f; line-height: 1.45; }
        .disclaimer-text ul { margin: 6px 0 0 18px; padding: 0; }
        .disclaimer-text li { margin-bottom: 4px; }
    </style>
</head>
<body>

    <h3>API Request/Traffic Analyzer</h3>

    <div class="control-panel" style="margin-bottom: 20px; background-color: #e9ecef; border-color: #ced4da;">
        <div class="control-group" style="margin-bottom: 0;">
            <span class="control-label" style="min-width: 120px;">Analyzer Scope:</span>
            <?php foreach ($nav_items as $file => $label): ?>
                <?php if (strpos($label, 'coming-soon') !== false): ?>
                    <span class="pill" style="opacity: 0.6; cursor: not-allowed;" title="Coming Soon">
                        <?= htmlspecialchars($label, ENT_QUOTES) ?>
                    </span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($file, ENT_QUOTES) ?>" class="pill <?= $current_page === $file ? 'active' : '' ?>">
                        <?= htmlspecialchars($label, ENT_QUOTES) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($_GET)): ?>
    <div class="disclaimer-card">
        <div class="disclaimer-title">
            API Traffic Clustering Advisory
        </div>
        <div class="disclaimer-text">
            This dashboard helps monitor API usage, highlighting registered consumer profiles against anonymous requests:
            <ul>
		<li><strong>API Traffic Segmentation:</strong> Focuses solely on traffic to primary APIs. Requests containing an API key are clustered separately from keyless 
		requests.</li>

		<li><strong>Metadata &amp; API Scope:</strong> This dashboard tracks purely API or metadata queries and does not include requests fetching physical image assets. 
		Furthermore, it does not capture automated crawlers harvesting standard HTML pages; tracking for website scraping - particularly stealth operations running without a 
		distinguishable User-Agent is handled separately.</li>

		<li><strong>Limited Historical Window:</strong> Data processing is currently only backfilled to early July 2026. While older raw log data exists, it has not yet been 
		processed or imported into this view.</li>

		<li><strong>Normalized Volume Metric:</strong> The "Hits / Week" column is a calculated estimate of sustained weekly volume (normalized to 168 hours). The actual 
		physical number of requests logged may be much lower if the client was only active for a short period; refer to the "Sum (Hits)" column for the literal absolute 
		totals.</li>

                <li><strong>Unauthenticated Key Grouping:</strong> Grouping by API key relies strictly on the self-reported <code>key</code>. Because we do 
                not authenticate these keys, using purely for tracking; generic or leaked keys may well represent multiple unrelated actors.</li>

                <li><strong>Keyless Footprint Detection:</strong> Keyless requests often include crawlers, scrapers, or third-party tools that have discovered the API endpoints. 
                While we actively attempt to filter out standard browser traffic (including internal API usage from our own site), some generic browser footprints may still slip 
                through.</li>

                <li><strong>Historical Block Status:</strong> This dataset includes traffic recorded *before* block rules or firewall restrictions were implemented. Some of these bots may already be actively blocked; this dashboard does not attempt to filter out or separate currently blocked actors.</li>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <div class="control-panel">
        <!-- View Mode Filter -->
        <div class="control-group">
            <span class="control-label">View Style:</span>
            <a href="<?= makeUrl(['view_mode' => 'apikey', 'key_status' => 'has_key']) ?>" class="pill <?= $view_mode === 'apikey' ? 'active' : '' ?>">Group By API Key</a>
            <a href="<?= makeUrl(['view_mode' => 'ident', 'key_status' => 'no_key']) ?>" class="pill <?= $view_mode === 'ident' ? 'active' : '' ?>">Keyless Requests</a>
        </div>

        <!-- Sort Rule Engine -->
        <div class="control-group">
            <span class="control-label">Sort By Order:</span>
            <a href="<?= makeUrl(['sort' => 'recent']) ?>" class="pill <?= $sort_order === 'recent' ? 'active' : '' ?>">Recent Active</a>
            <?php if ($view_mode === 'apikey'): ?>
                <a href="<?= makeUrl(['sort' => 'key_age']) ?>" class="pill <?= $sort_order === 'key_age' ? 'active' : '' ?>">Key Age (Newest)</a>
            <?php endif; ?>
            <a href="<?= makeUrl(['sort' => 'normalized_week']) ?>" class="pill <?= $sort_order === 'normalized_week' ? 'active' : '' ?>">Sustained Vol (Hits/Week)</a>
            <a href="<?= makeUrl(['sort' => 'new_activity']) ?>" class="pill <?= $sort_order === 'new_activity' ? 'active' : '' ?>">New Activity</a>
            <a href="<?= makeUrl(['sort' => 'avg_usage']) ?>" class="pill <?= $sort_order === 'avg_usage' ? 'active' : '' ?>">Avg Usage (Hits/Hr)</a>
            <a href="<?= makeUrl(['sort' => 'most_active']) ?>" class="pill <?= $sort_order === 'most_active' ? 'active' : '' ?>">Most Active Hits</a>
        </div>

        <!-- Key Status Filter -->
        <div class="control-group" style=display:none>
            <span class="control-label">Key Status:</span>
            <a href="<?= makeUrl(['key_status' => 'all']) ?>" class="pill <?= $filter_key_status === 'all' ? 'active' : '' ?>">All Statuses</a>
            <a href="<?= makeUrl(['key_status' => 'has_key']) ?>" class="pill <?= $filter_key_status === 'has_key' ? 'active' : '' ?>">Has Registered Key</a>
            <a href="<?= makeUrl(['key_status' => 'no_key']) ?>" class="pill <?= $filter_key_status === 'no_key' ? 'active' : '' ?>">Missing / Keyless Traffic</a>
        </div>

        <!-- Dynamic Filter Status Indicators -->
        <?php if (!empty($filter_apikey) || !empty($filter_ident)): ?>
        <div class="control-group">
            <span class="control-label">Active Filters:</span>
            <?php if (!empty($filter_apikey)): ?>
                <span class="pill active">
                    Key: <?= htmlspecialchars($filter_apikey, ENT_QUOTES) ?>
                    <a href="<?= makeUrl(['apikey' => null]) ?>" style="color:#fff; margin-left:8px; font-weight:bold; text-decoration:none;">X</a>
                </span>
            <?php endif; ?>
            <?php if (!empty($filter_ident)): ?>
                <span class="pill active">
                    Ident Footprint Restructured
                    <a href="<?= makeUrl(['ident' => null]) ?>" style="color:#fff; margin-left:8px; font-weight:bold; text-decoration:none;">X</a>
                </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Device Filter -->
	<?php if ($view_mode === 'ident'): ?>
        <div class="control-group">
            <span class="control-label">Device Filter:</span>
            <a href="<?= makeUrl(['device' => null]) ?>" class="pill <?= empty($filter_device) ? 'active' : '' ?>">ALL</a>
            <?php foreach ($device_counts as $dev => $count): ?>
                <?php $label = ($dev === 'NULL' || $dev === '') ? 'Unspecified' : $dev; ?>
                <a href="<?= makeUrl(['device' => $dev === 'NULL' ? '' : $dev]) ?>" class="pill <?= $filter_device === ($dev === 'NULL' ? '' : $dev) ? 'active' : '' ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES) ?>
                    <span class="count">(<?= $count ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
	<?php endif; ?>

        <!-- Minimum Hits Filter -->
        <div class="control-group">
            <span class="control-label">Min Volume Filter:</span>
            <a href="<?= makeUrl(['min_hits' => null]) ?>" class="pill <?= $filter_min_hits === 0 ? 'active' : '' ?>">Show All</a>
            <a href="<?= makeUrl(['min_hits' => 10]) ?>" class="pill <?= $filter_min_hits === 10 ? 'active' : '' ?>">&gt;= 10 Hits</a>
            <a href="<?= makeUrl(['min_hits' => 100]) ?>" class="pill <?= $filter_min_hits === 100 ? 'active' : '' ?>">&gt;= 100 Hits</a>
            <a href="<?= makeUrl(['min_hits' => 1000]) ?>" class="pill <?= $filter_min_hits === 1000 ? 'active' : '' ?>">&gt;= 1,000 Hits</a>
            <a href="<?= makeUrl(['min_hits' => 10000]) ?>" class="pill <?= $filter_min_hits === 10000 ? 'active' : '' ?>">&gt;= 10,000 Hits</a>
        </div>

        <div class="control-group">
            <span class="control-label">Days:</span>
            <a href="<?= makeUrl(['days' => null]) ?>" class="pill <?= !$days ? 'active' : '' ?>">All Time</a>
            <?php foreach ([2, 7, 10, 30, 60, 90] as $size): ?>
                <a href="<?= makeUrl(['days' => $size]) ?>" class="pill <?= $days === $size ? 'active' : '' ?>"><?= $size ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Page Limit Sizes -->
        <div class="control-group">
            <span class="control-label">Page Size:</span>
            <?php foreach ([10, 100, 1000, 10000] as $size): ?>
                <a href="<?= makeUrl(['limit' => $size]) ?>" class="pill <?= $page_size === $size ? 'active' : '' ?>"><?= $size ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Results Processing Table -->
    <table class="review-table">
        <caption><?= number_format($results ? $results->numRows() : 0, 0); ?> Groups Displayed</caption>
        <thead>
            <tr>
                <?php if ($view_mode === 'ident'): ?>
                    <th style="width: 40%;">Compound Client footprint (Ident)</th>
                    <th style="width: 8%;">Sum (Hits)</th>
                    <th style="width: 5%;">Hits / Week</th>
                    <th style="width: 5%;">Sum (Hours)</th>
                    <th style="width: 5%;">Avg (Hits/Hr)</th>
                    <th style="width: 8%;">Earliest Seen</th>
                    <th style="width: 8%;">Latest Seen</th>
                <?php else: ?>
                    <th style="width: 15%;">API Key</th>
                    <th style="width: 8%;">Key Age</th>
                    <th style="width: 8%;">Sum (Hits)</th>
                    <th style="width: 10%;">Hits / Week</th>
                    <th style="width: 10%;">Sum (Hours)</th>
                    <th style="width: 10%;">Avg (Hits/Hr)</th>
                    <th style="width: 12%;">Earliest Seen</th>
                    <th style="width: 12%;">Latest Seen</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($results && !$results->EOF): ?>
                <?php while ($row = $results->FetchRow()): ?>
                    <tr>
                        <?php if ($view_mode === 'ident'): ?>
                            <!-- Grouped by footprint (Ident) -->
                            <td class="ident-string" title="<?= htmlspecialchars($row['ident'] ?? '', ENT_QUOTES) ?>">
                                <strong><?= str_replace($row['fragment'],'<u>'.$row['fragment'].'</u>',htmlspecialchars($row['ident'] ?? 'NULL', ENT_QUOTES)) ?></strong>
                            </td>
                            <td align="right"><span class="badge" style="background:#e1f5fe; color:#0288d1; font-weight:bold;"><?= number_format($row['total_hits']) ?></span></td>
                            <td align="right"><span style="color: #6200ea;<? if ($row['total_hits'] > $row['hits_per_week']) { echo ';font-weight:bold'; } ?>"><?= number_format($row['hits_per_week']) ?></span></td>
                            <td align="right"><?= number_format($row['total_hours']) ?> hrs</td>
                            <td align="right"><strong><?= number_format($row['avg_usage']) ?></strong></td>
                            <td><span class="text-muted"><?= htmlspecialchars(str_replace(':00:00','',$row['earliest_seen'] ?? ''), ENT_QUOTES) ?></span></td>
                            <td><span class="text-muted"><?= htmlspecialchars(str_replace(':00:00','',$row['latest_seen'] ?? ''), ENT_QUOTES) ?></span></td>
                        <?php else: ?>
                            <!-- Grouped by API Key -->
                            <td>
                                <a href="<?= makeUrl(['view_mode' => 'ident', 'apikey' => $row['apikey']]) ?>" title="Drill into footprints using this key">
                                    <strong style="font-family: monospace;"><?= htmlspecialchars($row['apikey'] ?: '-empty-', ENT_QUOTES) ?></strong>
                                </a>
                            </td>
                            <td align="right">
                                <span class="text-muted">
                                    <?= isset($row['key_age_days']) ? number_format($row['key_age_days']) . ' days' : '' ?>
                                </span>
                            </td>
                            <td align="right"><span class="badge" style="background:#e8f5e9; color:#2e7d32; font-weight:bold;"><?= number_format($row['total_hits']) ?></span></td>
                            <td align="right"><span style="color: #6200ea; <? if ($row['total_hits'] > $row['hits_per_week']) { echo ';font-weight:bold'; } ?>"><?= number_format($row['hits_per_week']) ?></span></td>
                            <td align="right"><?= number_format($row['total_hours']) ?> hrs</td>
                            <td align="right"><strong><?= number_format($row['avg_usage']) ?></strong></td>
                            <td><span class="text-muted"><?= htmlspecialchars(str_replace(':00:00','',$row['earliest_seen'] ?? ''), ENT_QUOTES) ?></span></td>
                            <td><span class="text-muted"><?= htmlspecialchars(str_replace(':00:00','',$row['latest_seen'] ?? ''), ENT_QUOTES) ?></span></td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 30px; color: #868e96;">No matching records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?php if ($view_mode === 'apikey') { ?>
	<p>Note, the "geograph_demo" API key is used by our Coverage Map (not sure why never gave it a proper key), isnt nesserailly just usage by others. We havent exluded where our code uses an API key!
<? } ?>
</body>
</html>
