<?php

if (!empty($_GET['none'])) {
	header("HTTP/1.0 204 No Content");
	header("Cache-Control: max-age: 604800");
	header('Access-Control-Allow-Origin: *');
	exit;
}

?>

<script>
	//srouce: https://www.feitsui.com/en/article/3
	function cloudRequestCloudfrontEdge(callback) {
		var request=new XMLHttpRequest;
		request.onreadystatechange=function() {
			if(request.readyState===XMLHttpRequest.HEADERS_RECEIVED){
				var n=request.getResponseHeader("X-Amz-Cf-Pop");
				callback(n)
			}
		}
		request.open("HEAD","//t0.geograph.org.uk/tile/edge.php?none=1",!0);
		request.send(null);
	}

	function performDNSLookup(hostname, callback) {
		var req = new XMLHttpRequest();
		req.overrideMimeType("application/json");
		req.open('GET', "https://dns.google/resolve?name="+encodeURIComponent(hostname)+"&type=A", true);
		req.onload  = function() {
		   var jsonResponse = JSON.parse(req.responseText);
		   // do something with jsonResponse
			if (jsonResponse && jsonResponse.Answer && jsonResponse.Answer.length) {
				var results = new Array();
				for(var q=0;q<jsonResponse.Answer.length;q++)
					results.push(jsonResponse.Answer[q].data);
				callback(results);
			}
		};
		req.send(null);
	};

function onload() {
	cloudRequestCloudfrontEdge(function(result) {
		document.getElementById('result').innerHTML = result;
	});
	performDNSLookup(window.location.host, function(result) {
		document.getElementById('ips').innerHTML = result.join(', ');
	});
}
</script>
<body onload=onload()>
	<div id="result">Loading, please wait...</div>
	<div id="ips"></div>
</body>
