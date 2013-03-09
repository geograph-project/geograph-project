<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/

function enCodeBB($msg,$admin) {

$pattern=array(); $replacement=array();

$pattern[]="/\[url[=]?\](.+?)\[\/url\]/i";
$replacement[]="<a href=\"\\1\" target=\"_blank\" ref=\"nofollow\">\\1</a>";

$pattern[]="/\[url=((f|ht)tp[s]?:\/\/[^<> \n]+?)\](.+?)\[\/url\]/i";
$replacement[]="<a href=\"\\1\" target=\"_blank\">\\3</a>";

$pattern[]="/\[email=([^<>(): \n]+?)\](.+?)\[\/email\]/i";
$replacement[]="<a href=\"mailto:\\1\">\\2</a>";

$pattern[]="/\[img(left|right)?\]http:\/\/{$_SERVER['HTTP_HOST']}\/photo\/(\d+)\[\/img\]/i";
$replacement[]='[[[\\2]]]';

$pattern[]="/\[img(left|right)?\]http:\/\/{$_SERVER['HTTP_HOST']}\/photos\/\w+\/\w+\/(\d+)_([\w_]+)\.jpg\[\/img\]/i";
$replacement[]='[[[\\2]]]';


$pattern[]="/\[img(left|right)?\](http:\/\/([^<> \n]+?)\.(gif|jpg|jpeg|png))\[\/img\]/i";
$replacement[]='<img src="\\2" border="0" align="\\1" alt="">';

$pattern[]="/\[[bB]\](.+?)\[\/[bB]\]/s";
$replacement[]='<b>\\1</b>';

$pattern[]="/\[[iI]\](.+?)\[\/[iI]\]/s";
$replacement[]='<i>\\1</i>';

$pattern[]="/\[[uU]\](.+?)\[\/[uU]\]/s";
$replacement[]='<u>\\1</u>';

$pattern[]="/\[code\](.+?)\[\/code\]/se";
$replacement[]='"<pre>".str_replace("<br>","","\\1")."</pre>"'; //any real /n will become <br> later anyway

if($admin==1) {
$pattern[]="/\[font(#[A-F0-9]{6})\](.+?)\[\/font\]/is";
$replacement[]='<font color="\\1">\\2</font>';
}

$msg=preg_replace($pattern, $replacement, $msg);

return $msg;
}

//--------------->
function deCodeBB($msg) {

$pattern=array(); $replacement=array();

$pattern[]="/<a href=\"mailto:(.+?)\">(.+?)<\/a>/i";
$replacement[]="[email=\\1]\\2[/email]";

$pattern[]="/<a href=\"(.+?)\" target=\"(_new|_blank)\"( ref=\"nofollow\")?>(.+?)<\/a>/i";
$replacement[]="[url=\\1]\\4[/url]";

$pattern[]="/<img src=\"(.+?)\" border=\"0\" align=\"(left|right)?\" alt=\"\">/i";
$replacement[]="[img\\2]\\1[/img]";

$pattern[]="/<[bB]>(.+?)<\/[bB]>/s";
$replacement[]="[b]\\1[/b]";

$pattern[]="/<[iI]>(.+?)<\/[iI]>/s";
$replacement[]="[i]\\1[/i]";

$pattern[]="/<[uU]>(.+?)<\/[uU]>/s";
$replacement[]="[u]\\1[/u]";

$pattern[]="/<pre>(.+?)<\/pre>/s";
$replacement[]="[code]\\1[/code]";

$pattern[]="/<font color=\"(#[A-F0-9]{6})\">(.+?)<\/font>/is";
$replacement[]='[font\\1]\\2[/font]';

$msg=preg_replace($pattern, $replacement, $msg);
$msg=str_replace ('<br>', "\n", $msg);
if(substr_count($msg, '[img\\2]')>0) $msg=str_replace('[img\\2]', '[img]', $msg);

if(function_exists('smileThis') and function_exists('getSmilies')) $msg=smileThis(FALSE,TRUE,$msg);

return $msg;
}

?>
