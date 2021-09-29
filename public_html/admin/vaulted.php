<?php
/**
 * $Project: GeoGraph $
 * $Id: viewps.php 2295 2006-07-05 12:15:49Z barryhunter $
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


require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

$USER->hasPerm("director") || $USER->mustHavePerm("moderator");

################################################

$db = GeographDatabaseConnection(false); //the job creation/update statements are not replication safe, so need to use master

if (!empty($_POST)) {
	if (!empty($_GET['d'])) {
		print "<pre>";
		print_r($_POST);
		print "</pre>";
	}

	$updates = array();
	$updates['user_id'] = $USER->user_id;

	if (!empty($_POST['review_date'])) {
		foreach($_POST['review_date'] as $gridimage_id => $date) {
			if (!empty($date)) {
				$updates['gridimage_id'] = intval($gridimage_id);
				$updates['review_date'] = $date;
				if (!empty($_POST['osticket'][$gridimage_id])) {
					$updates['osticket'] = $_POST['osticket'][$gridimage_id];
					unset($_POST['osticket'][$gridimage_id]);
				} elseif (isset($updates['osticket']))
					unset($updates['osticket']);

				$db->Execute($sql = 'INSERT INTO gridimage_vault SET `created` = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?'.
		                        ' ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ?',
                			       array_merge(array_values($updates),array_values($updates))) or die("$sql\n".$db->ErrorMsg()."\n\n");;
			}
		}
	}

	if (!empty($_POST['invalid'])) {
		$updates['review_date'] = '1000-00-00';
		foreach($_POST['invalid'] as $gridimage_id => $dummy) {
			if (!empty($dummy)) {
				$updates['gridimage_id'] = intval($gridimage_id);

				$db->Execute($sql = 'INSERT INTO gridimage_vault SET `created` = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?'.
		                        ' ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ?',
                			       array_merge(array_values($updates),array_values($updates))) or die("$sql\n".$db->ErrorMsg()."\n\n");;
			}
		}
	}

	if (!empty($_POST['osticket'])) {
		unset($updates['review_date']);
		foreach($_POST['osticket'] as $gridimage_id => $ref) {
			if (!empty($ref)) {
				$updates['gridimage_id'] = intval($gridimage_id);
				$updates['osticket'] = $ref;

				$db->Execute($sql = 'INSERT INTO gridimage_vault SET `created` = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?'.
		                        ' ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ?',
                			       array_merge(array_values($updates),array_values($updates))) or die("$sql\n".$db->ErrorMsg()."\n\n");;
			}
		}
	}


}

################################################

$sph = GeographSphinxConnection('sphinxql', true);

$smarty->display('_std_begin.tpl');

print "<script src=\"".smarty_modifier_revision("/sorttable.js")."\"></script>";

if (empty($_GET['q']) && @$_GET['date'] != 'past')
	$_GET['q'] = 'auto vault';
if (empty($_GET['limit']))
	$_GET['limit'] = 20;
if (empty($_GET['page']))
	$_GET['page'] = 1;

?>
<form method=get>
	Query*: <input type=search name=q value="<? echo htmlentities2($_GET['q']); ?>" size=30>
	Results: <input type=number name=limit value="<? echo intval($_GET['limit']); ?>" min=1 step=1 max=1000 style=text-align:right>(1000 max)
	Page: <input type=number name=page value="<? echo intval($_GET['page']); ?>" min=0 step=1 max=100 style=text-align:right>(100 max)<br>

	<select name="date"><?

		$options = array(''=>'All Matches', 'yes'=>'With Date', 'past'=>'Past Review Date', 'no'=>'No Date Yet', 'invalid'=>'Invalid only');

		$wheres = array(''=>'', 'yes'=>"review_date > '2000-00-00'", 'past'=>"review_date > '2000-00-00' and review_date < DATE(NOW())",
			'no'=>'review_date IS NULL', 'invalid'=>"review_date = '1000-00-00'");

		foreach ($options as $key => $text)

			printf('<option value="%s"%s>%s</option>', $key, @($_GET['date'] == $key)?' selected':'', $text);
	?>
	</select>
	<label for="open">Include Open Suggestions</label> <input type=checkbox name=open <? if (!empty($_GET['open'])) { echo " checked"; } ?>>
	<input type=submit value="Search &gt;"><br><br>
</form>

<?

if (!empty($_GET['q']) || @$_GET['date'] == 'past') {
	#############################
	$options = array();

	if (strpos($_GET['q'],'@') === FALSE)
		$q = $sph->Quote("@notes ".$_GET['q']);
	else
		$q = $sph->Quote($_GET['q']);
	$index = empty($_GET['open'])?'tickets_closed':'tickets_closed,tickets';
	$last = $limit = intval($_GET['limit']);
	if (!empty($_GET['page']) && $_GET['page'] > 1) {
		$limit = sprintf('%d,%d',($_GET['page']-1)*$limit,$limit);
		$last = $_GET['page']*$limit;
	}
	if ($last > 1000)
		$options = "max_matches=$last";

	$where = '';
	if (!empty($wheres[$_GET['date']]))
		$where = " AND ".$wheres[$_GET['date']];

	#############################

	$options = $options?(" OPTION ".implode(', ',$options)):'';

	if (!empty($_GET['q'])) {
		$ids = $sph->getCol($sql = "SELECT id FROM $index WHERE MATCH($q) ORDER BY id DESC LIMIT $limit $option");
		if (!empty($_GET['d'])) {
			print("$sql;<hr>");
		}
		$meta = $sph->getAssoc("SHOW META");
	}

	#############################

	if (!empty($ids) || @$_GET['date'] == 'past') {
		$merge = $db->getOne("SHOW TABLES LIKE 'gridimage_ticket_merge'")?'_merge':'';
		if (empty($ids) && !empty($where)) {
			//special prvision to work with empty query! (the INNER JOIN gridimage_vault - means only reviewed images, so limtied!)
			$sql = "SELECT gridimage_id as image, moderation_status as modstat, suggested, notes, review_date, osticket
				FROM gridimage_ticket$merge
				INNER JOIN gridimage_vault USING (gridimage_id)
				INNER JOIN gridimage USING (gridimage_id)
				WHERE 1 $where
				GROUP BY gridimage_id DESC
				LIMIT 10000";
		} else {
			$sql = "SELECT gridimage_id as image, moderation_status as modstat, suggested, notes, review_date, osticket
				FROM gridimage_ticket$merge
				INNER JOIN gridimage USING (gridimage_id)
				LEFT JOIN gridimage_vault USING (gridimage_id)
				WHERE gridimage_ticket_id IN (".implode(',',$ids).") $where
				ORDER BY gridimage_ticket_id DESC";
		}
		if (!empty($_GET['d'])) {
			print("$sql;<hr>");
		}
		$recordSet = $db->Execute($sql);

		$row = $recordSet->fields;

		print "<form method=post>";

		print "<p>".$recordSet->recordCount()." records shown";
		if (!empty($meta['total_found']))
			print ", of {$meta['total_found']} matching keywords";

		print "<TABLE border='1' cellspacing='0' cellpadding='2' class=\"report sortable\" id=\"photolist\" style=font-size:0.8em><THEAD><TR>";
		foreach ($row as $key => $value) {
			print "<TH>$key</TH>";
		}
		print "</TR></THEAD><TBODY>";
		$keys = array_keys($row);
		$first = $keys[0];
		while (!$recordSet->EOF) {
			$row = $recordSet->fields;

			print "<TR>";
			$align = "left";
        	        if (is_null($row[$first])) {
	                        $row['team'] = '-EVERYONE-';
			}
 			foreach ($row as $key => $value) {
				if ($key == 'image') {
					print "<TD sortvalue={$value}><a href=\"/editimage.php?id={$value}\">{$value}</a></TD>";
				} elseif ($key == 'notes') {
					print "<TD>".preg_replace('/^(Auto-generated.*?image because:)/','<span style=color:silver>$1</span><br>',htmlentities($value))."</TD>";
				} elseif ($key == 'review_date') {
					print "<TD sortvalue=".htmlentities($value)."><input type=date id=d{$row['image']} name=review_date[{$row['image']}] value=\"".htmlentities(str_replace('1000-00-00','',$value))."\"><br>";
					foreach (array(5,10,15,50) as $y) {
						$bits = explode('-',$row['suggested']);
						$bits[0]+= $y;
						$date= substr(implode('-', $bits),0,10);
						print "<a href=\"#\" onclick=\"return setDate('d{$row['image']}','$date');\">$y</a> ";
					}
					print "<a href=\"#\" onclick=\"return setDate('d{$row['image']}','');\">X</a> ";

					print " or Invalid:<input type=checkbox name=invalid[{$row['image']}]".($value == '1000-00-00'?' checked':'')."></TD>";
				} elseif ($key == 'osticket' && empty($value)) {
						print "<TD><input type=text name=osticket[{$row['image']}] size=10 maxlength=64>";
				} else {
					print "<TD>".htmlentities($value)."</TD>";
				}
			}
			print "</TR>";
                	$recordSet->MoveNext();
		}

		print "</TR></TBODY></TABLE>";

		print "<input type=submit value=\"Save\">";
		print "</form>";

	} else {
		print "No Results";
	}

	#############################
}

?><hr>
<script>
	function setDate(element,date) {
		document.getElementById(element).value = date;
		return false;
	}
</script>

<p><small>* Stemming is enabled on the index, so to match exactly say 'vaulted' prefix with =, eg <tt style="border:1px solid gray;padding:2px">=vaulted</tt> ;
 otherwise will match 'vaulting', 'vaulted', and even 'vault'</p>

<p>Prefix single words to negate the match, eg <tt style="border:1px solid gray;padding:2px">-bridge</tt>

<p>Surround with phrase marks to match a whole phrase <tt style="border:1px solid gray;padding:2px">"Auto Generated Ticket"</tt> (Note may need to still need to use = prefix, on individual words in the phrase. And can negate the whole phrase by prefixing with -

<p>It normally is only searching the text of the initial opening ticket. Use @syntax to search specific fields:<br>
@notes - the initial ticket opening text<br>
@suggestor - the name of the user making the suggestion<br>
@title - the (current) image title<br>
@comment - the (current) image description<br>
@realname - the image owner name<br>
@grid_reference, @hectad or @myriad - the grid_reference of the image 4fig, 2fig or myriad letter only

<p>Can combine multiple at once
<br>
Example: <tt style="border:1px solid gray;padding:2px">@notes vaulted @suggestor mary @title -bridge</tt><br><br>

<tt style="border:1px solid gray;padding:2px">@notes =vault auto @grid_reference TQ7070</tt><br><br>

<tt style="border:1px solid gray;padding:2px">@notes "vaulting image" @myriad -SH -TQ -NT -HN</tt>
 <?

$smarty->display('_std_end.tpl');


################################################

function dump_recordSet($sql,$title) {
	global $db;

	print "<H3>$title</H3>";

        if ($recordSet->EOF) {
                print "0 rows";
                return;
        }

}

