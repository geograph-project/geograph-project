<?php

require_once('geograph/global.inc.php');
init_session();


$smarty = new GeographPage;

$USER->hasPerm("director") || $USER->mustHavePerm("moderator");

$smarty->display('_std_begin.tpl');


$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


// --- Configuration Arrays ---

$tables = array(
	'Recent Profiles'   => array( 'table'=>'moderation', 'key_col'=>'user_id', 'extra'=>'title, moderation_status as status' ),
	'All User Profiles' => array( 'table'=>'moderation_all', 'key_col'=>'user_id', 'extra'=>'title, moderation_status as status' ),
	// The complication: 'gridimage_link' does not have a 'source' column.
	'External Links' => array( 'table'=>'gridimage_link', 'key_col'=>'gridimage_id', 'extra'=>'url' ),
);

$sources = array(
	'All' => '', // Empty string for 'All' means no specific source filter will be applied
	'UserName' => 'user',
	'UserText' => 'user_about',
	'UserURL' => 'user_website', // This source triggers the use of 'ai_assessment'
);

$columns = array(
	'Classification'=> 'ai_class',
	'Assessment'=>'ai_assessment', // This is the column enforced under specific conditions
);

$filters1 = array(
	'All Profiles' => false,
	// Value is an array containing the join clause
	'Only Contributors' => array('join' => 'INNER JOIN user_stat USING (user_id)'),
	'Only non-contributors' =>  array('join' => 'LEFT JOIN user_stat USING (user_id)', 'where'=>'user_stat.user_id is null'),
);

// --- NEW DEFINITION for flexible result filtering ---
$results = array(
	'All Results' => false, // false means no extra WHERE clause is added
	'Unsafe'=> " IN ('unsafe','flagged','negative')",
	'Marketing' => " IN ('marketing','spam')",
	'Unrelated' => " = 'unrelated'",
	'Exclude Benign' => "NOT IN ('normal','personal','approved')", // The original 'Exclude Benign' functionality
	'Exclude Failed' => "NOT IN ('failed','unknown')",
);

// --- Form Submission and Default Handling ---

// Determine the submitted values or set defaults for initial load
$selected_table_key = $_GET['table_key'] ?? 'Recent Profiles';
$selected_source_key = $_GET['source_key'] ?? 'All';
$selected_column_key = $_GET['column_key'] ?? 'Classification';
$selected_filter1_key = $_GET['filter1_key'] ?? 'All Profiles';
$selected_result_key = $_GET['result_key'] ?? 'All Results';

