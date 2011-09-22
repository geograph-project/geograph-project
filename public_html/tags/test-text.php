<?

?>
  <style>

    ul li {list-style: none; cursor: pointer;}
    li.smart_autocomplete_highlight {background-color: #C1CE84;}
    ul { margin: 10px 0; padding: 5px; background-color: #E3EBBC; }
    
    a { border:1px solid gray; background-color:#eeeeee; padding:2px; color:black; text-decoration:none}
  </style>

  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
  <script type="text/javascript" src="/js/jquery.smart_autocomplete.js"></script>
  
  <script type="text/javascript">
    $(function(){
    
         //example 6
        $("#textarea_autocomplete_field").smartAutoComplete({source: "tags.json.php", maxResults: 50, delay: 200 } );
        $("#textarea_autocomplete_field").bind({

           keyIn: function(ev){
             var tag_list = ev.smartAutocompleteData.query.split(/,\s*/); 
             //pass the modified query to default event
             ev.smartAutocompleteData.query = $.trim(tag_list[tag_list.length - 1]);
           },

           itemSelect: function(ev, selected_item){ 
            var options = $(this).smartAutoComplete();

            //get the text from selected item
            var selected_value = $(selected_item).text();
            var cur_list = $(this).val().split(/,\s*/); 
            cur_list[cur_list.length - 1] = selected_value;
            $(this).val(cur_list.join(", ") + ", "); 

            //set item selected property
            options.setItemSelected(true);

            //hide results container
            $(this).trigger('lostFocus');
              
            //prevent default event handler from executing
            ev.preventDefault();
          },

        });   
    
     });
     
     function useIt(text) {
     	var ele = $("#textarea_autocomplete_field");
     	

	var cur_list = ele.val().split(/,\s*/); 
	cur_list[cur_list.length - 1] = text;
	ele.val(cur_list.join(", ") + ", ");
     }
   </script>
             <fieldset id="example_6">
               <legend><b>Tag(s)</b> [You can enter mulitple tags seperated by comma]</legend>
 		
 		<div style="float:right">
 			<b>Topics</b>:<br/>
 			<a href="javascript:void(useIt('Belfast'));">Belfast</a>, <a href="javascript:void(useIt('Cave'));">Cave</a>, <a href="javascript:void(useIt('Triassic sandstone'));">Triassic sandstone</a>
 		</div>
 		
               <div>
                 <label for="textarea_autocomplete_field">Public tags</label>
                 <textarea  rows="3" cols="55" autocomplete="off" id="textarea_autocomplete_field" placeholder="Type Public tag(s) here"></textarea>
               </div>

               <div>
                 <label for="textarea_autocomplete_field">Private tags</label>
                 <textarea  rows="3" cols="55" autocomplete="off" id="textarea_autocomplete_field2" placeholder="Type Private tag(s) here"></textarea>
               </div>
            </fieldset>