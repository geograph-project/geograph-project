<?php

/**
 * $Project: GeoGraph $
 * $Id: functions.inc.php 2911 2007-01-11 17:37:55Z barry $
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

/**************************************************
*
******/

if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 8 ) {
	header("HTTP/1.1 503 Service Unavailable");
	die("server busy, please try later");
}

class game {

	public $rastermap = '';
	public $image = '';

	public $images = array();
	public $done = array();

	
	public function __construct() {

	}

	public function storeScore($points) {
		global $USER;
		if (isset($this->score)) {
			$this->score+= $points;
		} else {
			$this->score = $points;
		}

		if (isset($this->games)) {
			$this->games++;
		} else {
			$this->games = 1;
		}
		
		if (isset($this->image->gridimage_id)) {
			$this->done[] = $this->image->gridimage_id;
		}
	
		$updates = array();
		
		if (!empty($USER->user_id)) {
			$updates['user_id'] = $USER->user_id;
		}
		if (!empty($this->image)) {
			$updates['gridimage_id'] = $this->image->gridimage_id;
		}
		
		$updates['game_id'] = $this->game_id;
		if (!empty($this->l) && $this->l < 10) {
			$updates['level'] = $this->l;
		}
		$updates['score'] = $points;
		
		$this->_getDB()->Execute('INSERT INTO game_image_score SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	}
	
	public function saveScore($where = 'user',$username = '') {
		global $USER;
		
		$db = $this->_getDB();
		
		$updates = array();
		
		$app = 1; 
		if (!empty($USER->user_id)) {
			$updates['user_id'] = $USER->user_id;
		} elseif (!$db->getOne('SELECT game_score_id FROM game_score WHERE approved = 1 and username = '.$db->Quote($username))) {
			$updates['approved'] = '0';
			$app = 0; 
			
			$mods=$db->GetCol("select email from user where FIND_IN_SET('admin',rights)>0;");			
			
			mail(implode(',',$mods), "[Geograph] Scoreboard approval required","Click the following link to review current list\n\nhttp://{$_SERVER['HTTP_HOST']}/games/approve.php","From: Geograph <mail@hlipp.de>", "-f mail@hlipp.de"); //FIXME from+env from
		}
		if (!empty($username)) {
			$updates['username'] = $username;
		}
		$updates['game_id'] = $this->game_id;
		if (!empty($this->l) && $this->l < 10) {
			$updates['level'] = $this->l;
		}
		$updates['score'] = $this->score;
		$updates['games'] = $this->games;
		$updates['ua'] = $_SERVER['HTTP_USER_AGENT'];
		$updates['session'] = session_id();
		
		$db->Execute('INSERT INTO game_score SET `ipaddr` = INET_ATON(\''.getRemoteIP().'\'),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
		
		return $app;
	}
	
	public function saveRate($rating) {
		global $USER;
		
		$updates = array();
		
		if (!empty($USER->user_id)) {
			$updates['user_id'] = $USER->user_id;
		}
		if (!empty($this->i)) {
			$updates['queries_id'] = $this->i;
		}
		if (!empty($this->image)) {
			$updates['gridimage_id'] = $this->image->gridimage_id;
		}
		$updates['game_id'] = $this->game_id;
		$updates['rating'] = $rating;
		
		
		$db = $this->_getDB();

		$db->Execute('INSERT INTO game_rate SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	
		if (rand(1,10) > 8) {
			$db->Execute("DROP TABLE IF EXISTS game_image_rate");
			$db->Execute("CREATE TABLE game_image_rate SELECT gridimage_id,game_id,round(avg(rating)) as rating,count(*) as ratings from game_rate where rating > 0 group by gridimage_id,game_id");
			$db->Execute("ALTER TABLE `game_image_rate` ADD PRIMARY KEY (`gridimage_id` , `game_id`)");
		}
	}

	public function issetImage($ref) {
		return isset($this->images[$ref]);
	}

	public function addImage($obj = null,$ref = '') {
		if (!is_object($obj)) {
			$obj = new GridImage($obj);
		}
		if (!empty($ref)) {
			$this->images[$ref] = $obj;
		} else {
			$this->images[] = $obj;
		}
		return $obj;
	}

	public function getImage($ref) {
		return $this->images[$ref];
	}

	public function unsetImage($ref) {
		if (isset($this->images[$ref])) {
			$d = $this->images[$ref];
			unset($this->images[$ref]);
			return $d;
		}
	}

	public function useImage($index,$setmap = false,$removeExactPosition = false) {
		$this->image = new GridImage($this->images[$index]->gridimage_id);
		
		if ($setmap) {
		//lets add an rastermap too
			$this->rastermap = new RasterMap($this->image->grid_square,2,!$removeExactPosition);
			
			if ($removeExactPosition) {
				$game->rastermap->nateastings = floor($game->rastermap->nateastings/1000) * 1000;
				$game->rastermap->natnorthings = floor($game->rastermap->natnorthings/1000) * 1000;
				$game->rastermap->exactPosition = false;
			}
		}
	}
	
	public function setSearchPage($i,$page) {
		
		if ($page =='x') {
			$db = $this->_getDB();
		
			$count = $db->getOne("select `count` from queries_count where id = ".intval($i));
			if ($count) {
				$this->engine = new SearchEngine($i);
				$this->engine->resultCount = $count;
				$pgsize = $this->engine->criteria->resultsperpage;
				
				if (!$pgsize) {$pgsize = 15;}
				$this->engine->numberOfPages = ceil($this->engine->resultCount/$pgsize);
				
				$page = rand(1,$this->engine->numberOfPages);
				
			} else {
				$page = 0;
			}
		} 
		$this->ipage = intval($page);
	}
	
	public function getImagesBySearch($i) {
		
		
		$pg = (!empty($this->ipage))?intval($this->ipage):1;
		if (empty($pg) || $pg < 1) {$pg = 1;}

		if (empty($this->engine)) {
			$this->engine = new SearchEngine($i);
		}
		$recordSet =& $this->engine->ReturnRecordset($pg,true);
		
		if ($recordSet)	{		
			$this->images=array();
			$index=0;

			while (!$recordSet->EOF) 
			{
				$this->images[$index]=new GridImage;
				$this->images[$index]->fastInit($recordSet->fields);
				$recordSet->MoveNext();
				$index++;
			}
			$recordSet->Close(); 
			$this->numberofimages = $index;
			$this->sanitiseImages(true);
		} else {
			$this->numberofimages = 0;
		}
	}

	public function getImagesByLevel($level,$reference_index = 0,$geoonly = true) {
		global $USER;
		$where = 1;
		$dist = $x = 0;
		if (empty($game->batchsize)) {
			$game->batchsize = 10;
		}
		switch($level) {
			case 1: $dist = 3;
			
			case 2: if (!$dist) $dist = 10;
				
			//case 1,2
				$square = new GridSquare();
				if ($square->setByFullGridRef($this->grid_reference)) {
					$x = $square->x;
					$y = $square->y;
					
				} 
				
			case 3: if (empty($x)) {
					$db = $this->_getDB();
					$rows = $db->CacheGetAll(3600,"SELECT x,y,COUNT(*) AS c FROM gridimage_search WHERE user_id='{$USER->user_id}' GROUP BY concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) ORDER BY COUNT(*) DESC LIMIT 20");
					if (count($rows) > 10 && $rows[0]['c'] > 10 && $rows[10]['c'] < max(5,$rows[0]['c']/10)) {
						$rows = array_slice($rows,0,10);
					}
					$pos = mt_rand(0,count($rows)-1);
					list($x,$y) = $rows[$pos];
				}

			case 4: if (empty($x)) {
					$db = $this->_getDB();
					$total = $db->CacheGetOne(3600,"select images from user_stat where user_id='{$USER->user_id}'");
					$pos = mt_rand(0,$total-1);
					list($x,$y) = $db->getRow("SELECT x,y FROM gridimage_search WHERE user_id='{$USER->user_id}' LIMIT $pos,1");
				}
			
			//case 3,4
				if (!$dist) $dist = 5;
			
			//case 1,2,3,4
				$left=$x-$dist;
				$right=$x+$dist;
				$top=$y+$dist;
				$bottom=$y-$dist;

				$rectangle = "'POLYGON(($left $bottom,$right $bottom,$right $top,$left $top,$left $bottom))'";

				$where = "CONTAINS(GeomFromText($rectangle),point_xy)";
				
				break;
		
			case 5: 
				$db = $this->_getDB();
				#$where .= sprintf(" and submitted like '____-%02d-%%'",(abs(crc32(session_id()))%12) + 1); 
				
				$maxId = $db->cacheGetOne(3600,"SELECT MAX(gridimage_id) AS max FROM gridimage_search");
				
				$ids = array();
				
				$needed = ($game->batchsize*3); //we need to allow for users images, ireland, rejected etc
				
				if ($maxId < $needed) {
					die("not enough images submitted");
				}
				
				mt_srand(abs(crc32(session_id()))*intval(time()/600));//give the sql a better chance of been cached
				while (count($ids) < $needed) {
					$id = mt_rand(1,$maxId);
					if (!in_array($id,$this->done)) {
						$ids[$id]=1;
					}
				}
				
				$where = "gridimage_id IN (".implode(',',array_keys($ids)).")"; 
				break;
		}
		
		if (!empty($reference_index)) {
			$where .= " and reference_index = $reference_index";
		}
		if ($USER->registered && !empty($USER->user_id)) {
			$where .= " and user_id!='{$USER->user_id}'";
		}
		if (!empty($geoonly)) {
			$where .= " and moderation_status = 'geograph'";
		}
		$sql = "select gi.*
			from gridimage_search gi
			where $where
			order by rand() limit ".($game->batchsize*2);
		
		$imagelist=new ImageList();
		$this->numberofimages =$imagelist->_getImagesBySql($sql,3600);
		$this->images =& $imagelist->images;
		
		$this->sanitiseImages(false);
	}
	
	
	public function getImagesByRating($rating) {
		if ($rating > 9) {
			$where = sprintf("rating BETWEEN %d AND %d",($rating/10)-1,($rating/10)+1);
		} else {
			$where = "rating = $rating";
		}
		if (empty($game->batchsize)) {
			$game->batchsize = 10;
		}
		$sql = "select gi.*
			from game_image_rate 
				inner join gridimage_search gi using(gridimage_id)
			where game_id = {$this->game_id} and $where
			order by rand() limit ".($game->batchsize*2);
		
		$imagelist=new ImageList();
		$this->numberofimages =$imagelist->_getImagesBySql($sql);
		$this->images =& $imagelist->images;
		
		$this->sanitiseImages(false);
	}
	
	public function sanitiseImages($issearch = false) {
		
		$ids = $this->done;
		
		if ($issearch) {
			$db = $this->_getDB();
			
			$ids += $db->getCol("select gridimage_id from game_rate where game_id = {$this->game_id} and gridimage_id > 0 and rating < 0");
			
		
			foreach ($this->images as $index => $image) {
				if (in_array($image->gridimage_id,$ids) || !($image->natgrlen == '8' && $image->use6fig=1) ) {
					unset($this->images[$index]);
					$this->numberofimages--;
				}
			}
		} else {
			foreach ($this->images as $index => $image) {
				if (in_array($image->gridimage_id,$ids)) {
					unset($this->images[$index]);
					$this->numberofimages--;
				}
			}
		}
		
	}
	
	
	/**
	* Return an opaque, url-safe token representing this game
	* @access public
	*/
	function getToken($expiry=0)
	{
		$token=new Token;
		$token->setValue("u", $this->game_id);
		if (!empty($this->image)) {
			$token->setValue("id", $this->image->gridimage_id);
		}
		if (!empty($this->numberofimages)) {
			$token->setValue("c", $this->numberofimages);
		}
		if (!empty($this->games)) {
			$token->setValue("g", $this->games);
		}
		if (!empty($this->score)) {
			$token->setValue("s", $this->score);
		}
		if (!empty($this->i)) {
			$token->setValue("i", $this->i);
		}
		if (!empty($this->ipage)) {
			$token->setValue("p", $this->ipage);
		}
		if (!empty($this->l)) {
			$token->setValue("l", $this->l);
		}
		if (!empty($this->grid_reference)) {
			$token->setValue("gr", $this->grid_reference);
		}
		if (!empty($this->rastermap) && $this->rastermap->enabled) {
			$token->setValueBinary("r", $this->rastermap->getToken());
			$token->setValue("e", $this->rastermap->exactPosition);
		}
		if (!empty($this->done)) {
			$token->setValueBinary("d", serialize($this->done));
		}
		return $token->getToken($expiry);
	}
	
	/**
	* Initialise class from a token
	* @access public
	*/
	function setToken($tokenstr)
	{
		$ok=false;
		$token=new Token;
			
		if ($token->parse($tokenstr))
		{
			if ($token->hasValue("u")) {
				$this->game_id = $token->getValue("u");
			}
			if ($token->hasValue("id")) {
				$this->image = new GridImage($token->getValue("id"));
			}
			if ($token->hasValue("c")) {
				$this->numberofimages = $token->getValue("c");
			}
			if ($token->hasValue("g")) {
				$this->games = $token->getValue("g");
			}
			if ($token->hasValue("s")) {
				$this->score = $token->getValue("s");
			}
			if ($token->hasValue("i")) {
				$this->i = $token->getValue("i");
			}
			if ($token->hasValue("p")) {
				$this->ipage = $token->getValue("p");
			}
			if ($token->hasValue("l")) {
				$this->l = $token->getValue("l");
			}
			if ($token->hasValue("gr")) {
				$this->grid_reference = $token->getValue("gr");
			}
			if ($token->hasValue("r")) {
				$square = false;				
				$this->rastermap = new RasterMap($square);
				$this->rastermap->setToken($token->getValueBinary("r"));
				$this->rastermap->exactPosition = $token->getValue("e");
			}
			if ($token->hasValue("d")) {
				$this->done = unserialize($token->getValueBinary("d"));
			}
			$ok=true;
		}
		return $ok;
	}
	
	
	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB()
	{
		if (!is_object($this->db))
			$this->db=NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed'); 
		return $this->db;
	}
}


?>
