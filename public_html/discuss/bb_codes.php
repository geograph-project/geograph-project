<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/

function preg_replace_array($patterns, $replacements, $s)
{
	$i = 0;
	foreach ($patterns as $pattern) {
		if (!isset($replacements[$i])) {
			$s = preg_replace($pattern, '', $s);
		} elseif (is_string($replacements[$i])) {
			$s = preg_replace($pattern, $replacements[$i], $s);
		} else {
			$s = preg_replace_callback($pattern, $replacements[$i], $s);
		}
		++$i;
	}
	return $s;
}

function enCodeBB($msg,$admin) {

$pattern=array(); $replacement=array();

$pattern[]="/\[url[=]?\](.+?)\[\/url\]/i";
$replacement[]="<a href=\"\\1\" target=\"_blank\" rel=\"nofollow\">\\1</a>";

$pattern[]="/\[url=((f|ht)tp[s]?:\/\/[^<> \n]+?)\](.+?)\[\/url\]/i";
$replacement[]="<a href=\"\\1\" target=\"_blank\">\\3</a>";

$pattern[]="/\[email=([^<>(): \n]+?)\](.+?)\[\/email\]/i";
$replacement[]="<a href=\"mailto:\\1\">\\2</a>";

$pattern[]="/\[img(left|right)?\]https?:\/\/\w+\.geograph(\.ie|\.org|\.uk)+\/photo\/(\d+)\[\/img\]/i";
$replacement[]='[[[\\3]]]';

$pattern[]="/\[img(left|right)?\]https?:\/\/\w+\.geograph(\.ie|\.org|\.uk)+\/(geophotos\/\d+|photos)\/\d+\/\d+\/(\d{6,})_([\w_]+)\.jpg\[\/img\]/i";
$replacement[]='[[[\\4]]]';

$pattern[]="/\[img(left|right)?\]https?:\/\/geo(-en|)\.hlipp\.de\/photo\/(\d+)\[\/img\]/i";
$replacement[]='[[[de:\\3]]]';

$pattern[]="/\[img(left|right)?\]https?:\/\/geo(-en|)\.hlipp\.de\/photos\/\w+\/\w+\/(\d+)_([\w_]+)\.jpg\[\/img\]/i";
$replacement[]='[[[de:\\3]]]';


$pattern[]="/\[img(left|right)?\](https?:\/\/([^<> \n]+?)\.(gif|jpg|jpeg|png))\[\/img\]/i";
$replacement[]='<img src="\\2" border="0" align="\\1" alt="">';

$pattern[]="/\[blockquote\](.+?)\[\/blockquote\]/is";
$replacement[]='<blockquote>\\1</blockquote>';

$pattern[]="/\[[bB]\](.+?)\[\/[bB]\]/s";
$replacement[]='<b>\\1</b>';

$pattern[]="/\[[iI]\](.+?)\[\/[iI]\]/s";
$replacement[]='<i>\\1</i>';

$pattern[]="/\[[uU]\](.+?)\[\/[uU]\]/s";
$replacement[]='<u>\\1</u>';

$pattern[]="/\[big\](.+?)\[\/big\]/is";
$replacement[]='<big>\\1</big>';

$pattern[]="/\[code\](.+?)\[\/code\]/s";
$replacement[] = function($m) {
	return "<pre>".str_replace("<br>","",$m[1])."</pre>"; //any real /n will become <br> later anyway
};

if($admin==1) {
$pattern[]="/\[font(#[A-F0-9]{6})\](.+?)\[\/font\]/is";
$replacement[]='<font color="\\1">\\2</font>';
}

$msg=preg_replace_array($pattern, $replacement, $msg);

return $msg;
}

//--------------->
function deCodeBB($msg) {

$pattern=array(); $replacement=array();

$pattern[]="/<a href=\"mailto:(.+?)\">(.+?)<\/a>/i";
$replacement[]="[email=\\1]\\2[/email]";

$pattern[]="/<a href=\"(.+?)\" target=\"(_new|_blank)\"( re[fvl]=\"nofollow\")?>(.+?)<\/a>/i";
$replacement[]="[url=\\1]\\4[/url]";

$pattern[]="/<img src=\"(.+?)\" border=\"0\" align=\"(left|right)?\" alt=\"\">/i";
$replacement[]="[img\\2]\\1[/img]";

$pattern[]="/<blockquote>(.+?)<\/blockquote>/s";
$replacement[]="[blockquote]\\1[/blockquote]";

$pattern[]="/<[bB]>(.+?)<\/[bB]>/s";
$replacement[]="[b]\\1[/b]";

$pattern[]="/<[iI]>(.+?)<\/[iI]>/s";
$replacement[]="[i]\\1[/i]";

$pattern[]="/<[uU]>(.+?)<\/[uU]>/s";
$replacement[]="[u]\\1[/u]";

$pattern[]="/<pre>(.+?)<\/pre>/s";
$replacement[]="[code]\\1[/code]";

$pattern[]="/<big>(.+?)<\/big>/s";
$replacement[]="[big]\\1[/big]";

$pattern[]="/<font color=\"(#[A-F0-9]{6})\">(.+?)<\/font>/is";
$replacement[]='[font\\1]\\2[/font]';

$msg=preg_replace($pattern, $replacement, $msg);
$msg=str_replace ('<br>', "\n", $msg);
if(substr_count($msg, '[img\\2]')>0) $msg=str_replace('[img\\2]', '[img]', $msg);

if(function_exists('smileThis') and function_exists('getSmilies')) $msg=smileThis(FALSE,TRUE,$msg);

return $msg;
}

?>
