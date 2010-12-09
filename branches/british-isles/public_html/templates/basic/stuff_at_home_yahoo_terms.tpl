{assign var="page_title" value="Geograph-At-Home"}
{include file="_std_begin.tpl"}
{literal}<style type="text/css">
#maincontent li { padding-bottom:10px;}
</style>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" ></script>
<script>

var results = {};
var c = 0;
var workerToken = 'aaa';
var jobId = 0;
var rawData;
var images = 0;

function startWork() {
	document.getElementById('message').innerHTML = 'Fetching Job...';
	$.ajax({
		type: "GET",
		url: "at-home.php?getJob&task=yahoo_terms&worker="+workerToken+"&output=json",
		dataType: 'json',
		success: function(data) {
			if (data.error) {
				alert(data.error);
			} else {
				jobId = data.jobId;
			
				document.getElementById('message').innerHTML = 'Downloading Job Data... (may take a while)';
				$.ajax({
					type: "GET",
					url: "at-home.php?downloadJobData="+jobId+"&task=yahoo_terms&worker="+workerToken+"&output=json",
					dataType: 'json',
					success: function(data) {
						if (data.error) {
							alert(data.error);
						} else if (data.length && data.length > 0) {
							rawData = data;
							images = rawData.length;
							performLookup();
						} else if (data.length && data.length == 0) {
							alert("Job Finished!");
							markJobFinished();
						} else {
							alert("No Data Downloaded. Job probably finished.");
							markJobFinished();
						}
					}
				});
			}
		}
	});
}

function performLookup() {
	if (rawData.length > 0) {
		data = rawData.shift();
		
		termExtraction(data.i,data.d,data.c);
		
		document.forms['progressForm'].style.display='block';
		document.forms['progressForm'].elements['comment'].value = data.d;
	}
	
	if (rawData.length > 0) {
		setTimeout("performLookup()",document.forms['optionsFrom'].elements['speed'][0].checked?10000:250);
	} else {
		finishUp();
	}
}

function finishUp() {
	if (results.length > 0) {
		submitToGeograph(); // calls markJobFinished();
	} else {
		markJobFinished();
	}
}

function markJobFinished() {
	document.getElementById('message').innerHTML = 'Marking Job as finished...';
	$.ajax({
		type: "GET",
		url: "at-home.php?finalizeJob="+jobId+"&task=yahoo_terms&worker="+workerToken,
		dataType: 'html',
		success: function(data) {
			alert(data+"\n\n You can now close this window. Please reopen it tomorrow if you can.");
		}
	});
}

function termExtraction(id,context,query) {
	var data = {
		appid: "R7drYPbV34FffYJ1XzR0uw2hACglcoZKtAALrgk3xShTg3M04lzPf9spFg_QEZh.xA--",
		context: context,
		query: query,
		output: "json"
	};
	
	document.getElementById('message').innerHTML = 'Extracting terms...';
	$.ajax({
		type: "GET",
		url: "http://search.yahooapis.com/ContentAnalysisService/V1/termExtraction",
		dataType: 'jsonp',
		data: data,
		success: function(data) {
			//jsonp1250516397852({"ResultSet":{"Result":["italian sculptors","virgin mary","painters","renaissance","inspiration"]}});
			if (data.ResultSet && data.ResultSet.Result.length) {
				results['results['+id+']'] = data.ResultSet.Result.join('|');
				
				document.forms['progressForm'].elements['terms'].value = data.ResultSet.Result.join('|');
			} else {
				 document.forms['progressForm'].elements['terms'].value = "**none**";
			}
			
			c=c+1;
			
			document.getElementById('message').innerHTML = 'Sleeping for a bit.';
			document.forms['progressForm'].elements['overall'].value = c+'/'+images;
			
			var sendevery = document.forms['optionsFrom'].elements['speed'][0].checked?10:100;
			if (c%sendevery == 0) {
				submitToGeograph();
			}
		}
	});

}
var sending = false;

function submitToGeograph() {
	if (sending) {
		return;
	}
	document.getElementById('message').innerHTML = 'Sending Progress to Geograph...';
	sending = true;
	$.ajax({
		type: "POST",
		url: "at-home.php?submitJobResults="+jobId+"&task=yahoo_terms&worker="+workerToken+"&output=json",
		dataType: 'json',
		data: results,
		success: function(data) {
			sending = false;
			if (rawData.length == 0) {
				markJobFinished();
			}
		}
	});
	results = {}; //the current ones are now in transit...
}

function createWorker(thatForm) {
	document.getElementById('message').innerHTML = 'Creating Worker...';
	$.ajax({
		type: "GET",
		url: "at-home.php?getWorkerToken&output=text&team="+escape(thatForm.team.value),
		dataType: 'html',
		data: results,
		success: function(data) {
			alert(data);
			history.go(0);
		}
	});
	return false;
}

function startupWorker() {
	if (readCookie('workerToken')) {
		workerToken = readCookie('workerToken');
		startWork();
	} else {
		document.forms['createWorkerForm'].style.display='block';
	}
}


 AttachEvent(window,'load',startupWorker,false);

</script>


{/literal}


<h2>Geograph-at-Home Client Worker</h2>

<div id="message" class="interestBox"></div><br/><br/>

<form style="display:none" onsubmit="return false" name="progressForm">
	<fieldset>
		<legend>Current Progress</legend>
		<div>
			<label>done:</label>
			<input name="overall" value="0" type="text" size="8" readonly/>
		</div>
		<div>
			<label>description:</label>
			<textarea name="comment" rows=4 cols=80 readonly></textarea>
		</div>
		<div>
			<label>terms:</label>
			<input name="terms" value="" type="text" size="80" readonly/>
		</div>
	</fieldset>
</form>


<form style="display:none" onsubmit="return createWorker(this)" name="createWorkerForm">
	<fieldset>
		<legend>Create Worker</legend>
		<div>
			<label>optional team name:</label>
			<input name="team" value="" type="text">
		</div>
		<input type="submit" value="Submit"/>
	</fieldset>
</form>


<form onsubmit="return createWorker(this)" name="optionsFrom">
	<fieldset>
		<legend>Speed Option</legend>
		<div>
			<input name="speed" value="slow" type="radio" checked> <b>Slow</b> - shouldn't affect normal operation of computer (<b>recommended</b>)<br/>
			<input name="speed" value="fast" type="radio" > <b>Fast</b> - runs as fast as possible (no real benefit - can still only run 5000 queries a day)
			
		</div>
	</fieldset>
</form>



{include file="_std_end.tpl"}
