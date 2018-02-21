<?

##Basic replacement for 'geograph/global.inc.php', so can run geotrips in a standalone enviroment

/* 
* Basic smarty replacement
*/
class GeographPage {
	var $vars;
	function assign($name,$value) {
		$this->vars[$name] = $value;
	}
	function display($template) {
		if ($template == '_std_begin.tpl') {
			print  "<html>
				<head>
				<title>{$this->vars['page_title']}</title>
				<script type=\"text/javascript\" src=\"https://s1.geograph.org.uk/js/geograph.js\"></script>
				<link rel=\"stylesheet\" type=\"text/css\" title=\"Monitor\" href=\"https://s1.geograph.org.uk/templates/basic/css/basic.css\" media=\"screen\" />
				</head>
				<body>
				<div class=\"content2\" id=\"maincontent_block\"><div id=\"maincontent\">
				";
		} elseif ($template == '_std_end.tpl') {
			print "</div></div></body></html>";
		}
	}
}

/* 
* Basic user replacement
*/
class GeographUser {
	var $user_id = 3;
	
	function mustHavePerm($perm) {
		return true;
	}
}

/* 
* Basic adodb replacement
*/
class GeographDatabase {
	var $_connectionID;
	
	function GeographDatabase($link) {
		$this->_connectionID = $link;
	}
}

function init_session() {
	session_start();

	//do we have a user object?
	if (!isset($_SESSION['user']))
	{
		session_regenerate_id();

		$_SESSION['user'] =& new GeographUser;

	}

	//put user object into global scope
	$GLOBALS['USER'] =& $_SESSION['user'];	
}

/* 
* After calling this function, can just use mysql_query etc in your code
*/
function GeographDatabaseConnection($allow_readonly = false) {
	
	$link = mysql_connect('example.com:3307', 'mysql_user', 'mysql_password');
	if (!$link) {
	    die('Could not connect: ' . mysql_error());
	}
	$db_selected = mysql_select_db('foo', $link);
	if (!$db_selected) {
	    die ('Can\'t use foo : ' . mysql_error());
	}
	$class = new GeographDatabase($link);
	$class->readonly = false;
	return $class;
}

function htmlentities2( $myHTML,$quotes = ENT_COMPAT,$char_set = 'ISO-8859-1')
{
    return preg_replace( "/&amp;([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};|#x[0-9a-fA-F]{2,4};)/", '&$1' ,htmlentities($myHTML,$quotes,$char_set));
}

