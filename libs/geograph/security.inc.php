<?php
/**
 * $Project: GeoGraph $
 * $Id: security.inc.php 8848 2018-09-18 17:13:40Z barry $
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
* Provides routines useful in preventing security issues
*
* Many of these routines are for checking data before it is placed in
* the database or transmitted back to a user, in order to prevent
* cross site scripting and similar attacks
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision: 8848 $
*/


function validateGooglebot() {
	if (empty($_SERVER['HTTP_USER_AGENT']))
		return;

	if (stripos($_SERVER['HTTP_USER_AGENT'],'Googlebot/') !== FALSE) {
		$ip = getRemoteIP();
		$host = gethostbyaddr($ip);
		//if dns fails, get $host=$ip - ignore that for now!
		if (!preg_match('/\.google(bot)?\.com$/i', $host)) { //sometimes, it might be a legitmate google proxy
			header("HTTP/1.0 403 Forbidden");
                	exit;
		}
		//we also need to do a forward check! As the PTR record COULD be spooffed!
		$ip2 = gethostbyname($host);
		if ($ip != $ip2) {
			header('HTTP/1.0 451 Unavailable For Legal Reasons');
                	exit;
		}
	}
}

/**
 * Detects if a request is coming from known Internet Archive netblocks.
 * Supports IPv4 /20 and IPv6 /48 ranges.
 */
function is_internet_archive($ip = null) {
    if (!$ip) {
        $ip = getRemoteIP();
    }

    // 1. Handle IPv4 (207.241.224.0/20)
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ip_long = ip2long($ip);
        $range_min = ip2long('207.241.224.0');
        $range_max = ip2long('207.241.239.255');
        return ($ip_long >= $range_min && $ip_long <= $range_max);
    }

    // 2. Handle IPv6 (2620:0:9c0::/48)
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $addr_bin = inet_pton($ip);
        $prefix_bin = inet_pton('2620:0:9c0::');
        // Check if both exist and compare the first 48 bits (6 bytes)
        if ($addr_bin !== false && $prefix_bin !== false) {
            return (substr($addr_bin, 0, 6) === substr($prefix_bin, 0, 6));
        }
    }
    return false;

/*
    // Check for the IA IPv6 Prefix (2620:0:9c0)
    if (str_contains($ip, ':')) {
        // Expand and check first 14 characters (approx 2620:0:9c0:)
        return (str_starts_with($ip, '2620:0:9c0:'));
    }
    // Check for the IA IPv4 Prefix (207.241.224.0/20)
    if (str_starts_with($ip, '207.241.')) {
        $octet3 = (int)explode('.', $ip)[2];
        return ($octet3 >= 224 && $octet3 <= 239);
    }
    return false;
*/
}

