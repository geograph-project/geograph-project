<?

require_once('geograph/global.inc.php');
init_session();

$db = GeographDatabaseConnection(true);

$smarty = new GeographPage;

$USER->mustHavePerm("basic");

$db->Execute('USE geograph_live');
$domain = $CONF['SELF_HOST']; //need to use current domain, becaues of iframe!


if (!empty($_POST['id'])) {
            if ($db->readonly)
                        $db = GeographDatabaseConnection(false);

                $cols = $db->getAssoc("DESCRIBE responsive_test");

                $updates= array();
		if (!empty($_POST['ok']))
			$updates['test_ok'] = 1;
		if (!empty($_POST['broken']))
			$updates['test_ok'] = 0;

                $updates['user_id'] = $USER->user_id;
                $updates['responsive_id'] = intval($_POST['id']);

                $db->Execute($sql = 'INSERT INTO responsive_test SET `'.implode('` = ?,`',array_keys($updates)).'` = ?'.
                         ' ON DUPLICATE KEY UPDATE `'.implode('` = ?,`',array_keys($updates)).'` = ?',
                         array_merge(array_values($updates),array_values($updates))) or die("$sql\n".$db->ErrorMsg()."\n\n");;

                if (!empty($_POST['skip']))
                        @$_SESSION['skip'][$row['responsive_id']] = $row['responsive_id'];

	if (!empty($_POST['broken'])) {
		header("Location: responsive-phase2.php?id=".intval($_POST['id'])."&auto=1");
		exit;
	}
}





if (!empty($_GET['id'])) {
	$row = $db->getRow("SELECT * FROM responsive_template WHERE responsive_id = ".intval($_GET['id']));
} else {
	$where = array();
        $where[] = "status IN ('whitelisted','converted')";
        if (!empty($_SESSION['skip'])) {
                $ids = implode(',',$_SESSION['skip']);
                if (preg_match('/^\d+(,\d+)*$/',$ids))
                        $where[] = "responsive_id NOT IN ($ids)";
        }
        $where[] = "url != ''";
        $where[] = "url != 'none'";
	$where[] = "responsive_test.responsive_id IS NULL";

        if ($USER->user_id != 3) {
                $where[] = "file NOT like 'admin_%'";
                $where[] = "file NOT like '%/admin%'";
                $where[] = "file NOT like '%adopt%'";
                $where[] = "file NOT like '%human%'";
                $where[] = "file NOT like '%photoset%'";
                $where[] = "file NOT like '%curated%'";
        }

	$row = $db->getRow($sql = "SELECT responsive_template.* FROM responsive_template
		LEFT JOIN responsive_test ON (responsive_test.responsive_id = responsive_template.responsive_id AND user_id = {$USER->user_id})
		WHERE ".implode(' AND ',$where)." LIMIT 1");
}

$row['url'] = preg_replace('/^https?:\/\/\w[\w.]+/',$domain,$row['url']);

$url = htmlentities($row['url']);

?>
<form method=post>
	<input type=hidden name=id value="<? echo $row['responsive_id']; ?>">
	<a href="<? echo $url; ?>"><? echo $url; ?></a><br><br>
	<input type="submit" name=ok value="Appears OK" style=background-color:lightgreen>
	<input type="submit" name=broken value="Broken!" style=background-color:pink>
	<input type="submit" name=skip value="Skip">
</form>

<div class="preview-wrapper">
  
	<input type="radio" name="size" class="size-select size1" checked id="ss1"><label for="ss1" title="Small (eg phone)">
		<svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
		    <path d="M0 0h24v24H0z" fill="none"/>
		    <path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/>
		</svg>
	</label>
  
	<input type="radio" name="size" class="size-select size2" id="ss2"><label for="ss2" title="Medium (eg tablet)">
		<svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
		    <path d="M0 0h24v24H0z" fill="none"/>
		    <path d="M21 4H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h18c1.1 0 1.99-.9 1.99-2L23 6c0-1.1-.9-2-2-2zm-2 14H5V6h14v12z"/>
		</svg>
	</label>
  
	<input type="radio" name="size" class="size-select size3" id="ss3"><label for="ss3" title="Large (eg desktop)">
		<svg height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
		    <path d="M0 0h24v24H0z" fill="none"/>
		    <path d="M21 2H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h7v2H8v2h8v-2h-2v-2h7c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H3V4h18v12z"/>
		</svg>
	</label>
  
	<iframe id='viewer' name='Viewer' src="<? echo $url; ?>"></iframe>
</div>

<style>
html, body {
  margin:0; 
  padding: 0; 
  width: 100%; 
  height: 100%; 
  overflow: hidden;
  font-family: 'Roboto', sans-serif;
}
.preview-wrapper {
  display:flex; 
  align-items:center;
  justify-content:center;
}
#viewer { 
  transition: all 0.5s; 
  width: 375px; 
  height: 667px;
  max-width: 100%; 
  max-height: 100%; 
  border: none;
  box-shadow: 0 0 80px rgba(0,0,0,0.3); 
  --transform: scale(0.8);
}
.size-select { 
  position: absolute; 
  top: -100px; 
  left: -100px; 
  opacity: 0 
}

.size-select + label { 
  position:fixed; 
  top: 10px; 
  width: 100px; 
  height: 40px; 
  z-index: 1;
  background: #FFF; 
  border: 1px solid #CCC; 
  display: inline-flex; 
  align-items: center; 
  justify-content: center; 
  color:  #807e7e;
}
.size-select + label svg { 
  fill:  currentcolor;
}
.size-select:first-of-type + label { 
  border-top-left-radius: 4px; 
  border-bottom-left-radius: 4px; 
}
.size-select:last-of-type + label { 
  border-top-right-radius: 4px; 
  border-bottom-right-radius: 4px; 
}
.size-select:checked + label { 
  background: #8557dc; 
  color: #FFF;
  border-color: #8557dc;
}
.size1 + label { 
  left: calc(50% - 100px); 
  transform: translateX(-50px); 
}
.size2 + label { 
  left: 50%; 
  transform: translateX(-50px); 
}
.size3 + label { 
  left: calc(50% + 100px); 
  transform: translateX(-50px); 
}
.size1:checked ~ #viewer { 
  width: 375px; height: 667px; 
}
.size2:checked ~ #viewer { 
  width: 1000px; 
  height: 672px;
}
.size3:checked ~ #viewer { 
  width: 1440px; 
  height: 840px; 
}
</style>
