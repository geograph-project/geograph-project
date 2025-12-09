<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Matching Test</title>
    <style>
        body { font-family: sans-serif; }
        #form-container { margin-bottom: 20px; }
        #form-container select, #form-container textarea, #form-container button {
            display: block;
            width: 100%;
            max-width: 600px;
            padding: 8px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }
        #results-table {
            width: 100%;
            max-width: 800px;
            border-collapse: collapse;
            margin-top: 20px;
        }
        #results-table th, #results-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        #results-table th {
            background-color: #f2f2f2;
        }
        #loading-indicator { display: none; }
        .panels-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .panel {
            flex: 1;
            border: 1px solid #ccc;
            padding: 10px;
            min-width: 0; /* Prevents flexbox overflow */
        }
        .panel h2 {
            margin-top: 0;
            font-size: 1.1em;
        }
        .image-container {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            max-height: 400px; /* Limit height */
            overflow-y: auto; /* Allow scrolling */
        }
        .image-item {
            text-align: center;
        }
        .image-item img {
            width: 100px; /* Fixed width */
            height: 100px; /* Fixed height */
            object-fit: cover; /* Prevents distortion */
        }
        .image-item p {
            font-size: 0.8em;
            margin: 3px 0 0 0;
            word-wrap: break-word;
        }
    </style>
