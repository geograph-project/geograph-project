<?

require_once('geograph/global.inc.php');
init_session();

if (empty($CONF['gallery_url'])) {
	header("HTTP/1.0 503 Unavailable");
	$smarty = new GeographPage;

	$smarty->display("sample8_unavailable.tpl");
	exit;
}

if ($CONF['template'] == 'ireland' && empty($_GET['crit']))
	$_GET['crit'] = "Country:Republic of Ireland";

if (!empty($mobile_browser))
	$_GET['mobile']= 1;

//awkward, but linktoself, uses REQUEST_URI
$_SERVER['REQUEST_URI'] = "?".http_build_query($_GET,'','&');


customGZipHandlerStart();

?><html>
<head><title>Geograph Gallery</title>
<meta http-equiv="content-type" content="text/html;charset=iso-8859-1">
<link rel="shortcut icon" type="image/x-icon" href="https://s1.geograph.org.uk/favicon.ico">
<link rel="apple-touch-icon" href="https://www.geograph.org.uk/apple-touch-icon.png">
<meta name="description" content="Used rated gallery of high quality photos">
<link rel="stylesheet" type="text/css" title="Monitor" href="<? echo smarty_modifier_revision("/templates/basic/css/basic.css"); ?>" media="screen">
<? if (!empty($mobile_browser)) { ?>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" title="Monitor" href="<? echo smarty_modifier_revision("/templates/resp/css/modification.css"); ?>" media="screen">
	<link rel="stylesheet" type="text/css" title="Monitor" href="<? echo smarty_modifier_revision("/templates/resp/css/responsive.css"); ?>" media="screen">
<? } ?>
<script type="text/javascript" src="<? echo smarty_modifier_revision("/js/geograph.js"); ?>"></script>

<style>
* { -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; }

body {
	background-color:black;
}
iframe.full_screen {
	position:absolute;top:0;left:0;width:100%;height:100%;padding-top:74px;border:0;
	background-color:black;
}
#header_block {
	position:absolute;top:0;left;0;width:100%;
}
#nav_block {
        position:absolute;top:74px;left;0;
}
</style>

<script>
var hidetimer = null;
function showMenu() {
	document.getElementById("nav_block").style.display = '';
	if (hidetimer) {
		clearTimeout(hidetimer);
		hidetimer = null;
	}
}
function hideMenu() {
	hidetimer = setTimeout(function() {
		document.getElementById("nav_block").style.display = 'none';
		hidetimer = null;
	},300);
}
</script>
</head>
<body>
<iframe src="<? echo $CONF['gallery_url'].smarty_function_linktoself(array()); if (!empty($_GET['id'])) { print "#!/www.geograph.org.uk/photo/".intval($_GET['id']); } ?>" width="100%" height="100%" frameborder="0" allow="geolocation" class="full_screen"></iframe>

<div id="header_block">
  <div id="header" click="document.location='/';">
    <h1 onclick="document.location='/';" onmouseover="showMenu()" onmouseout="hideMenu()"><a title="Geograph home page" href="/">GeoGraph - photograph every grid square</a></h1>
  </div>
</div>

<div id="nav_block" style="display:none" onmouseover="showMenu()" onmouseout="hideMenu()">
 <div class="nav">
  <ul>
    <li style="font-size:1.42em"><a accesskey="1" title="Return to the Home Page" href="/">Home</a></li>
    <li>View<ul>
     <li><a title="Find and locate images" href="/search.php">Search</a></li>
     <li><a title="View map of all submissions" href="/mapper/combined.php">Maps</a></li>
     <li><a title="Interactive Browse/Search/Map interface" href="/browser/#!start">Browser</a></li>
     <li><a title="Explore images by theme" href="/explore/">Explore</a></li>
     <li><a title="Curated selection of images" href="/gallery.php">Showcase</a></li>
    </ul></li>
    <li><ul>
     <li><a title="Submitted Pages, Galleries and Articles" href="/content/">Collections</a></li>
    </ul></li>
    <li>Interact<ul>
     <li><a title="Geographical games to play" href="/games/">Games</a></li>
     <li><a title="Discussion Forum" href="/discuss/">Discussions</a></li>
     <li><a title="Geograph Blog" href="/blog/">Blog</a></li>
    </ul></li>
    <li>Contributors<ul>
     <li><a title="Submit your photos" href="/submit.php">Submit</a></li>
     <li><a title="Your most recent submissions" href="/submissions.php" class="nowrap">Recent Uploads</a></li>
     <li><a title="Interesting facts and figures" href="/numbers.php">Statistics</a></li>
     <li><a title="Contributor leaderboards" href="/statistics/moversboard.php">Leaderboards</a></li>
    </ul></li>
    <li>General<ul>
     <li><a title="Frequently Asked Questions" href="/faq3.php?l=0">FAQ</a></li>
     <li><a title="Info, Guides and Tutorials" href="/content/documentation.php">Help Pages</a></li>
     <li><a title="View a list of all pages" href="/help/sitemap">Sitemap</a></li>
     <li><a accesskey="9" title="Contact the Geograph Team" href="/contact.php">Contact Us</a></li>
    </ul></li>
</ul>
<div style="text-align:center; padding-top:15px; border-top: 2px solid black; margin-top: 15px;">sponsored by <br/> <br/>
<a title="Geograph sponsored by Ordnance Survey" href="https://www.ordnancesurvey.co.uk/oswebsite/education/"><img src="https://s1.geograph.org.uk/img/os-logo-p64.png" width="64" height="50" alt="Ordnance Survey"/></a></div>
  </div>
</div>

<div id="search_block" class="no_print">
  <div id="search">
    <div id="searchform">
    <form method="get" action="/search.php">
    <div id="searchfield">
    <input type="hidden" name="form" value="simple"/>
    <input id="searchterm" type="text" name="q" value="" size="10" title="Enter a Postcode, Grid Reference, Placename or a text search" onfocus="search_focus(this)" onblur="search_blur(this)"/>    <input id="searchbutton" type="submit" name="go" value="Find"/></div>
    </form>
    </div>
  </div>
  <div id="login"><span class="nowrap">
	<? if ($USER->registered) { ?>
      	  Logged in as <? echo htmlentities($USER->realname); ?>
  	  <span class="sep">|</span>
  	  <a title="Profile" href="/profile.php">profile</a>
  	  <span class="sep">|</span></span>
  	  <a title="Log out" href="/logout.php">logout</a>
        <? } else { ?>
    	  You are not logged in
	  <a title="Already registered? Login in here" href="/login.php">login</a>
		<span class="sep">|</span></span>
	  <a title="Register to upload photos" href="/register.php">register</a>
        <? } ?>
      </div>
</div>

</body>
</html>

