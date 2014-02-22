<?php

//very simple argument parser
for($i=1; $i<count($_SERVER['argv']); $i++)
{
	$arg=$_SERVER['argv'][$i];

	if (substr($arg,0,2)=='--')

	{
		$arg=substr($arg,2);
		$bits=explode('=', $arg,2);
		if (isset($param[$bits[0]]))
		{
			//if we have a value, use it, else just flag as true
			$param[$bits[0]]=isset($bits[1])?$bits[1]:true;
		}
		else die("unknown argument --$arg\nTry --help\n");
	}
	else die("unexpected argument $arg - try --help\n");
}


if ($param['help'])
{
	echo <<<ENDHELP
---------------------------------------------------------------------
{$_SERVER['PHP_SELF']}
---------------------------------------------------------------------
php {$_SERVER['PHP_SELF']}
    --dir=<dir>         : base directory ({$param['dir']})
    --config=<domain>   : effective domain config ({$param['config']})
    --help              : show this message
---------------------------------------------------------------------

ENDHELP;
exit;
}

//set up  suitable environment
ini_set('include_path', $param['dir'].'/libs/');
$_SERVER['DOCUMENT_ROOT'] = $param['dir'].'/public_html/';
$_SERVER['HTTP_HOST'] = $param['config'];


//--------------------------------------------
// nothing below here should need changing

require_once('geograph/global.inc.php');

