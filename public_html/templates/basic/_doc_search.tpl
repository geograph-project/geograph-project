{literal}
<form action="/content/" method="get">
<div class="interestBox" style="margin-top:2px;width:200px">
<div style="position:relative;float:right;">
        <input type="submit" value="Find" onclick="loadSearchResults(this.form.q.value,true);$('ul.smart_autocomplete_container').html('');return false;"/>
</div>
<lable for="type_ahead_autocomplete_field">Keyword Search:</label>
<br style="clear:both"/>
<input type="text" name="q" size="20" autocomplete="off" id="type_ahead_autocomplete_field"/>
<input type=hidden name="scope" value="document"/>
<input type="hidden" name="order" value="relevance"/>

</div>
</form>

        <div id="searchresults"></div>
<style>
        ul.smart_autocomplete_container { margin: 10px 0; padding: 5px; background-color: #E3EBBC; }
        ul.smart_autocomplete_container li {list-style: none; cursor: pointer;}
        li.smart_autocomplete_highlight {background-color: #C1CE84;}

        #searchresults a { font-weight:bold; }
        #searchresults div.text { font-size:0.8em; margin-left:20px; border-left:1px solid silver; padding-left:2px; margin-bottom:4px; }
        #searchresults a.clear { color:red; display:block; background-color:#eeeeee;padding:5px;margin:20px; }

        .results_preview { margin-left:240px; margin-top:-35px; }
        .results_full {}
</style>

<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
<script type="text/javascript" src="/js/jquery.smart_autocomplete.js"></script>

<script type="text/javascript">
        $(function(){

                //example 3
                $("#type_ahead_autocomplete_field").smartAutoComplete({source: '/content/docs-suggest.json.php', typeAhead: true });

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

                $('#searchresults').html('Loading Results for <b>'+value+'</b>...');

                $.getJSON("/content/docs.json.php?"+param+"&callback=?",

                // on search completion, process the results
                function (data) {
                        if (data) {
                                loadedquery = param;

                                $('#searchresults').attr('class',highlight?'results_full':'results_preview');
                                $('#searchresults').html('Results for <b>'+value+'</b>.<br/>');

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
                                $('#searchresults').append(str);

                                if (highlight) {
                                        $('#searchresults').append('<a href="javascript:void($(\'#searchresults\').empty())" class="clear">Hide results</a>');
                                }
                        }
                });
        }
</script>
{/literal}
