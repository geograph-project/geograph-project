var subjectSuggestions = [];

/**
 * Fetches suggestions from the Geograph API and processes them.
 * @param {string} transferId - The unique transfer ID.
 * @param {string} [title] - Optional title to include in the API request.
 */
async function fetchAndProcessSuggestions(transferId, title = '') {
    // 1. Reset the dropdown options first
    const selectElement = document.getElementById('contexts');
    if (selectElement) {
        Array.from(selectElement.options).forEach(option => {
            option.style.color = ''; // Resets to default browser color
	    if (option.value.endsWith('~')) {
                // Slice off the trailing marker to restore the original clean value
                option.value = option.value.slice(0, -1);
            }
        });
    }
    // and clear the subject list
    subjectSuggestions = [];

    if (!transferId)
	return;

    // 2. Construct the URL dynamically
    const baseUrl = '/app/mlp.json.php';
    const url = new URL(baseUrl, window.location.origin);
    url.searchParams.append('transfer_id', transferId);

    if (title) {
        url.searchParams.append('title', title);
    }

    try {
        // 3. Make the API request
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        // Safety check to ensure suggestions exist in the response
        if (!data.suggestions || !Array.isArray(data.suggestions)) {
            return;
        }

        const subjectInput = document.getElementById('subject-input');

        // 4. Process the suggestions
        data.suggestions.forEach(suggestion => {
            if (suggestion.model === 'clip') {
                // If the model is 'clip', find matching options in the select dropdown and turn them blue
                if (selectElement) {
                    Array.from(selectElement.options).forEach(option => {
                        if (option.value === suggestion.label) {
                            option.style.color = '#0000CC';
			    if (!option.selected && !option.value.endsWith('~')) {
			        option.value += '~'; //so we know it 'primed'
			    }
                        }
                    });
                }
            } else if (suggestion.model === 'subjects') {
                // If the model is 'subjects', push it to the global array (only renders when click to focus)
                subjectSuggestions.push(suggestion);

		if (subjectInput && subjectInput.placeholder == 'Search subjects...')
			subjectInput.placeholder = "Search subjects... (or click to view suggestions)";
            } //else if (suggestion.model === 'types') {
		//todo, store non-geo flag, so can suppress provisional points
        });

    } catch (error) {
        console.error('Error fetching suggestions:', error);
    }
}
