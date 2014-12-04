<?php

if (!isset($param))
	$param = array();

//we expect $param already setup, but set some defaults
if (empty($param['dir']))
        $param['dir'] ='/var/www/geograph_live'; //base installation dir

if (empty($param['config']))
        $param['config']='www.geograph.org.uk'; //effective config

if (empty($param['help']))
        $param['help']=0;              //show script help?


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
ENDHELP;
if (!empty($HELP))
	print $HELP;
echo <<<ENDHELP
    --help              : show this message
---------------------------------------------------------------------

ENDHELP;
exit;
}

//set up  suitable environment
ini_set('include_path', $param['dir'].'/libs/');
$_SERVER['DOCUMENT_ROOT'] = $param['dir'].'/public_html/';
$_SERVER['HTTP_HOST'] = $param['config'];


//main include
require_once('geograph/global.inc.php');



/**
* get 1 minute load average
*/
function get_loadavg()
{
        if (!function_exists('posix_uname')) {
                return -1;
        }
        $uname = posix_uname();
        switch ($uname['sysname']) {
                case 'Linux':
                        return linux_loadavg();
                        break;
                case 'FreeBSD':
                        return freebsd_loadavg();
                        break;
                default:
                        return -1;
        }
}

/*
 * linux_loadavg() - Gets the 1 min load average from /proc/loadavg
 */
function linux_loadavg() {
        $buffer = "0 0 0";
        $f = fopen("/proc/loadavg","r");
        if (!feof($f)) {
                $buffer = fgets($f, 1024);
        }
        fclose($f);
        $load = explode(" ",$buffer);
        return (float)$load[0];
}

/*
 * freebsd_loadavg() - Gets the 1 min  load average from uptime
 */
function freebsd_loadavg() {
        $buffer= `uptime`;
        ereg("averag(es|e): ([0-9][.][0-9][0-9]), ([0-9][.][0-9][0-9]), ([0-9][.][0-9][0-9]*)", $buffer, $load);
        return (float)$load[2];
}


