<?php
/**
 * Standalone Article Gallery
 * Dependencies: ADODB (assumed $db is initialized)
 */

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$db = GeographDatabaseConnection(true);



// 1. Fetch Data (note we get all rows, so can display counts, even though we might not show all!)

$order = "article_id desc"; //show most recent first
if (!empty($_GET['category'])) $order = "regexp_replace(title,'^The ','') asc";

$sql = "SELECT article_id, url, pillar, category, title, user_id, realname, extract
        FROM article_ai 
        INNER JOIN article USING (article_id)
        INNER JOIN user USING (user_id)
	WHERE approved > 0 AND licence != 'none'
        ORDER BY pillar, category, $order";
$data = $db->getAll($sql);

// 2. Handle Filters
$selectedPillar = $_GET['pillar'] ?? '';
$selectedCategory = $_GET['category'] ?? '';

// 3. Group Data and Identify Dropdown Options
$grouped = [];
$pillars = [];
$categories = [];

foreach ($data as $row) {
    $p = $row['pillar'];
    $c = $row['category'];
    
    // Build unique lists for dropdowns
    @$pillars[$p]++;
    @$categories[$c]++;

    // Filtering Logic
    if ($selectedCategory && $c !== $selectedCategory) continue;
    if (!$selectedCategory && $selectedPillar && $p !== $selectedPillar) continue;

    $grouped[$p][$c][] = $row;
}

// Determine Limit
// 10 if viewing all, 40 if viewing a pillar, unlimited if viewing a category
$limit = 10;
if ($selectedPillar) $limit = 40;
if ($selectedCategory) $limit = 9999;

$smarty->display('_std_begin.tpl');

?>
	<h2>Geograph Articles</h2>
	<p><? echo count($data); ?> community articles, organized by AI for easier browsing. <span style="color:gray">The AI may make mistakes.</span></p>

    <style>
	#listing { max-width: 90ch; }

        #listing select { padding: 0.5rem; border-radius: 4px; border: 1px solid #ccc; flex-grow: 1; font-size: 1rem; }
        #listing h2 { border-bottom: 2px solid #0056b3; color: #0056b3; padding-bottom: 0.5rem; margin-top: 3rem; }
        #listing h3 { background: #eee; padding: 0.4rem 1rem; border-radius: 4px; margin-top: 2rem; margin-bottom:0.5rem; }
        .article-list { list-style: none; padding: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 0.5rem; }
        .article-item { background: #fff; padding: 0.25rem; border-radius: 4px; border-left: 4px solid #0056b340; border-bottom:1px solid #0056b340; transition: transform 0.1s; }
        .--article-item:hover { transform: translateX(5px); background: #f0f7ff; }
        .article-item a { text-decoration: none; color: #1a0dab; font-weight: 600; font-size: 1.1rem; line-height: 1.3; letter-spacing: 0.02em; display: block; }
        .author { font-family: 'Comic Sans MS', Georgia, Verdana, Arial, serif; font-size: 0.9rem; color: #666; padding-left: 8px }
        .meta-info { margin-top: 1rem; font-size: 0.85rem; color: #888; }
    </style>

<div id="listing">

    <form class="controls" id="filterForm">
        <select name="pillar" onchange="document.getElementsByName('category')[0].value=''; this.form.submit()">
            <option value="">-- All --</option>
            <?php foreach ($pillars as $p => $count): ?>
                <option value="<?= htmlspecialchars($p) ?>" <?= $selectedPillar == $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?> (<? echo $count; ?>)</option>
            <?php endforeach; ?>
        </select>

        <select name="category" onchange="this.form.submit()">
            <option value="">-- All Categories --</option>
            <?php foreach ($categories as $c => $count): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $selectedCategory == $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?> (<? echo $count; ?>)</option>
            <?php endforeach; ?>
        </select>
        
        <?php if ($selectedPillar || $selectedCategory): ?>
            <a href="?" style="padding: 0.5rem; color: #d9534f; text-decoration: none;">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($grouped)): ?>
        <p>No articles found for this selection.</p>
    <?php else: ?>
        <?php foreach ($grouped as $pillarName => $catGroups): ?>
            <h2><?= htmlspecialchars($pillarName) ?></h2>
            
            <?php foreach ($catGroups as $catName => $articles): ?>
                <h3><?= htmlspecialchars($catName) ?></h3>
                <div class="article-list">
                    <?php 
                    $count = 0;
                    foreach ($articles as $art): 
                        if ($count++ >= $limit) break;
			if ($art['title'] == strtoupper($art['title']) && strlen($art['title']) > 10)
				$art['title'] = recaps($art['title']);
                    ?>
                        <div class="article-item">
                            <a href="/article/<?= htmlspecialchars($art['url']) ?>" title="<?= htmlspecialchars($art['extract']) ?>">
                                <?= htmlspecialchars($art['title']) ?>
                            </a>
                            <span class="author">by <?= htmlspecialchars($art['realname']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($articles) > $limit): ?>
                    <p class="meta-info">... and <a href="?category=<?= urlencode($catName); ?>"><?= count($articles) - $limit ?> more in this category</a>.</p>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
<?

$smarty->display('_std_end.tpl');
