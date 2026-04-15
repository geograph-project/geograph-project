
    const snippetInput = document.getElementById('snippet-search');
    const suggestionsSnippets = document.getElementById('snippet-suggestions');
    const activeSnippetContainer = document.getElementById('active-snippets');
    let selectedSnippets = new Set(); // Use a Set to prevent duplicates

//    let debounceTimer = null;
    snippetInput.addEventListener('input', async (e) => {
        const query = e.target.value;
        if (query.length < 1) { suggestionsSnippets.innerHTML = ''; return; }

        //do need debounce
        if (debounceTimer) clearTimeout(debounceTimer);

        debounceTimer = setTimeout(async function() {
            let html = '';
            let normalizedResults = [];

            try {
        		//todo, take only the LAST component, if entered text includes a ;
                const response = await fetch(`/snippets.json.php?term=${encodeURIComponent(query)}&mode=ranked`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const results = await response.json(); // Expected: ["tag1", "tag2"]

                // Normalize results for comparison
                normalizedResults = results.map(t => t.toLowerCase());

                html = results.map(tag => {
                    // 1. Create a Case-Insensitive Regex of the user's query
                    const safeQuery = escapeRegex(query);
                    const regex = new RegExp(`(${safeQuery})`, "gi");
                    // 2. Replace the match with a bold version
                    // $1 keeps the original casing from the database (e.g., "Road" stays "Road")
                    const highlighted = escapeHTML(tag).replace(regex, "<strong>$1</strong>");
                    return `<div class="suggestion-item">${highlighted}</div>`;
                }).join('');

            } catch (error) {
                console.error("SD fetch failed:", error);
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

            suggestionsSnippets.innerHTML = html;
        }, 250);
    });
    // Handle clicking a suggestion
    suggestionsSnippets.addEventListener('click', (e) => {
        if (e.target.classList.contains('suggestion-item')) {
            addSnippet(e.target.dataset.snippet_id, e.target.dataset.title);
        }
    });

    snippetInput.addEventListener('focus', (e) => {
        // Wait a tiny bit for the mobile keyboard to fully animate up
        setTimeout(() => {
            const rect = snippetInput.getBoundingClientRect();
            const viewportHeight = window.innerHeight;

            // If the input is in the bottom 30% of the visible area
            if (rect.top > viewportHeight * 0.7) {
                let block = 'center'; // This puts it in the middle, not the top
                if (window.matchMedia("(max-height: 500px) and (orientation: landscape)").matches) {
                    block = 'start';
                }
                snippetInput.scrollIntoView({
                    behavior: 'smooth',
                    block: block
                });
            }
        }, 300);
    });

    function addSnippet(snippet_id, title) {

        if (!selectedSnippets.has(snippet_id)) {
            selectedSnippets.add(snippet_id);

            const span = document.createElement('span');
            span.className = 'tag-pill';

            // 1. Set text safely (avoids XSS and quote issues)
            span.textContent = title + ' ';

            // 2. Create the button as a real object
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = '&#10005;';

            // 3. Attach the remove logic directly to this specific button
            btn.onclick = function() {
                selectedSnippets.delete(snippet_id); // Remove from our Set
                span.remove();            // Remove the whole pill from DOM
            };

            // 4. Create the hidden input for the form POST
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'snippets[]';
            input.value = snippet_id;

            span.appendChild(btn);
            span.appendChild(input);
            activeTagsContainer.appendChild(span);
        }
        snippetInput.value = '';
        suggestionsSnippets.innerHTML = '';
    }

