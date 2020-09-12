<?


require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm("basic");



$db = NewADOConnection($GLOBALS['DSN']);

if (!empty($_GET['id'])) {
       
	$image=new GridImage();
	$ok = $image->loadFromId($_REQUEST['id']);
		
	if (!$ok || ($image->moderation_status=='rejected' && !$USER->hasPerm('admin'))) {
		//clear the image
		$image=new GridImage;
		header("HTTP/1.0 410 Gone");
		header("Status: 410 Gone");
		die("not found");
	} else {
		$smarty = new GeographPage;

		$smarty->display('_std_begin.tpl');

		
		$desc = "Situated at a road junction at the top of Stow Hill with Risca Road straight on and Bassaleg road to the left.
		For a picture taken in 1955 see http://www.francisfrith.com/search/wales/gwent/newport/photos/newport_N25191.htm  . 
		For an early 20th century view see http://www.newportpast.com/gallery/postcards/stowhill/p0057.htm .";
		$desc = $image->comment;
		
		?>
			Photo from Geograph
			<h2><a href="/photo/<? echo htmlentities2($image->gridimage_id); ?>"><? echo htmlentities2($image->title); ?></a></h2>
		 <div class="img-shadow" id="mainphoto"><? echo $image->getFull(); ?></div>
		 <div class="caption"><? echo htmlentities2($desc); ?></div>
		  Taken: <? echo htmlentities2($image->imagetaken); ?>
		  
		  <!-- Creative Commons Licence -->
		  <div class="ccmessage"><a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img 
		  alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" /></a> &nbsp; &copy; Copyright <a title="View profile" href="<? echo htmlentities2($image->profile_link); ?>" xmlns:cc="http://creativecommons.org/ns#" property="cc:attributionName" rel="cc:attributionURL"><? echo htmlentities2($image->realname); ?></a> and  
		  licensed for <a href="/reuse.php?id=<? echo htmlentities2($image->gridimage_id); ?>">reuse</a> under this <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap" about="<? echo htmlentities2($image->_getFullpath(false,true)); ?>" title="Creative Commons Attribution-Share Alike 2.0 Licence">Creative Commons Licence</a>.</div>
		<!-- /Creative Commons Licence -->
		 <hr/>
		<?
		
		if (
			preg_match("/http:\/\/www.francisfrith.com\/([\/\w,\+-]+)_([a-zA-Z0-9]+)\.htm/",$desc,$m) ||
			preg_match("/http:\/\/www.francisfrith.com\/([\/\w,\+-]+)_([a-zA-Z]*\d+)/",$desc,$m)
		) {
			
			$url = "http://www.francisfrith.com/fpn/api/photo/{$m[2]}/161c19bee07e8d1a";

		
			require_once '3rdparty/JSON.php';
		
			$value = json_decode(file_get_contents($url));
			if (!empty($value->frith->copyright) && !empty($value->frith->photo)) {
			 	
			 	$photo = $value->frith->photo;

				?>
					Photo from the Francis Frith Collection
					<h2><a href="<? echo htmlentities2($photo->url); ?>"><? echo htmlentities2($photo->caption); ?></a></h2>
				 <div class="img-shadow" id="mainphoto"><img src="<? echo htmlentities2($photo->image->src); ?>" width="<? echo htmlentities2($photo->image->width); ?>" height="<? echo htmlentities2($photo->image->height); ?>"></div>
				 Taken: <? echo htmlentities2($photo->date); ?>
				<?
			 	
			 	
				print "<b>".htmlentities($value->frith->copyright)."</b>";

?>
		<br/><br/>
		<div class="interestBox" id="hide155">
			<span style="color:red">New!</span> - <a href="javascript:void(show_tree(155));" onclick="document.getElementById('frame155').src = '/stuff/fade.php?1=<? echo htmlentities2($photo->image->src); ?>&2=<? echo $image->gridimage_id; ?>';">Show as draggable slider/fader</a>
		</div>

		<div id="show155" style="display:none">
			Drag the slider below to fade between the two images mentioned above:<br/>
			<iframe src="about:blank" height="700" width="700" id="frame155">
			</iframe>
		</div>
<?

			} else {
				print "Unable to load Frith Photo";
			}
		} else {
        	ob_start();
                debug_print_backtrace();
		print "\n\nHost: ".`hostname`."\n\n";
		unset($image->db);
		unset($image->gridsquare);
		unset($image->grid_square);
		print_r($image);
                $con = ob_get_clean();
                mail('geograph@barryhunter.co.uk','[Geograph Error] '."Frith #".intval($_GET['id']),$con);
	

			print "Unable to identify Frith Photo in description";
		}
		
		
		$smarty->display('_std_end.tpl');
		exit;
	}
	
	
	
	
} else {
	$q = "comment:\"http www.francisfrith.com\" photos";

	$sphinx = new sphinxwrapper($q);

	//gets a cleaned up verion of the query (suitable for filename etc) 
	$cacheid = $sphinx->q;

	$sphinx->pageSize = $pgsize = 60; 


	$pg = (!empty($_REQUEST['page']))?intval(str_replace('/','',$_REQUEST['page'])):0;
	if (empty($pg) || $pg < 1) {$pg = 1;}

	$cacheid .=".".$pg;



	$offset = (($pg -1)* $sphinx->pageSize)+1;

	if ($offset < (1000-$pgsize) ) { 
		$sphinx->processQuery();

		
		$ids = $sphinx->returnIds($pg,'_images');

		if (!empty($ids) && count($ids)) {

			$where = "gridimage_id IN(".join(",",$ids).")";

			$db = GeographDatabaseConnection(true);

			$limit = 60;

			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$rows = $db->getAssoc("
			select gridimage_id,title,realname,grid_reference
			from gridimage_search 
			where $where
			limit $limit");

			print "<ul>";
			$results = array();
			foreach ($ids as $c => $id) {
				$row = $rows[$id];
				
				print "<li><a href=\"?id=$id\">".htmlentities2($row['title'])."</a>";
				print " for ".htmlentities2($row['grid_reference']);
				print " by ".htmlentities2($row['realname']);
				print "</li>";
			}
			print "</ul>";



		} else {
			print "no results";
		}
	} else {
		print "unable to fetch results";
	
	}
}

/*
{ "frith" : {
 "copyright" : "Frith Content Inc 2010. All rights reserved.",
  "photo" : 
  {
    "url"         : "http://www.francisfrith.com/newport,gwent/photos/the-handpost-inn-c1955_n25191/",
    "title"       : "The Handpost Inn c1955",
    "caption"     : "The Handpost Inn c1955, Newport",
    "date"        : "1955",
    "orientation" : "landscape",
    "thumbnail"   : "http://images.francisfrith.com/c10/120/49/N25191.jpg",
    "image"       :
      {
        "src"     : "http://images.francisfrith.com/c10/450/49/N25191.jpg",
        "width"   : "450",
        "height"  : "272"
      },
    "location"    :
      {
        "url"     : "http://www.francisfrith.com/newport,gwent/",
        "name"    : "Newport",
        "county"  : "Gwent",
        "lat"     : "51.5877",
        "lng"     : "-2.9871"
      },
    "index_url"   : "http://www.francisfrith.com/newport,gwent/photos",
    "text"  : 
    {
      "total_extracts"    : "0",
      "total_memories"    : "0",
      "extracts"          : [{}],
      "memories"          : [{}]
    }
  }}
}
*/
