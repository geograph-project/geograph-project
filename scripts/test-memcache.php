<?php

$param = array();
$param['adv'] = 0;
$param['verbose'] = 0;
$param['latency'] = 0;

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

/*
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
*/

/*
//var_export($CONF['memcache']['app']);print "\n";
unset($CONF['memcache']['app']['redis']); //need to unset, as 0/false means db:0!
//dont use 'host' as that will immidately try connect, want to mutli-server via 'addServer'
$CONF['memcache']['app']['host1'] = "memcache-0.memcache.dev.svc.cluster.local";   $CONF['memcache']['app']['port1'] = 11211;
$CONF['memcache']['app']['host2'] = "memcache-1.memcache.dev.svc.cluster.local";   $CONF['memcache']['app']['port2'] = 11211;
$CONF['memcache']['app']['host3'] = "memcache-2.memcache.dev.svc.cluster.local";   $CONF['memcache']['app']['port3'] = 11211;
done in config now!
*/
//print_r($CONF['memcache']);
print json_encode($CONF['memcache'])."\n\n";

// Test Memcache Connection
echo "Testing Memcache Connection...\n";

if (!empty($CONF['memcache']['app'])) {
    $memcache = new MultiServerMemcache($CONF['memcache'], 'app');

    if (isset($CONF['memcache']['app']['redis'])) {
        // Redis is being used as the memcache server.
        $stats = $memcache->getStats(); // Directs to Redis stats command

        if ($stats) {
            if (!empty($param['verbose'])) {
                print_r($stats);
            }
            success("Successfully connected to Memcache (using Redis).");
            printf(
                "  Redis Server: %s | Role: %s | Slaves: %d\n",
                $stats['redis_version'],
                $stats['role'],
                $stats['connected_slaves']
            );
        } else {
            error("Failed to connect to Memcache (Redis server is down or unreachable).");
        }
    } else {
        // Standard Memcache servers are being used.
        $stats = $memcache->getExtendedStats();

        if ($stats) {
            $total_servers = count($stats);
            $servers_online = 0;

            if (!empty($param['verbose'])) {
                print_r($stats);
            }

            // Loop through and report stats for each server
            foreach ($stats as $server => $data) {
                if (is_array($data) && isset($data['version'], $data['uptime'])) {
                    printf(
                        "  + Server: %s | Version: %s | Uptime: %d seconds\n",
                        $server,
                        $data['version'],
                        $data['uptime']
                    );
                    $servers_online++;
                } else {
                    printf(
                        "  - Server: %s | Status: Failed to connect\n",
                        $server
                    );
                }
            }

            // --- Final Status Report ---
            if ($servers_online === $total_servers) {
                success("Memcache: All $total_servers servers are functional.");
            } elseif ($servers_online > 0) {
                neutral("Memcache: Only $servers_online out of $total_servers servers are online.");
            } else {
                error("Memcache: Failed to connect to ALL $total_servers servers.");
            }
            // --- End Final Status Report ---

        } else {
            error("Failed to get Memcache stats. Check server configuration/connection.");
        }
    }
} else {
    neutral("Memcache is not configured. Skipping test.");
}
echo "\n";

echo "============================\n";

if (!empty($param['adv'])) {

	//we actully testing the 'memcache' inteface!
	$namespace = 'test';
	$mkey = $param['adv'].$_SERVER['PHP_SELF'];
	$payload = 'test';

	//$memcache = new MultiServerMemcache($CONF['memcache'], 'app');

	$period = $memcache->period_short;

	print "starting...";
	while (1) {
		$r = array();
		$c = 0;
		foreach(range(1,10)  as $idx) {
			$str = $memcache->name_get($namespace,$mkey.$idx);
			if ($str) {
				$r[] = sprintf('%3ds ago',time() - $str);
				$c++;
			} else {
				$r[] = "now     ";
				$payload = time(); //will be passed by referece!
$period = rand(16,32);
				$memcache->name_set($namespace,$mkey.$idx,$payload, false,$period);
			}
		}
		$r[] = $c;
		print "\r".implode(', ',$r).str_repeat(' ',30);
		sleep(1);
	}
	print "\n";
}

###################################

