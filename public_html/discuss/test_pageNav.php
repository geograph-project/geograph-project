<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the functions file
require_once 'bb_functions.php';

// Setup necessary global variables
$GLOBALS['mod_rewrite'] = true; // Default, can be overridden
$GLOBALS['viewpagelim'] = 5000;  // Default high value
$GLOBALS['l_menu'] = array(); // Define as an array
$GLOBALS['indexphp'] = 'index.php';
$GLOBALS['main_url'] = 'http://localhost';
$GLOBALS['action'] = ''; // Default action
$GLOBALS['sitename'] = 'Test Site';
// For pageNav, these are the most likely relevant ones.
// Others from bb_functions.php like $dateFormat, $l_months etc., are not directly used by pageNav.
// $user_id might be relevant if pageNav had user-specific logic, but it doesn't seem to.
$GLOBALS['user_id'] = 0;


// Helper function to run and print test cases
function run_test_case($description, $page, $numRows, $url, $viewMax, $navCell) {
    echo "<h3>" . htmlspecialchars($description) . "</h3>\n";
    echo "<pre>";
    echo "Parameters:\n";
    echo "  page      = " . htmlspecialchars($page) . "\n";
    echo "  numRows   = " . htmlspecialchars($numRows) . "\n";
    echo "  url       = " . htmlspecialchars($url) . "\n";
    echo "  viewMax   = " . htmlspecialchars($viewMax) . "\n";
    echo "  navCell   = " . ($navCell ? 'true' : 'false') . "\n";
    echo "  mod_rewrite = " . ($GLOBALS['mod_rewrite'] ? 'true' : 'false') . "\n";
    echo "  viewpagelim = " . htmlspecialchars($GLOBALS['viewpagelim']) . "\n";
    
    $output = pageNav($page, $numRows, $url, $viewMax, $navCell);
    
    echo "</pre>\nOutput:\n";
	if (empty($output)) {
		print "<i>none</i>";
	} else {
	    echo $output;
	}
    echo "<hr>\n";
}

echo "<h1>pageNav Test Script</h1>\n";

// --- Test Case 1: Single Page ---
echo "<h2>Test Case 1: Single Page</h2>\n";
run_test_case("1.1: Single Page, navCell=false", 0, 10, 'test.php?foo=bar&p=', 10, false);
run_test_case("1.2: Single Page, navCell=true", 0, 10, 'test.php?foo=bar&p=', 10, true);
run_test_case("1.3: Single Page (less items than viewMax), navCell=false", 0, 5, 'test.php?foo=bar&p=', 10, false);

// --- Test Case 2: Two Pages ---
echo "<h2>Test Case 2: Two Pages</h2>\n";
$numRows_tc2 = 20; $viewMax_tc2 = 10; $url_tc2 = 'page.php?p=';
run_test_case("2.1: Two Pages, Page 1, navCell=false", 0, $numRows_tc2, $url_tc2, $viewMax_tc2, false);
run_test_case("2.2: Two Pages, Page 1, navCell=true", 0, $numRows_tc2, $url_tc2, $viewMax_tc2, true);
run_test_case("2.3: Two Pages, Page 2, navCell=false", 1, $numRows_tc2, $url_tc2, $viewMax_tc2, false);
run_test_case("2.4: Two Pages, Page 2, navCell=true", 1, $numRows_tc2, $url_tc2, $viewMax_tc2, true);

// --- Test Case 3: Three Pages ---
echo "<h2>Test Case 3: Three Pages</h2>\n";
$numRows_tc3 = 30; $viewMax_tc3 = 10; $url_tc3 = 'itemlist.php?cat=1&p=';
run_test_case("3.1: Three Pages, Page 1, navCell=false", 0, $numRows_tc3, $url_tc3, $viewMax_tc3, false);
run_test_case("3.2: Three Pages, Page 2, navCell=true", 1, $numRows_tc3, $url_tc3, $viewMax_tc3, true);
run_test_case("3.3: Three Pages, Page 3, navCell=false", 2, $numRows_tc3, $url_tc3, $viewMax_tc3, false);

// --- Test Case 4: Moderate Pages (7 pages) ---
echo "<h2>Test Case 4: Moderate Pages (7 pages)</h2>\n";
$numRows_tc4 = 70; $viewMax_tc4 = 10; $url_tc4 = 'archive.php?y=2023&p=';
run_test_case("4.1: 7 Pages, Page 1, navCell=false", 0, $numRows_tc4, $url_tc4, $viewMax_tc4, false);
run_test_case("4.2: 7 Pages, Page 2, navCell=true", 1, $numRows_tc4, $url_tc4, $viewMax_tc4, true);
run_test_case("4.3: 7 Pages, Page 4 (middle), navCell=false", 3, $numRows_tc4, $url_tc4, $viewMax_tc4, false);
run_test_case("4.4: 7 Pages, Page 6 (second to last), navCell=true", 5, $numRows_tc4, $url_tc4, $viewMax_tc4, true);
run_test_case("4.5: 7 Pages, Page 7 (last), navCell=false", 6, $numRows_tc4, $url_tc4, $viewMax_tc4, false);

// --- Test Case 5: Many Pages (20 pages) ---
echo "<h2>Test Case 5: Many Pages (20 pages)</h2>\n";
$GLOBALS['mod_rewrite'] = false; // For this block
$numRows_tc5 = 200; $viewMax_tc5 = 10; $url_tc5 = 'search.php?q=test&p=';
run_test_case("5.1: 20 Pages, Page 1, navCell=false", 0, $numRows_tc5, $url_tc5, $viewMax_tc5, false);
run_test_case("5.2: 20 Pages, Page 2, navCell=true", 1, $numRows_tc5, $url_tc5, $viewMax_tc5, true);
run_test_case("5.3: 20 Pages, Page 10 (middle), navCell=false", 9, $numRows_tc5, $url_tc5, $viewMax_tc5, false);
run_test_case("5.4: 20 Pages, Page 19 (second to last), navCell=true", 18, $numRows_tc5, $url_tc5, $viewMax_tc5, true);
run_test_case("5.5: 20 Pages, Page 20 (last), navCell=false", 19, $numRows_tc5, $url_tc5, $viewMax_tc5, false);

$numRows_tc5 = 15000;  $viewMax_tc5 = 10;
run_test_case("5.9: 1500 Pages, Page 1234 (last), navCell=false", 1234, $numRows_tc5, $url_tc5, $viewMax_tc5, false);

$GLOBALS['mod_rewrite'] = true; // Reset

// --- Test Case 6: viewpagelim Effect ---
echo "<h2>Test Case 6: viewpagelim Effect</h2>\n";
$numRows_tc6 = 200; $viewMax_tc6 = 10; $url_tc6 = 'longlist.php?p=';
$GLOBALS['viewpagelim'] = 5; // Limit to 5 pages
run_test_case("6.1: viewpagelim=5, Page 1, navCell=false", 0, $numRows_tc6, $url_tc6, $viewMax_tc6, false);
run_test_case("6.2: viewpagelim=5, Page 4, navCell=false", 3, $numRows_tc6, $url_tc6, $viewMax_tc6, false);
run_test_case("6.3: viewpagelim=5, Page 5 (effective last), navCell=false", 4, $numRows_tc6, $url_tc6, $viewMax_tc6, false);
run_test_case("6.4: viewpagelim=5, Page 8 (requested beyond limit), navCell=false", 7, $numRows_tc6, $url_tc6, $viewMax_tc6, false);
$GLOBALS['viewpagelim'] = 100; // Reset

echo "<h2>All tests completed.</h2>";

