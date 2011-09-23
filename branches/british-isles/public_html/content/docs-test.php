<?

?>
  <style>

    ul li {list-style: none; cursor: pointer;}
    li.smart_autocomplete_highlight {background-color: #C1CE84;}
    ul { margin: 10px 0; padding: 5px; background-color: #E3EBBC; }
    
  </style>

  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
  <script type="text/javascript" src="/js/jquery.smart_autocomplete.js"></script>
  
  <script type="text/javascript">
    $(function(){
    
        //example 3
        $("#type_ahead_autocomplete_field").smartAutoComplete({source: 'docs.json.php', typeAhead: true });
        
        $("#type_ahead_autocomplete_field").bind({
	
	           itemFocus: function(ev, selected_item){ 
	             $('#results').html('Loading Results for '+$(selected_item).text()+'...');
	            loadSearchResults($(selected_item).text());
	           },
	
	           itemSelect: function(ev, selected_item){ 
	            
	            loadSearchResults($(selected_item).text());
	          },
	
        });
        
    });
    
    function loadSearchResults(value) {
    
    		param = 'q='+encodeURIComponent(value);
    
    		$.getJSON("/content/docs.json.php?"+param+"&callback=?",
    
    		// on search completion, process the results
    		function (data) {
    			if (data) {
    				$('#results').html('Results for <b>'+value+'</b>.<br/>');
    				
    				str = '';
    				for(var idx in data) {
    					if (data[idx].query_info) {
    						str += "<br/><i>"+data[idx].query_info+"</i><br/>";
    					} else {
						var text = data[idx].title;


						str += "<a href=\""+data[idx].url+"\">"+text+"</a> "+data[idx].source+"<br/>";
					}
    				}
    				$('#results').append(str);
    			}
    		});
	}
    
   </script>
   
            <fieldset id="example_3">
              <legend>Example 3</legend>

              <p>With <em>typeAhead</em> option enabled</p>

              <div>
                <label for="type_ahead_autocomplete_field">Search</label>
                <input type="text" autocomplete="off" id="type_ahead_autocomplete_field"/>
              </div>

		<div id="results" style="margin-top:150px"></div>

            </fieldset>