// Initialize variables for SQL construction
$sql = '';
$error_message = '';
$column_override_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && count($_GET) > 0) {
    try {
        // 1. Extract values based on selected keys
        $table_data = $tables[$selected_table_key] ?? null;
        $source_val = $sources[$selected_source_key] ?? null;
        $column_val = $columns[$selected_column_key] ?? null; // Initial choice
        $filter_data = $filters1[$selected_filter1_key] ?? null;
	$result_filter_sql = $results[$selected_result_key] ?? false; 

        if (!$table_data || $source_val === null || !$column_val || $filter_data === null) {
             throw new Exception("Invalid selection detected.");
        }

        $table = $table_data['table'];
        $optional_join = isset($filter_data['join']) ? $filter_data['join'] : '';
        $optional_where_filter = isset($filter_data['where']) ? $filter_data['where'] : ''; 
        $key_column = $table_data['key_col']; // user_id or gi_id
        $extra_column = $table_data['extra'];

        // --- NEW LOGIC: Column Override for ai_assessment/ai_class ---
        $final_column_val = $column_val;

        // Rule 1: If gridimage_link is chosen, enforce ai_assessment (it doesn't have ai_class)
        if ($table === 'gridimage_link') {
            $final_column_val = 'ai_assessment';
            if ($column_val !== 'ai_assessment') {
                $column_override_message = "Your selected column ('{$selected_column_key}') was automatically set to 'Assessment' (ai_assessment) because the 'External Links' table only contains this classification column.";
            }
        }
        // Rule 2: If user chose ai_assessment, but it is not a link context, rollback to ai_class
        // Link contexts are: gridimage_link table (covered by Rule 1) OR UserURL source.
        // We only check this if Rule 1 wasn't triggered.
        elseif ($column_val === 'ai_assessment' && $source_val !== 'user_website') {
            $final_column_val = 'ai_class';
            $column_override_message = "Your selected column ('{$selected_column_key}') was automatically set to 'Classification' (ai_class) because 'Assessment' (ai_assessment) is only available for link-related records (UserURL source or External Links table).";
        }
        // If not forced, $final_column_val remains the user's initial choice

	//if still choosen ai_assessment, we can still show class
	if ($final_column_val == 'ai_assessment' && $table !== 'gridimage_link')
		$extra_column .= ", ai_class";

        // Use the final, potentially overridden column value for the query
        $select_cols = "{$key_column}, {$extra_column}, {$final_column_val}";

        // 2. Start Base Query
        $sql = "SELECT {$select_cols} FROM {$table} {$optional_join} WHERE {$final_column_val} IS NOT NULL";

        // Append the optional WHERE condition from the profile filter, if present
        if (!empty($optional_where_filter)) {
            $sql .= " AND {$optional_where_filter}";
        }

        if (!empty($_GET['miss']))
                $sql .= " AND moderation_status != ai_class";

        if (!empty($_GET['status']) && preg_match('/^\w+$/',$_GET['status']))
                $sql .= " AND moderation_status = ".$db->Quote($_GET['status']);

        if (!empty($_GET['class']) && preg_match('/^\w+$/',$_GET['class']))
                $sql .= " AND {$final_column_val} = ".$db->Quote($_GET['class']);


        // 3. Handle 'source' column complication (gridimage_link table)
        // 'gridimage_link' table does not have a 'source' column.
        if ($table !== 'gridimage_link' && $source_val !== '') {
            // Add source filter, quoting the value as it's an SQL string literal
            $sql .= " AND source = '{$source_val}'";
        }

        // 4. Handle 'results' filter: Add the dynamic SQL fragment
        if ($result_filter_sql !== false) {
            // $result_filter_sql contains the operator and values (e.g., " = 'unsafe'")
            $sql .= " AND {$final_column_val} {$result_filter_sql}";
        }

        // 5. Finalize Query
        $sql .= " LIMIT 250;";

	//////////////////////
	// quick summary query!

	$extra_column = preg_replace('/^(\w+).*/','$1',$extra_column);

	$sql2 = "SELECT $final_column_val,count(*),$extra_column AS example FROM {$table} {$optional_join} WHERE 1";

        if ($table !== 'gridimage_link' && $source_val !== '') {
            // Add source filter, quoting the value as it's an SQL string literal
            $sql2 .= " AND source = '{$source_val}'";
        }

        // Append the optional WHERE condition from the profile filter, if present
        if (!empty($optional_where_filter)) {
            $sql2 .= " AND {$optional_where_filter}";
        }

	$sql2 .= " GROUP BY $final_column_val";


	//////////////////////

    } catch (Exception $e) {
        $error_message = "Error generating SQL: " . $e->getMessage();
        $sql = '';
    }
}

#########################################################################

?>

    <form method="GET" class="space-y-6">

        <!-- Tables Dropdown -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            <!-- 1. Data Source Table -->
            <div>
                <label for="table_key" class="block text-sm font-medium text-gray-700 mb-1">Select Data Table</label>
                <select id="table_key" name="table_key" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                    <?php foreach ($tables as $key => $value): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>"
                            <?php if ($selected_table_key === $key) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($key); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 2. Source Field Filter -->
            <div>
                <label for="source_key" class="block text-sm font-medium text-gray-700 mb-1">Filter By Source Field</label>
                <select id="source_key" name="source_key" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                    <?php foreach ($sources as $key => $value): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>"
                            <?php if ($selected_source_key === $key) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($key); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 4. Profile Filter -->
            <div>
                <label for="filter1_key" class="block text-sm font-medium text-gray-700 mb-1">Profile Filter</label>
                <select id="filter1_key" name="filter1_key" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                    <?php foreach ($filters1 as $key => $value): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>"
                            <?php if ($selected_filter1_key === $key) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($key); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

		<br>

            <!-- 3. Column to Select/Filter -->
            <div>
                <label for="column_key" class="block text-sm font-medium text-gray-700 mb-1">Select Result Column</label>
                <select id="column_key" name="column_key" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                    <?php foreach ($columns as $key => $value): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>"
                            <?php if ($selected_column_key === $key) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($key); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 5. Result Filter (formerly Success Filter) -->
            <div class="lg:col-span-4">
                <label for="result_key" class="block text-sm font-medium text-gray-700 mb-1">Result Success Filter</label>
                <select id="result_key" name="result_key" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm">
                    <?php foreach ($results as $key => $value): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>"
                            <?php if ($selected_result_key === $key) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($key); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
		<br>

        </div>

        <div class="pt-4">
            <button type="submit" class="w-full inline-flex justify-center py-3 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                Generate Report
            </button>
        </div>
    </form>

    <?php if ($error_message): ?>
        <div class="mt-8 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-md" role="alert">
            <p class="font-bold">Error</p>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php endif; ?>


	<hr>
