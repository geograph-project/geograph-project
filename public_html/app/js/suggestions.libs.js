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
        });
    }

console.log('fetching', transferId, title);

    // 2. Construct the URL dynamically
    const baseUrl = '/app/mlp.json.php';

console.log(baseUrl, window.location.origin);

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

        // 4. Process the suggestions
        data.suggestions.forEach(suggestion => {
            if (suggestion.model === 'clip') {
                // If the model is 'clip', find matching options in the select dropdown and turn them blue
                if (selectElement) {
                    Array.from(selectElement.options).forEach(option => {
                        if (option.value === suggestion.label) {
                            option.style.color = 'blue';
                        }
                    });
                }
            } else if (suggestion.model === 'subjects') {
                // If the model is 'subjects', push it to the global array
                subjectSuggestions.push(suggestion);
            }
        });

    } catch (error) {
        console.error('Error fetching suggestions:', error);
    }
}
