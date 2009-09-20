<?php
/**
 * $Project: GeoGraph $
 * $Id: token.class.php 3183 2007-03-20 21:50:37Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005  Barry Hunter (geo@barryhunter.co.uk)
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
 
 
function save_throttle_tokens() {
	foreach ($GLOBALS['ThrottleTokens'] as $token) {
		$token->save();
	}
}

class ThrottleToken
{
	/**
	* the token for this object
	* @access public
	*/
	var $token='';
	
	/**
	* number of times this token can be used.
	* @access public
	*/
	var $uses=10;

	/**
	* number of times this token has been used.
	* needs to be stored staticly
	* @access public
	*/
	var $used=10;
	
	var $changed=0;
	
	/**
	* hash secret used as part of md5 validation hash generation
	* this is initialised from the configuration array if available
	* @access private
	*/
	var $magic="dangermouse";
	
	/**
	* Constructor
	*/
	function ThrottleToken($token = '',$autosave = true)
	{
		global $CONF;
		if (isset($CONF['token_secret']))
		{
			$this->magic=$CONF['token_secret'];
		}
		
		
		if (empty($token)) {
			$this->_generate();
			$this->changed++;
		} else {
			$this->token = $token;
			$this->load();
		}
		
		if ($autosave) {
			if (!isset($GLOBALS['ThrottleTokens'])) {
				$GLOBALS['ThrottleTokens'] = array();
				register_shutdown_function('save_throttle_tokens');
			}
			$GLOBALS['ThrottleTokens'][] = $this;
		}
	}
	
	function useCredit()
	{
		if ($this->used < $this->uses) {
			$this->used++;	
			$this->changed++;
			return true;
		} else {
			return false;
		}
	}
	
	function load() {
		global $memcache;
		if ($memcache->valid) {
			$t2 = $memcache->name_get('tt',$this->token);
			if ($t2 == '') {
				//todo now what?
			} else {
				$vars =& get_object_vars($t2);
				foreach ($vars as $k => $v) {
					$this->{$k} = $v;
				}
			}
		} else {
			//todo save in database?
		}
	}
	
	function save() {
		global $memcache;
		if ($this->changed) {
			if ($memcache->valid) {
				$memcache->name_set('tt',$this->token,$this,$memcache->compress,$memcache->period_short);
			} else {
				//todo save in database?
			}
		}
	}
	
	/** 
	* 
	* @access private
	*/
	function _generate()
	{
		$token = md5(uniqid().time().$this->magic);
		$this->token = $token;
	}
	
	/**
	 * remove the stored db object ready to serialize
	 * @access private
	 */
	function __sleep() {
		if (is_object($this->db)) {
			#$this->db->Close();
			unset($this->db);
		}
		
		$vars =& get_object_vars($this);
		return array_keys($vars);
	}	
}
?>
