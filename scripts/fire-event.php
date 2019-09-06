<?

//these are the arguments we expect
$param=array(
	'event'=>'',
	'param'=>'',
	'priority'=>75,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################


require_once('geograph/event.class.php');

if (empty($param['event']))
	die("specify event, try --help\n");


Event::fire($param['event'], $param['param'], $param['priority']);
