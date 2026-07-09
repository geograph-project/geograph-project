<?php

require_once('geograph/global.inc.php');

if (!empty($_GET['url'])) {
        if (!preg_match('/^https?:\/\/(m|www|schools)\.geograph\.(org\.uk|ie)\.?\/.+/', $_GET['url'])) {
                die();
        }

        // Setup the TinyURL API request
        $apiUrl = 'https://api.tinyurl.com/create';
        $authToken = $CONF['tinyurl_key'];

        $postData = json_encode([
                'url' => $_GET['url'],
                'domain' => 'tinyurl.com' // You can change this to 'tiny.one' or your custom branded domain
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $authToken,
                'Content-Type: application/json',
                'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Process response if HTTP status is 200 OK
        if ($httpCode === 200 && $response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['data']['tiny_url'])) {
                        $res = array('ok' => true, 'tinyurl' => $responseData['data']['tiny_url']);
                } else {
                        $res = array('ok' => false, 'error' => 'unexpected API response payload');
                }
        } else {
                // Read error payload from TinyURL if available, otherwise generic
                $errorData = json_decode($response, true);
                $errorMsg = isset($errorData['errors'][0]) ? $errorData['errors'][0] : 'API request failed';
                $res = array('ok' => false, 'error' => $errorMsg . " (HTTP $httpCode)");
        }

} else {
        $res = array('ok' => false, 'error' => 'unknown url');
}

outputJSON($res);

