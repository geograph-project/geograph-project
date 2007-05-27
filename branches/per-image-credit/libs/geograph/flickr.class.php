<?php

/**
 * A simple PHP class to access the Flickr API.
 *
 * @author  Dan Phiffer <dan@phiffer.org>
 * @version 0.1
 * @see     http://flickr.com/services/api/
 */

class Flickr {
    
    public $api_key;
    protected $email;
    protected $password;
    
    // Store the API key and login info if it's been provided
    public function __construct($api_key, $email = null, $password = null) {
        
        $this->api_key = $api_key;
        $this->login($email, $password);
    }
    
    // Store the login information for each subsequent API request
    public function login($email = null, $password = null) {
        $this->email = $email;
        $this->password = $password;
    }
    
    // Execute a Flickr API call and return the response as a SimpleXML object
    public function request($method, $params = null) {
        
        $request_url = 'http://www.flickr.com/services/rest/?';
        
        if (empty($params)) {
            $params = array();
        }
        
        $params['api_key'] = $this->api_key;
        $params['method'] = $method;
        
        if (!empty($this->email) && !empty($this->password)) {
            $params['email'] = $this->email;
            $params['password'] = $this->password;
        }
        
        // Iterate over each parameter and append it to the request URL
        foreach ($params as $key => $value) {
            $key = urlencode($key);
            $value = urlencode($value);
            $request_url .= "&$key=$value";
        }
        
        // Make the request and return the response
        $response = file_get_contents($request_url);
        return simplexml_load_string($response);
    }
}

?>
