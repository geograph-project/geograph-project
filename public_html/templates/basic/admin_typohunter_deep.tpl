{assign var="page_title" value="Typo Hunter"}
{include file="_std_begin.tpl"}
{dynamic}
<h2><a href="/admin/typolist.php">Typos</a> :: Typo Hunter v0.9 - Deep Search</h2>

<form action="{$script_name}" method="get" id="theForm">
	<div class="interestBox">
		<label for="include"><b>Include</b>:</label> <input type="text" size="40" name="include" value="{$include|escape:'html'}" id="include"/> |
		<label for="exclude">Exclude (optional):</label> <input type="text" size="40" name="exclude" value="{$exclude|escape:'html'}" id="exclude"/>
		<input type="submit" value="Recent Images"/>
		<input type="submit" name="deep" value="Deep Search" style="font-size:1.1em"/>
		<br/>
		<select name="profile">
			<option value="phrase">phrase - legacy style 'substring' matching</option>
			<option value="expression"{if $profile=='expression'} selected{/if}>expression - case sensitive regular-expression engine</option>
		</select> |
	</div>
</form>

<input type=button value="Stop/Abort Checking" onclick="stopCheck()" class="stopper">
<table id=results border=1 cellspacing=0 cellpadding=6>
	<tr>
		<th>Shard</th>
		<th>Images</th>
		<th>Example</th>
		<th>View</th>
	</tr>
</table>
<input type=button value="Stop/Abort Checking" onclick="stopCheck()" class="stopper">

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

<script>
var shards = {$shards};
var data = new Object();
data.include = '{$include|escape:'javascript'}';
data.exclude = '{$exclude|escape:'javascript'}';
data.profile = '{$profile|escape:'javascript'}';

{literal}

var timer = null; var stopped = false;
var link = '';

data.count = 1;
function fetch(shard) {
	data.shard = shard;

	var startTime = Date.now();

                $.ajax({
                  dataType: "json",
                  url: "/admin/typohunter.php",
                  data: data,
                  cache: true,
                  success: function(result) {
			var endTime = Date.now();


                        $('table#results').append(
                                '<tr class=r'+result.matches+'>'+
                                        '<td align=right>'+(shard*50000)+'</td>'+
                                        '<td align=right>'+result.matches+'</td>'+
                                        '<td>'+(result.gridimage_id>1?'[[[<a href="/editimage.php?id='+result.gridimage_id+'" target=_blank>'+result.gridimage_id+'</a>]]]':'')+'</td>'+
                                        '<td>'+(result.gridimage_id>1?'<a href="'+link+shard+'" target=_blank>View More</a>':'')+'</td>'+
                                '<tr>');

			if (shard>0 && !stopped)
				timer = setTimeout(function() {
					fetch(shard-1);
				}, (endTime-startTime)+25 );
			else
				$('.stopper').hide('fast');
                  }
                });


}

function stopCheck() {
	clearTimeout(timer);
	stopped = true;
}

$(function() {
	link = "?"+$('form#theForm').serialize()+"&shard=";
	fetch(shards); ///fetches the last!
});

</script>
<style>
tr.r0 {
	font-size:0.8em;
	color:gray;
}
</style>

{/literal}



{/dynamic}
{include file="_std_end.tpl"}
