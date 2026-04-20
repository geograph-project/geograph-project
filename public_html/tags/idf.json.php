<?php

require_once('geograph/global.inc.php');

$sph = GeographSphinxConnection('sphinxql',true);

$query = $_GET['q'] ?? '';

if (empty($query)) {
    die("No query provided.");
}

// 1. Get stats for each word in the query string
$quotedQuery = $sph->Quote($query);
$indexName = 'tags';
$stats = $sph->getAll("CALL KEYWORDS($quotedQuery, '$indexName', 1)");

$weightedTerms = [];
$totalDocsInIndex = 100000; // Ideally, get this from 'SHOW INDEX tags STATUS'

foreach ($stats as $row) {
    $word = $row['normalized'];
    $hits = (int)$row['hits'];

    // Skip words not in the index (like 'avranches' in your example)
    if ($hits === 0) {
        $weightedTerms[] = $word;
        continue;
    }

    // 2. Calculate a boost: Rare words get higher numbers.
    // Logic: TotalDocs / Hits.
    // Example: Dover (25 hits) = 4000 boost. The (10k hits) = 10 boost.
    $boost = round($totalDocsInIndex / ($hits + 1));

    // Cap the boost to stay within reasonable Manticore limits
    $boost = min(max($boost, 1), 10000);

    $weightedTerms[] = "{$word}^{$boost}";
}

// 3. Form the final MATCH string
// We use /1 to ensure we get results, but the boosts + ranker will sort them.
$matchString = $sph->Quote('"'.implode(' ', $weightedTerms) . '"/1');

$sql = "SELECT id, prefix, tag, weight() as distance
        FROM $indexName
        WHERE MATCH($matchString)
        LIMIT 30
        OPTION ranker=expr('sum(sum_idf+atc)'), idf='plain,tfidf_unnormalized'
";

// 4. Get the final results
$rows = $sph->getAll($sql);

// Output for testing
header('Content-Type: application/json');
echo json_encode($rows);