function rate_limiting($slug, $per_minute = 5, $enforce = false) {
	global $USER, $memcache;

	if (empty($memcache) || empty($memcache->redis)) //for now only do if have redis, although we could possibly do this with memcache directly
		return;

	if (!empty($_GET['size']) && $_GET['size'] == 'largest')
		$enforce = false; //for now allow wikimedia to do their bulk transfer

	if (strpos($_SERVER['HTTP_USER_AGENT'],'MediaWiki') === 0)
		$enforce = false; //and allow wikipedia 'one off' transfers

	if (!empty($USER) && $USER->user_id)
		$per_minute *= 2;

	if (preg_match('/Chrome\/[67]/',$_SERVER['HTTP_USER_AGENT'])) {
		//we starting to get a lot of requests, were the 'minor' version appears randomized.
		//2		Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.2123.122 Safari/537.36
		//2		Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.2124.18 Safari/537.36
		//2		Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.2130.125 Safari/537.36
		//2		Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.2130.64 Safari/537.36
		//2		Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.2135.105 Safari/537.36
		$ua = preg_replace('/\/(\d+)\.[\d\.]+/',"/$1.x",$_SERVER['HTTP_USER_AGENT']);
	} else {
		$ua = $_SERVER['HTTP_USER_AGENT'];
	}

	//todo, this should probably use IP! and/or session/user_id
	$mkey = 'rate:'.$memcache->prefix.md5($slug.'.'.getRemoteIP().'@'.$ua).':'.date('i');

	$counter = $memcache->redis->incr($mkey);
	$memcache->redis->expire($mkey, 59); //always need to expire!

	if ($counter > $per_minute) {
		//todo
		//header(439 too many requests)
		//if (user_id|session) show_capacha?


		//for now just log it!
		global $db;

		if (empty($db) || !empty($db->readonly))
			$db = GeographDatabaseConnection(false);

		$ins = "INSERT INTO rate_limiting_log SET
		mkey = ".$db->Quote($mkey).",
	        slug = ".$db->Quote($slug).",
	        counter = ".intval($counter).",
	        php_self = ".$db->Quote($_SERVER['REQUEST_URI']).",
		request_time = ".intval($_SERVER['REQUEST_TIME']).",
	        ipaddr = INET6_ATON('".getRemoteIP()."'),
	        referer = ".$db->Quote(@$_SERVER['HTTP_REFERER']).",
	        useragent = ".$db->Quote($_SERVER['HTTP_USER_AGENT']).",
	        session = ".$db->Quote(session_id())."

		ON DUPLICATE KEY UPDATE counter = ".intval($counter); //so we only keep one log line per minute!
		//todo, add HTTP_REFERER (if external) to UPDATE, so we can record an external referer, even if wasnt on the first hit!

		$db->Execute($ins);

		if ($enforce) {
			header("HTTP/1.0 429 Too Many Requests");
			print "Too Many Requests. Please slow down.";
			exit;
		}
	}
	return $counter;
}




function inEmptyRequestInt($key,$def = 0) {
	return (!empty($_REQUEST[$key]))?intval($_REQUEST[$key]):$def;
}

function inSetRequestInt($key,$def = 0) {
	return (isset($_REQUEST[$key]))?intval($_REQUEST[$key]):$def;
}


/**
* basic email address check
*/
function isValidEmailAddress($email) 
{
	return preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._\-\+])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/' , $email)?true:false; 
}

/**
* basic name check
*/
function isValidRealName($name) 
{
	return preg_match('/^[a-zA-Z0-9\-\s\']+$/' , $name)?true:false; 
}

/**
* web url check
*/
function isValidURL($url) 
{
	return preg_match('{^http(s?)\:\/\/[a-zA-Z0-9\-\._]+(\.[a-zA-Z0-9\-\._]+){1,}(\/?)([a-zA-Z0-9\~\-\.\?\,=\'\/\\\+&%\$#_]*)?$}' , $url)?true:false; 
}

/**
 * Heuristic spam check intended for email message checking
 *
 */
function isSpam($msg)
{
	//some spam features url and entity encoding to hide
	//the real content from filters. Bugger off!
	$msg=html_entity_decode(urldecode($msg));
	$msg=strtolower($msg);

	//no legitimate use for html or bbedit tags
	if (strstr($msg, '[url')!==false)
		return true;
	if (strstr($msg, 'href=')!==false)
		return true;

	//how many times does http appear?
	$matches=array();
	preg_match_all("{http}", $msg, $matches);
	$count=count($matches[0]);

	preg_match_all("{https?://www.geograph.org.uk}", $msg, $matches);
	$legit=count($matches[0]);

	//we'll let you off for using geograph links...
	$count-=$legit;

	if ($count>3)
		return true;

	return false;
}

/**
 * Return IP address of user
 * 
 */
function getRemoteIP()
{
	//get IP address of user
	if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']))
	{
		//for now trust this, in case X-Forwarded-For was not maintained by intermediate proxies
		$ip=$_SERVER['HTTP_CF_CONNECTING_IP'];
	}
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		$ips=explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$ip=trim($ips[0]);
	}
	else
	{
		$ip=$_SERVER['REMOTE_ADDR'];
	}
