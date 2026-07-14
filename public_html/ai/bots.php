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
// INPUT HANDLING & STATE MANAGEMENT
// ============================================================================

$filter_device   = $_GET['device'] ?? '';
$filter_purpose  = $_GET['purpose'] ?? '';
$filter_fragment = $_GET['fragment'] ?? '';
$filter_bot      = isset($_GET['bot']) && $_GET['bot'] !== '' ? (int)$_GET['bot'] : 'all'; // 'all', 0, or 1
$sort_order      = $_GET['sort'] ?? 'recent'; 
$view_mode       = $_GET['view_mode'] ?? 'individual'; // 'individual' or 'fragment_group'
$page_size       = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Optional column display states (defaulting to 0/hidden to keep view clean, togglable via UI)
$show_version    = isset($_GET['show_version']) ? (int)$_GET['show_version'] : 0;
$show_website    = isset($_GET['show_website']) ? (int)$_GET['show_website'] : 0;
$show_url        = isset($_GET['show_url']) ? (int)$_GET['show_url'] : 0;
$show_email      = isset($_GET['show_email']) ? (int)$_GET['show_email'] : 0;
$show_headless   = isset($_GET['show_headless']) ? (int)$_GET['show_headless'] : 0;

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
// BASE WHERE CLAUSE (Always exclude the junk record)
// ============================================================================
$base_wheres = ["useragent != '-'","device IS NOT NULL"];

if ($filter_device !== '') {
    $base_wheres[] = "device = " . $db->qstr($filter_device);
} elseif (isset($_GET['device'])) {
    $base_wheres[] = "(device = '')"; //NOT null, because that will be all the unclassified stuff!
}

if ($filter_purpose !== '') {
    $base_wheres[] = "purpose = " . $db->qstr($filter_purpose);
} elseif (isset($_GET['purpose'])) {
    $base_wheres[] = "(purpose = '' OR purpose IS NULL)";
}

if ($filter_fragment !== '') {
    $base_wheres[] = "fragment = " . $db->qstr($filter_fragment);
} elseif (isset($_GET['fragment'])) {
    $base_wheres[] = "(fragment = '' OR fragment IS NULL)";
}
if ($filter_bot !== 'all') {
    $base_wheres[] = "bot = " . (int)$filter_bot;
}

// ============================================================================
// FETCH SIDEBAR COUNTS (Dynamic pill counts for labels)
// ============================================================================
$device_counts = [];
$purpose_counts = [];

// Fetch distinct devices with counts based on current filters (except device itself)
$dev_wheres = ["useragent != '-'","device IS NOT NULL"]; //NOT null, because that will be all the unclassified stuff!
if ($filter_purpose !== '') {
    $dev_wheres[] = "purpose = " . $db->qstr($filter_purpose);
} elseif (isset($_GET['purpose'])) {
    $dev_wheres[] = "(purpose = '' OR purpose IS NULL)";
}
if ($filter_fragment !== '') $dev_wheres[] = "fragment = " . $db->qstr($filter_fragment);
if ($filter_bot !== 'all')   $dev_wheres[] = "bot = " . (int)$filter_bot;

$dev_res = $db->Execute("SELECT device, COUNT(*) as cnt FROM agents_all_time WHERE " . implode(" AND ", $dev_wheres) . " GROUP BY device");
if ($dev_res) {
    while ($row = $dev_res->FetchRow()) {
        $device_counts[$row['device'] ?? 'NULL'] = $row['cnt'];
    }
}

// Fetch distinct purposes with counts based on current filters (except purpose itself)
$purp_wheres = ["useragent != '-'","device IS NOT NULL"]; //NOT null, because that will be all the unclassified stuff!
if ($filter_device !== '') {
    $purp_wheres[] = "device = " . $db->qstr($filter_device);
} elseif (isset($_GET['device'])) {
    $purp_wheres[] = "(device = '')"; // Fix cross-talk count on device
}
if ($filter_fragment !== '') $purp_wheres[] = "fragment = " . $db->qstr($filter_fragment);
if ($filter_bot !== 'all')   $purp_wheres[] = "bot = " . (int)$filter_bot;

$purp_res = $db->Execute("SELECT purpose, COUNT(*) as cnt FROM agents_all_time WHERE " . implode(" AND ", $purp_wheres) . " GROUP BY purpose");
if ($purp_res) {
    while ($row = $purp_res->FetchRow()) {
        $purpose_counts[$row['purpose'] ?? 'NULL'] = $row['cnt'];
    }
}

