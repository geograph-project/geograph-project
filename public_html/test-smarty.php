<?


require_once('geograph/global.inc.php');

ini_set('display_errors',1);

print "host: ".`hostname`;

#########################################################################################################

$smarty = new GeographPage;
$smarty->assign('smarty_version',$smarty->_version);

if (empty($CONF['memcache']['smarty']) && !empty($smarty->cache_dir)) // only use if NOT using memcache, but cache_dir is still set!
	outputRow('Smarty Cache Dir Writable?', is_writable($smarty->cache_dir)?'pass':'error');

//for smarty, use a .tpl template, to render the pass!

$result = $smarty->fetch('toy.tpl');
if (strpos($result,'Two times above are same') !== FALSE) { //if the template is first time rendered, wont be cached...
	sleep(2); // so wait 2 seconds and try again!
	$result = $smarty->fetch('toy.tpl');
}

//print $result;

$next = false;
foreach (explode("\n",$result) as $line) {
	if (preg_match('/<tr class=(\w+)/',$line,$m))
		$class = $m[1];
	elseif (preg_match('/<td class=/',$line))
		$next = true;
	elseif ($next && preg_match('/<td>(.+?)<\/td>/',$line,$m))
		print "$class: {$m[1]}\n";
	else
		$next = false;
}


if (preg_match_all('/class=result>pass/',$result) !== 3) //needs to be three passes!
	outputRow('Smarty Templating', 'error', 'the template didnt appear to render');

$tpl_file = 'toy.tpl';
$cache_id = '';
$_auto_id = $smarty->_get_auto_id($cache_id,$smarty->compile_id);
$cache_file = substr($smarty->_get_auto_filename(".",$tpl_file,$_auto_id),2);

print "file: {$smarty->template_dir}$cache_file   (".trim(`hostname`).")\n";

#########################################################################################################

function outputRow($message, $class = 'notice', $text = '') {
	print "$class: $message ($text)\n";
	flush();
}

function outputBreak($header) {
	print "-- $header --\n";
}
