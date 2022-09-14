<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
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

$smarty = new GeographPage;

pageMustBeHTTPS();

$db = GeographDatabaseConnection(false);

$smarty->assign('page_title',"Featured Collections");

$smarty->display('_std_begin.tpl',md5($_SERVER['PHP_SELF']));
flush();

if (!empty($_POST)) {
        if (!empty($_POST['url'])) {
                $updates = array();

                $p = parse_url($_POST['url']);

                if (!empty($p['path'])) {
                        $updates['url'] = $p['path'];
                } elseif (strpos($updates['url'],'/') === 0) {
                        $updates['url'] = $_POST['url'];
                }

                if (!empty($updates['url'])) {
                        $updates['url'] = preg_replace('/(article|gallery)(\/.+)\/\d+$/','$1$2',$updates['url']); //remove the page number
                        $updates['content_id'] = $db->getOne("SELECT content_id FROM content WHERE url = ".$db->Quote($updates['url']));

                        if (!empty($updates['content_id'])) {
                                if (!empty($_POST['showday']))
                                        $updates['showday'] = $_POST['showday'];

                                $db->Execute('INSERT IGNORE INTO content_featured SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
				if ($db->Affected_Rows()==1) {
					print "Thank you for the suggestion.";
				} else {
					print "This collection is already on the list.";
				}
                        } else {
                                print htmlentities($updates['url'])." does not appear to be a valid collection. ";
                        }

                } else {
                        print htmlentities($_POST['url'])." does not appear to be a valid url. ";
                }
        }
}

if (empty($_GET['tab'])) {
	$_GET['tab'] =  'past';
}
switch($_GET['tab']) {
	case 'pool': $where = 'showday is NULL'; $title = "In the potential Pool"; break;
	case 'future': $where = 'showday is NOT NULL AND showday > DATE(NOW())'; $title = "Scheduled for publication"; break;
	case 'past':
	default:  $where = 'showday is NOT NULL AND showday < NOW()'; $title = "Already featured"; break;
}

?>
<div class="tabHolder">
        <a href="/content/" class="tabSelected">Collections</a>
        <a href="/article/" class="tab">Articles</a>
        <a href="/article/?table" class="tab">Article List</a>
        <a href="/geotrips/" class="tab">GeoTrips</a>
        <a href="/article/tree.php" class="tab">Tree</a>
        <a href="/gallery/" class="tab">Galleries</a>
        <? if ($CONF['forums']) { ?>
                <a href="/discuss/index.php?action=vtopic&amp;forum=6" class="tab">Themed Topics</a>
                <a href="/discuss/index.php?action=vtopic&amp;forum=5" class="tab">Grid Square Discussions</a>
                <a href="/article/Content-on-Geograph" class="tab">Contribute...</a>
        <? } ?>
</div>
<div class="interestBox">
<h2 style="margin:0">Featured Collections - <? echo $title; ?></h2>
</div>
<?

if ($_GET['tab'] == 'past') {
	print "<p>Note, the date shown is when the collection was first featured. The dates are somewhat erratic, because still establishing a schedule</p>";
}

?>
<form method=post>
<table cellpadding=10 width=800>
	<tr>
		<th>URL</th>
		<th>Day</th>
	</tr>

<? if ($USER->registered) { ?>
	<tr style="background-color:#eee">
		<td><input type=text name=url value="" size=64 placeholder="suggest a new collection by pasting its URL here"></td>
		<td><input type=submit value="Suggest"></td>
	</tr>
<? }


$today = date('Y-m-d');
foreach ($db->getAll("SELECT f.*,title,realname,user_id,extract FROM content_featured f INNER JOIN content USING (content_id) LEFT JOIN user USING (user_id) WHERE $where ORDER BY showday DESC") as $row) {

?>
        <tr>
                <td><b><a href="<? echo htmlentities($row['url']); ?>"><? echo htmlentities($row['title']); ?></a></b> by <a href="/profile/<? echo intval($row['user_id']); ?>"><? echo htmlentities($row['realname']); ?></a><br/>
			<small style="color:green"><? echo htmlentities($row['url']); ?></small>
			<? if (!empty($row['extract'])) { ?>
				<small> - <? echo htmlentities($row['extract']); ?></small>
			<? } ?>
			</td>
                <td class=nowrap><? if (empty($row['showday']) || $row['showday'] > $today) { ?>
		<? } else { ?>
			<? echo $row['showday']; ?>
		<? } ?></td>
        </tr>
<?


}
?>
</table>

</form>

<?

$smarty->display('_std_end.tpl');

