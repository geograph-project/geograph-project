<?

//~~~~ TEMP BODGE!

$_SERVER['DOCUMENT_ROOT'] = '/var/www/geograph_toy/public_html';
ini_set('include_path','.:/var/www/geograph_toy/libs');

//~~~~

require_once('geograph/global.inc.php');

?>

<h3>Geograph Demo/Toy Application</h3>

This site is a very basic implementation of the Geograph Application. Just enough implementation to test the various backend services needed.

If all components are working, should see responces via each service bewow... 
<hr>

<?


###################################
# Files!

print "<h4>File Serving</h4>";

$filesystem = new FileSystem;

$filename = "photos/test-photo.jpg";

if ($filesystem->exists($filename)) {
	print "<p>If see small image here: it works!";
	print "<img src=\"{$CONF['STATIC_HOST']}/$filename\" style=max-width:100px><hr>";
} else {
	print "Error: $filename NOT found<hr>";
}

###################################
# Mysql

###################################
# Sphinx/Manticore

###################################
# Redis

###################################
# Memcache (possibly Redis anyway!)

###################################
# Smarty

print "<h4>Smarty PHP Templateing</h4>";

$smarty = new GeographPage;

$smarty->display('toy.tpl');

print "<hr>";

###################################
#

###################################
#

###################################
#

print "<hr>. Page Generated: ".date('r');