// ============================================================================
// DETERMINING SORT ORDER
// ============================================================================
switch ($sort_order) {
    case 'normalized_week':
        $orderby = ($view_mode === 'fragment_group')
            ? "ORDER BY (SUM(hits) / GREATEST(1, TIMESTAMPDIFF(HOUR, MIN(first_hour), MAX(last_hour)))) * 168 DESC"
            : "ORDER BY (hits / GREATEST(1, TIMESTAMPDIFF(HOUR, first_hour, last_hour))) * 168 DESC";
        break;
    case 'new_agents':
        $orderby = ($view_mode === 'fragment_group') ? "ORDER BY MIN(first_hour) DESC" : "ORDER BY first_hour DESC";
        break;
    case 'peak_usage':
        $orderby = ($view_mode === 'fragment_group') ? "ORDER BY SUM(hits)/SUM(hours) DESC" : "ORDER BY (hits/hours) DESC";
        break;
    case 'most_active':
        $orderby = ($view_mode === 'fragment_group') ? "ORDER BY SUM(hits) DESC" : "ORDER BY hits DESC";
        break;
    case 'recent':
    default:
        $orderby = ($view_mode === 'fragment_group') ? "ORDER BY MAX(last_hour) DESC" : "ORDER BY last_hour DESC";
        break;
}

// ============================================================================
// DATA FETCH: MAIN QUERY
// ============================================================================
$where_clause = " WHERE " . implode(" AND ", $base_wheres);

if ($view_mode === 'fragment_group') {
    // Mode A: Grouped by Fragment summary view
    $main_sql = "SELECT 
                    fragment,
                    useragent, /* sample useragent fallback */
                    SUM(hits) AS total_hits,
                    SUM(hours) AS total_hours,
                    AVG(hits/hours) AS avg_usage,
                    MIN(first_hour) AS earliest_seen,
                    MAX(last_hour) AS latest_seen,
(SUM(hits) / GREATEST(1, TIMESTAMPDIFF(HOUR, MIN(first_hour), MAX(last_hour)))) * 168 AS hits_per_week,
                    GROUP_CONCAT(DISTINCT device SEPARATOR ', ') AS device,
                    GROUP_CONCAT(DISTINCT purpose SEPARATOR ', ') AS purpose
                 FROM agents_all_time
                 $where_clause AND fragment IS NOT NULL
                 GROUP BY fragment
                 $orderby 
                 LIMIT $page_size";
} else {
    // Mode B: Individual raw user agent classification rows
    $main_sql = "SELECT 
                    id, useragent, hits, hours, (hits/hours) as avg_usage, 
                    first_hour, last_hour, device, purpose, fragment,
                    version, website, url, email, headless, bot,
(hits / GREATEST(1, TIMESTAMPDIFF(HOUR, first_hour, last_hour))) * 168 AS hits_per_week
                 FROM agents_all_time
                 $where_clause
                 $orderby 
                 LIMIT $page_size";
}
//print $main_sql;

