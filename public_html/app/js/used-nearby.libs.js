
function showNearbyTagsModalWrapper() {
    const form = document.forms['theForm'];
    if (!form) return;

    const gridRef = form.elements['grid_reference'].value;
    if (!gridRef) {
        alert("Please enter a Grid Reference first.");
        return;
    }

    const usedNames = [];
    const usedIds = [];

    // 1. Get selected Contexts from the <select multiple>
    const contextSelect = document.getElementById('contexts');
    if (contextSelect) {
        Array.from(contextSelect.options).forEach(opt => {
            if (opt.selected) usedNames.push(opt.value);
        });
    }

    // 2. Get Tags from hidden inputs or checkboxes
    // form.elements["tags[]"] can be a single element or a collection
    const tagElements = form.elements["tags[]"];
    if (tagElements) {
        // Force to array to handle both single elements and NodeLists safely
        const elementsArray = tagElements.length === undefined ? [tagElements] : Array.from(tagElements);
        elementsArray.forEach(item => {
            if (item.type === 'hidden') {
                usedNames.push(item.value);
            } else if (item.type === 'checkbox' && item.checked) {
                usedNames.push(item.value);
            } else if (item.type === 'select-one' && item.value) {
                usedNames.push(item.value);
            }
        });
    }

    // 3. Get the current Subject value
    const currentSubject = form.elements['subject'].value;

    // 4. Fire the Modal
    showNearbyTagsModal(gridRef, usedNames, usedIds, currentSubject);
}


async function showNearbyTagsModal(gridRef, usedNames = [], usedIds = [], currentSubject = null) {
    try {
        const response = await fetch(`/finder/used-nearby.json.php?gr=${gridRef}`);
        const rawData = await response.json();

        // 1. Flatten and Sort Data
        // Distance keys might be strings ("1") or indices. We sort them numerically.
        const sortedDistances = Object.entries(rawData).sort((a, b) => Number(a[0]) - Number(b[0]));
        const attributeOrder = ['context', 'subject', 'tag', 'snippet'];

        const fragment = document.createDocumentFragment();
        const seenIds = new Set();

        // Create the dialog
        const dialog = document.createElement('dialog');
        dialog.id = 'tag-selector-modal';
        dialog.innerHTML = `
            <div class="tag-modal-floating-note">
                Few may apply; pick only what fits.
            </div>
            <form method="dialog">
                <div class="tag-modal-header">
                    <strong>Nearby Tags</strong>
                    <button type="submit" value="cancel">CLOSE</button>
                </div>
                <div class="tag-list-scroll"></div>
                <div class="tag-modal-footer">
                    <button class="btn btn-primary" type="button" id="confirm-selection">Apply Selection</button>
                </div>
            </form>
        `;
        const listContainer = dialog.querySelector('.tag-list-scroll');

        // 2. Process and Render
        sortedDistances.forEach(([dist, attributes]) => {
            attributeOrder.forEach(attr => {
                if (attributes[attr]) {
                    attributes[attr].forEach(item => {
                        // Deduplicate by the 'group' ID
                        if (seenIds.has(`${attr}/${item.group}`)) return;
                        seenIds.add(`${attr}/${item.group}`);

                        const row = document.createElement('label');
                        row.className = `tag-item-row attr-${attr}`;

                        // Use radio for subject (to help UI logic), checkbox for others
                        const inputType = (attr === 'subject') ? 'radio' : 'checkbox';
                        const name = (attr === 'subject') ? 'used_subject' : `used_ids[${item.group}]`; //need a consisten name for radio

                        // Check if this item should be pre-selected
                        let isChecked = false;
                        let prefix = '';
                        let previewBtn = '';
                        if (attr === 'snippet') {
                            prefix = 'SD';
                            previewBtn = `<a href="/snippet/${item.group}" target="_blank" class="preview-link" title="Preview" style="text-decoration:none; margin-left:5px;">&#x1f441;</a>`;
                        }
                        if (attr === 'subject') {
                            if (currentSubject && item.label.toLowerCase() === currentSubject.toLowerCase())
                                isChecked = true;
                            prefix = 'subject';
                        } else {
                            // Match others by ID or Name
                            const matchId = usedIds.includes(item.group) || usedIds.includes(Number(item.group));
                            const matchName = usedNames.some(n => n.toLowerCase() === item.label.toLowerCase());
                            if (matchId || matchName) isChecked = true;
                            if (attr === 'context') prefix = 'top';
                        }

                        let distStr = item.dist ? (item.dist / 1000).toFixed(1) : dist;
                        row.innerHTML = `
                            <input type="${inputType}" name="${name}" value="${item.group}" data-label="${item.label}" data-type="${attr}" ${isChecked ? 'checked' : ''}>
                            <span class="tag-dist">${distStr}km</span>
                            <!--span class="tag-attr-type">${attr.substr(0,1).toUpperCase()}</span-->
                            <span class="tag-label-text">${prefix?`<span class=prefix>${prefix}:</span>`:''}${item.label}</span>
                            ${previewBtn}
                            <span class="tag-count">${item.count}</span>
                        `;

                        // Subject logic: Handle the "Already selected" confirmation
                        if (attr === 'subject') {
                            const input = row.querySelector('input');
                            input.addEventListener('click', (e) => {
                                if (currentSubject && currentSubject.toLowerCase() !== item.label.toLowerCase()) {
                                    const confirmChange = confirm(`Already selected subject '${currentSubject}', are you sure you wish to change to '${item.label}'?`);
                                    if (!confirmChange) {
                                        e.preventDefault();
                                        return;
                                    }
                                }
                                currentSubject = item.label;
                            });
                        }

                        listContainer.appendChild(row);
                    });
                }
            });
        });

        document.body.appendChild(dialog);
        dialog.showModal();

        // Handle Completion
        dialog.querySelector('#confirm-selection').addEventListener('click', () => {
            dialog.querySelectorAll('input:checked').forEach(i => {
                const type = i.dataset.type;
                const label = i.dataset.label;
                const id = i.value;

                if (type === 'context') {
                    const select = document.getElementById('contexts'); // Your <select multiple>
                    if (select) {
                        // Look for the option where the value matches the label text
                        Array.from(select.options).forEach(opt => {
                            if (opt.value === label) {
                                opt.selected = true;
                            }
                        });
                        // Trigger change event so other scripts know the select updated
                        select.dispatchEvent(new Event('input')); //the app uses, input not change
                    }
                } else if (type === 'subject') {
                    const subjectInput = document.getElementById('subject-input');
                    if (subjectInput) {
                        subjectInput.value = label;
                        subjectInput.dispatchEvent(new Event('input'));
                    }
                } else if (type === 'tag') {
                    // Check if addTag function exists globally
                    if (typeof addTag === 'function') {
                        addTag(label);
                    }
                } else if (type === 'snippet') {
                    // Check if addSnippet function exists globally
                    if (typeof addSnippet === 'function') {
                        addSnippet(id, label);
                    }
                }
            });

            if (typeof updateFormProgress == 'function')
                updateFormProgress();

            dialog.close();
            dialog.remove(); // Clean up DOM
        });

        // Cleanup on native close (Escape key)
        dialog.addEventListener('close', () => dialog.remove());

        dialog.addEventListener('click', (event) => {
            // If the click target is the dialog itself (the backdrop), close it.
            // Clicks on the form or internal divs will have a different event.target.
            if (event.target === dialog) {
                dialog.close();
            }
        });

    } catch (err) {
        console.error("Failed to fetch tags", err);
    }
}
