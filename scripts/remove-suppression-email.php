<?

//these are the arguments we expect
$param=array(
	'email'=>'',
);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

if (empty($param['email']))
	die("specify email with --email=..\n");

############################################

	$filesystem = new FileSystem(); //extends 3rdparty/S3.php!


$host = str_replace('-smtp','',$CONF['smtp_host']);

$method = "DELETE";
//host is prepended after creating signature!
$uri = "/v2/email/suppression/addresses/".urlencode($param['email']);

$amzHeaders = array(
	"X-Amz-Security-Token" => S3::$securityToken,
);

$otherHeaders = array(
	"Host" => $host,
	'Date' => gmdate('D, d M Y H:i:s T'),
);

############################################

print "$uri\n";



                                        $newHeaders = $filesystem->__getSignatureV4(
                                                $amzHeaders, //$this->amzHeaders,
                                                $otherHeaders, //$this->headers
                                                $method, //$this->verb,
                                                str_replace('%','%25',$uri), //$this->uri,
							//Each path segment must be URI-encoded twice (except for Amazon S3 which only gets URI-encoded once).


                                                '', //$this->data
						'ses'
                                        );
					//still need to add the amz ones to the headers
                                        foreach ($amzHeaders as $k => $v) {
                                                $headers[] = $k .': '. $v;
                                        }
                                        foreach ($otherHeaders as $k => $v) {
                                                $headers[] = $k .': '. $v;
                                        }
                                        foreach ($newHeaders as $k => $v) {
                                                $headers[] = $k .': '. $v;
                                        }

$url = "https://$host$uri";

    $ch = curl_init();

//curl_setopt($ch, CURLOPT_VERBOSE, true);

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

############################################

print "code = $httpCode\n";
print "$result\n\n";

if ($httpCode != 200) exit;

############################################

$db = GeographDatabaseConnection(false);

$set = "block_cleared = 1";
$where = array();
$where[] = "Type = 'Notification'";
$where[] = "block_cleared = 0";
$where[] = "JSON_VALUE(Message,'$.mail.destination[0]') = ".$db->Quote($param['email']);


$sql = "UPDATE sns_message SET $set WHERE ".implode(' AND ',$where);

print "$sql;\n\n";

