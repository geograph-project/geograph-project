<?php

require_once 'scripts/_scripts.inc.php';

$tty = posix_isatty(STDOUT);
$counts = ['success' => 0, 'error' => 0, 'neutral' => 0];

function success($message) {
    global $tty, $counts;
    $counts['success']++;
    if ($tty) {
        echo "\033[32m" . $message . "\033[0m\n";
    } else {
        echo $message . "\n";
    }
}

function error($message) {
    global $tty, $counts;
    $counts['error']++;
    if ($tty) {
        echo "\033[31m" . $message . "\033[0m\n";
    } else {
        echo $message . "\n";
    }
}

function neutral($message) {
    global $tty, $counts;
    $counts['neutral']++;
    if ($tty) {
        echo "\033[33m" . $message . "\033[0m\n";
    } else {
        echo $message . "\n";
    }
}

echo "Manticore Query Test Script\n";
echo "===========================\n\n";

$queries = file_get_contents('../schema/manticore-queries.txt');
$queries = explode("\n", $queries);

$sphinx = GeographSphinxConnection();
if (!$sphinx) {
    error("Could not connect to Manticore.");
    exit(1);
}

foreach ($queries as $query) {
    if (empty($query) || strpos($query, '#') === 0) {
        continue;
    }

    if (strpos($query, 'SELECT') === 0) {
        $query = trim($query);
        neutral("Testing query: $query");

        // Replace placeholders
        $query = str_replace('<index>', 'gi_stemmed', $query);
        $query = str_replace('<query>', 'test', $query);
        $query = str_replace('<filters>', 'user_id=1', $query);
        $query = str_replace('<attribute>', 'user_id', $query);
        $query = str_replace('<direction>', 'DESC', $query);
        $query = str_replace('<lat>', '51.5074', $query);
        $query = str_replace('<lon>', '0.1278', $query);
        $query = str_replace('<distance>', '1000', $query);

        $start_time = microtime(true);
        $result = $sphinx->getAll($query);
        $end_time = microtime(true);

        if ($result) {
            $meta = $sphinx->getAssoc("SHOW META");
            success("Query executed successfully. Rows: " . count($result) . " Total: " . $meta['total'] . " Total found: " . $meta['total_found'] . " Time: " . round($end_time - $start_time, 4) . "s (Manticore: " . $meta['time'] . "s)");
        } else {
            error("Query failed: " . $sphinx->ErrorMsg());
        }
    }
}

echo "\n===========================\n";
echo "Success: {$counts['success']}, Error: {$counts['error']}, Neutral: {$counts['neutral']}\n";
