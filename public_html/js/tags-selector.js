
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

const MAX_TAGS = 20;
const STORAGE_KEY = 'recentTagsHistory';
/**
 * Retrieves the recent tags from localStorage.
 * @returns {Array<string>} An array of recent tag strings.
 */
function getRecentTags() {
    try {
        const storedTags = localStorage.getItem(STORAGE_KEY);
        return storedTags ? JSON.parse(storedTags) : [];
    } catch (e) {
        console.error("Error reading localStorage:", e);
        return [];
    }
}

/**
 * Adds a new tag to the recent tags list in localStorage,
 * keeping it to a maximum of MAX_TAGS.
 * @param {string} tag - The tag to add.
 */
function addTagToHistory(tag) {
    let tags = getRecentTags();

    // 1. Remove the tag if it already exists to move it to the front
    tags = tags.filter(t => t.toLowerCase() !== tag.toLowerCase());

    // 2. Add the new tag to the beginning
    tags.unshift(tag);

    // 3. Trim the list to MAX_TAGS
    if (tags.length > MAX_TAGS) {
        tags = tags.slice(0, MAX_TAGS);
    }

    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(tags));
    } catch (e) {
        console.error("Error writing to localStorage:", e);
    }
}

// --- Autocomplete Setup ---

$( "#tags" ).autocomplete({
    minLength: 0,
    source: function( request, response ) {
        // 1. Get the term the user is currently typing
        var fullTerms = request.term;
        var currentTerm = extractLast(request.term);

        // CASE 1: Current term is empty (input just focused/clicked) -> SHOW RECENT TAGS
        if (currentTerm === '') {
            // Get tags from history and format them for the autocomplete response
            const recentTags = getRecentTags();
            const existingTags = getSelectedTags(fullTerms);

            const results = recentTags
                .filter(tag => existingTags.indexOf(tag) === -1) // Filter out already selected tags
                .map(tag => ({
                    value: tag,
                    label: tag,
                    title: "Recently Used Tag" // Add a title to distinguish
                }));

            response(results);
            return;
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
    focus: function(event, ui) {
        // Prevent value inserted on focus
        return false;
    },

    select: function(event, ui) {
        if (!ui.item.value) { // Ignore info/copyright items
            return false;
        }

        // 1. ADD selected tag to localStorage history
        addTagToHistory(ui.item.value);

        // 2. Append the selected tag to the input (multiple-select logic)
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

})
.on("focus", function() {
            // Check if the input is empty to avoid showing a full list when a value is already present
            if ($(this).val() === "") {
                $(this).autocomplete("search", "");
            }
})
.data( "autocomplete" )._renderItem = function( ul, item ) {

    var currentSearchTerm = extractLast($("#tags").val()); // Use #tags for the value
    var re = new RegExp('(' + $.ui.autocomplete.escapeRegex(currentSearchTerm) + ')', 'gi');

    if (!item.title) item.title = '';

    return $( "<li></li>" )
        .data( "item.autocomplete", item )
        // Highlight the search term within the label and title
        .append( "<a>" + item.label.replace(re,'<b>$1</b>') + " <small>" + item.title.replace(re,'<b>$1</b>') + "</small></a>" )
        .appendTo( ul );
};


