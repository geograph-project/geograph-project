<?php

// --- Test Environment Setup ---
// Simulate the environment of search.php

// Mock the GeographUser class and $USER object
class MockGeographUser {
    public $user_id = 0;
    public $registered = false;
    public $displayclass = null; // User's preference
    public $criteria_user_id = null; // for engine->criteria->user_id check

    public function __construct($is_registered = false, $preference = null, $user_id = 0) {
        $this->registered = $is_registered;
        $this->displayclass = $preference;
        $this->user_id = $is_registered ? ($user_id ?: 1) : 0;
    }
}

// Mock the SearchEngine and its criteria property
class MockSearchEngine {
    public $criteria;
    public $temp_displayclass = null; // Used by search.php
    public $display = null; // Set by search.php

    public function __construct() {
        $this->criteria = new stdClass();
        $this->criteria->user_id = null; // Simulate the user_id of the query owner
    }

    public function getDisplayclass() {
        // Simulate the initial display class fetched by the engine,
        // which might be based on query's own settings or a system default
        // before user preferences or URL params are checked in search.php.
        return $this->criteria->displayclass ?? 'full'; // Default to 'full' if not set on criteria
    }

    public function setDisplayclass($displayclass) {
        // Simulate setting display class on the engine's criteria (permanent save for query)
        $this->criteria->displayclass = $displayclass;
        echo "MockSearchEngine: setDisplayclass called with {$displayclass}\n";
    }
}

// Define $displayclasses array, as in search.php
$displayclasses =  array(
    'full' => 'full listing',
    'more' => 'full listing + links',
    'thumbs' => 'thumbnails only',
    'thumbsmore' => 'thumbnails + links',
    'bigger' => 'thumbnails - bigger',
    'grid' => 'thumbnail grid',
    'excerpt' => 'highlighted keywords',
    'map' => 'on a map',
    'slide' => 'slideshow',
    'slidebig' => 'slideshow - full page',
    'reveal' => 'slideshow - map imagine',
    'black' => 'georiver - full images + detail',
    'text' => 'text list only',
    'spelling' => 'multi editor'
);

// --- Test Functions ---

function run_search_logic() {
    // This function simulates the relevant part of search.php
    // It uses global variables $USER, $_GET, $engine, $displayclasses
    global $USER, $_GET, $engine, $displayclasses;

    // Logic copied and adapted from public_html/search.php (around lines 1000-1015)
    // This is the block of code we are testing.

    $display = $engine->getDisplayclass(); // Initial display class from engine/query

	// Prioritize URL parameters for displayclass
	if (isset($_GET['displayclass']) && preg_match('/^\w+$/',$_GET['displayclass'])) {
		$current_display_param = $_GET['displayclass'];
        if (isset($displayclasses[$current_display_param])) { // Validate against known displayclasses
            $display = $current_display_param;
            // Simulate search.php logic:
            // If the user is registered AND the owner of the search query,
            // and the displayclass isn't a special 'search' type, then persist it.
            if ($USER->registered && $USER->user_id == $engine->criteria->user_id && $current_display_param != 'search' && $current_display_param != 'searchtext') {
                $engine->setDisplayclass($current_display_param);
            } else {
                //don't store search override permently
                $engine->temp_displayclass = $display;
            }
        }
	} elseif (isset($_GET['temp_displayclass']) && preg_match('/^\w+$/',$_GET['temp_displayclass'])) {
		$current_temp_display_param = $_GET['temp_displayclass'];
        if (isset($displayclasses[$current_temp_display_param])) { // Validate
            $display = $current_temp_display_param;
            $engine->temp_displayclass = $display;
        }
	// If no displayclass in URL, try user preference
	} elseif (isset($USER->displayclass) && !empty($USER->displayclass) && isset($displayclasses[$USER->displayclass])) {
		$display = $USER->displayclass;
		// Set it on the engine as well, if applicable
		$engine->temp_displayclass = $USER->displayclass;
	}

	if (empty($display) || !isset($displayclasses[$display])) { // Ensure display is valid or default
		$display = 'full'; // Fallback to default if no preference or invalid preference
    }
	$engine->display = $display; // search.php sets this on the engine instance

    return $display; // Return the determined display value
}

function testUserPreferenceApplied() {
    echo "Running testUserPreferenceApplied...\n";
    global $USER, $_GET, $engine;

    // Setup: Logged-in user with 'thumbs' preference, no URL param
    $USER = new MockGeographUser(true, 'thumbs');
    $_GET = array(); // No displayclass in URL
    $engine = new MockSearchEngine();
    $engine->criteria->user_id = $USER->user_id; // User is owner of the (simulated) query

    $result_display = run_search_logic();

    if ($result_display === 'thumbs') {
        echo "SUCCESS: Search uses user preference 'thumbs'.\n";
    } else {
        echo "FAILURE: Search did not use user preference. Expected 'thumbs', got '{$result_display}'.\n";
    }
    if ($engine->temp_displayclass === 'thumbs') {
        echo "INFO: engine->temp_displayclass correctly set to 'thumbs'.\n";
    } else {
        echo "INFO: engine->temp_displayclass is '{$engine->temp_displayclass}'.\n";
    }
}

