<?php

//we expect $param already setup, but set some defaults, so doesn't need to be provided
// we can't use the config files here, as this is LOADING the config! we could in theory read DocumentRoot/ServerName from /etc/apache2/sites-enabled/{*}.conf ??

if (!isset($param))
	$param = array();

//base installation dir
if (empty($param['dir'])) {
	if (!empty($_SERVER['BASE_DIR'])) //running inside a container
		$param['dir'] =$_SERVER['BASE_DIR'];
	elseif (strpos(__DIR__,'geograph_live'))
	        $param['dir'] ='/var/www/geograph_live';
	else
		$param['dir'] ='/var/www/geograph_svn';
}

//effective config
if (empty($param['config'])) {
	if (!empty($_SERVER['CLI_HTTP_HOST'])) //running inside a container
		//Note, the ENV[CONF_PROFILE] will override the config file used.
		$param['config']=$_SERVER['CLI_HTTP_HOST'];
	elseif (strpos(__DIR__,'geograph_live'))
        	$param['config']='www.geograph.org.uk';
	else
		$param['config']='staging.geograph.org.uk';
}

//show script help?
if (empty($param['help']))
        $param['help']=0;

##########################################################

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
$_SERVER['DOCUMENT_ROOT'] = $param['dir'].'/public_html';
$_SERVER['HTTP_HOST'] = $param['config'];
$_SERVER['REMOTE_ADDR'] = null;
$_SERVER['HTTP_USER_AGENT'] = 'Geograph Script';
$_SERVER['REQUEST_URI'] = $argv[0];

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

	//if available, this seems the most reliable way
	if (function_exists('sys_getloadavg'))
                return @array_shift(sys_getloadavg()); //array_shift accepts by reference, which emits notice when used like this

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
        preg_match("#averag(es|e): ([0-9][.][0-9][0-9]), ([0-9][.][0-9][0-9]), ([0-9][.][0-9][0-9]*)#", $buffer, $load);
        return (float)$load[2];
}


