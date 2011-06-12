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
				<script type=\"text/javascript\" src=\"http://s0.geograph.org.uk/js/geograph.v7217.js\"></script> 
				<link rel=\"stylesheet\" type=\"text/css\" title=\"Monitor\" href=\"http://s0.geograph.org.uk/templates/basic/css/basic.v7290.css\" media=\"screen\" />
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
	$class = new GeographDatabase($link);
	
	$link = mysql_connect('example.com:3307', 'mysql_user', 'mysql_password');
	if (!$link) {
	    die('Could not connect: ' . mysql_error());
	}
	$db_selected = mysql_select_db('foo', $link);
	if (!$db_selected) {
	    die ('Can\'t use foo : ' . mysql_error());
	}
	$class->readonly = false;
	return $class;
}