<?php

require_once('geograph/global.inc.php');

$sph = GeographSphinxConnection('sphinxql',true);

$query = $_GET['q'] ?? '';

if (empty($query)) {
    die("No query provided.");
}

//TODO, just for DEV!
customNoCacheHeader();


// 1. Get stats for each word in the query string
$quotedQuery = $sph->Quote($query);
$indexName = 'snippet';
$stats = $sph->getAll("CALL KEYWORDS($quotedQuery, '$indexName', 1)");

$weightedTerms = [];
$meta = $sph->getAssoc("SHOW INDEX snippet_main STATUS"); //snippet is a distributed index!
$totalDocsInIndex = $mata['indexed_documents'] ?? 100000;

foreach ($stats as $row) {
    $word = $row['normalized'];
    $hits = (int)$row['hits'];

    // Skip words not in the index (like 'avranches' in your example)
    if ($hits === 0 || !empty($_GET['legacy'])) {
        $weightedTerms[] = $word;
        continue;
    }

    // 2. Calculate a boost: Rare words get higher numbers.
    // Logic: TotalDocs / Hits.
    // Example: Dover (25 hits) = 4000 boost. The (10k hits) = 10 boost.
    //$boost = round($totalDocsInIndex / ($hits + 1));
    //$boost = round(sqrt($boost));

    // Logarithmic scaling is even smoother than sqrt
    $boost = round(log($totalDocsInIndex / ($hits + 1), 2)) + 1;

    // Cap the boost to stay within reasonable Manticore limits
    $boost = min(max($boost, 1), 10000);
    if ($boost > 1.9) {
        $weightedTerms[] = "{$word}^{$boost}";
    } else {
        $weightedTerms[] = $word;
    }
}

// 3. Form the final MATCH string
// We use /1 to ensure we get results, but the boosts + ranker will sort them.
$matchString = $sph->Quote('"'.implode(' ', $weightedTerms) . '"/1');

$sql = "SELECT *, weight() as distance
        FROM $indexName
        WHERE MATCH($matchString)
        LIMIT 30
        OPTION ranker=expr('sum(sum_idf+atc)'), idf='plain,tfidf_unnormalized', field_weights=(title=4)
";

if (!empty($_GET['debug'])) {
	print htmlentities($sql); exit;
}

// 4. Get the final results
$rows = $sph->getAll($sql);

// Output for testing
header('Content-Type: application/json');
echo json_encode($rows);