<?

#########################################################################

	if ($final_column_val == 'ai_assessment') {
	  $comments = array(
	    'normal'      => 'A **safe and potentially relevant** link.',
	    'personal'    => 'A **safe, personal** website (e.g., a hobby blog).',
	    'unrelated'   => '**Safe**, but the topic is **not related** to Geograph or the location.',
	    'unsafe'      => 'Site appears **unsafe** (e.g., explicit content, security risk, or malware).',
	    'mismatch'    => 'Content **doesn\'t match the URL** (likely a domain was bought by spammers).',
	    'holding'     => 'The page is now a **static placeholder** (e.g., "Coming Soon" or a parked domain).',
	    'spam'        => 'Site appears to be **low-quality marketing or link-dropping spam**.',
	    'gone'        => 'The **specific page no longer exists** (returns a "Not Found" error).',
	    'unknown'     => 'Unable to determine (e.g., site unclear, or got blocked).',
	    'failed'      => 'The **website is completely offline** (server refusal or timeout).',
	    'other'       => 'something else, not covered above',
	    '' => 'pending',
	  );
	} elseif ($selected_source_key == 'UserText') {
	  $comments = array(
	    'unsafe'      => 'apparently unsafe',
	    'negative'      => 'negative sentiment',
	    'marketing'      => 'clearly seems marketting',
	    'spam'      => 'low quality spam',
	    'personal'      => 'benign personal message',
	    'other'      => 'something else, not covered above',
	    '' => 'pending',
	  );
	} else {
	  $comments = array(
	    'approved'      => 'apparently safe',
	    'flagged'      => 'flagged for attention',
	    'unclear'      => 'ambigious',
	    '' => 'pending',
	  );
	}

	if (!empty($sql2)) {
		$rows= $db->getAssoc($sql2);
		if (!empty($rows)) {
		        print "<h2>Statistics</h2>";
		        print "<table cellspacing=0 cellpadding=4 border=1>";
		        foreach ($rows as $key => $value) {
		                print "<tr>";
		                print "<td>$key";
	       	                print "<td align=right><b>".implode("<td>",array_map('htmlentities',$rows[$key]))."</td>";
		                if (!empty($comments[$key]))
			                print "<td><i>".str_replace('**','',$comments[$key])."</i>";;
		        }
		        print "</table><br><br>";
		}
	}

#########################################################################

        print "<h2>AI Reviewed Content (sample)</h2>";

	//print "<pre>$sql</pre>";
	$data= $db->getAll($sql);

        print "<p>The <b>status</b> is human reviewer result, the <b>ai_class</b> is the AI prediction, <b>ai_assessment</b> is a more indepth look. Can click column headers to reorder rows. Sample of upto 250 results";

        print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

        print "<table cellspacing=0 cellpadding=4 border=1 bordercolor=#eee class=\"report sortable\" id=\"photolist\"><THEAD>";
                print "<tr><th>".implode("</th><th>",array_map('htmlentities',array_keys($data[0])))."</th></tr>";
        print "</THEAD><TBODY>";
        foreach($data as $row) {
                print "<tr>";
		if (!empty($row['status']) && $row['status'] == 'pending') $row['status']='';

                foreach($row as $key => $value) {
                        if (is_numeric($value)) {
                                print "<td align=right>".floatval($value);

                        } elseif ($key == 'ai_class') {
                                $color = 'gray';
                                if ($value == 'unsafe' || $value == 'spam' || $value == 'negative' || $value == 'flagged')
                                        $color = 'red;font-weight:bold';
                                elseif ($value == 'personal' || $value == 'normal')
                                        $color = 'green';
                                elseif ($value == 'other')
                                        $color = 'black';
                                print "<td style=color:$color>".htmlentities($value);

                        } elseif ($key == 'ai_assessment') {
                                $color = 'gray';
                                if ($value == 'unsafe' || $value == 'spam' || $value == 'mismatch')
                                        $color = 'red;font-weight:bold';
                                elseif ($value == 'personal' || $value == 'normal')
                                        $color = 'green';
                                elseif ($value == 'unrelated')
                                        $color = 'black';
                                print "<td style=color:$color>".htmlentities($value);

                        } else {
                                print "<td>".htmlentities($value);
                        }
                }
        }
        print "</table>";

#########################################################################

$smarty->display('_std_end.tpl');
exit;

