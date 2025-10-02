
// Function to extract the currently searched term (the part after the last ']')
function extractLast(term) {
    // Regex to find all tags enclosed in square brackets and then what follows (the search term)
    var tags = term.match(/\[.*?\]\s*/g);
    if (tags && tags.length > 0) {
        // Find the index right after the last tag and return the substring from there
        var lastTagEndIndex = tags[tags.length - 1].length;
        var remainingText = term.substring(term.lastIndexOf(tags[tags.length - 1]) + lastTagEndIndex);
        return remainingText.trim();
    }
    // If no tags, the whole term is the search term
    return term.trim();
}

// Function to get the already selected tags, which will be ignored for the search
function getSelectedTags(term) {
    // Regex to find all tags enclosed in square brackets
    var tags = term.match(/\[.*?\]/g);
    return tags ? tags.map(tag => tag.slice(1, -1).trim()) : []; // Remove brackets and trim
}

$( "#tags" ).autocomplete({
    minLength: 1,
    source: function( request, response ) {
        // 1. Get the term the user is currently typing
        var currentTerm = extractLast(request.term);

        // 2. Check if the current term is empty, if so, don't search/show dropdown
        if (currentTerm === '') {
            return response([]);
        }

        // 3. Make the AJAX call using the current search term
        var url = "/tags/tags.json.php?q=" + encodeURIComponent(currentTerm);

        $.ajax({
            url: url,
            dataType: 'jsonp',
            jsonpCallback: 'serveCallback',
            cache: true,
            success: function(data) {
                if (!data || data.length < 1) {
                    $("#message").html("No tags found matching '" + currentTerm + "'");
                    // close is on #tags, not #loc
                    $("#tags").autocomplete("close");
                    return;
                }

                // Get already selected tags to filter results on the client side if necessary,
                // although it's better to filter on the server.
                var existingTags = getSelectedTags(request.term);

                var results = [];
                $.each(data, function(i, item){
                    var tag = item.tag;

                                        if (item.prefix && item.prefix!='term' && item.prefix!='category' && item.prefix!='cluster' && item.prefix!='wiki') {
                                                tag = item.prefix+':'+tag;
                                        }

                    // Optionally filter out already selected tags (assuming item.tag is the clean tag name)
                    if (existingTags.indexOf(tag) === -1) {
                        results.push({value: tag, label: tag});
                    }
                });

                // Append info/copyright messages
                if (data.query_info)
                    results.push({value:'',label:'',title:data.query_info});
                if (data.copyright)
                    results.push({value:'',label:'',title:data.copyright});

                response(results);
            }
        });
    },
    focus: function() {
        // prevent value inserted on focus
        return false;
    },

    select: function(event, ui) {
        if (!ui.item.value) { // Ignore info/copyright items
            return false;
        }

        var terms = this.value;
        var currentTerm = extractLast(terms);

        // Remove the currently typed term part (which is the partial tag being searched)
        var newTerms = terms.substring(0, terms.length - currentTerm.length).trim();

        // Append the new selected tag in the [tag] format, plus a space for the next search
        newTerms += " [" + ui.item.value + "] ";

        // Set the new value and keep the focus for continued typing
        $(this).val(newTerms);
        return false;
    }

}).data( "autocomplete" )._renderItem = function( ul, item ) {

    var currentSearchTerm = extractLast($("#tags").val()); // Use #tags for the value
    var re = new RegExp('(' + $.ui.autocomplete.escapeRegex(currentSearchTerm) + ')', 'gi');

    if (!item.title) item.title = '';

    return $( "<li></li>" )
        .data( "item.autocomplete", item )
        // Highlight the search term within the label and title
        .append( "<a>" + item.label.replace(re,'<b>$1</b>') + " <small>" + item.title.replace(re,'<b>$1</b>') + "</small></a>" )
        .appendTo( ul );
};


