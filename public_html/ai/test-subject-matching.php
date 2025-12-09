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
    </style>
</head>
<body>

    <h1>Subject Matching Test</h1>
    <p>Select a subject to see how well its text embedding matches the embeddings of images tagged with that subject.</p>

    <div id="form-container">
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

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="/js/vector.class.js"></script>
    <script>
    $(document).ready(function() {
        const $subjectSelect = $('#subject-select');
        const $queryText = $('#query-text');
        const $runButton = $('#run-button');
        const $resultsTableBody = $('#results-table tbody');
        const $loadingIndicator = $('#loading-indicator');

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

        function runAnalysis(query, subject) {
            $loadingIndicator.show();

            const matchQuery = `@subjects "_SEP_ ${subject.replace(/[^\w]+/g, ' ')} _SEP_"`;

            $.when(
                $.ajax({
                    url: '/finder/label-vectors.json.php',
                    method: 'GET',
                    data: { labels: query, model: 'clip' },
                    dataType: 'json'
                }),
                $.ajax({
                    url: '/api-facetql-vector.php',
                    method: 'GET',
                    data: {
                        match: matchQuery,
                        select: 'id,image_vector',
                        limit: 200, // Fetch a good number of images for meaningful stats
                        model: 'clip'
                    },
                    dataType: 'json'
                })
            ).done(function(labelResponse, imagesResponse) {
                const labelData = labelResponse[0];
                const imagesData = imagesResponse[0];

                if (!labelData || !labelData[query]) {
                    alert(`Could not find a vector for the query: "${query}"`);
                    return;
                }
                if (!imagesData || !imagesData.rows || imagesData.rows.length === 0) {
                    alert(`No images found for the subject: "${subject}"`);
                    return;
                }

                try {
                    const textVector = new EmbeddingVector(labelData[query]).normalize();
                    const distances = [];

                    imagesData.rows.forEach(image => {
                        if (image.image_vector) {
                            const imageVector = new EmbeddingVector(image.image_vector).normalize();
                            const distance = textVector.distance(imageVector);
                            distances.push(distance);
                        }
                    });

                    if (distances.length === 0) {
                        alert('No valid image vectors could be processed.');
                        return;
                    }

                    // Calculate statistics
                    const stats = calculateStats(distances);

                    // Add results to the table
                    addResultRow(query, stats);

                } catch (e) {
                    alert('An error occurred during vector processing. Check the console.');
                    console.error(e);
                }

            }).fail(function() {
                alert('An error occurred while fetching data from the APIs.');
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

        // --- Event Handlers ---

        // 1. Populate subjects when the page is ready
        loadSubjects();

        // 2. When a subject is selected from the dropdown
        $subjectSelect.on('change', function() {
            const selectedSubject = $(this).val();
            if (selectedSubject) {
                $queryText.val(selectedSubject);
                $resultsTableBody.empty(); // Clear previous results
                runAnalysis(selectedSubject, selectedSubject);
            }
        });

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
            runAnalysis(query, subject);
        });

    });
    </script>

</body>
</html>
