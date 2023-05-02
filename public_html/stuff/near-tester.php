<?

require_once('geograph/global.inc.php');
require_once('geograph/imagelist.class.php');
init_session();


$smarty = new GeographPage;




	$db = GeographDatabaseConnection(false);

	$data = $db->getAll("SELECT gridimage_id,gridsquare_id,nateastings,natnorthings,natgrlen,grid_reference
		FROM gridimage
		INNER JOIN gridsquare USING (gridsquare_id)
		WHERE reference_index = 2
		AND moderation_status != 'rejected'
		group by gridsquare_id
		order by gridimage_id
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
		}
	}


