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
		$updates['score'] = $points;
		
		$this->_getDB()->Execute('INSERT INTO game_image_score SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	}
	
	public function saveScore($where = 'user',$username = '') {
		global $USER;
		
		$updates = array();
		
		if (!empty($USER->user_id)) {
			$updates['user_id'] = $USER->user_id;
		}
		if (!empty($username)) {
			$updates['username'] = $username;
		}
		$updates['game_id'] = $this->game_id;
		$updates['score'] = $this->score;
		$updates['games'] = $this->games;
		
		$this->_getDB()->Execute('INSERT INTO game_score SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
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
		
		$this->_getDB()->Execute('INSERT INTO game_rate SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
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
		$game->ipage = intval($page);
	}
	
	public function getImagesBySearch($i) {
		
		
		$pg = (!empty($game->ipage))?intval($game->ipage):1;
		if (empty($pg) || $pg < 1) {$pg = 1;}

		if (empty($this->engine)) {
			$this->engine = new SearchEngine($i);
		}
		$recordSet = $this->engine->ReturnRecordset($pg);
		
		
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
	}

	public function getImagesByRating($rating) {
		if (rand(1,10) > 7) {
			$db = $this->_getDB();
			
			$db->Execute("DROP TABLE IF EXISTS game_image_rate");
			$db->Execute("CREATE TABLE game_image_rate SELECT gridimage_id,game_id,round(avg(rating)) as rating,count(*) as ratings from game_rate where rating > 0 group by gridimage_id,game_id");
			$db->Execute("ALTER TABLE `game_image_rate` ADD PRIMARY KEY (`gridimage_id` , `game_id`)");
		}
		
		$sql = "select gi.*
			from game_image_rate 
				inner join gridimage_search gi using(gridimage_id)
			where game_id = {$this->game_id} and rating = $rating
			order by rand()";
		
		$imagelist=new ImageList();
		$this->numberofimages =$imagelist->_getImagesBySql($sql);
		$this->images =& $imagelist->images;
		
		$this->sanitiseImages(false);
	}
	
	public function sanitiseImages($issearch = false) {
		
		$ids = $this->done;
		
		if ($issearch) {
			$db = $this->_getDB();
			
			$ids += $db->getCol("select gridimage_id from game_rate where game_id = {$this->game_id} and gridimage_id > 0 and rating = -2");
			
		} 
		
		foreach ($this->images as $index => $image) {
			if (in_array($image->gridimage_id,$ids) || !($image->natgrlen == '8' && $image->use6fig=1) ) {
				unset($this->images[$index]);
				$this->numberofimages--;
			}
		}
		
	}
	
	
	/**
	* Return an opaque, url-safe token representing this game
	* @access public
	*/
	function getToken()
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
		if (!empty($this->rastermap) && $this->rastermap->enabled) {
			$token->setValueBinary("r", $this->rastermap->getToken());
			$token->setValue("e", $this->rastermap->exactPosition);
		}
		if (!empty($this->done)) {
			$token->setValueBinary("d", serialize($this->done));
		}
		return $token->getToken();
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
			if ($token->hasValue("r")) {
				$square = false;				
				$this->rastermap = new RasterMap($square);
				$this->rastermap->setToken($token->getValueBinary("r"));
				$this->rastermap->exactPosition = $token->getValue("e");
			}
			if ($token->hasValue("d")) {
				$this->done = unserialize($token->getValueBinary("d"));
			}	
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