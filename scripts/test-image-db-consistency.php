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

echo "Geograph Image DB Consistency Test Script\n";
echo "=========================================\n\n";

// Check gridimage and gridimage_search tables
echo "Checking gridimage and gridimage_search tables...\n";
$db = GeographDatabaseConnection();
if ($db) {
    $gridimage_status = $db->GetRow("SHOW TABLE STATUS LIKE 'gridimage'");
    $gridimage_search_status = $db->GetRow("SHOW TABLE STATUS LIKE 'gridimage_search'");

    if ($gridimage_status && $gridimage_search_status) {
        if ($gridimage_status['Rows'] == $gridimage_search_status['Rows']) {
            success("Row counts for gridimage and gridimage_search match on primary.");
        } else {
            error("Row counts for gridimage and gridimage_search do not match on primary.");
        }
        neutral("gridimage: {$gridimage_status['Rows']} rows, last updated {$gridimage_status['Update_time']}");
        neutral("gridimage_search: {$gridimage_search_status['Rows']} rows, last updated {$gridimage_search_status['Update_time']}");
    } else {
        error("Could not get table status for gridimage and gridimage_search on primary.");
    }
} else {
    error("Could not connect to primary database.");
}

if (!empty($CONF['db_read_connect'])) {
    $db_slave = GeographDatabaseConnection(true);
    if ($db_slave && $db_slave->readonly) {
        $gridimage_status_slave = $db_slave->GetRow("SHOW TABLE STATUS LIKE 'gridimage'");
        $gridimage_search_status_slave = $db_slave->GetRow("SHOW TABLE STATUS LIKE 'gridimage_search'");

        if ($gridimage_status_slave && $gridimage_search_status_slave) {
            if ($gridimage_status_slave['Rows'] == $gridimage_search_status_slave['Rows']) {
                success("Row counts for gridimage and gridimage_search match on replica.");
            } else {
                error("Row counts for gridimage and gridimage_search do not match on replica.");
            }
            neutral("gridimage (replica): {$gridimage_status_slave['Rows']} rows, last updated {$gridimage_status_slave['Update_time']}");
            neutral("gridimage_search (replica): {$gridimage_search_status_slave['Rows']} rows, last updated {$gridimage_search_status_slave['Update_time']}");
        } else {
            error("Could not get table status for gridimage and gridimage_search on replica.");
        }
    } else {
        error("Could not connect to replica database.");
    }
} else {
    neutral("Replica database not configured. Skipping check.");
}
echo "\n";

// Check gridsquare table
echo "Checking gridsquare table...\n";
if ($db) {
    $gridsquare_sum = $db->GetOne("SELECT SUM(imagecount) FROM gridsquare");
    $gridimage_search_count = $db->GetOne("SELECT COUNT(*) FROM gridimage_search");

    if ($gridsquare_sum == $gridimage_search_count) {
        success("SUM(imagecount) from gridsquare matches row count from gridimage_search.");
    } else {
        error("SUM(imagecount) from gridsquare does not match row count from gridimage_search.");
    }
    neutral("gridsquare.imagecount sum: $gridsquare_sum");
    neutral("gridimage_search row count: $gridimage_search_count");
} else {
    error("Could not connect to primary database.");
}
echo "\n";

