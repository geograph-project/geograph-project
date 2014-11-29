<?php
/**
 * $Project: GeoGraph $
 * $Id: contributors.php 6407 2010-03-03 20:44:37Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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

if (isset($_GET['mine'])) {
	$_GET['q'] .= " user:user{$USER->user_id} by:{$USER->realname}";
}

if (!empty($_GET['upload_id'])) {

        $gid = crc32($_GET['upload_id'])+4294967296;
        $gid += $USER->user_id * 4294967296;
        $gid = sprintf('%0.0f',$gid);

} elseif (!empty($_REQUEST['gridimage_id'])) {

        $gid = intval($_REQUEST['gridimage_id']);
}

$smarty = new GeographPage;

$cacheid = md5(serialize($_GET));
$extra = array();

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache


$smarty->display('_basic_begin.tpl');

if (!empty($_GET['gr'])) {
	$q=trim($_GET['gr']);

?>
<style>
ul {
	margin-top:0px;
	padding-left:15px;
}
ul a.used {
	font-weight:bold;
	text-decoration:none;
}
</style>
<?


        $square=new GridSquare;
        if (preg_match_all('/\b([a-zA-Z]{1,2}) ?(\d{2,5})(\.\d*|) ?(\d{2,5})(\.*\d*|)\b/',$q,$matches)) {
                $gr = array_pop($matches[0]); //take the last, so that '/near/Grid%20Reference%20in%20C1931/C198310' works!
                $grid_ok=$square->setByFullGridRef($gr,true,true);
		$q = $square->grid_reference;
        }

        //for some unexplainable reason, setByFullGridRef SOMETIMES returns false, and fails to set nateastings - even though allow-zero-percent is set. Fix that...
        if (!$square->nateastings && $square->x && $square->y) {
                require_once('geograph/conversions.class.php');
                $conv = new Conversions;
                list($e,$n,$reference_index) = $conv->internal_to_national($square->x,$square->y);
                $square->nateastings = $e;
                $square->natnorthings = $n;
                $square->reference_index = $reference_index;
                $grid_ok = 1;
        }

        if ($grid_ok) {
		require_once('geograph/conversions.class.php');
                $conv = new Conversions;

                list($lat,$lng) = $conv->national_to_wgs84($square->nateastings,$square->natnorthings,$square->reference_index);

		$lat = deg2rad($lat);
		$lng = deg2rad($lng);
	}


	$sphinx = new sphinxwrapper($q);
	if (!empty($sphinx->q))
		$extra[] = "q=".urlencode($sphinx->q);

	$sphinx->pageSize = $pgsize = 20;

	$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	if (isset($_REQUEST['inner'])) {
		$smarty->assign('inner',1);
		$extra[] = "inner";
	}


		$sphinx->processQuery();

		if (preg_match('/@grid_reference \(/',$sphinx->q) && preg_match('/^\w{1,2}\d{4}$/',$sphinx->qclean)) {
			$smarty->assign('gridref',$sphinx->qclean);
		}

		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		$sph = NewADOConnection($CONF['sphinxql_dsn']) or die("unable to connect to sphinx. ".mysql_error());

		$where = "match(".$sph->Quote($sphinx->q).")";

		$results = array();
		$attributes = array('context' ,'subject','tag','snippet');
		$links = array(
			'snippet' => 'http://www.geograph.org.uk/snippets.php?q=&gr=$gr&radius=2'
		);

		foreach ($attributes as $attribute) {
			$rows = $sph->getAll($sql = "
			select id,{$attribute}s,{$attribute}_ids,COUNT(*) as count,GROUPBY() as group,MIN(geodist(wgs84_lat,wgs84_long,$lat,$lng)) as dist
			from sample8
			where $where
			group by {$attribute}_ids
			order by dist asc
			limit {$sphinx->pageSize}");

			foreach ($rows as $idx => $row) {
				$ids = explode(',',$row[$attribute.'_ids']);
				$names = explode('_SEP_',$row[$attribute.'s']);array_shift($names); //the first is always blank!
				$row['label'] = trim($names[array_search($row['group'],$ids)]);

				$dist = round($row['dist']/1000);

				@$results[$dist][$attribute][] = $row;
			}
		}


		ksort($results);

		print "<b>Items used in or near $q</b>. To give feedback on this feature, please <a href=\"/discuss/index.php?&action=vthread&forum=12&topic=27039\" target=_blank>use this thread</a>.";

if (empty($results)) {
	die("<p>No Tags or Shared Descriptions Found</p>");
}

		print "<br/><i>The other tabs, should reflect items added from this box, but currently changes made elsewhere might not reflect here.</i>";

		if (!empty($gid)) {
			$db = GeographDatabaseConnection(true);
			$tag_ids = $db->getAssoc("SELECT tag_id,tag_id FROM gridimage_tag WHERE status = 2 AND gridimage_id = $gid");
			$snippet_ids = $db->getAssoc("SELECT snippet_id,snippet_id FROM gridimage_snippet WHERE gridimage_id = $gid");
		} else {
			$tag_ids = $snippet_ids = array();
		}

		print "<table cellspacing=0 cellpadding=1 border=1 bordercolor=#eee>";
		print "<tr>";
			print "<th></th>";
		foreach ($attributes as $attribute) {
			if (!empty($links[$attribute]) && empty($_GET['upload_id'])) {
				print "<th><a href='".str_replace('$gr',$gr,$links[$attribute])."'>$attribute</a></th>";
			} else {
				print "<th>$attribute</th>";
			}
		}
		print "</tr>";
		foreach ($results as $dist => $group) {
			print "<tr>";
			print "<th>$dist km</th>";
			foreach ($attributes as $attribute) {
				print "<td valign=top>";
				if (!empty($group[$attribute])) {
					print "<ul class=$attribute>";
					foreach ($group[$attribute] as $row) {
						switch($attribute) {
							case 'tag': $url = "/tagged/".urlencode($row['label']); break;
							case 'context': $url = "/tagged/top:".urlencode($row['label']); break;
							case 'subject': $url = "/tagged/subject:".urlencode($row['label']); break;
							case 'snippet': $url = "/snippet/{$row['group']}"; break;
						}
						if ($attribute == 'snippet')
							$class = isset($snippet_ids[$row['group']])?'used':'';
						else
							$class = isset($tag_ids[$row['group']])?'used':'';
						print "<li><a class=\"$class\" href=\"$url\" onclick=\"return useIt(this,'$attribute',{$row['group']},'".addslashes(htmlentities($row['label']))."')\">".htmlentities($row['label'])."</li>";
					}
					print "</ul>";
				}
				print "</td>";
			}
			print "</tr>";
		}
		print "</table>";


?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script>
var gridimage_id = <? print empty($gid)?'null':$gid; ?>;

function useIt(that,attribute,id,label) {
	$that = $(that);
	if ($that.hasClass('used')) {
		$that.removeClass('used');
		var status = 0;
		var checked = false;
	} else {
		if (attribute=='subject') {
			$("ul.subject a.used").removeClass('used');
		}
		$that.addClass('used');
		var status = 2;//public if possible!
		var checked = true;
	}

		switch(attribute) {
			case 'tag':	if (gridimage_id) {
						submitTag(gridimage_id,label,status);
					}
					break;
			case 'subject':	if (window.parent && window.parent.document.forms && window.parent.document.forms['theForm'] && window.parent.document.forms['theForm'].elements['subject']) {
						var ele = window.parent.document.forms['theForm'].elements['subject'];
						found = false;
						for(q=0;q<ele.options.length;q++) {
                                                        if (ele.options[q].value == label) {
                                                                ele.options[q].selected=checked;

								if (typeof window.parent.parentUpdateVariables == 'function') {
									window.parent.parentUpdateVariables();
								}
								found=true;
                                                        }
                                                }
						if (!found) {
							alert("This is not an offical subject, it can't be used"); //todo, add via a tag anyway?
							$that.removeClass('used');
						}
					} else if (gridimage_id) {
						submitTag(gridimage_id,'subject:'+label,status);
					}
					break;
			case 'context':	if (window.parent && window.parent.document.forms && window.parent.document.forms['theForm'] && window.parent.document.forms['theForm'].elements['tags[]']) {
						var eles = window.parent.document.forms['theForm'].elements['tags[]'];
						for(q=0;q<eles.length;q++) {
							if (eles[q].value == 'top:'+label) {
								eles[q].checked=checked;

								window.parent.window.rehighlight(eles[q],true);
								if (typeof window.parent.parentUpdateVariables == 'function') {
									window.parent.parentUpdateVariables();
								}
							}
						}
					} else if (gridimage_id) {
						submitTag(gridimage_id,'top:'+label,status);
					}
					break;
			case 'snippet': if (gridimage_id) {
						submitSnippet(gridimage_id,id,status);
					}
					break;

		}


	return false;
}

	function submitTag(gridimage_id,tag,status) {
		var data = new Object;
		data['tag'] = tag;
		data['status'] = status;
		data['gridimage_id'] = gridimage_id;
		$.ajax({
			url: "/tags/tagger.json.php",
			data: data
		});
	}
	function submitSnippet(gridimage_id,snippet_id,status) {
		var data = new Object;
		data['snippet_id'] = snippet_id;
		data['status'] = status;
		data['gridimage_id'] = gridimage_id;
		$.ajax({
			url: "/submit_snippet.json.php",
			data: data
		});
	}

</script>
<?

}




$smarty->display('_basic_end.tpl');
