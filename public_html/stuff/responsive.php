<?

require_once('geograph/global.inc.php');
init_session();

$db = GeographDatabaseConnection(true);

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$smarty->display('_std_begin.tpl');

$is_admin = $USER->hasPerm('admin') || ($USER->user_id == 1469); // or dsp!


if (!empty($_GET['id'])) {
	$row = $db->getRow("SELECT * FROM responsive_template WHERE responsive_id=".intval($_GET['id']));
	if (!empty($_POST['status'])) {
		if ($db->readonly)
			$db = GeographDatabaseConnection(false);

		$updates= array();
		foreach ($_POST as $key => $value) {
			if (isset($row[$key]))
				$updates[$key] = $value;
		}
		$notes = "set to {$updates['status']} by ".$USER->realname." ".date('r');


		$where = "responsive_id = ".intval($_GET['id']);
		$notes = ", notes = CONCAT(notes,".$db->Quote($notes."\n").")";
		$db->Execute('UPDATE responsive_template SET `'.implode('` = ?,`',array_keys($updates)).'` = ?'.$notes.' WHERE '.$where, array_values($updates));


		if (!empty($_POST['skip']))
			@$_SESSION['skip'][$row['responsive_id']] = $row['responsive_id'];

		if ($updates['status'] == 'in progress') {
			print "<script>window.open('https://development.geograph.org.uk/git/auto-edit-resp.php?file={$row['file']}','_blank');</script>";
		} else {
			if (!empty($_POST['auto'])) {
				$where = array();
				$where[] = "status = 'unknown'";
				if (!empty($_SESSION['skip'])) {
					$ids = implode(',',$_SESSION['skip']);
					if (preg_match('/^\d+(,\d+)*$/',$ids))
						$where[] = "responsive_id NOT IN ($ids)";
				}
		//		if ($USER->user_id != 3) {
					$where[] = "file NOT like 'admin_%'";
					$where[] = "file NOT like '%/admin%'";
		//		}
				$id = $db->getOne($sql = "SELECT responsive_id FROM responsive_template WHERE ".implode(' AND ',$where));
			}
			if (!empty($id)) {
				//js redirect, so the window.open above still works!
				print "<script>location.href = '?id=$id&auto=1';</script>";
			} else {
				print "<script>location.href = '?';</script>";
			}
			exit;
		}
	}

	$statuses = $db->getCol("SELECT DISTINCT status FROM responsive_template");
	?>
		<h2><a href=?>&lt;-- back</a> :: <? echo $row['file']; ?></h2>
	<?
	if (empty($row['url'])) {
		print "We dont know a good test URL for this page, a possible URL has been autofilled below, will need to confirm you really ARE testing the specific template, might need to figure out a different URL to find the right template!";
		if (preg_match('/static_(\w[\w-]+)\.tpl/',$row['file'],$m)) {
                        $row['url'] = "https://staging.geograph.org.uk/help/{$m[1]}?responsive=4";
                } elseif (!empty($row['used_by'])) {
			if (preg_match('/public_html(\/\w+.*?\.php)/',$row['used_by'],$m))
				$row['url'] = "https://staging.geograph.org.uk{$m[1]}?responsive=4";

		} elseif (preg_match('/public_html(\/\w+.*\.php)$/',$row['file'],$m)) {
			$row['url'] = "https://staging.geograph.org.uk{$m[1]}?responsive=4";
		}
		$row['url'] = str_replace('/index.php?','/?',$row['url']);
	}
	if (!empty($row['url'])) {
		//always show th dev their local domain!
		$row['url'] = preg_replace('/^https?:\/\/\w[\w.]+/',$domain,$row['url']);
	}
	if (preg_match('/\.tpl/',$row['file'])) {
		$gitURL = "https://github.com/geograph-project/geograph-project/blob/british-isles/public_html/templates/basic/".$row['file'];
	} elseif (preg_match('/public_html\/\w/',$row['file'])) {
		$gitURL = "https://github.com/geograph-project/geograph-project/blob/british-isles/".$row['file'];
	} else
		$gitURL = "unknown";

	?>
	<form method=post style=background-color:#eee;padding:10px>
		Test URL: <input type=text name=url value="<? echo htmlentities($row['url']); ?>" size=90 maxlength=255><button type=button id=openlink>Open in new window</button><br>
		<blockquote>
			When testing the page, suggest using the Chrome DevTools <a href="https://developer.chrome.com/docs/devtools/device-mode/">Device Mode</a>.
			Set to 'Responsive' mode, and try resizing window both narrow AND wide.<hr>
			Also make sure that <b><tt><? echo $row['file']; ?></tt> is <u>really</u> being used</b> (the URL might be displaying some OTHER template)
			Make sure the page viewing is coming from the <a href="<? echo $gitURL; ?>" target=github>template</a> (open <tt><? echo $row['file']; ?></tt> on github)
			<i>(once found a good test URL for <tt><? echo $row['file']; ?></tt>, remember to enter above!)</i>
			<hr>
			Finally make sure the responsive template is active (top header should change, even if content doesnt). In rare cases it might not be.
		</blockquote>
		Status: <? if ($is_admin) { ?>
				<select name=status><?
				foreach ($statuses as $status)
					printf('<option%s>%s</option>',($status == $row['status'])?' selected':'', $status);
				print "</select><br>";
			} else {
				print htmlentities($row['status'])."</br>";
				print "<input type=hidden name=status value=\"".htmlentities($row['status'])."\">";
			}
		if ($row['status'] == 'unknown') {
			print "<button type=button value=broken>Set to broken</button> - click this if you've tested the template, and can see it NOT responsive! (needs developer attention)<br>";
			print "<button type=button value=infeasible>Set to infeasible</button> - click this if the page doesnt appear WORTH fixing. (eg its a little used admin tool)<br>";
			print "<button type=button value=whitelisted>Set to whitelisted</button> - click if tested and found is WORKS as is (no fixing required)<br>";
			print "&nbsp; &middot; NOTE: before selecting this, make sure ALL variations the template is used for are tested. (eg submission has many steps, will need to check ALL steps are responsive!)<br><br>";
		}
		if ($is_admin) {
			print "<button type=button value='in progress'>Set to in progress</button> - click if are developer and going to fix the template (use so other developers know you starting!)<br>";
			print "<button type=button value=converted>Set to converted</button> - click if are developer and have fixed the template and committed the changes<br>";
		}
		if (true) {
			print "<label><input type=checkbox name=skip> Skip - tick this box, if unsure how to proceed (let someone else deal with it!)</label>";
		}
	?>
	<br><br>
	<input type=submit value="Save changes">   (<input type=checkbox name="auto" <? if (empty($_POST) || @$_REQUEST['auto']) { echo "checked"; } ?>> automatically load next template)
	</form>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script>
	$(function() {
		$('button[value]').click(function(e) {
			var ele = this.form.elements['status'];
			if (ele.type == 'select-one')
				for(let q=0;q<ele.options.length;q++)
					if (this.value == ele.options[q].value)
						ele.selectedIndex = q;
			else
				ele.value = this.value;
		});
		$('button#openlink').click(function(e) {
			window.open(this.form.elements['url'].value,'_blank');
			return false;
		});
	});
	</script>
	<?
} else {
	print "<h2>Site Templates to Test for responsiveness</h2>";
	$where = array();
	$where[] = "status != 'invalid'";
	$where[] = "status != 'infeasible'";
	$where[] = "file NOT LIKE '%/stuff/%'";
	$where[] = "file NOT like '%/admin/%'";
	$where[] = "file NOT like 'admin_%.tpl'";
	$recordSet = $db->Execute("SELECT * FROM responsive_template WHERE ".implode(" AND ",$where)." ORDER BY status, file");

	print "<table>";
	while (!$recordSet->EOF) {
        	$row = $recordSet->fields;

		print "<tr><th><tt>{$row['file']}</tt>";
		print "<td>{$row['status']}";

		if (strpos($row['file'],'/admin/') !== FALSE || strpos($row['file'],'admin_') === 0) {
			$recordSet->MoveNext();
			continue;
		}

		if ($row['status'] == 'unknown') {
			print "<td><a href=?id={$row['responsive_id']}>Update</a>";
		} elseif ($is_admin) {
			print "<td><a href=?id={$row['responsive_id']}>Update</a>";
		}

		$recordSet->MoveNext();
	}
	$recordSet->Close();
	print "</table>";
}

$smarty->display('_std_end.tpl');
