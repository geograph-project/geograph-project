
    const subjectInput = document.getElementById('subject-input');
    const subjectList  = document.getElementById('subject-list');
    const subjectSugg = document.getElementById('subject-suggestions');

    async function loadSubjects() {
        try {
            const response = await fetch("/tags/subject.json.php?v=2");
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

            const data = await response.json();

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.tag; // This is what the user sees/types
                option.dataset.id = item.tag_id; // Store the ID for the form
                option.dataset.count = parseInt(item.count,10);
                subjectList.appendChild(option);
            });
        } catch (error) {
            console.error("Tag fetch failed:", error);
        }
    }

    subjectInput.addEventListener('input', () => {
        if (subjectList.options.length==0) {
            subjectSugg.innerHTML = '<div class="suggestion-item">No Suggestions Available</div>';
            return;
        }

        const query = subjectInput.value.trim().toLowerCase();

        if (query.length < 1) {
            subjectSugg.innerHTML = '';
            return;
        }

        // 1. Get all options from your hidden datalist
        const options = Array.from(subjectList.options);

        // 2. Filter and Score
        let matches = options
            .map(opt => {
                const val = opt.value.toLowerCase();
                let score = 0;
                if (val === query) score = 3; // Perfect match
                else if (val.startsWith(query)) score = 2; // Priority 1: Starts with
                else if (val.includes(query)) score = 1; // Priority 2: Contains
                return { val: opt.value, id: opt.dataset.id, score };
            })
            .filter(match => match.score > 0)
            .sort((a, b) => b.score - a.score); // Higher score first

        if (matches.length === 0) {
            const query_safe = escapeHTML(cleanTag(query));

            // Only show the "Add" pill if the query isn't empty
            subjectSugg.innerHTML = `
                <div class="suggestion-item error-text">No matching subjects found</div>
		${(query_safe.length > 2 && query_safe !== 'blank') ? `
                <div class="suggestion-item add-new-tag" data-tag="${query_safe}">
                    <span class="plus-icon">+</span> Add [<strong>${query_safe}</strong>] as a Tag
                </div>`:''}
            `;
            return;
        }

        // Optimized: If we have exactly one perfect match, clear the suggestions
        if (matches.length === 1 && matches[0].score === 3) {
            subjectInput.setCustomValidity("");
            subjectSugg.innerHTML = '';
            return;
        }

        // If there are many matches, trim!
        if (matches.length > 100) {
            // 1. Split by relevance
            const highRelevance = matches.filter(m => m.score >= 2); // StartsWith
            const lowRelevance = matches.filter(m => m.score === 1);  // Contains

            // 2. Keep ALL high relevance, but limit low relevance
            const limitedLowRelevance = lowRelevance.slice(0, 50);

            matches = [...highRelevance, ...limitedLowRelevance];

            // Add a visual indicator to the list so the user knows they should keep typing
            if (lowRelevance.length > 50) {
                matches.push({
                    val: `...and ${lowRelevance.length - 50} more. Keep typing!`,
                    id: null,
                    isHint: true
                });
            }
        }

	const safeQuery = escapeRegex(query);
	const regex = new RegExp(`(${safeQuery})(?!(?:[^&;]+;))`, "gi");

        // 3. Render
        subjectSugg.innerHTML = matches.map(m => {
                    // 2. Replace the match with a bold version
                    const highlighted = escapeHTML(m.val).toTitleCase().replace(regex, "<strong>$1</strong>");
                    return `<div class="suggestion-item" data-id="${m.id}">${highlighted}</div>`;
        }).join('');
    });

    // 4. Click handling: Update the input and clear dropdown
    subjectSugg.addEventListener('click', (e) => {
        const item = e.target.closest('.suggestion-item');
        if (item) {
            if (item.dataset.tag) {
		addTag(item.dataset.tag);
                subjectInput.value = ''; //its been added as tag instead!
                subjectInput.placeholder = 'Type to search subjects...';

            } else if (item.dataset.id) { //useful to avoid error messages, even dont use id!
                subjectInput.value = item.textContent;
                document.getElementById('subject-id').value = item.dataset.id;

		if (typeof updateFormProgress == 'function')
		    updateFormProgress();
            }
            subjectInput.setCustomValidity("");
            subjectSugg.innerHTML = '';
        }
    });

    subjectInput.addEventListener('focus', (e) => {
        if (subjectList.options.length==0) {
            subjectSugg.innerHTML = '<div class="suggestion-item">No Suggestions Available</div>';
            return;
        }
        const query = subjectInput.value.trim().toLowerCase();
        if (query.length < 1) {
            subjectInput.placeholder = 'Start typing... (showing popular subjects)';
            subjectInput.setCustomValidity("");
            const options = Array.from(subjectList.options);

            const matches = options
            .map(opt => {
                const val = opt.value.toLowerCase();
                return { val: opt.value, id: opt.dataset.id, count: opt.dataset.count };
            })
            .sort((a, b) => b.count - a.count) // Higher score first
            .slice(0, 25);

            subjectSugg.innerHTML = matches.map(m => {
                return `<div class="suggestion-item" data-id="${m.id}">${escapeHTML(m.val).toTitleCase()}</div>`
            }).join('');
        } else {
            subjectInput.placeholder = 'Type to search subjects...'; //probably wont be seen, but resets the default above!
        }

        // Wait a tiny bit for the mobile keyboard to fully animate up
        setTimeout(() => {
            const rect = subjectInput.getBoundingClientRect();
            const viewportHeight = window.innerHeight;


            // If the input is in the bottom 30% of the visible area
            if (rect.top > viewportHeight * 0.7) {
                let block = 'center'; // This puts it in the middle, not the top
                if (window.matchMedia("(max-height: 500px) and (orientation: landscape)").matches) {
                    block = 'start';
                }
                subjectInput.scrollIntoView({
                    behavior: 'smooth',
                    block: block
                });
            }
        }, 300);
    });

    subjectInput.addEventListener('change', () => {
        const options = document.querySelectorAll('#subject-list option');
        const match = Array.from(options).find(o => o.value === subjectInput.value);

        //this is only added during submit (if needed), but need to clear it!
        if (match) {
            subjectInput.setCustomValidity("");
        }
    });

