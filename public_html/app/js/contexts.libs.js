
    async function loadContexts() {
        const select = document.getElementById('contexts');
        const fragment = document.createDocumentFragment();

        // 1. Add "Recently Used"
        const recent = getRecent('submit.contexts');
        if (recent.length > 0) {
            const group = document.createElement('optgroup');
            group.label = 'Recently Used';
            recent.forEach(tag => {
                if (tag.match(/[\*~]$/)) //these shouldnt of been saved, but a few early testers saved a few rows
                    return;
                const opt = new Option(tag, tag);
                group.appendChild(opt);
            });
            fragment.appendChild(group);
        }

        // 2. Fetch Remote Data
        try {
            const response = await fetch("https://www.geograph.org.uk/tags/primary.json.php");
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            let currentGroup = null;
            data.forEach(item => {
                if (item.grouping !== currentGroup) {
                    currentGroup = item.grouping;
                    const group = document.createElement('optgroup');
                    group.label = currentGroup;
                    fragment.appendChild(group);
                }
                fragment.lastChild.appendChild(new Option(item.tag, item.tag));
            });
        } catch (e) { console.error("Failed to load tags", e); }

        select.appendChild(fragment);

        select.addEventListener('input', e => {
            const selectedOptions = Array.from(select.options).filter(o => o.selected);
            const uniqueValues = new Set(selectedOptions.map(o => o.value));
            const selectedCount = uniqueValues.size;
            if (selectedCount>6)
                 document.getElementById('context-count').textContent = `${selectedCount} is TOO MANY`;
            else
                document.getElementById('context-count').textContent = `${selectedCount} selected`;
            select.classList.toggle('input-invalid', (selectedCount == 0 || selectedCount>6));
        });
    }