// Check Manticore indexes
echo "Checking Manticore indexes...\n";
$sphinx = GeographSphinxConnection();
if ($sphinx) {
    $gi_stemmed_count = $sphinx->GetOne("SELECT COUNT(*) FROM gi_stemmed");
    $gi_stemmed_delta_count = $sphinx->GetOne("SELECT COUNT(*) FROM gi_stemmed_delta");
    $total_stemmed_count = $gi_stemmed_count + $gi_stemmed_delta_count;
    $gridimage_search_count = $db->GetOne("SELECT COUNT(*) FROM gridimage_search");

    if ($total_stemmed_count == $gridimage_search_count) {
        success("Row count for gi_stemmed and gi_stemmed_delta matches gridimage_search.");
    } else {
        error("Row count for gi_stemmed and gi_stemmed_delta does not match gridimage_search.");
    }
    neutral("gi_stemmed: $gi_stemmed_count rows");
    neutral("gi_stemmed_delta: $gi_stemmed_delta_count rows");
    neutral("Total stemmed: $total_stemmed_count rows");
    neutral("gridimage_search: $gridimage_search_count rows");

    $sample8_shards = ['A', 'B', 'C', 'D', 'E'];
    $sample8_count = 0;
    foreach ($sample8_shards as $shard) {
        $sample8_count += $sphinx->GetOne("SELECT COUNT(*) FROM sample8$shard");
    }

    if ($sample8_count == $gridimage_search_count) {
        success("Row count for sample8 shards matches gridimage_search.");
    } else {
        error("Row count for sample8 shards does not match gridimage_search.");
    }
    neutral("sample8 shards total: $sample8_count rows");
    neutral("gridimage_search: $gridimage_search_count rows");

    $sample8_dist_count = $sphinx->GetOne("SELECT COUNT(*) FROM sample8");
    if ($sample8_dist_count == $gridimage_search_count) {
        success("Row count for sample8 distributed index matches gridimage_search.");
    } else {
        error("Row count for sample8 distributed index does not match gridimage_search.");
    }
    neutral("sample8 distributed index: $sample8_dist_count rows");
    neutral("gridimage_search: $gridimage_search_count rows");
} else {
    error("Could not connect to Manticore.");
}
echo "\n";

// Check user_stat table
echo "Checking user_stat table...\n";
if ($db) {
    $user_stat_images = $db->GetOne("SELECT images FROM user_stat WHERE user_id = 0");
    $gridimage_search_count = $db->GetOne("SELECT COUNT(*) FROM gridimage_search");

    if ($user_stat_images == $gridimage_search_count) {
        success("images from user_stat where user_id = 0 matches row count from gridimage_search.");
    } else {
        error("images from user_stat where user_id = 0 does not match row count from gridimage_search.");
    }
    neutral("user_stat.images: $user_stat_images");
    neutral("gridimage_search row count: $gridimage_search_count");
} else {
    error("Could not connect to primary database.");
}
echo "\n";

// Check hectad_stat table
echo "Checking hectad_stat table...\n";
if ($db) {
    $hectad_stat_images = $db->GetOne("SELECT SUM(images) FROM hectad_stat");
    $gridimage_search_count = $db->GetOne("SELECT COUNT(*) FROM gridimage_search");

    if ($hectad_stat_images == $gridimage_search_count) {
        success("SUM(images) from hectad_stat matches row count from gridimage_search.");
    } else {
        error("SUM(images) from hectad_stat does not match row count from gridimage_search.");
    }
    neutral("hectad_stat.images sum: $hectad_stat_images");
    neutral("gridimage_search row count: $gridimage_search_count");
} else {
    error("Could not connect to primary database.");
}
echo "\n";

// Check date_stat table
echo "Checking date_stat table...\n";
if ($db) {
    $date_stat_images = $db->GetOne("SELECT SUM(images) FROM date_stat WHERE type = 'submitted' AND referece_index = 0");
    $gridimage_search_count = $db->GetOne("SELECT COUNT(*) FROM gridimage_search");

    if ($date_stat_images == $gridimage_search_count) {
        success("SUM(images) from date_stat where type = 'submitted' and referece_index = 0 matches row count from gridimage_search.");
    } else {
        error("SUM(images) from date_stat where type = 'submitted' and referece_index = 0 does not match row count from gridimage_search.");
    }
    neutral("date_stat.images sum: $date_stat_images");
    neutral("gridimage_search row count: $gridimage_search_count");
} else {
    error("Could not connect to primary database.");
}
echo "\n";


echo "=========================================\n";
echo "Success: {$counts['success']}, Error: {$counts['error']}, Neutral: {$counts['neutral']}\n";
