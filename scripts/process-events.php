<?

//these are the arguments we expect
$param=array(
	'testmode'=>1,
	'verbosity'=>4,
	'max_execution'=>10, //180,
	'max_load'=>0.5,
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/geophotos/')) {
	die("Unable to write to geophotos/ mount may be non-functional (and/or may need to run as www-data)\n");
}

############################################


        $processor=new EventProcessor;
        //$processor->setFilter($param['filter']);
        $processor->setTestMode($param['testmode']);
        $processor->setVerbosity($param['verbosity']);
        $processor->setMaxTime($param['max_execution']);
        $processor->setMaxLoad($param['max_load']);

	$processor->start();

