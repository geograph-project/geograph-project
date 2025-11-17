function runSearch() {
    const query = $('#search-query').val().trim();
    const mode = $('input[name="mode"]:checked').val();
    const groupByPlace = $('#group-by-place').is(':checked');
    const model = $('input[name="model"]:checked').val();

    if (!query) {
        alert('Please provide a search query.');
        return;
    }

    const $loading = $('#loading-indicator');
    const $resultsContainer = $('#results-container');
    $loading.show();
    $resultsContainer.empty();

    let paramname = $('input[name=type]:checked').val();

    if (mode === 'classify') {
        const labelsStr = $('#search-labels').val().trim();
        if (!labelsStr) {
            alert('Please provide classification labels.');
            $loading.hide();
            return;
        }
        const labels = labelsStr.split(',').map(s => s.trim()).filter(Boolean);
        if (labels.length === 0) {
            alert('Please provide valid, comma-separated labels.');
            $loading.hide();
            return;
        }

        // --- Classification Mode ---
        $.when(
            $.ajax({
                url: '/finder/label-vectors.json.php',
                method: 'GET',
                data: {
                    labels: labels.join(','),
                    model: model
                },
                dataType: 'json'
            }),
            $.ajax({
                url: '/api-facetql-vector.php',
                method: 'GET',
                data: {
                    [paramname]: query,
                    select: 'id,hash,grid_reference,realname,title,image_vector,place',
                    long: 1,
                    utf: 1,
                    limit: (paramname == 'match') ? 100 : 30,
                    model: model
                },
                dataType: 'json'
            })
        ).done(function(labelsResponse, imagesResponse) {
            const labelVectorsData = labelsResponse[0];
            const imageResults = imagesResponse[0];

            // --- Classification Logic ---
            // (The existing classification logic goes here)
            classifyImages(labelVectorsData, imageResults, labels, groupByPlace, $resultsContainer);

        }).fail(function() {
            $resultsContainer.html('<p>An error occurred during classification. Please check the console.</p>');
        }).always(function() {
            $loading.hide();
        });

    } else if (mode === 'cluster') {
        const numClusters = parseInt($('#num-clusters').val(), 10);
        if (isNaN(numClusters) || numClusters < 2) {
            alert('Please enter a valid number of clusters (at least 2).');
            $loading.hide();
            return;
        } else if (numClusters > 100) {
            alert('100 is the maxiumn number of clusters.');
            $loading.hide();
            return;
        }

        // --- Clustering Mode ---
        $.ajax({
            url: '/api-facetql-vector.php',
            method: 'GET',
            data: {
                [paramname]: query,
                select: 'id,hash,grid_reference,realname,title,image_vector,place',
                long: 1,
                utf: 1,
                limit: 100,
                model: model
            },
            dataType: 'json'
        }).done(function(imageResults) {
            if (!imageResults || !imageResults.rows || imageResults.rows.length === 0) {
                $resultsContainer.html('<p>No images found for the given query.</p>');
                return;
            }

            const imageVectors = [];
            const imageData = [];
            const unprocessed = [];

            imageResults.rows.forEach(function(image) {
                if (image.image_vector) {
                    try {
                        imageVectors.push(new EmbeddingVector(image.image_vector).normalize());
                        imageData.push(image);
                    } catch (e) {
                        console.error(`Could not process vector for image ID ${image.id}:`, e);
                        unprocessed.push(image);
                    }
                } else {
                    unprocessed.push(image);
                }
            });

            if (imageVectors.length < numClusters) {
                $resultsContainer.html('<p>Not enough images with vector data to perform clustering. Try a broader search query.</p>');
                return;
            }

            // Perform K-means clustering
            const clusters = EmbeddingVector.kmeans(imageVectors, numClusters);

            // Group images based on cluster results
            const groupedResults = {};
            clusters.forEach((cluster, i) => {
                const clusterName = `Cluster #${i + 1}`;
                groupedResults[clusterName] = cluster.indices.map(index => imageData[index]);
            });

            const sortedGroups = Object.entries(groupedResults);
            sortedGroups.sort((a, b) => b[1].length - a[1].length);

            if (unprocessed.length) {
                sortedGroups.push(['Unprocessed', unprocessed]);
            }

            render1DResults(sortedGroups);

        }).fail(function() {
            $resultsContainer.html('<p>An error occurred while fetching data for clustering. Please check the console.</p>');
        }).always(function() {
            $loading.hide();
        });
    }
}

