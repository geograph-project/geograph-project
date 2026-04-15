
    const searchInput = document.getElementById('tag-search');
    const suggestions = document.getElementById('tag-suggestions');
    const activeTagsContainer = document.getElementById('active-tags');
    let selectedTags = new Set(); // Use a Set to prevent duplicates

    let debounceTimer = null;
    searchInput.addEventListener('input', async (e) => {
        const query = e.target.value;
        if (query.length < 1) { suggestions.innerHTML = ''; return; }

        //do need debounce
        if (debounceTimer) clearTimeout(debounceTimer);

        debounceTimer = setTimeout(async function() {
            let html = '';
            let normalizedResults = [];

            try {
        		//todo, take only the LAST component, if entered text includes a ;
                const response = await fetch(`/tags/tags.json.php?term=${encodeURIComponent(query)}&mode=ranked`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const results = await response.json(); // Expected: ["tag1", "tag2"]

                // Normalize results for comparison
                normalizedResults = results.map(t => t.toLowerCase());

                // 1. Create a Case-Insensitive Regex of the user's query
                const safeQuery = escapeRegex(query);
                const regex = new RegExp(`(${safeQuery})`, "gi");

                html = results.map(tag => {
                    // 2. Replace the match with a bold version
                    // $1 keeps the original casing from the database (e.g., "Road" stays "Road")
                    const highlighted = escapeHTML(tag).toTitleCase().replace(regex, "<strong>$1</strong>");
                    return `<div class="suggestion-item">${highlighted}</div>`;
                }).join('');

            } catch (error) {
                console.error("Tag fetch failed:", error);
                // Inform the user that suggestions are currently unavailable
                html = `<div class="suggestion-item error-text"><em>Suggestions unavailable</em></div>`;
            }

            //still runs even if fails!
            if (query.length > 2 && !normalizedResults.includes(query.toLowerCase())) {
        		// Auto-split, on semicolons
                if (query.includes(';')) {
                    // 1. Split and clean each tag
                    const tagArray = query.split(/\s*;\s*/).map(t => t.trim()).filter(t => t.length > 0);
                    const cleanedTags = tagArray.map(t => cleanTag(t)).filter(t => t.length > 0);
                    if (cleanedTags.length > 0) { //could end up zero!

                        // 2. Create the data-tag string for bulk processing
                        const query_safe = escapeHTML(cleanedTags.join(';'));

                        // 3. Generate the visual display
                        const displayList = cleanedTags.map(t => `[${escapeHTML(t)}]`).join(' ');

                        html += `<div class="suggestion-item add-new-tag" data-tag="${query_safe}">+ Add all: ${displayList}</div>`;
                    }

		        } else {
                    const query_safe = escapeHTML(cleanTag(query));
                    html += `<div class="suggestion-item add-new-tag" data-tag="${query_safe}">+ Add [${query_safe}]</div>`;
                }
            }

            suggestions.innerHTML = html;
        }, 250);
    });
    // Handle clicking a suggestion
    suggestions.addEventListener('click', (e) => {
        if (e.target.classList.contains('suggestion-item') && !e.target.classList.contains('error-text')) {
            // If it's the "Add New" pill, grab the custom data attribute
            const tag = e.target.classList.contains('add-new-tag')
                ? e.target.dataset.tag
                : e.target.innerText; //to ignore bolding!
            addTag(tag);
        }
    });

    searchInput.addEventListener('focus', (e) => {
        // Wait a tiny bit for the mobile keyboard to fully animate up
        setTimeout(() => {
            const rect = searchInput.getBoundingClientRect();
            const viewportHeight = window.innerHeight;

            // If the input is in the bottom 30% of the visible area
            if (rect.top > viewportHeight * 0.7) {
                let block = 'center'; // This puts it in the middle, not the top
                if (window.matchMedia("(max-height: 500px) and (orientation: landscape)").matches) {
                    block = 'start';
                }
                searchInput.scrollIntoView({
                    behavior: 'smooth',
                    block: block
                });
            }
        }, 300);
    });

    function cleanTag(text) {
        //Allows chars: A-Z a-z 0-9 _ ( ) + . & / ! ? % @ # - (plus space)

        //basic HTML injection protection
        text = text.replace(/\\/g, "").replace(/<[^>]*>/g, "").replace(/[<>]+/ig, " ");

        //clean up text, doing fairly full unicode->ascii transliteration
        text = anyAscii(text);

        //standardize brackets
        text = text.replace(/[\{\(\[<]+/g, "(").replace(/[\}\)\]>]+/g, ")");

        //special case, turns out some are missing the colon! (getCorrectedPrefix will find other typos!)
        if (m = text.match(/^[Mm]ilestone(\s*id)?.?([A-Z]{2}[\s\._][A-Z].+)/))
            text = "milestoneid:"+m[2];

        //hive off the prefix (added back at the end)
        var prefix = null;
        if (text.indexOf(':') > -1) {
                var bits = text.split(/\s*:+\s*/,2);

                //prefixes have particully restricted charactor set. (also corrects known typos!)
                prefix = getCorrectedPrefix(bits[0]);

                text = bits[1].replace(/:/g,' ');
        }

        //special support for listin building rating
        text = text.replace(/\*/g,'(star)');

        //quotes not supported
        text = text.replace(/['"`]+/g, ""); //dont want to replace with space, because of apos

        //then remove any none supported chars (by now only have ascii left to deal with)
        text = text.replace(/[^\w()\+\.&\/!?%@#-]+/g, " ");

        //clean/collapse whitespace
        text = text.replace(/[ _\t\n\r]+/g, " ").replace(/(^\s+|\s+$)/g, "");

        //this is a well known and common issue to fix, our house style doesnt have dot after st.
        text = text.replace(/\b(st)\.+\s*/i, '$1 ');

        //just to catch odd cases were tag ends up actully blank!
        text = text.replace(/^\s*$/,'blank');

        //add the prefix again
        if (prefix)
                text = prefix+':'+text;
        return text;
    }

    function addTag(tag) {
        if (tag.includes(';')) {
            const tagArray = tag.split(/\s*;\s*/).map(t => t.trim()).filter(t => t.length > 0);
            if (tagArray.length == 0)
                return;

            for(let q=0;q<tagArray.length;q++)
                addTag(tagArray[q]);
            return;
        }

        if (!selectedTags.has(tag)) {
            selectedTags.add(tag);

            const span = document.createElement('span');
            span.className = 'tag-pill';

            // 1. Set text safely (avoids XSS and quote issues)
            span.textContent = tag + ' ';

            // 2. Create the button as a real object
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = '&#10005;';

            // 3. Attach the remove logic directly to this specific button
            btn.onclick = function() {
                selectedTags.delete(tag); // Remove from our Set
                span.remove();            // Remove the whole pill from DOM
            };

            // 4. Create the hidden input for the form POST
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tags[]';
            input.value = tag;

            span.appendChild(btn);
            span.appendChild(input);
            activeTagsContainer.appendChild(span);

	    if (typeof updateFormProgress == 'function')
                updateFormProgress();
        }
        searchInput.value = '';
        suggestions.innerHTML = '';
    }

/**
 * Corrects variations and typos of 'milestoneid'.
 * @param {string} prefix - The input string to check.
 * @returns {string} - The corrected string or the original input.
 */
function getCorrectedPrefix(rawInput) {

    // prefixes have particully restricted charactor set
    const prefix = rawInput.toLowerCase().replace(/[^\w]+/g, " ").replace(/[ _]+/g, " ").trim();

    //this is focused on catching typos for milestone ids, have seen many!
    const target = "milestoneid";

    // 1. Handle the "Milestone Society" or "National ID" long-form cases
    if (prefix.includes("milestone") && (prefix.includes("society") || prefix.includes("national"))) {
        return target;
    }

    // 2. Clean up common prefixes like "add tag " or spaces (targeting like "milestone id", so not a space that removed normally)
    const cleaned = prefix.replace(/^((add|tag)\s*)+/, "").replace(/\s+/g, "");

    // 3. Exact match check after cleaning
    if (cleaned === target) return target;

    // 4. Fuzzy Match (Levenshtein Distance)
    // We only want to correct it if it's very close (distance of 1 or 2)
    if (getLevenshteinDistance(cleaned, target) <= 2)  return target;

    // Return original if no confident match found
    return prefix;
}

/**
 * Calculates the edit distance between two strings.
 */
function getLevenshteinDistance(a, b) {
    const matrix = [];

    for (let i = 0; i <= b.length; i++) matrix[i] = [i];
    for (let j = 0; j <= a.length; j++) matrix[0][j] = j;

    for (let i = 1; i <= b.length; i++) {
        for (let j = 1; j <= a.length; j++) {
            if (b.charAt(i - 1) === a.charAt(j - 1)) {
                matrix[i][j] = matrix[i - 1][j - 1];
            } else {
                matrix[i][j] = Math.min(
                    matrix[i - 1][j - 1] + 1, // substitution
                    matrix[i][j - 1] + 1,     // insertion
                    matrix[i - 1][j] + 1      // deletion
                );
            }
        }
    }
    return matrix[b.length][a.length];
}
