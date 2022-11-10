<?

require_once('geograph/global.inc.php');
init_session();

$db = GeographDatabaseConnection(true);

$CONF['template'] = 'resp'; //need to do BEFORE new GeographPage!

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$smarty->assign('responsive',true); //for this page, we force respsonive, so can open this page on mobile!

$smarty->display('_std_begin.tpl',true);

$is_admin = $USER->hasPerm('admin') || ($USER->user_id == 1469); // or dsp!

$db->Execute('USE geograph_live');
$domain = $db->getOne("SELECT domain FROM responsive_domain WHERE user_id = {$USER->user_id}");
if (empty($domain))
	$domain = "https://www.geograph.org.uk";

if (!empty($_GET['id'])) {
	$row = $db->getRow("SELECT * FROM responsive_template WHERE responsive_id=".intval($_GET['id']));
	if (!empty($_POST['url'])) { //will always be set!

		if ($db->readonly)
			$db = GeographDatabaseConnection(false);

		$cols = $db->getAssoc("DESCRIBE responsive_test");

		$updates= array();
		foreach ($_POST as $key => $value) {
			if (isset($cols[$key]))
				$updates[$key] = $value;
		}
		$updates['user_id'] = $USER->user_id;
		$updates['responsive_id'] = intval($_GET['id']);

                $db->Execute($sql = 'INSERT INTO responsive_test SET `'.implode('` = ?,`',array_keys($updates)).'` = ?'.
                         ' ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ?',
                         array_merge(array_values($updates),array_values($updates))) or die("$sql\n".$db->ErrorMsg()."\n\n");;

		if (!empty($_POST['skip']))
			@$_SESSION['skip'][$row['responsive_id']] = $row['responsive_id'];

		if (!empty($_GET['auto'])) {
			print "<script>location.href = 'responsive-viewer.php';</script>";
			exit;
		} else {
			if (!empty($_POST['auto'])) {
				$where = array();
				$where[] = "status in ('converted','whitelisted','enabled')";
				$where[] = "url != ''";
				$where[] = "url != 'none'";
				$where[] = "responsive_test.responsive_id IS NULL";
				if (!empty($_SESSION['skip'])) {
					$ids = implode(',',$_SESSION['skip']);
					if (preg_match('/^\d+(,\d+)*$/',$ids))
						$where[] = "responsive_id NOT IN ($ids)";
				}
				$id = $db->getOne($sql = "SELECT responsive_id FROM responsive_template
				 LEFT JOIN responsive_test ON (responsive_test.responsive_id = responsive_template.responsive_id AND user_id = {$USER->user_id})
				WHERE ".implode(' AND ',$where));
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

	$r2 = $db->getRow("SELECT * FROM responsive_test WHERE responsive_id = {$row['responsive_id']}  AND user_id = {$USER->user_id}");

	if (!empty($row['url'])) {
		//always show th dev their local domain!
		$row['url'] = preg_replace('/^https?:\/\/\w[\w.]+/',$domain,$row['url']);
	}

	?>
		<h2><a href=<? echo empty($_GET['auto'])?'?':'responsive-viewer.php'; ?>>&lt;-- back</a> :: <? echo $row['file']; ?></h2>

	<form method=post style=background-color:#eee;padding:10px>
		Test URL: <input type=text name=url value="<? echo htmlentities($row['url']); ?>" size=90 maxlength=255 readonly style="max-width:85vw"><button type=button id=openlink>Open in new window</button>
		<button type=button id=openqr>Open QR Code</button>
		<br>
		<blockquote>
	<? if ($row['file'] == 'mapper_combined.tpl') { ?>
		Note, this is one of a few special cases. <b>Can't test using the 'Responsive' DevTools mode</b>, as it doesn't respond to just resizing windows. Will need to load the page directly on a mobile device (not just a small window) to test.
		<hr>
		Although you can still use the Chrome DevTools Device Emulator, but will need to explicitly set a mobile device, eg 'iPhone SE' and say 'iPad' for tablet.
		<hr>
		<b>Goto <a href="<? echo $domain; ?>/explore/?responsive=4"><? echo $domain; ?>/explore/?responsive=4</a> on the device, THEN click 'Maps' in the sidebar/menu to test.</b>
	<? } elseif ($row['file'] == 'public_html/gallery.php') { ?>
		Note, this is one of a few special cases. <b>Can't test using the 'Responsive' DevTools mode</b>, as it doesn't respond to just resizing windows. Will need to load the page directly on a mobile device (not just a small window) to test.
		<hr>
		Although you can still use the Chrome DevTools Device Emulator, but will need to explicitly set a mobile device, eg 'iPhone SE' and say 'iPad' for tablet.
		<hr>
		<b>Goto <a href="<? echo $domain; ?>/explore/?responsive=4"><? echo $domain; ?>/explore/?responsive=4</a> on the device, THEN click 'Showcase' in the sidebar/menu to test.</b>
	<? } elseif ($row['status'] == 'enabled') { ?>
		Note, this is one of a few special cases. <b>The page may not be totally responsive itself, but should have special handling</b>
		... for this page in particular, can flag the page as ok, even if it not perfect. It just needs to load enough to be usable, and to read the message, suggesting an alternative.
	<? } else { ?>
			When testing the page, suggest using the Chrome DevTools <a href="https://developer.chrome.com/docs/devtools/device-mode/">Device Mode</a>.
                        Set to 'Responsive' mode, and try resizing window both narrow AND wide.<hr>
			But ideally test on real mobile devices too.
	<? } ?>
		</blockquote>

			 I've tested this on mobile - '<b>portrait</b>' format:
			<input type=radio name=test_mobile value=0 <? if (strlen(@$r2['test_mobile'])) { echo "checked"; } ?>>Broken &middot;
			<input type=radio name=test_mobile value=1 <? if (!empty(@$r2['test_mobile'])) { echo "checked"; } ?>>Working &middot;
			<br><br>

			 I've tested this on mobile - '<b>landscape</b>' format:
			<input type=radio name=test_landscape value=0 <? if (strlen(@$r2['test_landscape'])) { echo "checked"; } ?>>Broken &middot;
			<input type=radio name=test_landscape value=1 <? if (!empty(@$r2['test_landscape'])) { echo "checked"; } ?>>Working &middot;
			<br><br>

			 I've tested this on <b>tablet</b> (ideally at various sizes):
			<input type=radio name=test_tablet value=0 <? if (strlen(@$r2['test_tablet'])) { echo "checked"; } ?>>Broken &middot;
			<input type=radio name=test_tablet value=1 <? if (!empty(@$r2['test_tablet'])) { echo "checked"; } ?>>Working &middot;
			<br><br>

			 I've tested this on <b>desktop</b> (ideally at various sizes):
			<input type=radio name=test_desktop value=0 <? if (strlen(@$r2['test_desktop'])) { echo "checked"; } ?>>Broken &middot;
			<input type=radio name=test_desktop value=1 <? if (!empty(@$r2['test_desktop'])) { echo "checked"; } ?>>Working &middot;
			<br><br>

			 I've tested this on</label> <a href="https://www.bing.com/webmaster/tools/mobile-friendliness?url=<? echo urlencode($row['url']); ?>" target=testwindow>Bing Mobile Friendly Test</a>:
			<input type=radio name=test_bing value=0 <? if (strlen(@$r2['test_bing'])) { echo "checked"; } ?>>Broken &middot;
			<input type=radio name=test_bing value=1 <? if (!empty(@$r2['test_bing'])) { echo "checked"; } ?>>Working &middot;
			<br><br>

		Comments: (optional) - no need to enter anything if page seems to work fine on all screen sizes, but can add explanation, if something doesn't work!<br>
		<textarea name=comments rows=4 cols=80 style="max-width:85vw"><? echo htmlentities(@$r2['comments']); ?></textarea>

	<br><br>
	<input type=submit value="Save changes">   (<input type=checkbox name="auto" <? if (empty($_POST) || @$_REQUEST['auto']) { echo "checked"; } ?>> automatically load next template)
	</form>
	(Google also have a mobile friendly tester, but we going to call that programmatically)
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
	<script>
	$(function() {
		$('button#openlink').click(function(e) {
			window.open(this.form.elements['url'].value,'testwindow');
			return false;
		});
		$('button#openqr').click(function(e) {
			getQR(this.form.elements['url'].value);
			return false;
		});
	});
	function getQR(url) {
		$.getScript("https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js", function() {
			$('body').append('<div style="position:fixed; top:30px; right:30px; left:30px; background-color:white;padding:20px; z-index:50000" onclick="$(this).remove()"><div id=qrcode style="width:250px;height:250px"></div><br><br>Click to Close</div>');
			new QRCode(document.getElementById("qrcode"), url);
		});
	}
	</script>
	<?

} else {
	$recordSet = $db->Execute("SELECT t.*,count(responsive_test_id) as tests,SUM(IF(user_id = {$USER->user_id},1,0)) as own
	 FROM responsive_template t
	 LEFT JOIN responsive_test USING (responsive_id)
	 WHERE status in ('converted','whitelisted','enabled') AND url != '' AND url != 'none' GROUP BY responsive_id ORDER BY tests, file");

	print "<h2>Pages to be tested on small screens</h2>";

	#############################################

	/* Rather than <table> should use CSS Grid!

	print "<table>";
	while (!$recordSet->EOF) {
        	$row = $recordSet->fields;
		if ($row['own'])
			print "<tr style=color:silver>";
		else
			print "<tr>";
		print "<th><tt>{$row['file']}</tt>";
		$base = preg_replace('/^https?:\/\/\w[\w.]+/','',str_replace('?responsive=4','',$row['url']));
		print "<td>{$base}";
		print "<td>{$row['status']}";
		print "<td>{$row['tests']} done";

		print "<td><a href=?id={$row['responsive_id']}>Test</a>";

		$recordSet->MoveNext();
	}
	$recordSet->Close();
	print "</table>";
	*/

	#############################################

	// tip: https://medium.com/evodeck/responsive-data-tables-with-css-grid-3c58ecf04723
	// also: https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Grid_Layout/Basic_Concepts_of_Grid_Layout

	while (!$recordSet->EOF) {
        	$row = $recordSet->fields;
		if ($row['own'])
			print "<div class=grid style=color:silver>";
		else
			print "<div class=grid>";
		print "<span><tt>{$row['file']}</tt></span>";
		$base = preg_replace('/^https?:\/\/\w[\w.]+/','',$row['url']);
		$base = preg_replace('/\?.*/','',$base);
		print "<span>{$base}</span>";
		print "<span>{$row['status']}</span>";
		print "<span>{$row['tests']} done</span>";

		print "<span><a href=?id={$row['responsive_id']}>Test</a></span>";
		print "</div>";
		$recordSet->MoveNext();
	}
	$recordSet->Close();
	?>
	<style>
	div.grid {
		display:grid;
		grid-template-columns: 3fr 3fr 2fr 1fr 1fr;
		border-top: 1px solid silver;
		--border-right: 1px solid silver;
		border-bottom: 1px solid #eee;
	}
	div.grid > span {
		padding:8px 4px;
		--border-left: 1px solid #eee;
		--border-bottom: 1px solid #eee;
	}
	div.grid > span:nth-child(5) {
		text-align:right;
	}

	@media screen and (max-width:900px) {
	        div.grid {
        	  grid-template-columns: repeat(3,1fr);
	        }
		div.grid > span:nth-child(1) {
			overflow:hidden;
			grid-column: 1/3;
		}
	}

	</style>
	<?

	#############################################

	print "<p>(grey lines are ones, you've already tested)</p>";
	print "Note: if 'stuck' in responsive template around the rest of the site, click <a href=\"$domain/?responsive=0\">this link</a>, and should revert to normal template";

}

$smarty->display('_std_end.tpl',true);
