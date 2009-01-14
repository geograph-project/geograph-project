<?php

/** 
 * @author  Michael Malone 
 * @url     http://immike.net 
 */ 

/** 
 * SpellChecker provies spell checking and correction functionality by making 
 * a remote procedure call to the spell checking web service that Google uses 
 * for Google Toolbar at https://www.google.com/tbproxy/spell 
 *  
 * @package SpellChecker 
 */ 
class SpellChecker 
{ 
  private static $instance; 

  private function __construct( ) {} 

  public static function getInstance()  
  { 
    if(is_null(self::$instance)) 
      { self::$instance = new self(); } 
    return self::$instance; 
  } 

  /** 
   * Determines whether any words are misspelled 
   *  
   * @param string query The query to check for spelling mistakes 
   * @param string lang  Language 
   * @param string hl    Human interface language 
   *  
   * @return bool Whether the query is spelled correctly. 
   */ 
  public static function Check( $query, $lang='en', $hl='en' ) 
  { 
    return( strcasecmp($query, self::Correct($query, $lang, $hl)) === 0 ); 
  } 

  /** 
   * Get Google's suggested spelling for a query. 
   * 
   * @param string query The query to check for spelling mistakes 
   * @param string lang  Language 
   * @param string hl    Human interface language 
   *  
   * @return string Google's suggested spelling for the query. 
   */ 
  public static function Correct( $query, $lang='en', $hl='en' ) 
  { 
    $result = $query; 

    $xml = new SimpleXMLElement(self::GetSuggestions( $query, $lang, $hl )); 

    $replacement = array(); 
    foreach($xml->c as $correction) 
    { 
      $suggestions = explode("\t", (string)$correction); 
      $offset = $correction['o']; 
      $length = $correction['l']; 

      $replacement[mb_substr($result, $offset, $length)] = $suggestions[0]; 
    } 

    foreach($replacement as $old => $new) 
    { 
      $old = preg_quote($old); 
      $result = preg_replace("/$old/is", $new, $result, 1); 
    } 

    return $result; 
  } 

  /** 
   * Get the XML response object containing spelling suggestions for a query. 
   * 
   * @param string query The query to check for spelling mistakes 
   * @param string lang  Language 
   * @param string hl    Human interface language 
   * 
   * @param string Google's XML response containing suggested alternative spellings for a query. 
   */ 
  public static function GetSuggestions( $query, $lang='en', $hl='en' ) 
  { 
	global $memcache;
	$mkey = md5($query).$lang.$hl;
	//fails quickly if not using memcached!
	$data =& $memcache->name_get('sp',$mkey);
	if ($data)
		return $data;

    $post = '<spellrequest textalreadyclipped="0" ignoredups="1" ignoredigits="1" ignoreallcaps="0"><text>'.htmlspecialchars($query).'</text></spellrequest>'; 

    $server = "www.google.com"; 
    $path = "/tbproxy/spell?lang=$lang&hl=$hl"; 
     
    $url = "https://www.google.com"; 
     
    $header  = "POST ".$path." HTTP/1.0 \r\n"; 
    $header .= "MIME-Version: 1.0 \r\n"; 
    $header .= "Content-type: application/PTI26 \r\n"; 
    $header .= "Content-length: ".strlen($post)." \r\n"; 
    $header .= "Content-transfer-encoding: text \r\n"; 
    $header .= "Request-number: 1 \r\n"; 
    $header .= "Document-type: Request \r\n"; 
    $header .= "Interface-Version: Test 1.4 \r\n"; 
    $header .= "Connection: close \r\n\r\n"; 
    $header .= $post; 
       
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL,$url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 4); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $header); 

    $data = curl_exec($ch); 
    if (curl_errno($ch)) { 
        throw new Exception( curl_error($ch) ); 
    } else { 
        curl_close($ch); 
    } 

    $xml_parser = xml_parser_create(); 
    xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0); 
    xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1); 
    xml_parse_into_struct($xml_parser, $data, $vals, $index); 
    xml_parser_free($xml_parser); 

	//fails quickly if not using memcached!
	$memcache->name_set('sp',$mkey,$data,$memcache->compress,$memcache->period_long);

    // Returns the same data you'd get form Google toolbar 
    return $data; 
  } 
} 

?>