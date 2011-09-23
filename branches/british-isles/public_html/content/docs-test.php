<?

?>
  <style>
body {
	font-family: georgia;
}
    ul.smart_autocomplete_container { margin: 10px 0; padding: 5px; background-color: #E3EBBC; }
    ul.smart_autocomplete_container li {list-style: none; cursor: pointer;}
    li.smart_autocomplete_highlight {background-color: #C1CE84;}
    
	#results a {
		font-weight:bold;
	}
	#results div.text {
		font-size:0.8em;
		margin-left:20px;
		border-left:1px solid silver;
		padding-left:2px;
		margin-bottom:4px;
	}

	.results_preview {
		margin-left:200px;
		margin-top:-35px;
	}
	.results_full {
	}
  </style>

  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
  <script type="text/javascript" src="/js/jquery.smart_autocomplete.js"></script>
  
  <script type="text/javascript">
    $(function(){
    
        //example 3
        $("#type_ahead_autocomplete_field").smartAutoComplete({source: 'docs-suggest.json.php', typeAhead: true });
        
        $("#type_ahead_autocomplete_field").bind({
	
	           itemFocus: function(ev, selected_item){ 
	            loadSearchResults($(selected_item).text(),false);
	           },
	
	           itemSelect: function(ev, selected_item){ 
	            
	            loadSearchResults($(selected_item).text(),true);
	          },
	
        });
        
    });

var loadedquery = null;
    
    function loadSearchResults(value,highlight) {
    

    		param = 'q='+encodeURIComponent(value);
		if (highlight) {
			param = param + "&h=1";
		}

		if (param == loadedquery) {
			return;
		}
		
	        $('#results').html('Loading Results for <b>'+value+'</b>...');
    
    		$.getJSON("/content/docs.json.php?"+param+"&callback=?",
    
    		// on search completion, process the results
    		function (data) {
    			if (data) {
				$('#results').attr('class',highlight?'results_full':'results_preview');
				loadedquery = param;
    				$('#results').html('Results for <b>'+value+'</b>.<br/>');
    				
    				str = '';
    				for(var idx in data) {
    					if (data[idx].query_info) {
    						str += "<br/><i>"+data[idx].query_info+"</i><br/>";
    					} else {
						var text = data[idx].title;


						str += "&middot; <a href=\""+data[idx].url+"\">"+text+"</a> "+data[idx].source+"<br/>";
						if (data[idx].words) {
							str += "<div class=\"text\">"+data[idx].words+"</div>";
						} else {
							
						}
					}
    				}
    				$('#results').append(str);
    			}
    		});
	}
    
   </script>
   <form onsubmit="return false">
            <fieldset id="example_3">
              <legend>Example 3</legend>

              <p>With <em>typeAhead</em> option enabled</p>

              <div>
                <label for="type_ahead_autocomplete_field">Search</label><br/>
                <input type="text" name="q" autocomplete="off" id="type_ahead_autocomplete_field"/><br/>
		<input type="button" value="Search" onclick="loadSearchResults(this.form.q.value,true)">
              </div>

		<div id="results"></div>

            </fieldset>
	</form>
