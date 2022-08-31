<?


ini_set('display_errors',1);
require_once('geograph/global.inc.php');


$smarty = new GeographPage;

init_session();
$USER->mustHavePerm("basic");



$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');


$type = "i164253434";
if (!empty($_GET['type']) && preg_match('/^\w+$/',$_GET['type']))
	$type = $_GET['type'];

$break = 7;
if (!empty($_GET['one']))
	$break = 4;


print "<body style=\"color:white; background-color:black\">";
?>
<script type="text/javascript" src="<? echo smarty_modifier_revision("/js/geograph.js"); ?>"></script>
<?

print "<h3>Votes per image (only counting last vote, per image, per user)</h3>";

$query = "select @max := max(users) from vote_stat where type='$type'";
$query = "select @max := COUNT(DISTINCT vote_log.user_id,ipaddr) as max, avg(vote) as avg from vote_log where vote > 0 AND `final` = 1 and type='$type'";
$row = $db->getRow($query);

print "Total Voters = {$row['max']}, Overall Average = {$row['avg']} (used in the baysian calculation)<BR>";
print "<hr>";


$votes = $db->getAssoc("select id,vote from vote_log where type='$type' and user_id = ".$USER->user_id);

$query = "select imagetaken,id,(@max-users) as v0,' ',v1,v2,v3,v4,v5,'.',users,avg,v.baysian,round(avg*users) as total, (avg*users)/@max as avg0,
	((avg*users)+(@max-users)*3)/@max as avg3,last_vote
	from vote_stat v inner join gridimage_search on (id = gridimage_id) where type='$type' ORDER BY substring(imagetaken,1,$break),v.baysian DESC LIMIT 1000";


        $recordSet = $db->Execute($query) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

	if ($recordSet->EOF)
		return;


	$max = array();
	while(!$recordSet->EOF) {
		$row = $recordSet->fields;
		$last = substr($row['imagetaken'],1,$break);
		if (!is_null($row['id']))
			foreach ($row as $key => $value)
				if(empty($max[$last][$key]) || $value > $max[$last][$key])
					$max[$last][$key] = $value;
		$recordSet->MoveNext();
	}

	$recordSet->Move(0);

	$row = $recordSet->fields;

	$last = substr($row['imagetaken'],1,$break);
	$c = 0;
	$_GET['large'] =0;
        while(!$recordSet->EOF) {
		$row = $recordSet->fields;
		if ($last != substr($row['imagetaken'],1,$break)) {
			print "<hr>";
			$c=0;
		}
		$last = substr($row['imagetaken'],1,$break);

		$image = new Gridimage($row['id']);

		if ($c < 4) {
			print "<div style=\"display:inline-block;width:640px;height:500px\">";
			print $image->getFull();
		} else {
			print "<div style=\"display:inline-block;width:213px;height:210px\">";
			print $image->getThumbnail(213,160);
		}
		print "<div id=\"votediv{$image->gridimage_id}\">";
		smarty_function_votestars(array('type'=>$type,'id'=>$row['id']));
		if (!empty($votes[$row['id']]))
			print " (existing: {$votes[$row['id']]})</div></div>";
		else
			print "</div></div>";



		$recordSet->MoveNext();
		$c++;
        }
        print "</TR></TABLE>";



function smarty_function_votestars($params) {
	global $CONF;
	static $last;
	
	$type = $params['type'];
	$id = $params['id'];
	$names = array('','Hmm','Below average','So So','Good','Excellent');
	foreach (range(1,5) as $i) {
		print "<a href=\"javascript:void(record_vote('$type',$id,$i));\" title=\"{$names[$i]}\"><img src=\"{$CONF['STATIC_HOST']}/img/star-light.png\" width=\"14\" height=\"14\" alt=\"$i\" onmouseover=\"star_hover($id,$i,5)\" onmouseout=\"star_out($id,5)\" name=\"star$i$id\"/></a>";
	}
	if ($last != $type) {
		print " (<a href=\"/help/voting\">about</a>)";
	} 
	$last = $type;
}
