<?

//https://www.geograph.org.uk/browser/#!/q=user3+%40takenyear+2018/display=map/pagesize=50

if (!empty($_GET['q'])) {
	$url = '/browser/#!/q='.urlencode($_GET['q']).'/display=map/pagesize=50';
	header("Location: $url", true, 302);
	print "<a href=$url>Click here to continue to map</a>";
	exit;
}

require_once('geograph/global.inc.php');
init_session();

pageMustBeHTTPS();

customGZipHandlerStart();

?><html>
<head><title>Geograph ThumbMap</title>
<link rel="shortcut icon" type="image/x-icon" href="https://s1.geograph.org.uk/favicon.ico"/>
<link rel="apple-touch-icon" href="https://www.geograph.org.uk/apple-touch-icon.png"/>
<meta http-equiv="content-type" content="text/html;charset=iso-8859-1">
<link rel="stylesheet" type="text/css" title="Monitor" href="<? echo smarty_modifier_revision("/templates/basic/css/basic.css"); ?>" media="screen" />
<script type="text/javascript" src="<? echo smarty_modifier_revision("/js/geograph.js"); ?>"></script>

<style>
* { -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box; }

iframe {
	position:absolute;top:0;left;0;width:100%;height:100%;padding-top:74px;border:0;
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
<iframe src="quick-inner.php<? echo smarty_function_linktoself(array()); ?>" width="100%" height="100%" frameborder="0" name="innerframe"></iframe>

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
<a title="Geograph sponsored by Ordnance Survey" href="http://www.ordnancesurvey.co.uk/oswebsite/education/"><img src="https://s1.geograph.org.uk/img/os-logo-p64.png" width="64" height="50" alt="Ordnance Survey"/></a></div>
  </div>
</div>

<div id="search_block" class="no_print">
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

<div class="no_print" style="position:absolute;top:35px;left:500px;color:white">
	<form method="get" action="https://www.geograph.org/leaflet/new.php" target="innerframe" onsubmit="return updateUrl(this)">
		Keywords: <input type="search" name="q" value="<? echo htmlentities($_GET['q']); ?>">
		<input type=submit value="Update Map">
	</form>
</div>
<script>
function updateUrl(form) {
	var q= form.elements['q'].value;
	if (history.pushState) {
		history.pushState({q:q}, q, "?q="+encodeURIComponent(q));
		return true;
	} else {
		location.href='?q='+encodeURIComponent(q);
		return false;
	}
}

window.onpopstate = function(event) {
	window.open('https://www.geograph.org/leaflet/new.php?q='+encodeURIComponent(event.state.q),'innerframe');
};

</script>

</body>
</html>

