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

out;
echo load_header();
echo $tplTmp[0];

$manbase=($logged_admin)?'manual':'usermanual';
$langpage=$pathToFiles.'templates/'.$manbase.'_'.$lang.'.html';
$engpage=$pathToFiles.'templates/'.$manbase.'_eng.html';

if(file_exists($manpage)) 
	include($manpage);
elseif(file_exists($engpage)) 
	include($engpage);

echo $tplTmp[1];

?>