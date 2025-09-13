<?php

//https://gemini.google.com/app/c2f2eb6bf37784c0

function queryS3Vectors(
    array $queryPayload,
    string $awsRegion = 'us-east-1',
    $verbose = false
): array {

    $canonicalUri = '/QueryVectors';
    $amzTarget = "S3Vectors.QueryVectors"; // The X-Amz-Target header for this API - probbaly ooptional!

    list($httpCode, $response) = s3vectorRequest($canonicalUri, $amzTarget, $queryPayload, $awsRegion, $verbose);

    $vectorsData = [];
    if ($httpCode >= 200 && $httpCode < 300) {
        $responseData = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $vectorsData = $responseData['vectors'] ?? [];
        } else {
            echo "Failed to parse JSON response.\n";
        }
    } else {
        echo "API call failed with status code " . $httpCode . ".\n";
    }

    return [
        'http_code' => $httpCode,
        'vectors' => $vectorsData
    ];
}

/**
 * Queries the S3Vectors service with a given payload.
 *
 * @param string $canonicalUri The canonical URI (e.g., '/QueryVectors').
 * @param string $amzTarget The X-Amz-Target header value (e.g., 'S3Vectors.QueryVectors').
 * @param array $queryPayload The associative array for the request body.
 * @throws Exception If cURL fails or JSON encoding/decoding errors occur.
 */
function s3vectorRequest($canonicalUri, $amzTarget, $queryPayload, $awsRegion = 'us-east-1', $verbose = false) {

    //string $awsAccessKeyId,
    //string $awsSecretAccessKey,
    //?string $awsSecurityToken,
	//just loads the authentication from S3 class - so assumes already loaded
        $awsAccessKeyId = S3::$__accessKey;
	$awsSecretAccessKey = S3::$__secretKey;
	$awsSecurityToken = S3::$securityToken;

    $service = 's3vectors';
    $requestBody = json_encode($queryPayload);

    if ($requestBody === false) {
        throw new Exception("Failed to JSON encode request body: " . json_last_error_msg());
    }

    $hashedRequestBody = hash('sha256', $requestBody);

    $headers = [
        'Host' => "{$service}.{$awsRegion}.api.aws",
        'Content-Type' => 'application/json',
    ];

    $endpoint = "https://{$service}.{$awsRegion}.api.aws" . $canonicalUri;

    $signedHeaders = signAwsV4(
        $awsAccessKeyId,
        $awsSecretAccessKey,
        $awsRegion,
        $service,
        'POST', // Method is always POST for QueryVectors
        $canonicalUri,
        '', // Canonical query string is empty for POST body
        $headers,
        $requestBody,
        $amzTarget,
        $hashedRequestBody,
        $awsSecurityToken
    );

    $curlHeaders = [];
    foreach ($signedHeaders as $key => $value) {
        $curlHeaders[] = "{$key}: {$value}";
    }

    if ($verbose) {
	    echo "Sending request to: {$endpoint}\n";
	    echo "Request Body: " . $requestBody . "\n";
	    echo "Headers:\n";
	    foreach ($curlHeaders as $header) {
        	echo "  " . $header . "\n";
	    }
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    if ($verbose)
        curl_setopt($ch, CURLOPT_VERBOSE, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        throw new Exception("cURL Error: " . $curlError);
    }

    if ($verbose) {
        echo "\n--- Response ---\n";
        echo "HTTP Status Code: " . $httpCode . "\n";
        echo "Response Body: " . $response . "\n";
    }

    return array($httpCode, $response);
}


/**
 * Implements AWS Signature Version 4 for signing HTTP requests.
 *
 * @param string $accessKeyId Your AWS Access Key ID.
 * @param string $secretAccessKey Your AWS Secret Access Key.
 * @param string $region The AWS region (e.g., 'us-east-1').
 * @param string $service The AWS service name (e.g., 's3vectors').
 * @param string $method The HTTP method (e.g., 'POST').
 * @param string $canonicalUri The canonical URI (e.g., '/').
 * @param string $canonicalQueryString The canonical query string (e.g., '').
 * @param array $headers Associative array of headers (e.g., ['Host' => '...', 'Content-Type' => '...']).
 * @param string $requestBody The raw request body.
 * @param string $amzTarget The X-Amz-Target header value.
 * @param string $hashedRequestBodySha256 The SHA256 hash of the request body.
 * @param string|null $securityToken Optional AWS Security Token for STS credentials.
 * @return array An array containing the Authorization header and X-Amz-Date header.
 * @throws Exception If signing fails.
 */
function signAwsV4(
    string $accessKeyId,
    string $secretAccessKey,
    string $region,
    string $service,
    string $method,
    string $canonicalUri,
    string $canonicalQueryString,
    array $headers,
    string $requestBody,
    string $amzTarget,
    string $hashedRequestBodySha256,
    ?string $securityToken = null
): array {
    $algorithm = 'AWS4-HMAC-SHA256';
    $amzDate = gmdate('Ymd\THis\Z');
    $date = substr($amzDate, 0, 8);

    $headers['x-amz-date'] = $amzDate;
    $headers['x-amz-target'] = $amzTarget;
    $headers['x-amz-content-sha256'] = $hashedRequestBodySha256;
    if ($securityToken) {
        $headers['x-amz-security-token'] = $securityToken;
    }

    ksort($headers);

    $canonicalHeaders = '';
    $signedHeaders = [];
    foreach ($headers as $key => $value) {
        $lowerKey = strtolower($key);
        $canonicalHeaders .= $lowerKey . ':' . trim($value) . "\n";
        $signedHeaders[] = $lowerKey;
    }
    $signedHeadersString = implode(';', $signedHeaders);

    $canonicalRequest = implode("\n", [
        $method,
        $canonicalUri,
        $canonicalQueryString,
        $canonicalHeaders,
        $signedHeadersString,
        $hashedRequestBodySha256,
    ]);

    $credentialScope = implode('/', [$date, $region, $service, 'aws4_request']);
    $stringToSign = implode("\n", [
        $algorithm,
        $amzDate,
        $credentialScope,
        hash('sha256', $canonicalRequest),
    ]);

    $kSecret = 'AWS4' . $secretAccessKey;
    $kDate = hash_hmac('sha256', $date, $kSecret, true);
    $kRegion = hash_hmac('sha256', $region, $kDate, true);
    $kService = hash_hmac('sha256', $service, $kRegion, true);
    $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

    $signature = hash_hmac('sha256', $stringToSign, $kSigning);

    $authorizationHeader = $algorithm . ' ' .
                           'Credential=' . $accessKeyId . '/' . $credentialScope . ', ' .
                           'SignedHeaders=' . $signedHeadersString . ', ' .
                           'Signature=' . $signature;

    $finalHeaders = [
        'Authorization' => $authorizationHeader,
        'X-Amz-Date' => $amzDate,
        'X-Amz-Target' => $amzTarget,
        'Content-Type' => $headers['Content-Type'],
        'Host' => $headers['Host'],
        'X-Amz-Content-Sha256' => $hashedRequestBodySha256
    ];
    if ($securityToken) {
        $finalHeaders['X-Amz-Security-Token'] = $securityToken;
    }
    return $finalHeaders;
}

