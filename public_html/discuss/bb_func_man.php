<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

$tpl=makeUp('faq');
$tplTmp=str_replace('{$manual}','<!--MANUAL-->',$tpl);
$tplTmp=ParseTpl($tplTmp);
$tplTmp=explode('<!--MANUAL-->',$tplTmp);

$title.=$l_menu[4]; 
$l_meta.=<<<out

<style type="text/css">
<!--
P{
font-family: Verdana,Arial,Helvetica,sans-serif;
color: #000000;
text-decoration: none;
font-size: 11px;
}


SMALL{
font-family: Verdana,Arial,Helvetica,sans-serif;
color: #000000;
text-decoration: none;
font-size: 10px;
}

PRE{
font-family: Helvetica,sans-serif;
color: #000000;
text-decoration: none;
font-size: 12px;
}

H1{
font-family: Verdana,Arial,Helvetica,sans-serif;
color: #000000;
text-decoration: none;
font-size: 15px;
}

LI{
font-family: Verdana,Arial,Helvetica,sans-serif;
color: #000000;
text-decoration: none;
font-size: 11px;
margin-top: 0px;
margin-bottom: 0px;
margin-right: 0px;
margin-left: 15px;
}

UL{
font-family: Verdana,Arial,Helvetica,sans-serif;
color: #000000;
text-decoration: none;
font-size: 11px;
margin-top: 0px;
margin-bottom: 0px;
margin-right: 15px;
margin-left: 15px;
}
//-->
</style>
out;
echo load_header();
echo $tplTmp[0];
if(file_exists($pathToFiles.'templates/manual_'.$lang.'.html')) include($pathToFiles.'templates/manual_'.$lang.'.html');
elseif(file_exists($pathToFiles.'templates/manual_eng.html')) include($pathToFiles.'templates/manual_eng.html');
echo $tplTmp[1];

?>