function testUrlParameterOverride() {
    echo "Running testUrlParameterOverride...\n";
    global $USER, $_GET, $engine;

    // Setup: Logged-in user with 'thumbs' preference, URL param 'grid'
    $USER = new MockGeographUser(true, 'thumbs');
    $_GET = array('displayclass' => 'grid');
    $engine = new MockSearchEngine();
    $engine->criteria->user_id = $USER->user_id; // User is owner

    $result_display = run_search_logic();

    if ($result_display === 'grid') {
        echo "SUCCESS: Search uses URL parameter 'grid', overriding user preference.\n";
    } else {
        echo "FAILURE: Search did not use URL parameter. Expected 'grid', got '{$result_display}'.\n";
    }
    // Check if permanent save was attempted (if user owns the query)
    if ($engine->criteria->displayclass === 'grid') {
        echo "INFO: engine->setDisplayclass was called with 'grid' (permanent save attempted).\n";
    } elseif ($engine->temp_displayclass === 'grid') {
        echo "INFO: engine->temp_displayclass was set to 'grid'.\n";
    } else {
        echo "INFO: Neither criteria->displayclass nor temp_displayclass ended up as 'grid'. temp_displayclass: '{$engine->temp_displayclass}', criteria->displayclass: '{$engine->criteria->displayclass}'.\n";
    }
}

function testUrlParameterOverrideNotOwner() {
    echo "Running testUrlParameterOverrideNotOwner...\n";
    global $USER, $_GET, $engine;

    // Setup: Logged-in user with 'thumbs' preference, URL param 'grid', but user does NOT own the search query
    $USER = new MockGeographUser(true, 'thumbs', 1); // User ID 1
    $_GET = array('displayclass' => 'grid');
    $engine = new MockSearchEngine();
    $engine->criteria->user_id = 2; // Query owned by User ID 2

    $result_display = run_search_logic();

    if ($result_display === 'grid') {
        echo "SUCCESS: Search uses URL parameter 'grid' (user not owner).\n";
    } else {
        echo "FAILURE: Search did not use URL parameter. Expected 'grid', got '{$result_display}'.\n";
    }
    if ($engine->temp_displayclass === 'grid') {
        echo "INFO: engine->temp_displayclass correctly set to 'grid' as URL override (not permanent).\n";
    } else {
        echo "INFO: engine->temp_displayclass is '{$engine->temp_displayclass}'. Expected 'grid'.\n";
    }
     if (isset($engine->criteria->displayclass) && $engine->criteria->displayclass === 'grid') {
        echo "INFO: engine->criteria->displayclass was set to 'grid', but shouldn't have been permanent.\n";
    } else {
        echo "INFO: engine->criteria->displayclass not permanently set to 'grid', which is correct.\n";
    }
}


function testNoPreferenceNoUrlParameter() {
    echo "Running testNoPreferenceNoUrlParameter (Guest User)...\n";
    global $USER, $_GET, $engine;

    // Setup: Guest user (no preference), no URL param
    $USER = new MockGeographUser(false); // Not registered
    $_GET = array();
    $engine = new MockSearchEngine();
    // $engine->criteria->user_id remains null or different from $USER->user_id

    $result_display = run_search_logic();

    if ($result_display === 'full') {
        echo "SUCCESS: Search uses default 'full' for guest user.\n";
    } else {
        echo "FAILURE: Search did not use default. Expected 'full', got '{$result_display}'.\n";
    }
}

function testNoPreferenceNoUrlParameterLoggedIn() {
    echo "Running testNoPreferenceNoUrlParameterLoggedIn (User with no pref)...\n";
    global $USER, $_GET, $engine;

    // Setup: Logged-in user with null preference, no URL param
    $USER = new MockGeographUser(true, null);
    $_GET = array();
    $engine = new MockSearchEngine();
    $engine->criteria->user_id = $USER->user_id;

    $result_display = run_search_logic();

    if ($result_display === 'full') {
        echo "SUCCESS: Search uses default 'full' for logged-in user with no preference.\n";
    } else {
        echo "FAILURE: Search did not use default. Expected 'full', got '{$result_display}'.\n";
    }
}

