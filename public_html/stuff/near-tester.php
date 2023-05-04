<?

require_once('geograph/global.inc.php');
require_once('geograph/imagelist.class.php');
init_session();


$smarty = new GeographPage;




	$db = GeographDatabaseConnection(false);

	//Note, at this time, this page is ONLY for testing Ireland!
		// dont have a way of testing what would happen with os_gaz_250_old

	if (false) {
		$where = "reference_index = 2
                AND moderation_status != 'rejected'
                group by gridsquare_id"; //the group by to cut down on duplicates!
	} else {
		//WHERE CONTAINS(GeomFromText('POLYGON((27 168,377 168,377 613,27 613,27 168))'),point_xy)  order by sequence is still slow!

		$sph = GeographSphinxConnection('sphinxql',true);
		$ids = $sph->getCol("select id from sample8 where scenti >= 2000000000 order by sequence asc LIMIT 75");

		$where = "gridimage_id IN (".implode(',',$ids).") AND moderation_status != 'rejected'"; //shouldnt get rejected from sample8, but just in case!
	}


	$data = $db->getAll("SELECT gridimage_id,gridsquare_id,nateastings,natnorthings,natgrlen,grid_reference
		FROM gridimage
		INNER JOIN gridsquare USING (gridsquare_id)
		WHERE $where
		ORDER BY grid_reference
		LIMIT 75
		");

	foreach ($data as $row) {
		$grid_ok = false;
                $image = new Gridimage();

       	        $image->_initFromArray($row);
                if ($image->isValid() && $image->moderation_status!='rejected') {
                        $grid_ok = 1;
       	                $square = $image->grid_square;
                }

		if ($grid_ok) {
			$updates = array();
			$updates['gr'] = $square->get6FigGridRef();

			$place = $square->findNearestPlace(75000,'geonames'); //have to specifically ask for old one!
			$place['html'] = smarty_function_place(array('place'=>$place));
	                $old = strip_tags($place['html']);

			$place = $square->findNearestPlace(75000); //we are now making the new one the default anyway!
			$place['html'] = smarty_function_place(array('place'=>$place));
	                $new = strip_tags($place['html']);


			print "<b><a href=/near.php?gridref={$updates['gr']}>{$updates['gr']}</a></b>";
			print " (from image [[<a href=\"/photo/{$row['gridimage_id']}\">{$row['gridimage_id']}</a>]])<br>";
			print "<div style=color:red>{$old}</div>";

			print "<div style=color:green>{$new}</div>";
			print "<hr>";
			flush();
		}
	}


