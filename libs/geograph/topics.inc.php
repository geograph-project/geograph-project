<?

function termExtraction($context,$query = '') {
        global $yahoo_appid;

        // The POST URL and parameters
        $request =  'http://search.yahooapis.com/ContentAnalysisService/V1/termExtraction';

        $postargs = 'output=php&appid='.$yahoo_appid.'&context='.urlencode($context).'&query='.urlencode($query);

        // Get the curl session object
        $session = curl_init($request);

        // Set the POST options.
        curl_setopt ($session, CURLOPT_POST, true);
        curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
        curl_setopt($session, CURLOPT_HEADER, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_USERAGENT, 'Geograph Britain and Ireland - Tagging Interface (+http://www.geograph.org.uk)');

        // Do the POST and then close the session
        $response = curl_exec($session);
        curl_close($session);

        // Get HTTP Status code from the response
        $status_code = array();
        preg_match('/HTTP\/1.\d (\d\d\d)/s', $response, $status_code);

        // Check for errors
        switch( $status_code[1] ) {
                case 100:
                case 200:
                        // Success
                        break;
                case 503:
                case 403:
                case 400:
                default:
                        return 0; //
                        die('Your call to Yahoo Web Services returned an unexpected HTTP status of:' . $status_code[1]."\n");
        }
        $response = strstr($response, 'a:');

        return unserialize($response);
}