</head>
<body>

    <h1>Subject Matching Test</h1>
    <p>Select a subject to see how well its text embedding matches the embeddings of images tagged with that subject.</p>

    <div id="form-container">
        <label for="model-select">Choose an AI Model:</label>
        <select id="model-select">
            <option value="clip">CLIP</option>
            <option value="pe">Perception Encoder</option>
        </select>

        <label for="subject-select">Choose a Subject:</label>
        <select id="subject-select">
            <option value="">Loading subjects...</option>
        </select>

        <label for="query-text">Query Text:</label>
        <textarea id="query-text" rows="3"></textarea>

        <button id="run-button">Run Analysis</button>
    </div>

    <div id="loading-indicator">Loading...</div>

    <table id="results-table">
        <thead>
            <tr>
                <th>Query</th>
                <th>Min Distance</th>
                <th>Max Distance</th>
                <th>Avg Distance</th>
                <th>Std Dev</th>
            </tr>
        </thead>
        <tbody>
            <!-- Results will be appended here -->
        </tbody>
    </table>

    <div class="panels-container">
        <div class="panel" id="tagged-images-panel">
            <h2>Images Tagged with Subject</h2>
            <div class="image-container" id="tagged-images-container"></div>
        </div>
        <div class="panel" id="similarity-images-panel">
            <h2>Similarity Search Results</h2>
            <div class="image-container" id="similarity-images-container"></div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="/js/vector.class.js"></script>
    <script>
    $(document).ready(function() {
        const $modelSelect = $('#model-select');
        const $subjectSelect = $('#subject-select');
        const $queryText = $('#query-text');
        const $runButton = $('#run-button');
        const $resultsTableBody = $('#results-table tbody');
        const $loadingIndicator = $('#loading-indicator');
        const $taggedImagesContainer = $('#tagged-images-container');
        const $similarityImagesContainer = $('#similarity-images-container');

        // --- Core Functions ---

        // Fetches subjects and populates the dropdown
        function loadSubjects() {
            $.ajax({
                url: '/tags/tags.json.php',
                data: {
                    mode: 'subject',
                    q: '.',
                    limit: 1000
                },
                dataType: 'json',
                success: function(data) {
                    $subjectSelect.empty().append('<option value="">Select a subject</option>');
                    if (data && data.length > 0) {
                        data.sort((a, b) => a.tag.localeCompare(b.tag));
                        data.forEach(function(subject) {
                            $subjectSelect.append($('<option>', {
                                value: subject.tag,
                                text: subject.tag
                            }));
                        });
                    } else {
                        $subjectSelect.append('<option value="">No subjects found</option>');
                    }
                },
                error: function() {
                    $subjectSelect.empty().append('<option value="">Failed to load subjects</option>');
                    alert('Could not load the subject list.');
                }
            });
        }

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

        function runAnalysis(query, subject) {
            const model = $modelSelect.val();
            $loadingIndicator.show();
            $taggedImagesContainer.empty();
            $similarityImagesContainer.empty();

            const matchQuery = `@subjects "_SEP_ ${subject.replace(/[^\w]+/g, ' ')} _SEP_"`;

            // Perform all three API calls in parallel
            $.when(
                // 1. Get the vector for the input query text
                $.ajax({
                    url: '/finder/label-vectors.json.php',
                    method: 'GET',
                    data: { labels: query, model: model },
                    dataType: 'json'
                }),
                // 2. Get images tagged with the original subject
                $.ajax({
                    url: '/api-facetql-vector.php',
                    method: 'GET',
                    data: {
                        match: matchQuery,
                        select: 'id,hash,grid_reference,realname,title,image_vector',
                        limit: 100, // Fetch up to 100 for stats, but we'll display 20
                        model: model
                    },
                    dataType: 'json'
                }),
                // 3. Get images via similarity search on the new query
                $.ajax({
                    url: '/api-facetql-vector.php',
                    method: 'GET',
                    data: {
                        label: query,
                        select: 'id,hash,grid_reference,realname,title',
                        limit: 20, // Only need the top 20 for display
                        model: model
                    },
                    dataType: 'json'
                })
            ).done(function(labelResponse, taggedImagesResponse, similarityImagesResponse) {
                const labelData = labelResponse[0];
                const taggedImagesData = taggedImagesResponse[0];
                const similarityImagesData = similarityImagesResponse[0];

                // --- Render Similarity Search Results ---
                if (similarityImagesData && similarityImagesData.rows) {
                    renderThumbnails($similarityImagesContainer, similarityImagesData.rows);
                }

                // --- Calculate and Display Statistics ---
                if (!labelData || !labelData[query]) {
                    alert(`Could not find a vector for the query: "${query}"`);
                    return;
                }
                if (!taggedImagesData || !taggedImagesData.rows || taggedImagesData.rows.length === 0) {
                    alert(`No images found for the subject: "${subject}"`);
                    // Still render the similarity results
                    renderThumbnails($taggedImagesContainer, []);
                    return;
                }

                // Display tagged images
                renderThumbnails($taggedImagesContainer, taggedImagesData.rows.slice(0, 20));

                try {
                    const textVector = new EmbeddingVector(labelData[query]).normalize();
                    const distances = [];

                    taggedImagesData.rows.forEach(image => {
                        if (image.image_vector) {
                            const imageVector = new EmbeddingVector(image.image_vector).normalize();
                            distances.push(textVector.distance(imageVector));
                        }
                    });

                    if (distances.length > 0) {
                        const stats = calculateStats(distances);
                        addResultRow(query, stats);
                    } else {
                         alert('No valid image vectors could be processed for stats.');
                    }
                } catch (e) {
                    alert('An error during vector processing. Check console.');
                    console.error(e);
                }

            }).fail(function(jqXHR, textStatus, errorThrown) {
                alert('An error occurred while fetching data from the APIs. Check console for details.');
                console.error("API call failed:", textStatus, errorThrown);
            }).always(function() {
                $loadingIndicator.hide();
            });
        }

        function calculateStats(numbers) {
            if (numbers.length === 0) {
                return { min: 0, max: 0, avg: 0, stdDev: 0 };
            }

            const min = Math.min(...numbers);
            const max = Math.max(...numbers);
            const sum = numbers.reduce((acc, val) => acc + val, 0);
            const avg = sum / numbers.length;

            const squaredDiffs = numbers.map(val => Math.pow(val - avg, 2));
            const avgSquaredDiff = squaredDiffs.reduce((acc, val) => acc + val, 0) / numbers.length;
            const stdDev = Math.sqrt(avgSquaredDiff);

            return { min, max, avg, stdDev };
        }

        function addResultRow(query, stats) {
            const newRow = `<tr>
                <td>${escapeHtml(query)}</td>
                <td>${stats.min.toFixed(4)}</td>
                <td>${stats.max.toFixed(4)}</td>
                <td>${stats.avg.toFixed(4)}</td>
                <td>${stats.stdDev.toFixed(4)}</td>
            </tr>`;
            $resultsTableBody.append(newRow);
        }

        function escapeHtml(str) {
            if (!str) return '';
            return $('<div>').text(str).html();
        }

        function renderThumbnails($container, images) {
            $container.empty();
            if (!images || images.length === 0) {
                $container.html('<p>No images to display.</p>');
                return;
            }

            images.forEach(function(image) {
                const imageUrl = getGeographUrl(image.id, image.hash, 'small');
                const $item = $(`
                    <div class="image-item">
                        <a href="https://www.geograph.org.uk/photo/${image.id}" target="_blank" title="${escapeHtml(image.title)} by ${escapeHtml(image.realname)}">
                            <img src="${imageUrl}" alt="${escapeHtml(image.title)}" loading="lazy">
                        </a>
                    </div>
                `);
                $container.append($item);
            });
        }
        // --- Event Handlers ---

        // 1. Populate subjects when the page is ready
        loadSubjects();

        function handleAnalysisTrigger() {
            const selectedSubject = $subjectSelect.val();
            if (selectedSubject) {
                $queryText.val(selectedSubject);
                $resultsTableBody.empty();
                runAnalysis(selectedSubject, selectedSubject);
            }
        }

        // 2. When a subject is selected from the dropdown
        $subjectSelect.on('change', handleAnalysisTrigger);


        // 3. When the 'Run Analysis' button is clicked
        $runButton.on('click', function() {
            const query = $queryText.val().trim();
            const subject = $subjectSelect.val();

            if (!subject) {
                alert('Please select a subject first.');
                return;
            }
            if (!query) {
                alert('Please enter a query.');
                return;
            }
            // Do not clear results here, to allow comparison
            runAnalysis(query, subject);
        });

    });
    </script>

</body>
</html>
