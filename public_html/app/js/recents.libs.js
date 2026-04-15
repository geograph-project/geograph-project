
    function renderRecent(storageKey, elementId) {
        const dropdown = document.getElementById(elementId);
        const items = getRecent(storageKey); // Using your existing getRecent logic

        if (!items || items.length === 0) {
            dropdown.style.display = 'none';
            // Check if we already added the optional label to prevent duplicates
            if (!dropdown.parentElement.querySelector('.optional-label')) {
                const span = document.createElement('span');
                span.className = 'optional-label';
                span.textContent = '(optional)';
                // Style it to match your "Recent" dropdown's aesthetic
                dropdown.parentElement.appendChild(span);
            }
            return;
        }

        // Clear existing
        dropdown.innerHTML = '<option value="">Recently Used</option>';
        items.forEach(item => {
            const opt = document.createElement('option');
	    if (item.indexOf(':::') == -1) {
	            opt.value = item;
        	    opt.textContent = item;
	    } else {
		const bits = item.split(':::');
	        opt.value = bits[0];
        	opt.textContent = bits[1];
	    }
            dropdown.appendChild(opt);
        });
    }

    // When a Recent Subject is picked
    function useRecentSubject(select) {
        if (!select.value) return;
        const input = document.getElementById('subject-input');
        input.value = select.value;
        select.options[select.selectedIndex].style.color = 'silver';
        select.value = ""; // Reset dropdown
        document.getElementById('subject-suggestions').innerHTML = ''; //just in case had search open!

	if (typeof updateFormProgress == 'function')
	    updateFormProgress();
    }

    // When a Recent Tag is picked
    function useRecentTag(select) {
        if (!select.value) return;
        addTag(select.value); //automatically clears suggestions
        select.options[select.selectedIndex].style.color = 'silver';
        select.value = ""; // Reset dropdown

	if (typeof updateFormProgress == 'function')
	    updateFormProgress();
    }

    // When a Recent Snippet is picked
    function useRecentSnippet(select) {
        if (!select.value) return;
        addSnippet(select.value, select.options[select.selectedIndex].textContent); //automatically clears suggestions
        select.options[select.selectedIndex].style.color = 'silver';
        select.value = ""; // Reset dropdown

	if (typeof updateFormProgress == 'function')
	    updateFormProgress();
    }

    //these with the existing format used by old submission

    const getRecent = (key) => {
        const raw = localStorage.getItem(key);
        if (!raw) return [];

        let lines = [];
        try {
            // Handle the JSON double-encoding
            const decoded = JSON.parse(raw);
            lines = decoded.split('\n');
        } catch (e) {
            // Fallback for plain string format
            lines = raw.split('\n');
        }

        const entries = lines
            .map(line => {
                const [timestamp, tag] = line.split('|');
                return { tag, ts: parseInt(timestamp, 10) };
            })
            .filter(e => e.tag && !isNaN(e.ts));

        // Return tags sorted by timestamp descending
        return entries
            .sort((a, b) => b.ts - a.ts)
            .map(e => e.tag);
    };

    const saveRecent = (key, selectedTags) => {
        // 1. Get current entries as a Map (tag -> timestamp)
        const raw = localStorage.getItem(key);
        let existingEntries = {};

        try {
            const decoded = JSON.parse(raw);
            decoded.split('\n').forEach(line => {
                const [ts, tag] = line.split('|');
                if (tag) existingEntries[tag] = parseInt(ts, 10);
            });
        } catch(e) {}

        // 2. Update with new selections (overwrite/set to NOW)
        selectedTags.forEach(tag => {
            existingEntries[tag] = Date.now();
        });

        // 3. Convert back to "timestamp|tag" lines
        const lines = Object.entries(existingEntries)
            .map(([tag, ts]) => `${ts}|${tag}`);

        // 4. Double encode: join with \n, then JSON.stringify
        localStorage.setItem(key, JSON.stringify(lines.join('\n')));
    };

