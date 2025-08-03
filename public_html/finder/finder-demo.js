$(document).ready(function() {
    $('#search-button').on('click', function() {
        const query = $('#search-query').val().trim();
        const labelsStr = $('#search-labels').val().trim();

        if (!query || !labelsStr) {
            alert('Please provide both a search query and labels.');
            return;
        }

        const labels = labelsStr.split(',').map(s => s.trim()).filter(Boolean);
        if (labels.length === 0) {
            alert('Please provide valid, comma-separated labels.');
            return;
        }

        const $loading = $('#loading-indicator');
        const $resultsContainer = $('#results-container');

        $loading.show();
        $resultsContainer.empty();

        // Perform two AJAX requests in parallel
        $.when(
            // 1. Fetch vectors for the labels
            $.ajax({
                url: '/finder/label-vectors.json.php',
                method: 'GET',
                data: { labels: labels.join(',') },
                dataType: 'json'
            }),
            // 2. Fetch images for the search query
            $.ajax({
                url: '/api-facetql-vector.php',
                method: 'GET',
                // Request the vector ('image_vector') and other useful fields
                data: {
                    label: query,
                    select: 'id,hash,title,image_vector',
                    thumb: 1, // To get thumbnail URL components
                    limit: 50 // Get a reasonable number of images
                },
                dataType: 'json'
            })
        ).done(function(labelsResponse, imagesResponse) {
            const labelVectorsData = labelsResponse[0];
            const imageResults = imagesResponse[0];

            if (!labelVectorsData || !imageResults || !imageResults.rows) {
                $resultsContainer.html('<p>Error: Could not fetch data from the server.</p>');
                return;
            }

            // --- Classification Logic ---

            // 1. Create EmbeddingVector objects for each label
            const labelVectors = {};
            for (const label in labelVectorsData) {
                if (labelVectorsData[label]) {
                    try {
                        labelVectors[label] = new EmbeddingVector(labelVectorsData[label]);
                    } catch (e) {
                        console.error(`Could not create vector for label "${label}":`, e);
                    }
                }
            }

            if (Object.keys(labelVectors).length === 0) {
                $resultsContainer.html('<p>Could not process any of the provided labels.</p>');
                return;
            }

            // 2. Group images by their nearest label
            const groupedResults = {};
            labels.forEach(label => {
                groupedResults[label] = [];
            });

            imageResults.rows.forEach(function(image) {
                if (image.image_vector) {
                    try {
                        const imageVector = new EmbeddingVector(image.image_vector);

                        // Find the nearest label using KNN (with k=1)
                        const nearest = imageVector.knn(labelVectors, 1);

                        if (nearest.length > 0) {
                            const closestLabel = nearest[0].key;
                            groupedResults[closestLabel].push(image);
                        }
                    } catch(e) {
                        console.error(`Could not process vector for image ID ${image.id}:`, e);
                    }
                }
            });

            // --- Render the results (handled in the next step, but called here) ---
            renderResults(groupedResults);

        }).fail(function() {
            $resultsContainer.html('<p>An error occurred while fetching the search results. Please check the browser console for details.</p>');
        }).always(function() {
            $loading.hide();
        });
    });

    /**
     * Renders the grouped image results into the results container.
     * @param {Object} groupedResults - An object where keys are labels and values are arrays of image objects.
     */
    function renderResults(groupedResults) {
        const $resultsContainer = $('#results-container');
        $resultsContainer.empty();

        if (Object.values(groupedResults).every(arr => arr.length === 0)) {
            $resultsContainer.html('<p>No images could be classified. Try a different search query or labels.</p>');
            return;
        }

        for (const label in groupedResults) {
            const images = groupedResults[label];
            if (images.length > 0) {
                const $group = $('<div class="label-group"></div>');
                $group.append(`<h2>${escapeHtml(label)} (${images.length})</h2>`);

                const $imageContainer = $('<div class="image-container"></div>');
                images.forEach(function(image) {
                    const imageUrl = getGeographUrl(image.id, image.hash, 'med');
                    const $item = $(`
                        <div class="image-item">
                            <a href="https://www.geograph.org.uk/photo/${image.id}" target="_blank">
                                <img src="${imageUrl}" alt="${escapeHtml(image.title)}" title="${escapeHtml(image.title)}">
                            </a>
                            <p>${escapeHtml(image.title)}</p>
                        </div>
                    `);
                    $imageContainer.append($item);
                });

                $group.append($imageContainer);
                $resultsContainer.append($group);
            }
        }
    }

    /**
     * Constructs a Geograph image URL.
     * @param {number} gridimageId - The ID of the image.
     * @param {string} hash - The image hash.
     * @param {string} size - The desired size ('small', 'med', 'full').
     * @returns {string} The full image URL.
     */
    function getGeographUrl(gridimageId, hash, size = 'small') {
        const yz = String(Math.floor(gridimageId / 1000000)).padStart(2, '0');
        const ab = String(Math.floor((gridimageId % 1000000) / 10000)).padStart(2, '0');
        const cd = String(Math.floor((gridimageId % 10000) / 100)).padStart(2, '0');
        const abcdef = String(gridimageId).padStart(6, '0');

        let fullpath;
        if (yz === '00') {
            fullpath = `/photos/${ab}/${cd}/${abcdef}_${hash}`;
        } else {
            fullpath = `/geophotos/${yz}/${ab}/${cd}/${abcdef}_${hash}`;
        }

        const server = `https://s${gridimageId % 4}.geograph.org.uk`;

        switch (size) {
            case 'full': return `https://s0.geograph.org.uk${fullpath}.jpg`;
            case 'med': return `${server}${fullpath}_213x160.jpg`;
            case 'small':
            default: return `${server}${fullpath}_120x120.jpg`;
        }
    }

    /**
     * Escapes HTML special characters to prevent XSS.
     * @param {string} str - The string to escape.
     * @returns {string} The escaped string.
     */
    function escapeHtml(str) {
        if (!str) return '';
        return $('<div>').text(str).html();
    }
});
