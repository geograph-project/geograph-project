<?

require_once('geograph/global.inc.php');
require_once('geograph/imagelist.class.php');
init_session();


$smarty = new GeographPage;


customGZipHandlerStart();

if (!empty($_POST['gr'])) {

	$updates= $_POST;
	$updates['user_id'] = $USER->user_id;
/*
	Array
(
    [gridref] => 19
    [gr] => SH665445
    [pid] => 2022081
    [distance] => 2.01
    [text] => near to Tanygrisiau, Gwynedd, Great Britain
    [os_gaz] => 
    [os_gaz_250] => 
    [os_open_names] => 100701088|2325m|Tanygrisiau|Gwynedd - Gwynedd|Village
    [loc_placenames] => 
)

CREATE TABLE near_feedback (
  `feedback_id` int(10) unsigned NOT NULL AUTO_INCREMENT,

  `gridref` VARCHAR(32) NOT NULL,
  `gr` VARCHAR(32) NOT NULL,
  `pid` INT UNSIGNED NOT NULL,
  `distance` FLOAT NOT NULL,
  `text` VARCHAR(255) NOT NULL,

`os_gaz` VARCHAR(255) NOT NULL,
`os_gaz_250` VARCHAR(255) NOT NULL,
`os_open_names` VARCHAR(255) NOT NULL,
`loc_placenames` VARCHAR(255) NOT NULL,
  `pid2` INT UNSIGNED NOT NULL,


  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),

	PRIMARY KEY (`feedback_id`)
);
*/

	$updates['pid2'] = @intval($_POST['os_gaz'])+intval($_POST['os_gaz_250'])+intval($_POST['os_open_names'])+intval($_POST['loc_placenames'])+intval($_POST['ie_open_data']);


	$db = GeographDatabaseConnection(false);

	$db->Execute($sql = 'INSERT INTO near_feedback SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

	$saved = $db->Affected_Rows();

} elseif (!empty($_GET['gridref'])) {


	if (is_numeric($_GET['gridref'])) {
		$image = new Gridimage();
		$image->loadFromId(intval($_GET['gridref']));
		if ($image->isValid() && $image->moderation_status!='rejected') {
			$grid_ok = 1;
			$square = $image->grid_square;
		}
	} else {
		$square=new GridSquare;
        	$grid_ok=$square->setByFullGridRef($_GET['gridref'],true);
		$place = array();
	}

	if ($grid_ok) {
		$place = $square->findNearestPlace(75000); //same as photo-page!

		$place['html'] = smarty_function_place(array('place'=>$place));
		$place['text'] = strip_tags($place['html']);

		$square->getNatEastings(); //set nateastings IF NOT already set! (eg by loadFromId/setByFullGridRef)
		$place['gr'] = $square->get6FigGridRef();

		if (!empty($square->nateastings)) {
		###############################

			$db=$square->_getDB();

			//$gaz->findListByNational does not QUITE work, so we unroll it here!

			$e = $square->nateastings;
			$n = $square->natnorthings;
			$reference_index = $square->reference_index;
			$radius = 75000;


	                //to optimise the query, we scan a square centred on the
	                //the required point
        	        $left=$e-$radius;
                	$right=$e+$radius;
	                $top=$n-$radius;
        	        $bottom=$n+$radius;

	                $rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";


		###############################

			if ($reference_index == 1) {
				$place['os_gaz'] = $db->GetAll("select
                                        `def_nam` as full_name,
                                        km_ref as grid_reference,
                                        `full_county` as adm1_name,
                                        (seq + 1000000) as pid,
                                        f_code,
                                        ( pow(cast(east as signed)-{$e},2)+pow(cast(north as signed)-{$n},2) ) as distance
                                from
                                        os_gaz
                                where
                                        CONTAINS(
                                                GeomFromText($rectangle),
                                                point_en)
                                order by distance asc,f_code+0,def_nam
				limit 100");

		###############################

				$place['os_gaz_250'] = $db->GetAll("select
                                        `def_nam` as full_name,
                                        `full_county` as adm1_name,
                                        (seq + 2000000) as pid,
                                        ( pow(cast(east as signed)-{$e},2)+pow(cast(north as signed)-{$n},2) ) as distance,
                                        'OS250' as gaz
                                from
                                        os_gaz_250
                                where
                                        CONTAINS(
                                                GeomFromText($rectangle),
                                                point_en)
                                order by distance asc
				limit 100");

		###############################

				$place['os_open_names'] = $db->GetAll("select
					concat_ws(' / ',nullif(name1_utf,''),nullif(name2_utf,'')) AS full_name,
					county_unitary AS adm1_name,
					(seq + 100000000) as pid,
					pow(e-{$e},2)+pow(n-{$n},2) as distance,
					local_type
		                from
                                        os_open_names2
                                where
                                        CONTAINS(
                                                GeomFromText($rectangle),
                                                point_en)
                                order by distance asc
                                limit 250");

		###############################

			} else {

				//doesnt have a point_en col!

				$place['ie_open_data'] = $db->GetAll("select
					name as full_name,
					county as adm1_name,
					town_class as local_type,
					(id + 9000000) as pid,
					( pow(cast(e as signed)-{$e},2)+pow(cast(n as signed)-{$n},2) ) as distance
				from
					ie_open_data
				where
					e between $left and $right and n between $top and $bottom
				order by distance asc
                                limit 100");
			}

		###############################

			$place['loc_placenames'] = $db->GetAll("select
                                        full_name,
                                        dsg,
                                        loc_placenames.reference_index,
                                        loc_adm1.name as adm1_name,
                                        loc_placenames.id as pid,
                                        pow(e-{$e},2)+pow(n-{$n},2) as distance
                                from
                                        loc_placenames
                                        left join loc_adm1 on (loc_placenames.adm1 = loc_adm1.adm1 and  loc_adm1.country = loc_placenames.country)
                                where
                                        dsg LIKE 'PPL%' AND
                                        CONTAINS(
                                                GeomFromText($rectangle),
                                                point_en) AND
                                        loc_placenames.reference_index = {$reference_index}
                                group by gns_ufi
                                order by distance asc
				limit 100");

		###############################

			if (!empty($place['os_gaz']))
				foreach ($place['os_gaz'] as &$row)
					$row['full_name'] = latin1_to_utf8($row['full_name']);
			if (!empty($place['os_gaz_250']))
				foreach ($place['os_gaz_250'] as &$row) {
					$row['full_name'] = latin1_to_utf8($row['full_name']);
					$row['adm1_name'] = recaps($row['adm1_name']);
				}

		###############################
		}
	}

	outputJSON($place);
	exit;
}

$USER->mustHavePerm("basic");

if (empty($db))
	$db = GeographDatabaseConnection(true);



$smarty->display("_std_begin.tpl");

if (!empty($_GET['list'])) {
	$data = "select gridref as location,gr,distance,text as original
	,COALESCE(nullif(os_gaz_250,''),nullif(os_gaz,''),nullif(os_open_names,''),nullif(loc_placenames,''),nullif(ie_open_data,'')) as suggestion, explanation
	 from near_feedback limit 1000";
	dump_sql_table($data,'Existing Reports');
}



?>
<h2>Feedback on 'near'</h2>

<? if (!empty($saved)) {
	print "$saved report(s) saved. Can submit another... ";
} ?>

<form method=post name=theForm>

1. Enter Grid-Reference OR numberic Image-ID
	<input type=text name=gridref value="" placeholder="eg SH506742 or 1234335" required>
	<input type=button onclick="lookup()" value=continue>

<div style=display:none id="more"><br>
	GR: <input type=text name=gr readonly><br>
	Place ID: <input type=text name=pid readonly required><br>
	Dist: <input type=text name=distance readonly><br>
	Result: <input type=text name=text size=80 readonly><hr><br>

	2. Please select what is wrong with above:<br>
	<input list="explanations" id="explanation" name="explanation" size=60 required />
<datalist id="explanations">
  <option value="Too far away from location (there is a better location closer)"></option>
  <option value="More well known place a litter further away"></option>
  <option value="Selected Unusual/unknown place (eg a small place people wont recognise)"></option>
  <option value="Selected an inappropriate type (eg a hill rather than settlement)"></option>
  <option value="Selected a place across border (eg selected a place in Wales, but the image isnt)"></option>
  <option value="Selected a place other side of river/estuary (eg image of Liverpool, selected a place in Birkenhead)"></option>
  <option value="Selected a place on different island/landmass (eg image is on Skye, but selected place on mainland)"></option>
  <option value="The gazetteer entry is misspelled"></option>
  <option value="The gazetteer entry is currupted (eg accents not displaying correctly)"></option>
  <option value="The gazetteer entry is wrong (might be misplaced)"></option>
  <option value="The location selected is great (select the same place in dropdown to confirm)"></option>
</datalist> (or can type other explanation)<hr><br>



	3. Please select a result you think is better: (Just select one place, from the first dropdown that has the place you looking for!) <br>
	250k Gazetteer: <select name="os_gaz_250"></select> <hr>
	50k Gazetteer: <select name="os_gaz"></select> <hr>
	Open Names Gazetteer: <select name="os_open_names"></select> <hr>
	Irish Open Data: <select name="ie_open_data"></select> <hr>
	GNS Gazetteer: <select name="loc_placenames"></select> <hr>

	4. <input type=submit>
</div>

</form>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
<link href="<? echo smarty_modifier_revision("/js/select2-3.3.2/select2.css"); ?>" rel="stylesheet"/>
<script src="<? echo smarty_modifier_revision("/js/select2-3.3.2/select2.js"); ?>"></script>
<script>
var url = "?";

function lookup() {
	var value = document.forms['theForm'].elements['gridref'].value;
	var eles = document.forms['theForm'].elements;

	if (value.length<1) {
		alert("please enter a image id, or a grid-refernce");
		return;
	}

	$('div#more').show();
	 eles['text'].value = "Loading, please wait...";
	 $('div#more select').select2('destroy').empty();

	$.getJSON(url+'&gridref='+encodeURIComponent(value), function(data) {
		console.log(data);
		if (data.pid)
			eles['pid'].value = data.pid;
		if (data.gr)
			eles['gr'].value = data.gr;
		if (data.distance)
			eles['distance'].value = data.distance;
		if (data.text)
			eles['text'].value = data.text;
		if (data.os_gaz)
			renderSelect($(eles['os_gaz']), data.os_gaz);
		if (data.os_gaz_250)
			renderSelect($(eles['os_gaz_250']), data.os_gaz_250);
		if (data.os_open_names)
			renderSelect($(eles['os_open_names']), data.os_open_names);
		if (data.ie_open_data)
			renderSelect($(eles['ie_open_data']), data.ie_open_data);
		if (data.loc_placenames)
			renderSelect($(eles['loc_placenames']), data.loc_placenames);

		$('div#more select').each(function() {
			if (this.options.length> 5)
				$(this).select2({width:"600px"});
		});
	});


}

function renderSelect($ele,values) {
	var $option = $('<option/>');
        $ele.append($option);

	$.each(values, function(index,row) {
		var $option = $('<option/>');

		var bits = [];

		var dist = Math.sqrt(row.distance); //the sorts by, so didnt sqrt it!
		bits.push(dist.toFixed(0)+'m');

		bits.push(row.full_name);
		if (row.adm1_name)
			bits.push(row.adm1_name);

		if (row.f_code)
			bits.push(f_code[row.f_code]);
		else if (row.local_type)
			bits.push(row.local_type);
		else if (row.dsg)
			bits.push(row.dsg);

		$option.attr({ 'value': row.pid+'|'+bits.join('|') }).text(bits.join(' | '));
		$ele.append($option);
	});
}

var f_code = <? print json_encode($db->getAssoc("SELECT f_code,IF(f_code IN ('C','T','O'),CONCAT(code_name,' ***'),code_name) AS code_name FROM os_gaz_code")); ?>;

</script>
<style>
	input[readonly] {
		border:0;
	}
</style>


<?

$smarty->display("_std_end.tpl");

function dump_sql_table($sql,$title) {
        global $db;
        $recordSet = $db->Execute($sql) or die ("Couldn't select photos : $sql " . $db->ErrorMsg() . "\n");

        if (!$recordSet->RecordCount())
                return;

        $row = $recordSet->fields;

        print "<H3>$title</H3>";

        print "<TABLE border='1' cellspacing='0' cellpadding='2'><TR>";
        foreach ($row as $key => $value) {
                print "<TH>$key</TH>";
        }
        print "</TR>";
        do {
                $row = $recordSet->fields;
                print "<TR>";
                foreach ($row as $key => $value) {
                        print "<TD>".htmlentities($value)."</TD>";
                }
                print "</TR>";
                $recordSet->MoveNext();
        } while ($recordSet && !$recordSet->EOF);
        print "</TR></TABLE>";
}