if (!empty($param['latency']) && $memcache->valid) {

    // --- Configuration ---
    $num_loops = 1000;
    $num_outer_runs = 10; // Number of times to repeat the full test sequence
    $pause_between_runs = 10; // Seconds to pause between test runs

    $namespace = 'latency_test';
    $base_key = 'latency_key_';
    $test_value = str_repeat('A', 100); // 100-byte payload
    $expiry = 60; // 1 minute expiry

    // Total duration array to accumulate results for averaging
    $total_durations = [
        'name_set' => 0.0,
        'name_get_miss' => 0.0,
        'name_get_hit' => 0.0,
        'name_increment' => 0.0,
    ];
    echo "Running Cache Latency Test (Inner Loops: {$num_loops}, Outer Runs: {$num_outer_runs})...\n";

    $memcache_type = $memcache->redis ? 'Redis (via Memcache)' : ('Memcache, Servers: '.count($CONF['memcache']['servers']));

    // Helper function to run and time a block of operations
    function time_operations($memcache, $operation_type, $num_loops, $namespace, $base_key, $test_value, $expiry, $setup_func = null) {
        $start = microtime(true);
        $total_success = 0;

        if ($setup_func) {
            $setup_func();
        }

        for ($i = 1; $i <= $num_loops; $i++) {
            $key = $base_key . $i;

            switch ($operation_type) {
                case 'name_set':
                    if ($memcache->name_set($namespace, $key, $test_value, false, $expiry)) {
                        $total_success++;
                    }
                    break;
                case 'name_get_miss':
                    // This relies on the key not existing, Delete the key before attempting a read
                    $memcache->name_delete($namespace, $key);
                    $result = $memcache->name_get($namespace, $key);
                    if ($result === false) { 
                        $total_success++; // Success is getting 'false'
                    }
                    // For the next loop's set test to be clean, we need to delete too
                    break;
                case 'name_get_hit':
                    // We must ensure the key is present before reading (SETUP FUNCTION handles this)
                    $result = $memcache->name_get($namespace, $key);
                    if ($result === $test_value) {
                        $total_success++;
                    }
                    break;
                case 'name_increment':
                    // Requires the key to be an integer (SETUP FUNCTION handles this)
                    // The loop iterates $num_loops times, hitting $num_loops different keys
                    if ($memcache->name_increment($namespace, $key)) {
                         $total_success++;
                    }
                    break;
            }
        }

        $duration = microtime(true) - $start;
        $avg_latency_ms = ($duration * 1000) / $num_loops;

        // Final cleanup for set/hit/increment tests
        if ($operation_type !== 'name_get_miss') {
             // Forcing deletion to avoid interference with other tests (only necessary for 1 key, but safe for all)
             // We can't delete all $num_loops keys here, but deleting the first one is a good habit.
             $memcache->name_delete($namespace, $base_key . 1);
        }

        printf(
            "  - %s: Duration: %.3f s | Success: %d/%d | Avg Latency: %.4f ms/op\n",
            str_pad($operation_type, 18), 
            $duration, 
            $total_success, 
            $num_loops, 
            $avg_latency_ms
        );

        return $duration;
    }

    // --- Setup Functions (Outside the main loop) ---

    // Setup function for GET HIT: pre-populate all keys
    $setup_hit = function() use ($memcache, $num_loops, $namespace, $base_key, $test_value, $expiry) {
        for ($i = 1; $i <= $num_loops; $i++) {
            $memcache->name_set($namespace, $base_key . $i, $test_value, false, $expiry);
        }
    };

    // Setup function for INCREMENT: pre-populate ALL $num_loops keys with 0
    $setup_inc = function() use ($memcache, $num_loops, $namespace, $base_key, $expiry) {
        for ($i = 1; $i <= $num_loops; $i++) {
            $zero = 0; //so can pass by reference
            $memcache->name_set($namespace, $base_key . $i, $zero, false, $expiry);
        }
    };

    // --- Test Execution ---
    echo "Testing Type: {$memcache_type}\n";
    echo str_repeat('=', 50) . "\n";

    for($outer = 1; $outer <= $num_outer_runs; $outer++) {
        echo "--- RUN {$outer}/{$num_outer_runs} ---\n";

        // 1. SET (Write Test)
        $total_durations['name_set'] += time_operations($memcache, 'name_set', $num_loops, $namespace, $base_key, $test_value, $expiry);

        // 2. GET MISS (Read Miss Test)
        $total_durations['name_get_miss'] += time_operations($memcache, 'name_get_miss', $num_loops, $namespace, $base_key, $test_value, $expiry);

        // 3. GET HIT (Read Hit Test)
        $total_durations['name_get_hit'] += time_operations($memcache, 'name_get_hit', $num_loops, $namespace, $base_key, $test_value, $expiry, $setup_hit);

        // 4. INCREMENT (Atomic Operation Test)
        $total_durations['name_increment'] += time_operations($memcache, 'name_increment', $num_loops, $namespace, $base_key, $test_value, $expiry, $setup_inc);

        if ($outer < $num_outer_runs) {
             printf("Pausing for %d seconds...\n", $pause_between_runs);
             sleep($pause_between_runs);
        }
    }

    // --- Final Summary ---
    echo str_repeat('=', 50) . "\n";
    echo "--- FINAL AVERAGE OVER {$num_outer_runs} RUNS ($memcache_type) ---\n";

    foreach ($total_durations as $op_type => $total_duration) {
        $avg_total_duration = $total_duration / $num_outer_runs;
        $avg_latency_ms = ($avg_total_duration * 1000) / $num_loops;

        printf(
            "  - %s: Avg Total Time: %.3f s | Avg Latency: %.4f ms/op\n",
            str_pad($op_type, 18),
            $avg_total_duration,
            $avg_latency_ms
        );
    }

    echo "Latency test complete.\n";

} elseif (!empty($param['adv'])) {
    echo "Cache object is not valid. Skipping latency test.\n";
}
echo "\n";

####################################

echo "Success: {$counts['success']}, Error: {$counts['error']}, Neutral: {$counts['neutral']}\n";
