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

echo "Geograph Backend Test Script\n";
echo "============================\n\n";

// Test AWS IAM Role
echo "Testing AWS IAM Role...\n";
if (!empty($_SERVER['AWS_WEB_IDENTITY_TOKEN_FILE'])) {
    neutral("AWS_WEB_IDENTITY_TOKEN_FILE is set.");
    $fs = new FileSystem();
    if (S3::$securityToken) {
        success("Successfully assumed IAM role.");
    } else {
        error("Failed to assume IAM role.");
    }
} else {
    neutral("AWS_WEB_IDENTITY_TOKEN_FILE is not set. Skipping test.");
}
echo "\n";

// Test MariaDB Connection
echo "Testing MariaDB Connection...\n";
$db = GeographDatabaseConnection();
if ($db) {
    $version = $db->GetOne("SELECT VERSION()");
    success("Successfully connected to MariaDB.");
    print "  Version: $version\n";
    if (!empty($CONF['db_read_connect'])) {
        $row = $db->getRow("SHOW MASTER STATUS");
        $master = $row['File'].'|'.$row['Position'];
    }
} else {
    error("Failed to connect to MariaDB.");
}
echo "\n";

// Test MariaDB Slave Connection
echo "Testing MariaDB Slave Connection...\n";
if (!empty($CONF['db_read_connect'])) {
    $db_slave = GeographDatabaseConnection(true);
    if ($db_slave && $db_slave->readonly) {
        $version = $db_slave->GetOne("SELECT VERSION()");
        success("Successfully connected to MariaDB slave.");
	print "  Version: $version\n";

	$row = $db_slave->getRow("SHOW SLAVE STATUS");

	if ($row['Slave_IO_Running'] !== 'Yes' || $row['Slave_SQL_Running'] !== 'Yes') {
	    print "  CRITICAL: Replication Threads Stopped\n";
	    print "  IO: {$row['Slave_IO_Running']} | SQL: {$row['Slave_SQL_Running']}\n";
	}

                $slave = $row['Master_Log_File'].'|'.$row['Read_Master_Log_Pos'];
                if ($slave != $master) {
                        print "  Slave Read Failure?\n";
                        print "  Master: $master\n  Slave: $slave\n";
                }

                $slave = $row['Master_Log_File'].'|'.$row['Exec_Master_Log_Pos'];
                if ($slave != $master) {
                        print "  ERROR: Slave Execute Failure?\n";
                        print "  Master: $master\n  Slave: $slave\n";
                }
                if ($row['Last_Error']) {
                        print "  Last Error: {$row['Last_Error']}\n";
                }
		if ($row['Last_SQL_Error']) {
			print "  SQL Error Detail: {$row['Last_SQL_Error']}\n";
		}

	if (is_null($row['Seconds_Behind_Master'])) {
	    print "  CRITICAL: Seconds_Behind_Master is NULL (Slave is stopped)\n";
	} elseif ($row['Seconds_Behind_Master'] > 30) {
	    // Give it a 30s grace period for heavy writes
	    print "  WARNING: High Replication Lag: {$row['Seconds_Behind_Master']}s\n";
	} else {
            //showing the actual number could be still reassuring, even when ok!
	    print "  Replication Lag: {$row['Seconds_Behind_Master']}\n";
        }
    } else {
        error("Failed to connect to MariaDB slave.");
    }
} else {
    neutral("MariaDB slave is not configured. Skipping test.");
}
echo "\n";

// Test Sphinx/Manticore Connection
echo "Testing Sphinx/Manticore Connection...\n";
$sphinx = GeographSphinxConnection();
if ($sphinx) {
    $result = $sphinx->GetRow("SHOW META");
    if ($result) {
        success("Successfully connected to Sphinx/Manticore.");
        foreach ($result as $key => $value) {
            echo "  $key: $value\n";
        }
    } else {
        error("Failed to connect to Sphinx/Manticore.");
    }
} else {
    error("Failed to connect to Sphinx/Manticore.");
}
echo "\n";

// Test Manticore RT Connection
echo "Testing Manticore RT Connection...\n";
if (!empty($CONF['manticorert_host'])) {
    $manticore_rt = GeographSphinxConnection('manticorert');
    if ($manticore_rt) {
        $result = $manticore_rt->GetRow("SHOW META");
        if ($result) {
            success("Successfully connected to Manticore RT.");
            foreach ($result as $key => $value) {
                echo "  $key: $value\n";
            }
        } else {
            error("Failed to connect to Manticore RT.");
        }
    } else {
        error("Failed to connect to Manticore RT.");
    }
} else {
    neutral("Manticore RT is not configured. Skipping test.");
}
echo "\n";

// Test Redis Connection
echo "Testing Redis Connection...\n";
if (!empty($CONF['redis_host'])) {
    $redis = new Redis();
    try {
        $redis->connect($CONF['redis_host']);
        $info = $redis->info();
        success("Successfully connected to Redis. Version: {$info['redis_version']}");
    } catch (Exception $e) {
        error("Failed to connect to Redis: " . $e->getMessage());
    }
} else {
    neutral("Redis is not configured. Skipping test.");
}
echo "\n";

// Test Memcache Connection
echo "Testing Memcache Connection...\n";
if ($memcache->valid) {
    if ($memcache->redis) {
        $stats = $memcache->getStats(); // Get stats from the single Redis endpoint
        success("Successfully connected to Redis via Memcache interface.");

        if (!empty($stats['redis_version'])) {
            print "  Redis Server: {$stats['redis_version']}  Role: {$stats['role']}  Slaves: {$stats['connected_slaves']}\n";
        }
    } else {
        $stats = $memcache->getExtendedStats();

        // Check connection success based on getting stats (since $memcache->valid is already true)
        if ($stats) {
            success("Successfully connected to Multi-Server Memcache.");
            foreach ($stats as $server => $data) {
                // If the server failed to respond, $data might be 'false', so check it.
                $version = is_array($data) ? $data['version'] : 'UNKNOWN/DOWN';
		$uptime = $data['uptime'] ?? '??';
                echo "  Server: $server, Version: $version, Uptime: $uptime\n";
            }
        } else {
             // This case is unlikely if $memcache->valid is true, but acts as a final safeguard.
             error("Failed to retrieve statistics from Memcache pool.");
        }
    }
} else {
    neutral("Cache is not configured (Null Object returned). Skipping connectivity test.");
}
echo "\n";

// Test Carrot2 DCS Connection
echo "Testing Carrot2 DCS Connection...\n";
if (!empty($CONF['carrot2_dcs_host'])) {
    require_once '3rdparty/Carrot2.class.php';
    $carrot2 = Carrot2::createDefault();
    if ($carrot2->isAvailable()) {
        success("Successfully connected to Carrot2 DCS.");
    } else {
        error("Failed to connect to Carrot2 DCS.");
    }
} else {
    neutral("Carrot2 DCS is not configured. Skipping test.");
}
echo "\n";

// Test EFS Mount
echo "Testing EFS Mount...\n";
if (!empty($CONF['photo_upload_dir'])) {
    if (is_dir($CONF['photo_upload_dir']) && is_writable($CONF['photo_upload_dir'])) {
        success("EFS mount at {$CONF['photo_upload_dir']} is present and writable.");
    } else {
        error("EFS mount at {$CONF['photo_upload_dir']} is not present or not writable.");
    }
} else {
    neutral("EFS mount is not configured. Skipping test.");
}
echo "\n";

echo "============================\n";
echo "Success: {$counts['success']}, Error: {$counts['error']}, Neutral: {$counts['neutral']}\n";
