<?

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

######################

$template = 'features_view.tpl'; $cacheid = '';

$db = GeographDatabaseConnection(true);
$isadmin=($USER->hasPerm('moderator') || $USER->hasPerm('director'))?1:0;

$id = intval($_GET['id']);

$cacheid = $id;

$row = $db->getRow("SELECT t.*,realname FROM feature_type t LEFT JOIN user USING (user_id)
	 WHERE feature_type_id = $id AND status > 0 AND (licence!='none' OR $isadmin OR user_id = {$USER->user_id})");

if (empty($row)) {
	header("HTTP/1.0 410 Gone");
       	header("Status: 410 Gone");
        $template = "static_404.tpl";

} elseif ($row['user_id'] == $USER->user_id || $USER->hasPerm('moderator')) {
	$smarty->assign('isadmin',1);
	$cacheid .= '|'.'adm';
}


if (!empty($row) && !$smarty->is_cached($template, $cacheid)) {

	$smarty->assign('page_title', $row['title']);

	$count = $db->getOne("SELECT COUNT(*) FROM feature_item  WHERE feature_type_id = $id AND status > 0");

	$row['item_columns'] = json_encode(explode(',',$row['item_columns']));

	$smarty->assign($row);
	$smarty->assign('count',$count);
}


$smarty->display($template,$cacheid);