$results = $db->Execute($main_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Agent Traffic Analyzer</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 20px; background-color: #f8f9fa; color: #333; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        .control-panel { background: #fff; border: 1px solid #e1e4e6; padding: 15px; border-radius: 6px; margin-bottom: 2px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .control-group { margin-bottom: 12px; display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
        .control-label { font-weight: bold; min-width: 140px; font-size: 0.9rem; }
        .pill { display: inline-block; padding: 4px 10px; background: #e9ecef; border: 1px solid #ced4da; text-decoration: none; color: #495057; border-radius: 16px; font-size: 0.85rem; transition: all 0.2s; }
        .pill:hover { background: #dee2e6; }
        .pill.active { background: #007bff; color: #fff; border-color: #007bff; }
        .pill .count { font-size: 0.75rem; opacity: 0.8; margin-left: 4px; font-weight: bold; }
        .review-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #e1e4e6; table-layout: fixed; }
        .review-table th, .review-table td { padding: 8px 11px; border-bottom: 1px solid #e1e4e6; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; }
        .review-table th { background-color: #f1f3f5; color: #495057; font-weight: 600; }
        .review-table tr:hover { background-color: #f8f9fa; }
        .badge { background: #e2e8f0; color: #4a5568; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; font-weight: 500; white-space:nowrap }
        .text-muted { color: #6c757d; font-size: 0.85rem; }
        .ua-string { font-family: monospace; font-size: 0.8rem; word-break: break-all; }

        /* Disclaimer Card Styling */
        .disclaimer-card { background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #d97706; padding: 15px; border-radius: 6px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .disclaimer-title { font-weight: bold; color: #b45309; font-size: 0.95rem; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .disclaimer-text { font-size: 0.85rem; color: #78350f; line-height: 1.45; }
        .disclaimer-text ul { margin: 6px 0 0 18px; padding: 0; }
        .disclaimer-text li { margin-bottom: 4px; }
    </style>
</head>
<body>

    <h3>Non-Stealth Bots &amp; Crawlers (and API Users!)</h3>
<? if (empty($_GET)) { ?>
<!-- Data Limitations Disclaimer -->
    <div class="disclaimer-card">
        <div class="disclaimer-title">
            Data &amp; Classification Advisory Notice
        </div>
        <div class="disclaimer-text">
            This dashboard displays prolific agents recorded since August 2024. Please keep the following limitations in mind when interpreting this data:
            <ul>
                <li><strong>Scope of Traffic:</strong> Captures hits across <code>geograph.org.uk</code> and <code>geograph.ie</code>. This consists primarily of standard page views but includes API traffic. Notably, this does <strong>not</strong> include direct asset accesses to main photos/images, though it does capture uncached map tile image requests.</li>
                <li><strong>No "Long Tail" Traffic:</strong> Only user agents that hit a threshold of at least 100 requests within a single hour are logged here.</li>
                <li><strong>AI-Generated Labels:</strong> The device, purpose, and name grouping labels are generated by an LLM. This represents a "best guess" classification and may contain inaccuracies. (in particular <tt>search-crawler</tt>, is a bit catch-all and will likly include AI crawlers, as well as traditional search engines, and non-search crawlers)</li>
                <li><strong>Unverified Identities:</strong> Groupings rely strictly on the self-reported User-Agent header, which is easily spoofed. This report does not include stealth crawlers, nor does it verify if traffic originates from the genuine bot owner (e.g., mainstream bot listings likely include bad actors posing as those bots).</li>
                <li><strong>Shared Library Footprints:</strong> Generic web software identifiers (such as the <code>software</code> group, python-requests, etc.) represent shared software libraries. Typically, traffic categorized under these headers represents multiple unrelated operators.</li>
                <li><strong>Generic Browsers Excluded:</strong> Strings identified as standard, uncustomized desktop or mobile web browsers are not cataloged in this archive.</li>
                <li><strong>API Traffic Included:</strong> This data logs all user agents accessing the website through any channel, including requests via authorized APIs, it is not purely a record of scraping or crawling attempts.</li>
                <li><strong>Historical Block Status:</strong> This dataset includes traffic recorded *before* block rules or firewall restrictions were implemented. Some of these bots may already be actively blocked; this dashboard does not attempt to filter out or separate currently blocked actors.</li>
            </ul>
        </div>
    </div>
<? } ?>
    <div class="control-panel">
        <!-- View Mode Filter -->
        <div class="control-group">
            <span class="control-label">View Style:</span>
            <a href="<?= makeUrl(['view_mode' => 'individual']) ?>" class="pill <?= $view_mode === 'individual' ? 'active' : '' ?>">Individual UserAgents</a>
            <a href="<?= makeUrl(['view_mode' => 'fragment_group']) ?>" class="pill <?= $view_mode === 'fragment_group' ? 'active' : '' ?>">Group By Fragment</a>
        </div>

        <!-- Sort Rule Engine -->
        <div class="control-group">
            <span class="control-label">Sort By Order:</span>
            <a href="<?= makeUrl(['sort' => 'recent']) ?>" class="pill <?= $sort_order === 'recent' ? 'active' : '' ?>">Recent Active</a>
            <a href="<?= makeUrl(['sort' => 'normalized_week']) ?>" class="pill <?= $sort_order === 'normalized_week' ? 'active' : '' ?>">Sustained Vol (Hits/Week)</a>
            <a href="<?= makeUrl(['sort' => 'new_agents']) ?>" class="pill <?= $sort_order === 'new_agents' ? 'active' : '' ?>">New Agents</a>
            <a href="<?= makeUrl(['sort' => 'peak_usage']) ?>" class="pill <?= $sort_order === 'peak_usage' ? 'active' : '' ?>">Peak Usage (Hits/Hr)</a>
            <a href="<?= makeUrl(['sort' => 'most_active']) ?>" class="pill <?= $sort_order === 'most_active' ? 'active' : '' ?>">Most Active Hits</a>
        </div>

        <!-- Bot Flags Filter -->
        <div class="control-group">
            <span class="control-label">Traffic Type:</span>
            <a href="<?= makeUrl(['bot' => null]) ?>" class="pill <?= $filter_bot === 'all' ? 'active' : '' ?>">All Statuses</a>
            <a href="<?= makeUrl(['bot' => 1]) ?>" class="pill <?= $filter_bot === 1 ? 'active' : '' ?>">Is Bot (bot=1)</a>
            <a href="<?= makeUrl(['bot' => 0]) ?>" class="pill <?= $filter_bot === 0 ? 'active' : '' ?>">Not Bot (bot=0)</a>
        </div>

        <!-- Device Filter -->
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

        <!-- Purpose Filter -->
        <div class="control-group">
            <span class="control-label">Purpose Filter:</span>
            <a href="<?= makeUrl(['purpose' => null]) ?>" class="pill <?= empty($filter_purpose) ? 'active' : '' ?>">ALL</a>
            <?php foreach ($purpose_counts as $purp => $count): ?>
                <?php $label = ($purp === 'NULL' || $purp === '') ? 'Unspecified' : $purp; ?>
                <a href="<?= makeUrl(['purpose' => $purp === 'NULL' ? '' : $purp]) ?>" class="pill <?= $filter_purpose === ($purp === 'NULL' ? '' : $purp) ? 'active' : '' ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES) ?>
                    <span class="count">(<?= $count ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Text Fragment Search Input Context Filter -->
        <?php if (!empty($filter_fragment)): ?>
        <div class="control-group">
            <span class="control-label">Fragment Constraint:</span>
            <span class="pill active">
                Fragment: <?= htmlspecialchars($filter_fragment, ENT_QUOTES) ?>
                <a href="<?= makeUrl(['fragment' => null]) ?>" style="color:#fff; margin-left:8px; font-weight:bold; text-decoration:none;"> X</a>
            </span>
        </div>
        <?php endif; ?>

        <!-- Toggle Optional Fields Fields visibility dynamically -->
        <div class="control-group">
            <span class="control-label">Optional Columns:</span>
            <a href="<?= makeUrl(['show_version' => $show_version ? 0 : 1]) ?>" class="pill <?= $show_version ? 'active' : '' ?>">Version</a>
            <a href="<?= makeUrl(['show_website' => $show_website ? 0 : 1]) ?>" class="pill <?= $show_website ? 'active' : '' ?>">Website</a>
            <a href="<?= makeUrl(['show_url' => $show_url ? 0 : 1]) ?>" class="pill <?= $show_url ? 'active' : '' ?>">URL</a>
            <a href="<?= makeUrl(['show_email' => $show_email ? 0 : 1]) ?>" class="pill <?= $show_email ? 'active' : '' ?>">Email</a>
            <a href="<?= makeUrl(['show_headless' => $show_headless ? 0 : 1]) ?>" class="pill <?= $show_headless ? 'active' : '' ?>">Headless</a>
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
	<caption><?= number_format($results ? $results->numRows() : 0, 0); ?> Rows Displayed</caption>
        <thead>
            <tr>
                <?php if ($view_mode === 'fragment_group'): ?>
                    <th style="width: 15%;">Fragment Distinct</th>
                    <th style="width: 5%;">Sum (Hits)</th>
                    <th style="width: 5%;">Hits / Week</th>
                    <th style="width: 5%;">Sum (Hours)</th>
                    <th style="width: 5%;">Avg (Hits/Hr)</th>
                    <th style="width: 8%;">Earliest Seen</th>
                    <th style="width: 8%;">Latest Seen</th>
                    <th>Example / Raw Sample Metadata</th>
                <?php else: ?>
                    <th style="width: 10%;">Fragment</th>
                    <th style="width: 5%;">Hits</th>
                    <th style="width: 5%;">Hits / Week</th>
                    <th style="width: 5%;">Hours</th>
                    <th style="width: 5%;">Hits/Hour</th>
                    <th style="width: 8%;">First Active</th>
                    <th style="width: 8%;">Last Active</th>
                    <th style="width: 10%;">Label Info</th>
                    <!-- Dynamic column processing parameters based on triggers toggled above -->
                    <?php if ($show_version):  ?><th>Version</th><?php endif; ?>
                    <?php if ($show_website):  ?><th>Website</th><?php endif; ?>
                    <?php if ($show_url):      ?><th>URL</th><?php endif; ?>
                    <?php if ($show_email):    ?><th>Email</th><?php endif; ?>
                    <?php if ($show_headless): ?><th>Headless</th><?php endif; ?>
                    <th>User Agent Full String</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($results && !$results->EOF): ?>
                <?php while ($row = $results->FetchRow()): ?>
                    <tr>
                        <?php if ($view_mode === 'fragment_group'): ?>
                            <!-- Grouped Summary Data Rendering Mapping -->
                            <td>
                                <a href="<?= makeUrl(['view_mode' => 'individual', 'fragment' => $row['fragment']]) ?>" title="Drill into items">
                                    <strong><?= htmlspecialchars($row['fragment'], ENT_QUOTES) ?></strong>
                                </a>
                            </td>
                            <td align=right><span class="badge" style="background:#e1f5fe; color:#0288d1; font-weight:bold;"><?= number_format($row['total_hits']) ?></span></td>
                            <td align=right><strong style="color: #6200ea;"><?= number_format($row['hits_per_week']) ?></strong></td>
                            <td align=right><?= number_format($row['total_hours']) ?> hrs</td>
                            <td align=right><strong><?= number_format($row['avg_usage']) ?></strong></td>
                            <td><span class="text-muted"><?= htmlspecialchars(str_replace(':00:00','',$row['earliest_seen']), ENT_QUOTES) ?></span></td>
                            <td><span class="text-muted"><?= htmlspecialchars(str_replace(':00:00','',$row['latest_seen']), ENT_QUOTES) ?></span></td>
                            <td class="ua-string" title="<?= htmlspecialchars($row['useragent'], ENT_QUOTES) ?>">
                                <span class="badge"><?= htmlspecialchars($row['device'] ?: 'unknown', ENT_QUOTES) ?></span>
                                <span class="badge" style="background:#f1f3f5"><?= htmlspecialchars($row['purpose'] ?: 'unclassified', ENT_QUOTES) ?></span>
                                <div style="margin-top:4px; opacity:0.75; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($row['useragent'], ENT_QUOTES) ?></div>
                            </td>
                        <?php else: ?>
                            <!-- Individual Logs Record Rows Mapping -->
                            <td><?= htmlspecialchars($row['fragment'], ENT_QUOTES) ?></td>
                            <td align=right><span class="badge" style="background:#e8f5e9; color:#2e7d32; font-weight:bold;"><?= number_format($row['hits']) ?></span></td>
                            <td align=right><strong style="color: #6200ea;"><?= number_format($row['hits_per_week']) ?></strong></td>
                            <td align=right><?= number_format($row['hours']) ?> hrs</td>
                            <td align=right><strong><?= number_format($row['avg_usage']) ?></strong></td>
                            <td><span class="text-muted" style="white-space:nowrap;"><?= htmlspecialchars(str_replace(':00:00','',$row['first_hour']), ENT_QUOTES) ?></span></td>
                            <td><span class="text-muted" style="white-space:nowrap;"><?= htmlspecialchars(str_replace(':00:00','',$row['last_hour']), ENT_QUOTES) ?></span></td>
                            <td>
                                <span class="badge" style="margin-top:2px;"><?= htmlspecialchars($row['device'] ?: '', ENT_QUOTES) ?></span>
                                <span class="badge" style="background:#fff; border:1px solid #cbd5e0; margin-top:2px;"><?= htmlspecialchars($row['purpose'] ?: '', ENT_QUOTES) ?></span>
                            </td>
                            <!-- Conditional Display Columns Engine Mapping -->
                            <?php if ($show_version):  ?><td><?= htmlspecialchars($row['version'] ?? '', ENT_QUOTES) ?></td><?php endif; ?>
                            <?php if ($show_website):  ?><td><?= htmlspecialchars($row['website'] ?? '', ENT_QUOTES) ?></td><?php endif; ?>
                            <?php if ($show_url):      ?><td><?= htmlspecialchars($row['url'] ?? '', ENT_QUOTES) ?></td><?php endif; ?>
                            <?php if ($show_email):    ?><td><?= htmlspecialchars($row['email'] ?? '', ENT_QUOTES) ?></td><?php endif; ?>
                            <?php if ($show_headless): ?><td><?= htmlspecialchars($row['headless'] ?? '', ENT_QUOTES) ?></td><?php endif; ?>
                            <td class="ua-string"><?= htmlspecialchars($row['useragent'], ENT_QUOTES) ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="20" style="text-align: center; padding: 30px; color: #868e96;">No matching records found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<p>Remember, because this is only looking at the user-reported User-Agent, these stats likly combine data from multiple providers using the same User-Agent Identity.

</body>
</html>