function testInvalidUserPreference() {
    echo "Running testInvalidUserPreference...\n";
    global $USER, $_GET, $engine;

    // Setup: Logged-in user with an invalid 'foo' preference, no URL param
    $USER = new MockGeographUser(true, 'foo');
    $_GET = array();
    $engine = new MockSearchEngine();
    $engine->criteria->user_id = $USER->user_id;

    $result_display = run_search_logic();

    if ($result_display === 'full') {
        echo "SUCCESS: Search uses default 'full' when user preference is invalid.\n";
    } else {
        echo "FAILURE: Search did not use default for invalid preference. Expected 'full', got '{$result_display}'.\n";
    }
}

function testInvalidUrlParameter() {
    echo "Running testInvalidUrlParameter...\n";
    global $USER, $_GET, $engine;

    // Setup: User with 'thumbs' preference, URL param 'invalidformat'
    $USER = new MockGeographUser(true, 'thumbs');
    $_GET = array('displayclass' => 'invalidformat');
    $engine = new MockSearchEngine();
    $engine->criteria->user_id = $USER->user_id;

    $result_display = run_search_logic();

    // If URL param is invalid, it should fall back to user pref or default.
    // The current logic in search.php, if URL param is present but not in $displayclasses, will make $display empty.
    // Then the final `if (empty($display))` check will set it to 'full'.
    // This means an invalid URL param effectively makes it behave like no URL param *if the user has no preference or an invalid one*.
    // If the user *has* a valid preference, that should be used if URL is invalid.
    // Let's re-verify search.php logic:
    // 1. $display = $engine->getDisplayclass() ('full' by default from mock)
    // 2. $_GET['displayclass'] is 'invalidformat'. It's not in $displayclasses. So $display remains 'full'.
    // 3. Then it checks $USER->displayclass ('thumbs'). This is valid. So $display becomes 'thumbs'.
    // This needs refinement in the test or the run_search_logic simulation.

    // Corrected simulation of search.php logic for invalid URL param:
    // if (isset($_GET['displayclass']) && preg_match... ) {
    //    $current_display_param = $_GET['displayclass'];
    //    if (isset($displayclasses[$current_display_param])) { // This condition is FALSE for 'invalidformat'
    //        $display = $current_display_param;
    //        ...
    //    } // <<<< IF NOT VALID, $display IS NOT MODIFIED HERE
    // } elseif (isset($_GET['temp_displayclass']) ... ) { ...
    // } elseif (isset($USER->displayclass) ... ) { // This IS evaluated if $_GET['displayclass'] was invalid
    //    $display = $USER->displayclass; // So 'thumbs'
    // }
    // if (empty($display) || !isset($displayclasses[$display])) { $display = 'full'; } // 'thumbs' is valid.

    if ($result_display === 'thumbs') {
        echo "SUCCESS: Search uses user preference 'thumbs' when URL parameter is invalid.\n";
    } else {
        echo "FAILURE: Search did not use user preference for invalid URL param. Expected 'thumbs', got '{$result_display}'.\n";
    }
}

function testTempDisplayClassParameter() {
    echo "Running testTempDisplayClassParameter...\n";
    global $USER, $_GET, $engine;

    // Setup: User with 'thumbs' preference, temp_displayclass URL param 'grid'
    $USER = new MockGeographUser(true, 'thumbs');
    $_GET = array('temp_displayclass' => 'grid');
    $engine = new MockSearchEngine();
    $engine->criteria->user_id = $USER->user_id;

    $result_display = run_search_logic();

    if ($result_display === 'grid') {
        echo "SUCCESS: Search uses 'temp_displayclass' URL parameter 'grid'.\n";
    } else {
        echo "FAILURE: Search did not use 'temp_displayclass'. Expected 'grid', got '{$result_display}'.\n";
    }
    if ($engine->temp_displayclass === 'grid') {
        echo "INFO: engine->temp_displayclass correctly set to 'grid'.\n";
    } else {
        echo "INFO: engine->temp_displayclass is '{$engine->temp_displayclass}'.\n";
    }
    if (isset($engine->criteria->displayclass) && $engine->criteria->displayclass === 'grid') {
        echo "INFO: engine->criteria->displayclass was set to 'grid', but 'temp_displayclass' should not cause permanent save.\n";
    } else {
        echo "INFO: engine->criteria->displayclass not permanently set, which is correct for 'temp_displayclass'.\n";
    }
}


// --- Run Tests ---
echo "--- Starting Search DisplayClass Logic Tests (search.php simulation) ---\n";
testUserPreferenceApplied();
echo "\n";
testUrlParameterOverride();
echo "\n";
testUrlParameterOverrideNotOwner();
echo "\n";
testNoPreferenceNoUrlParameter();
echo "\n";
testNoPreferenceNoUrlParameterLoggedIn();
echo "\n";
testInvalidUserPreference();
echo "\n";
testInvalidUrlParameter();
echo "\n";
testTempDisplayClassParameter();
echo "--- Finished Search DisplayClass Logic Tests ---\n\n";

?>
