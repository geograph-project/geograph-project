<?

//~~~~ TEMP BODGE!

$_SERVER['DOCUMENT_ROOT'] = '/var/www/geograph_toy/public_html';
ini_set('include_path','.:/var/www/geograph_toy/libs');

//~~~~

require_once('geograph/global.inc.php');

###################################
# Files!

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

$smarty = new GeographPage;

$smarty->display('toy.tpl');

print "<hr>";

###################################
#

###################################
#

###################################
#



