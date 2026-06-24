
/**
 * Fetches suggestions from the Geograph API and processes them for the checkbox/select UI.
 * @param {string} transferId - The unique transfer ID.
 * @param {string} [title] - Optional title to include in the API request.
 */
async function fetchAndProcessSuggestions(transferId, title = '') {

    // 1. Reset Context Checkboxes (Targeting the parent labels)
    // Find all labels inside containers with class 'plist'
    const contextLabels = document.querySelectorAll('.plist label');
    contextLabels.forEach(label => {
        label.style.color = ''; // Reset to default browser/CSS color

        // Find the input inside this label to strip out previous tracking markers
        const checkbox = label.querySelector('input[type="checkbox"]');
        if (checkbox && checkbox.value.endsWith('~')) {
            // Slice off the trailing mark to restore the original clean value
            checkbox.value = checkbox.value.slice(0, -1);
        }
    });

    // 2. Clear out any previous AI Suggestions optgroup from the subject dropdown
    const subjectSelect = document.getElementById('subject');
    if (subjectSelect) {
        const oldGroup = subjectSelect.querySelector('optgroup[data-type="ai-suggestions"]');
        if (oldGroup) {
            oldGroup.remove();
        }
    }

    // 3. Construct the URL dynamically using the relative path fix
    const baseUrl = '/app/mlp.json.php';
    const url = new URL(baseUrl, window.location.origin);
    url.searchParams.append('transfer_id', transferId);

    if (title) {
        url.searchParams.append('title', title);
    }

    try {
        // 4. Make the API request
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        if (!data.suggestions || !Array.isArray(data.suggestions)) {
            return;
        }

        // Create an optgroup element to hold our incoming subject suggestions
        let aiOptGroup = null;
        let topSuggestion = null;

        // 5. Process the suggestions
        data.suggestions.forEach(suggestion => {

            if (suggestion.model === 'clip') {
                // Look for the checkbox by its value attribute (matching "top:Label Name")
                const targetValue = `top:${suggestion.label}`;
                const checkbox = document.querySelector(`.plist input[value="${targetValue}"]`);

                if (checkbox) {
                    //mark the value, so we know it a 'suggested' tag!
                    if (!checkbox.checked) //but dont mark if already checked!
			    checkbox.value += '~'; //so know was 'primed'
                    // Find its closest parent label element and turn its text blue
                    const label = checkbox.closest('label');
                    if (label) {
                        label.style.color = '#0000CC';
                    }
                }

            } else if (suggestion.model === 'subjects' && subjectSelect) {
                // Initialize the optgroup if we haven't yet
                if (!aiOptGroup) {
                    aiOptGroup = document.createElement('optgroup');
                    aiOptGroup.label = 'Suggestions';
                    aiOptGroup.setAttribute('data-type', 'ai-suggestions');
                }

                // Create a new option element for this suggestion
                const option = document.createElement('option');
                option.value = suggestion.label+"*"; // so we know it a 'suggested' tag!

        		//save the first!
                if (!topSuggestion && suggestion.score > 0.2) topSuggestion = suggestion.label;

		//add the middot, so doesnt interfere with typing
                option.textContent = `\u00B7 ${suggestion.label} (${Math.round(suggestion.score * 100)}%)`;

                aiOptGroup.appendChild(option);
            }
        });

        // 6. Prepend the new optgroup right after the initial "select..." options
        if (aiOptGroup && subjectSelect) {
            // Inserts right after the very first element child (usually the placeholder "select...")
            if (subjectSelect.firstElementChild) {
                subjectSelect.insertBefore(aiOptGroup, subjectSelect.firstElementChild.nextSibling);
            } else {
                subjectSelect.appendChild(aiOptGroup);
            }

            if (topSuggestion && subjectSelect.selectedIndex !== -1) {
		//find the blank option, not nesserially the first, the dropdown can contain a empty subject which gets 'selected' as current value.
                const currentOption = subjectSelect.options[subjectSelect.selectedIndex];

                // Double-check it's an empty option so we don't overwrite an existing selection
                if (currentOption.value === "") {
                    const updatedText = `\u00B7 select subject... (top suggestion: ${topSuggestion})`;

                    currentOption.textContent = updatedText;

                    if (currentOption.hasAttribute('label')) {
                        currentOption.setAttribute('label', updatedText);
                    }
                }
            }

            subjectSelect.addEventListener('change', function() {
                // 1. Restore the original text (with percentages) for all options first
                Array.from(this.options).forEach(opt => {
                    if (opt.dataset.originalText) {
                        opt.textContent = opt.dataset.originalText;
                    }
                });

                // 2. Get the currently selected option
                const selectedOption = this.options[this.selectedIndex];
                if (!selectedOption) return;

                // 3. If it has a percentage, strip it for the display
                if (selectedOption.textContent.match(/\(\d+[\.\d]*%\)/)) {
                    // Save the original text so we can restore it later if they change their mind
                    if (!selectedOption.dataset.originalText) {
                        selectedOption.dataset.originalText = selectedOption.textContent;
                    }
                    
                    // Strip the trailing space and percentage (e.g., " " followed by "(85%)")
                    selectedOption.textContent = selectedOption.textContent.replace(/\s+\(\d+[\.\d]*%\)/, '').replace(/^\s*\u00B7\s*/, '');
                }
            });
        }

    } catch (error) {
        console.error('Error fetching suggestions:', error);
    }
}