function classifyImages(labelVectorsData, imageResults, labels, groupByPlace, $resultsContainer) {
    // 1. Create EmbeddingVector objects for each label
    const labelVectors = {};
    for (const label in labelVectorsData) {
        if (labelVectorsData[label]) {
            try {
                labelVectors[label] = new EmbeddingVector(labelVectorsData[label]).normalize();
            } catch (e) {
                console.error(`Could not create vector for label "${label}":`, e);
            }
        }
    }

    if (Object.keys(labelVectors).length === 0) {
        $resultsContainer.html('<p>Could not process any of the provided labels.</p>');
        return;
    }

    // 2. Group images by their nearest label (and optionally by place)
    if (groupByPlace) {
        const groupedResults2D = {};
        const places = new Set();
        const unprocessed = [];

        labels.forEach(label => {
            groupedResults2D[label] = {};
        });

        imageResults.rows.forEach(function(image) {
            if (image.image_vector) {
                try {
                    const imageVector = new EmbeddingVector(image.image_vector).normalize();
                    const nearest = imageVector.knn(labelVectors, 1);

                    if (nearest.length > 0) {
                        const closestLabel = nearest[0].key;
                        const place = image.place || 'Unknown';
                        places.add(place);

                        if (!groupedResults2D[closestLabel][place]) {
                            groupedResults2D[closestLabel][place] = [];
                        }
                        groupedResults2D[closestLabel][place].push(image);
                    }
                } catch(e) {
                    console.error(`Could not process vector for image ID ${image.id}:`, e);
                }
            } else {
                unprocessed.push(image);
            }
        });

        const sortedPlaces = Array.from(places).sort();
        render2DResults(groupedResults2D, sortedPlaces, labels, unprocessed);

    } else {
        const groupedResults = {};
        labels.forEach(label => {
            groupedResults[label] = [];
        });
        const unprocessed = [];

        imageResults.rows.forEach(function(image) {
            if (image.image_vector) {
                try {
                    const imageVector = new EmbeddingVector(image.image_vector).normalize();
                    const nearest = imageVector.knn(labelVectors, 1);

                    if (nearest.length > 0) {
                        const closestLabel = nearest[0].key;
                        groupedResults[closestLabel].push(image);
                    }
                } catch(e) {
                    console.error(`Could not process vector for image ID ${image.id}:`, e);
                }
            } else {
                unprocessed.push(image);
            }
        });

        const sortedGroups = Object.entries(groupedResults);
        sortedGroups.sort((a, b) => b[1].length - a[1].length);

        if (unprocessed.length) {
            sortedGroups.push(['unprocessed', unprocessed]);
        }
        render1DResults(sortedGroups);
    }
}


    /**
     * Renders the grouped image results into the results container for 1D view.
     * @param {Array} sortedResults - sorted list of labels and images
     */
    function render1DResults(sortedResults) {
        const $resultsContainer = $('#results-container');
        $resultsContainer.empty();

        if (Object.values(sortedResults).every(([label, images]) => images.length === 0)) {
            $resultsContainer.html('<p>No images could be classified. Try a different search query or labels.</p>');
            return;
        }

	sortedResults.forEach(([label, images]) => {
            if (images.length > 0) {
                const $group = $('<div class="label-group"></div>');
                $group.append(`<h2>${escapeHtml(label)} (${images.length} images)</h2>`);

                const $imageContainer = $('<div class="image-container"></div>');
                images.forEach(function(image) {
                    const imageUrl = getGeographUrl(image.id, image.hash, 'med');
                    const $item = $(`
                        <div class="image-item">
                            <a href="https://www.geograph.org.uk/photo/${image.id}" target="_blank" title="${image.grid_reference} ${escapeHtml(image.title)} by ${escapeHtml(image.realname)}">
                                <img src="${imageUrl}" alt="${escapeHtml(image.title)}" loading="lazy">
                            </a>
                            <p>${escapeHtml(image.title)}</p>
                        </div>
                    `);
                    $imageContainer.append($item);
                });

                $group.append($imageContainer);
                $resultsContainer.append($group);
            }
        });
    }

    /**
     * Renders the grouped image results into the results container for 2D view.
     * @param {Object} groupedResults - The results grouped by label and then by place.
     * @param {Array} places - An array of unique, sorted place names.
     * @param {Array} labels - An array of the original labels.
     * @param {Array} unprocessed - An array of images that could not be processed.
     */
    function render2DResults(groupedResults, places, labels, unprocessed) {
        const $resultsContainer = $('#results-container');
        $resultsContainer.empty();

        let totalClassifiedImages = 0;
        labels.forEach(label => {
            places.forEach(place => {
                if (groupedResults[label][place]) {
                    totalClassifiedImages += groupedResults[label][place].length;
                }
            });
        });

        if (totalClassifiedImages === 0) {
            $resultsContainer.html('<p>No images could be classified into places. Try a different search query or labels.</p>');
        } else {
            const $table = $('<table border="1" style="border-collapse: collapse; margin-top: 20px; width: 100%; position:relative;"></table>');
            const $thead = $('<thead></thead>');
            const $tbody = $('<tbody></tbody>');

            // Header row
            const $headerRow = $('<tr style="position:sticky;top:0;background-color:#eee"></tr>');
            $headerRow.append('<th style="width: 100px;">Label</th>');
            places.forEach(place => {
                $headerRow.append(`<th style="vertical-align: top; padding: 5px">${escapeHtml(place)}</th>`);
            });
            $thead.append($headerRow);

            // Body rows
            labels.forEach(label => {
                const $row = $('<tr></tr>');
                $row.append(`<td style="vertical-align: top; padding: 8px;background-color:#eee"><b>${escapeHtml(label)}</b></td>`);

                places.forEach(place => {
                    const $cell = $('<td style="vertical-align: top; padding: 5px;"></td>');
                    const images = groupedResults[label][place];

                    if (images && images.length > 0) {
                        const $imageContainer = $('<div class="image-container" style="flex-wrap: wrap;"></div>');
                        images.forEach(image => {
                            const imageUrl = getGeographUrl(image.id, image.hash, 'med');
                            const $item = $(`
                                <div class="image-item">
                                    <a href="https://www.geograph.org.uk/photo/${image.id}" target="_blank" title="${image.grid_reference} ${escapeHtml(image.title)} by ${escapeHtml(image.realname)}">
                                        <img src="${imageUrl}" alt="${escapeHtml(image.title)}" loading="lazy">
                                    </a>
                                </div>
                            `);
                            $imageContainer.append($item);
                        });
                        $cell.append($imageContainer);
                    }
                    $row.append($cell);
                });
                $tbody.append($row);
            });

            $table.append($thead);
            $table.append($tbody);
            $resultsContainer.append($table);
        }

        // Handle unprocessed images
        if (unprocessed.length > 0) {
            const $group = $('<div class="label-group" style="margin-top: 20px;"></div>');
            $group.append(`<h2>Unprocessed (${unprocessed.length})</h2>`);

            const $imageContainer = $('<div class="image-container"></div>');
            unprocessed.forEach(function(image) {
                const imageUrl = getGeographUrl(image.id, image.hash, 'med');
                const $item = $(`
                    <div class="image-item">
                        <a href="https://www.geograph.org.uk/photo/${image.id}" target="_blank" title="${image.grid_reference} ${escapeHtml(image.title)} by ${escapeHtml(image.realname)}">
                            <img src="${imageUrl}" alt="${escapeHtml(image.title)}" loading="lazy">
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
