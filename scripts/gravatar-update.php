<?php
/**
 * $Project: GeoGraph $
 * $Id: notification-mailer.php 7992 2013-11-15 14:24:20Z geograph $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2013 Barry Hunter (geo@barryhunter.co.uk)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */





//these are the arguments we expect
$param=array(
	'dir'=>'/home/geograph',		//base installation dir

	'config'=>'www.geograph.virtual', //effective config

        'action'=>'dummy',

	'help'=>0,		//show script help?
);

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
notification-mailer.php
---------------------------------------------------------------------
php notification-mailer.php --schedule=weekly
    --dir=<dir>         : base directory (/home/geograph)
    --config=<domain>   : effective domain config (www.geograph.org.uk)
    --schedule=<event>   : which event to run (weekly/daily/hourly)
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

require_once('geograph/global.inc.php');


$db = GeographDatabaseConnection(false);

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$recordSet = &$db->Execute("SELECT user_id,MD5(LOWER(email)) m5d FROM user INNER JOIN user_stat USING (user_id) WHERE gravatar = 'unknown' LIMIT 1000");
while (!$recordSet->EOF) {

	#"http://www.gravatar.com/avatar/53640618dabda27e268972addfe9bb83?d=404"
	$found = @file_get_contents("http://www.gravatar.com/avatar/{$recordSet->fields['m5d']}?d=404");

	$status = $found?'found':'none';

	$sql = "UPDATE user SET `gravatar` = '$status', updated = updated WHERE user_id = {$recordSet->fields['user_id']}";

	if ($param['action'] == 'execute') {
		$db->Execute($sql);
		//print "{$recordSet->fields['user_id']} ";
		sleep(1);
	} else
		print "$sql\n";


        $recordSet->MoveNext();
}
$recordSet->Close();

if ($param['action'] != 'execute')
	print "\n\n";
