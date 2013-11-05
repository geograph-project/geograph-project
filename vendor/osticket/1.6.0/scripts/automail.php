#!/usr/bin/php -q
<?php
/*********************************************************************
    automail.php

    PHP script used for remote email piping...same as as the perl version.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
#pre-checks
function_exists('file_get_contents') or die('upgrade php >=4.3');
set_time_limit(30);

#Configuration: Enter the url and key. That is it.
#  url=> URL to pipe.php e.g http://yourdomain.com/support/api/pipe.php
#  key=> API's Key (see admin panel on how to generate a key)
$config=array('url'=>'http://yourdomain.com/support/api/pipe.php',
              'key'=>'API KEY HERE');

#read stdin 
$data=file_get_contents('php://stdin');
if(empty($data)) die('Error reading stdin. No message');

#curl post
$ch = curl_init();        
curl_setopt($ch, CURLOPT_URL,$config['url']);        
curl_setopt($ch, CURLOPT_POST,1);        
curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
curl_setopt($ch, CURLOPT_USERAGENT,$config['key']);
curl_setopt($ch, CURLOPT_HEADER, TRUE);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
$result=curl_exec($ch);        
curl_close($ch);
$code=0;
if(preg_match('/HTTP\/.* ([0-9]+) .*/', $result, $status))
    $code=$status[1];
//Depending on your MTA add the exit codes.
//echo $code;
?>

