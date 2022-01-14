<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

if (empty($_GET['page']) || preg_match('/[^\w\.-]/',$_GET['page'])) {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	$smarty->display('static_404.tpl');
	exit;
}

$isadmin=$USER->hasPerm('moderator')?1:0;

$template = 'article_diff.tpl';
$cacheid = 'articles|'.$_GET['page'];
$cacheid .= '|'.$isadmin;

$db = GeographDatabaseConnection(true);

$page = $db->getRow("
select article.article_id,title,url,article.user_id,extract,licence,approved,realname
from article
	left join user using (user_id)
where ( (licence != 'none' and approved >0)
	or user.user_id = {$USER->user_id}
	or $isadmin )
	and url = ".$db->Quote($_GET['page']).'
limit 1');

if (count($page)) {
	if ($page['user_id'] == $USER->user_id) {
		$cacheid .= '|'.$USER->user_id;
	}

	$r1 = (!empty($_GET['1']))?intval($_GET['1']):0;
	$r2 = (!empty($_GET['2']))?intval($_GET['2']):0;

	if ($r2 && $r1 == -1) {
		$r1 = $db->getOne("SELECT article_revision_id FROM article_revisions WHERE article_id = {$page['article_id']} AND article_revision_id < $r2 ORDER BY article_revision_id DESC");
	}

	if ($r1 && $r2 && $r1 != $r2) {
		$cacheid .= "|$r1.$r2";
		if (!empty($_GET['c']))
			$cacheid .= "++";
	} else {
		$cacheid = '';
	}
}

if (!$smarty->is_cached($template, $cacheid))
{
	include("3rdparty/simplediff.inc.php");

	if (count($page)) {
		$smarty->assign($page);
		if ($r1 && $r2 && $r1 != $r2) {
			if ($r1 > $r2) {
				$a1 = getRevisionArray($page['article_id'],intval($r2));
				$a2 = getRevisionArray($page['article_id'],intval($r1),true);
			} else {
				$a1 = getRevisionArray($page['article_id'],intval($r1));
				$a2 = getRevisionArray($page['article_id'],intval($r2),true);
			}
			if (count($a1) > 1300 || count($a2) > 1300 || !empty($_GET['c'])) {
				$l1 = array_shift($a1);
				$l2 = array_shift($a2);
				$t1 = tempnam("/tmp", "diff");
				$t2 = tempnam("/tmp", "diff");
				$h1 = fopen($t1,'w'); foreach($a1 as $line) { fwrite($h1,$line."\n"); } fclose($h1);
				$h2 = fopen($t2,'w'); foreach($a2 as $line) { fwrite($h2,$line."\n"); } fclose($h2);
				$raw = `diff --unified $t1 $t2 --label "{$l1}" --label "{$l2}"`;
				$html = "<pre class=code>\n".htmlentities($raw)."</pre>";
				$html = preg_replace('/^(\+.*?)$/m','<span class=new>$1</span>',$html);
				$html = preg_replace('/^(-.*?)$/m','<span class=old>$1</span>',$html);
				$html = preg_replace('/^(@.*?)$/m','<span class=blank>$1</span>',$html);
				$smarty->assign('output', $html);
				unlink($t1);
				unlink($t2);
			} else
				$smarty->assign_by_ref('output', diff2table($a1,$a2));
		}
	} else {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
		$template = 'static_404.tpl';
	}
} else {
	$smarty->assign('user_id', $page['user_id']);
	$smarty->assign('url', $page['url']);
}




$smarty->display($template, $cacheid);



function getRevisionArray($aid,$revid,$showwho = false) {
	global $db,$isadmin,$USER;
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$page = $db->getRow("
	select category_name,url,title,extract,licence,approved,user.realname as modifier_realname,content,update_time,edit_prompt
	from article_revisions
		inner join user on (modifier = user.user_id)
		left join article_cat on (article_revisions.article_cat_id = article_cat.article_cat_id)
	where ( (licence != 'none' and approved > 0)
		or user.user_id = {$USER->user_id}
		or $isadmin )
		and article_id = {$aid}
		and article_revision_id = {$revid}");
	$ADODB_FETCH_MODE = $prev_fetch_mode;

	$a = array();
	$a[] = "Revision: {$page['update_time']}";
	$a[] = "Title: {$page['title']}";
	$a[] = "URL: {$page['url']}";
	$a[] = "Category: {$page['category_name']}";
	$a[] = "Extract: {$page['extract']}";
	$a[] = "Licence: {$page['licence']}";
	if (!empty($page['edit_prompt']))
		$a[] = "Prompt: {$page['edit_prompt']}";
	$a[] = "Approved: {$page['approved']}";
	if ($showwho)
		$a[] = "Modifier: {$page['modifier_realname']}";
	$a[] = "---------------------------------";
	$a[] = "";

        $f = array();
        $f[] = '';
        $f[] = "---------------------------------";
        $f[] = "Revision: {$page['update_time']}";

        return array_merge($a,explode("\n",str_replace("\r",'',$page['content'])),$f);
}

function diff2table($old, $new){
	$diff = diff($old,$new);
	$nr1 = $nr2 = -9;
	$ret = array();
	foreach($diff as $k){
		if (empty($k)) {
			$ret[] = "<tr class=\"blank\"><td colspan=\"3\"></td><td class=\"code\">&nbsp;</td></tr>";
			$nr1++;
			$nr2++;
		} elseif(is_array($k)) {
			if (!empty($k['d'])) {
				foreach ($k['d'] as $l)
					$ret[] = "<tr class=\"old\"><td>$nr1</td><td></td><td>-</td><td class=\"code\">".htmlentities($l)."</td></tr>";
				$nr1+=count($k['d']);
			}
			if (!empty($k['i'])) {
				foreach ($k['i'] as $l)
					$ret[] = "<tr class=\"new\"><td></td><td>$nr2</td><td>+</td><td class=\"code\">".htmlentities($l)."</td></tr>";
				$nr2+=count($k['i']);
			}
		} else {
			$ret[] = "<tr><td>$nr1</td><td>$nr2</td><td></td><td class=\"code\">".htmlentities($k)."</td></tr>";
			$nr1++;
			$nr2++;
		}
	}
	return join("\n",$ret);
}



