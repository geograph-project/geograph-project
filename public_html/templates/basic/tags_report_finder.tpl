{include file="_std_begin.tpl"}

<div class="tabHolder">
        <a class="tab nowrap" href="?">Public Suggestion Form</a>
        <a class="tab nowrap" href="?admin=1">Admin Suggestion Form</a>
        <a class="tabSelected nowrap" href="?finder=1">Quick Tag Searcher</a>
        <a class="tab nowrap" href="?approver=1">Approve Suggestions</a>
</div>
<div class="interestBox">
        <h3>Quick Tag Searcher</h3>
</div>


<form name="theForm" onsubmit="return false">
	<div class="interestBox">
		<label for="fq">Search</label>: <input type="text" name="q" id="fq" size="40" onkeyup="{literal}loadTagSuggestions(this,event){/literal}"/> (seperate multiple tags with "|" charactor)
	</div>
	<ol id="results" style="margin-left:100px"></ol>
</form>

Tips:
<ul>
	<li>Hold down Ctrl when clicking a tag, to open the admin page in a new tab/window</li>
</ul>

<script src="{"/js/to-title-case.js"|revision}"></script>
{literal}
<style>
	#results li a {
		text-decoration:none;
	}
</style>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<script>
$(function() {
                if (location.hash.length) {
                        // skip the first character, we are not interested in the "#"
                        var query = location.hash.substring(1);

                        var pairs = query.split("&");
                        for (var i=0; i<pairs.length; i++) {
                                var pos = pairs[i].indexOf("=");
                                var argname = pairs[i].substring(0,pos).toLowerCase();
                                var value = decodeURIComponent(pairs[i].substring(pos+1));
                                if (argname == 'q') {
                                        document.forms['theForm'].elements['q'].value = value;
                                        loadTagSuggestions(document.forms['theForm'].elements['q'],{keyCode:0});
                                }
                        }
                }
});

String.prototype.capitalizeTag = function () {
        var bits = this.split(":",2);
        if (bits.length == 2) {
                return bits[0].toLowerCase()+':'+bits[1].toTitleCase();
        } else {
                return this.toTitleCase();
        }
}


  
  function focusBox() {
  	if (el = document.getElementById('fq')) {
  		el.focus();
  	}
  }
  AttachEvent(window,'load',focusBox,false);


	function loadTagSuggestions(that,event) {

		var unicode=event.keyCode? event.keyCode : event.charCode;
		if (unicode == 13) {
			return;
		}
		
		param = 'q='+encodeURIComponent(that.value.replace(/\|/g,' OR '));

		window.location.hash = "#"+param;

		$.getJSON("/tags/tags.json.php?"+param+"&counts=1",

		// on search completion, process the results
		function (data) {
			var div = $('#results').empty();

			if (data && data.length > 0) {

				for(var tag_id in data) {
					var text = data[tag_id].tag;
					if (data[tag_id].prefix && data[tag_id].prefix!='term' && data[tag_id].prefix!='category' && data[tag_id].prefix!='cluster' && data[tag_id].prefix!='wiki') {
						text = data[tag_id].prefix+':'+text;
					}
					text = text.replace(/<[^>]*>/ig, "");
					text = text.replace(/['"]+/ig, " ");

					div.append("<li value=\""+data[tag_id].images+"\"><a href=\"?admin=1#t="+encodeURIComponent(text)+"\">"+text.capitalizeTag()+"</a></li>");
				}
			}
		});

	}

</script>
{/literal}

{include file="_std_end.tpl"}

