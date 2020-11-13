<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 *
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

ini_set("memory_limit","64M");

//these are the arguments we expect
$param=array(
        'event'=>'',
        'entry'=>'',
	'param'=>'',
	'verbosity'=>4,
);

if (!empty($_SERVER['argv'][1]) && preg_match('/event_handlers\/(\w+)\/(\w+)\.class/',$_SERVER['argv'][1],$m)) {
	$param['event'] = $m[1];
	$param['entry'] = $m[2];
	unset($_SERVER['argv'][1]); //avoid confusing hte normal parser!
}


$HELP = <<<ENDHELP

    --event=<event>     : which event to run (eg every_day)
    --entry=<entry>   : which entry (eg RebuildHectadStat)
    --param=<value>     : string param to send to the handler (eg 1234) - optional
ENDHELP;

chdir(__DIR__);
require "./_scripts.inc.php";

#######################

if (empty($param['event']) || empty($param['entry'])) {
        die("no event specified - try --help\n");
}

############################################

$filesystem = new FileSystem();

if (!$filesystem->hasAuth() && !is_writable($_SERVER['DOCUMENT_ROOT'].'/geophotos/')) {
	die("Unable to write to geophotos/ mount may be non-functional (and/or may need to run as www-data)\n");
}

############################################

require_once('event_handlers/'.$param['event'].'/'.$param['entry'].'.class.php');

$event = array();
if (!empty($param['param'])) {
	$event['event_param'] = $param['param'];
}

$handler=new $param['entry'];

if ($handler->processEvent($event))
{
	if ($param['verbosity']>2)
	        print "Event processed by {$param['entry']}\n";
}
else
{
        print "Event handler {$param['entry']} failed\n";
}