//    [HTTP_X_FORWARDED_FOR] => 2a05:d018:fcc:be01:35e0:4306:928a:97ca

	if (!preg_match('/^[a-f\d]+([\.:][a-f\d]*)+$/i',$ip))
	 //we often use getRemoteIP to insert directly into database. because from HTTP_X_FORWARDED_FOR there is a chance is spoofed, and vulnerable to SQL injection (although should be ok if REALLY behind cache, as OUR proxy will set it safely.
		return 0;
	return $ip;
}


/**
 * 
 * 
 */
function isLocalIPAddress()
{
	global $CONF;
	
	if ($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR'])
	{
		//is from self
		return true;
	} 
	elseif (!empty($CONF['server_ip']) && strpos($_SERVER['REMOTE_ADDR'],$CONF['server_ip']) === 0 && strpos(getRemoteIP(),$CONF['server_ip']) === 0) 
	{
		//its our server calling direct/our gateway is forwarding for our server
		return true;
	} 
	return false;
}

function appearsToBePerson() {
	global $CONF;
	if (empty($_SERVER['HTTP_USER_AGENT']))
		return false;
	if (!empty($_SERVER['HTTP_SEC_PURPOSE']) && strpos($_SERVER['HTTP_SEC_PURPOSE'],'prefetch') !==FALSE) //new chrome prefetch proxy
		return false;
	if (!empty($_SERVER['HTTP_X_PURPOSE']) || !empty($_SERVER['HTTP_PURPOSE']) || !empty($_SERVER['HTTP_X_MOZ']))  //'prefetch' and 'preview' requests
		return false;

	//if we have directed them to our archive template already know non-human.
	if ($CONF['template'] == 'archive')
		return false;

	//list of agents from NLWeb
	$agents = array(
	    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
	    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
	    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/121.0.0.0 Safari/537.36',
	    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
	);
	if (in_array($_SERVER['HTTP_USER_AGENT'], $agents))
		return false;

	//check for common bots, and/or likly to be non-human
	$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
		//note, catches Google-Read-Aloud as includes a URL (with http)
	$forbidden = [
		'http', 'bot', 'mediapartners', 'preview', 'magnus', 'curl',
		'oembed', 'go-http-client', 'java/', 'python', 'lwp::simple',
		'siege', 'httrack', 'cyotekwebcopy', 'headlesschrome',
		'inspectiontool', 'the knowledge ai', 'googleother', 'wordpress'
	];
	foreach ($forbidden as $term) {
		if (strpos($ua, $term) !== false) {
			return false;
		}
	}

	return true;
}


/**
 * Lightweight gatekeeper to validate if an incoming search query contains 
 * any indexed terms before committing to heavy application logic.
 * * Primarily used to short-circuit automated dictionary attacks, scrapers, 
 * or out-of-bounds character sets (e.g., Japanese/Cyrillic bots hitting a 
 * localized regional database) by querying the lightweight Manticore/Sphinx index dictionary.
 *
 * @param string $input The raw search query string to validate.
 * @return bool True if at least one token exists within the search index dataset; false otherwise.
 */
function hasValidTokens($input) {
    global $sph;

    $input = trim($input);
    if ($input === '') {
        return false;
    }

    // Lazy-load the SphinxQL connection if it hasn't been initialized yet
    if (empty($sph)) {
        $sph = GeographSphinxConnection('sphinxql', true);
    }

    // Tokenize the raw UTF-8 string against the index configuration.
    // Flag '1' forces the engine to return token statistics ('docs' and 'hits').
    $quoted_text = $sph->Quote($input);
    $keywords = $sph->getAll("CALL KEYWORDS({$quoted_text}, 'sample8', 1)");

    if (!empty($keywords)) {
        foreach ($keywords as $token) {
            // If even a single token has a physical footprint in our index, consider it valid
            if (isset($token['docs']) && $token['docs'] > 0) {
                return true;
            }
        }
    }

    return false;
}
