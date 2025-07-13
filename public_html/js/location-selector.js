//<![CDATA[

var regions = null;

function getLocation() {
    $.getScript("https://m.geograph.org.uk/js/jquery.geolocation.js").done(function(script, textStatus) {
        $.geolocation.get({
            success: function(position) {
                $('#loc').val(position.coords.latitude + "," + position.coords.longitude);
            },
            fail: function() {
                alert('Unable to load location');
            }
        });
    });
}

$(function() {

    $("#loc").autocomplete({
        minLength: 0,
        source: function(request, response) {

            if (request.term.length < 2 && regions) {
                var results = [];
                $.each(regions, function(i, item) {
                    results.push({
                        value: item,
                        label: item
                    });
                });

                response(results);
                return;
            }
            if (request.term.length < 2) {
                response([]);
                return;
            }


            var url = "https://www.geograph.org.uk/finder/places.json.php?q=" + encodeURIComponent(request.term) + "&new=1";

            $.ajax({
                url: url,
                dataType: 'jsonp',
                jsonpCallback: 'serveCallback',
                cache: true,
                success: function(data) {

                    if (!data || !data.items || data.items.length < 1) {
                        $("#message").html("No places found matching '" + request.term + "'");
                        $("#placeMessage").show().html("No places found matching '" + request.term + "'");
                        setTimeout('$("#placeMessage").hide()', 3500);
                        return;
                    }
                    var results = [];
                    $.each(data.items, function(i, item) {
                        results.push({
                            value: item.gr + ' ' + item.name,
                            label: item.name,
                            gr: item.gr,
                            title: item.localities
                        });
                    });
                    results.push({
                        value: '',
                        label: '',
                        title: data.query_info
                    });
                    results.push({
                        value: '',
                        label: '',
                        title: data.copyright
                    });
                    response(results);
                }
            });
        },
        select: function(event, ui) {
            document.locForm.elements['loc'].value = ui.item.value;
            jumpLocation(document.locForm);
            return false;
        }
    })
    .data("autocomplete")._renderItem = function(ul, item) {
        var re = new RegExp('(' + $("#loc").val() + ')', 'gi');
        if (!item.title) item.title = '';
        return $("<li></li>")
            .data("item.autocomplete", item)
            .append("<a>" + item.label.replace(re, '<b>$1</b>') + " <small> " + (item.gr || '') + "<br>" + item.title.replace(re, '<b>$1</b>') + "</small></a>")
            .appendTo(ul);
    };

    $("#loc").focus(function() {
        if ($(this).val() == '' && regions)
            $(this).autocomplete("search", ""); //need to trigger the empty search
    });
});


function jumpLocation(form) {
    form.submit();
    var value = form.elements['loc'].value;
}
//]]>
