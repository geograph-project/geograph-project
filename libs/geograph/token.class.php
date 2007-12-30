<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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
 
/**
* Provides a class for building tokens, opaque values which contain hash
* secured data. The idea is that tokens can be generated on the server side, and
* then passed onto the client side or third party systems for later presentation
* back our systems. At this point, we can validate that the token is valid and
* untampered.
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/




/**
* Token is a class for building tokens, opaque values which contain hash
* secured data. The idea is that tokens can be generated on the server side, and
* then passed onto the client side or third party systems for later presentation
* back our systems. At this point, we can validate that the token is valid and
* untampered.
*
* The class makes the values more opaque with a simple substition cipher
* so that a=43&thash=353470490e900b9c756a6706451ebb1f
* becomes WTZGR$0Wr0TGVGZM9Zh96h99HhdMVOWOM9OZVi6HHi3
*
* You might create a token as follows
*
* <code>
*    $token=new Token;
*    $token->setValue("x", $x);
*    $token->setValue("y", $y);
*    $token->setValue("uid", $user_id);
*    $tokenstr=$token->getToken();
* </code>
*
* When presented with this token, you might process it as follows
*
* <code>
*    $token=new Token;
*    if ($token->parse($tokenstr))
*    {
*        $x=$token->getValue("x");
*        $y=$token->getValue("y");
*        $user_id=$token->getValue("uid");
*    }
* </code>
*
* You can also make tokens time limited by supplying an expiry time in
* seconds when calling getToken();
*
* <code>
*    $token=new Token;
*    $token->setValue("p", $playlist_id);
*    $token->setValue("uid", $user_id);
*    $onehourtoken=$token->getToken(time()+3600);
* </code>
*
* @package System
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/
class Token
{
	/**
	* associative array of token data
	* @access private
	*/
	var $data=array();
	
	/**
	* hash secret used as part of md5 validation hash generation
	* this is initialised from the configuration array if available
	* @access private
	*/
	var $magic="dangermouse";
	
	
	/**
	* number of chars used from 32 character md5 hash. 
	* @access private
	*/
	var $hashlength=16;
	
	/**
	* Plain text part of caesar cipher 
	* @access private
	*/
	var $plain= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789&=_';
	
	/**
	* Cipher text part of caesar cipher 
	* @access private
	*/
	var $cipher='4wZa8tU5WHdRT$Gmyr0LDvFfq1KncQ9iC~xPBs263IpY7kEz@SgAXNlVOMbhjuJoe';
	
	
	/**
	* Filled with error messages during validation
	* @access public
	*/
	var $errors=array();
	
	/**
	* Constructor
	*/
	function Token()
	{
		global $CONF;
		if (isset($CONF['token_secret']))
		{
			$this->magic=$CONF['token_secret'];
		}
	}
	
	/** 
	* Obfuscate input with simple caesar cipher. This is more to make the value
	* more opaque than to provide any encryption, simply to ensure that code can
	* never use the value except through this class
	* @access private
	*/
	function _encipher($str) 
	{
		return strtr($str, $this->plain, $this->cipher);
	}

	/** 
	* Deciphers strings encoded with _encipher
	* @access private
	*/
	function _decipher($str) 
	{
		return strtr($str, $this->cipher, $this->plain);
	}

	/** 
	* This can be used during development to generate a substitution string
	* @access private
	*/
	function _generateCipher()
	{
		$plain=$this->plain;
		$this->cipher="";
		while (strlen($plain))
		{
			$p=rand(0, strlen($plain)-1);
			$ch=substr($plain, $p, 1);
			
			//obfuscate these further
			if ($ch=="&")
				$ch="$";
			if ($ch=="=")
				$ch="@";
			
			$this->cipher.=$ch;
			$plain=substr($plain, 0, $p).substr($plain, $p+1);
		}
		
		var_dump($this->cipher);
		exit;
	}
	
	
	/** 
	* Get named data value from a token, or null if not defined
	* @access public
	*/
	function getValue($name, $default=null)
	{
		return isset($this->data[$name])?$this->data[$name]:$default;
	}
	
	/** 
	* Get named data value from a token that was saved with setValueBinary, or null if not defined
	* @access public
	*/
	function getValueBinary($name, $default=null)
	{
		return base64_decode($this->getValue($name, $default));
	}
	
	/** 
	* Find out if a token value has been set
	* @access public
	*/
	function hasValue($name)
	{
		return isset($this->data[$name]);
	}
	
	/** 
	* Save this value in the token
	* WARNING: non-alphanumberic chars are not safe (eg $); use setValueBinary  
	* @access public
	*/
	function setValue($name, $value)
	{
		$this->data[$name]=$value;
	}
	
	/** 
	* Save binary value in the token
	* @access public
	*/
	function setValueBinary($name, $value)
	{
		$this->data[$name]=base64_encode($value);
	}
	
	
	
	
	/** 
	* Parse a token and return the result of the validate() method
	* @access public
	*/
	function parse($token)
	{
		//decode from base 64
		$decoded=$this->_decipher($token);
	#	var_dump( $decoded);
		//build member array of values
		$this->data=array();
		parse_str($decoded, $this->data); 
		
		return $this->validate();
	}
	
	/** 
	* Get data members and produce a token which encodes those
	* members along with an anti-tamper hash
	* @access public
	*/
	function getToken($expiry=0)
	{
		//add expiry time to data array if specified - this ensure
		//it gets included in the validation hash
		if ($expiry>0)
		{
			if ($expiry < 10000000) {
				$expiry += time();
			}
			$this->data['texp']=$expiry;
		}
		else
		{
			unset($this->data['texp']);
		}
		
		//sort the data array - this ensures we always
		//build the hash in a consistent way
		ksort ($this->data); 
		reset ($this->data); 
		
		$validation="";
		$encoded="";
		$sep="";
		while (list ($key, $val) = each ($this->data)) 
		{ 
		   	//build the query string
		   	if ($key!="thash")
		   	{
				$encoded.=$sep.$key.'='.urlencode($val);
				$sep="&";

				//build validation hash string
				$validation.=$key.$val;
		   	}
		} 

		//add the mystery magic
		$validation.=$this->magic;
		
		//append the hash to the query string
		$encoded.=$sep.'thash='.substr(md5($validation),0,$this->hashlength);
		
		//now just make it more opaque as a parameter
		return $this->_encipher($encoded);
	}
	
	/** 
	* Validates a token that has been loaded with parse - normally you
	* wouldn't need to call this, as parse will do it for you
	* @access public
	*/
	function validate()
	{
		//check there is a thash to check
		if (!isset($this->data['thash']))
		{
			$this->errors[]="Missing hash";
			return false;
		}
		
		//sort the data array - this ensures we always
		//build the hash in a consistent way
		ksort ($this->data); 
		reset ($this->data); 

		$validation="";
		while (list ($key, $val) = each ($this->data)) 
		{ 
			//build the query string
			if ($key!="thash")
			{
				//build validation hash string
				$validation.=$key.$val;
			}
		} 

		//add the mystery magic
		$validation.=$this->magic;

		//check hash is correct
		$ok= substr(md5($validation),0,$this->hashlength)==$this->data['thash'];
		if (!$ok)
		{
			$this->errors[]="Invalid hash";
			
		}
		
		//check token has not expired
		if ($ok && isset($this->data['texp']))
		{
			$ok=time() <= $this->data['texp'];
			
			if (!$ok)
			{
				$this->errors[]="Expired token";
			}
		
		}
		
		return $ok;

	}
	
}
?>
