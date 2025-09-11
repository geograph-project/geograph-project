// Based on location-selector.js
// Attaches jQuery UI autocomplete to an input with id="contributor"

$(function () {

    $( "#contributor" ).autocomplete({
            minLength: 3,
            source: function( request, response ) {

                if (request.term.length < 3) {
                    response([]);
                    return;
                }

                var url = "/finder/contributors.json.php?q="+encodeURIComponent(request.term)+"&new=1";

                $.ajax({
                        url: url,
                        dataType: 'jsonp',
                        jsonpCallback: 'serveCallback',
                        cache: true,
                        success: function(data) {

                                if (!data || !data.items || data.items.length < 1) {
                                    response([]);
                                    return;
                                }
                                var results = [];
                                $.each(data.items, function(i,item){
                                    results.push({
                                        value: item.user_id + ' ' + item.realname,
                                        label: item.realname,
                                        title: (item.nickname || '')+' ['+item.images+' images] id#'+item.user_id
                                    });
                                });
                                if (data.query_info)
                                    results.push({value:'',label:'',title:data.query_info});
                                if (data.copyright)
                                    results.push({value:'',label:'',title:data.copyright});
                                response(results);
                        }
                });
            },
            select: function(event,ui) {
                    $("#contributor").val(ui.item.value);
                    // In the context of finder_finder.tpl, we want to trigger a search on select.
                    if (typeof performSearch === 'function') {
                        performSearch();
                    }
                    return false;
            }
    })
    .data( "autocomplete" )._renderItem = function( ul, item ) {
            var re=new RegExp('('+$("#contributor").val()+')','gi');
            if (!item.title) item.title = '';
            return $( "<li></li>" )
                    .data( "item.autocomplete", item )
                    .append( "<a>" + item.label.replace(re,'<b>$1</b>') + " <small>" + item.title.replace(re,'<b>$1</b>') + "</small></a>" )
                    .appendTo( ul );
    };
});
