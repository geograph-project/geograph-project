<?

if (isset($_GET['update_candidate'])) {
    require_once('geograph/global.inc.php');
    $db = GeographDatabaseConnection(false);

    $subject = $_POST['subject'] ?? '';
    $query   = $_POST['query'] ?? '';
    $model   = $_POST['model'] ?? '';
    $action  = $_POST['action'] ?? '';

// Map actions to your integer flags
    $flag = ($action === 'no-match') ? -1 : 1;

    $sql = "UPDATE subject_embedding
            SET is_candidate = ?
            WHERE subject_id = (SELECT subject_id FROM subjects WHERE subject = ?)
              AND model = ?
              AND query = ?";

    $result = $db->Execute($sql, [$flag, $subject, $model, $query]);

    if ($result) {
        echo json_encode(['status' => 'success', 'flag' => $flag]);
    } else {
        header("HTTP/1.1 500 Internal Server Error");
        echo $db->ErrorMsg();
    }
    exit;
}

if (!empty($_POST)) {

	require_once('geograph/global.inc.php');
	init_session();

	$db = GeographDatabaseConnection(false);

	// Get data from the POST request
	$subject_name = $_POST['subject'] ?? '';
	$stats        = $_POST['stats'] ?? [];

	if (!$subject_name || empty($stats)) {
	    header("HTTP/1.1 400 Bad Request");
	    exit("Missing required data");
	}

	// 1. Resolve subject_id from the subjects table
	$subject_id = $db->GetOne("SELECT subject_id FROM subjects WHERE subject = ?", [$subject_name]);

	if (!$subject_id) {
	    header("HTTP/1.1 404 Not Found");
	    exit("Subject not found");
	}

	// 2. Prepare data for the existing table structure
	//note, with Replace function, there is no auto-quoting
	$record = [
	    'subject_id' => (int)$subject_id,
	    'model'      => $db->Quote($_POST['model'] ?? ''),
	    'query'      => $db->Quote($_POST['query'] ?? ''),
	    'cosine_cnt' => (int)$stats['count'],
	    'cosine_min' => (float)$stats['min'],
	    'cosine_max' => (float)$stats['max'],
	    'cosine_avg' => (float)$stats['avg'],
	    'cosine_std' => (float)$stats['stdDev'],
	    'user_id'    => (int)$USER->user_id
	];

	//$result = $db->AutoExecute('subject_embedding', $record, 'INSERT');
	$result = $db->Replace('subject_embedding', $record, ['subject_id', 'model', 'query']);

	if ($result) {
	    echo json_encode(['status' => 'success', 'operation' => ($result == 1 ? 'updated' : 'inserted') ]);
	} else {
	    header("HTTP/1.1 500 Internal Server Error");
	    echo $db->ErrorMsg();
	}
	exit;

/*
alter table subject_embedding add query varchar(255) default null after model;
alter table subject_embedding add user_id int unsigned default null, add created timestamp not null default current_timestamp();
 UPDATE subject_embedding SET query = '' WHERE query IS NULL;
 ALTER TABLE subject_embedding
      MODIFY query VARCHAR(255) NOT NULL DEFAULT '',
      DROP INDEX uk_model_subject,
      ADD UNIQUE KEY `uk_subject_model_query` (`subject_id`, `model`, `query`);

... actully should reset these, its when the column is added not, when oriiginally creatd, could instead of added the coolumn without default, then altered to add the default
update subject_embedding set created = 0 where created = '2025-12-17 14:22:13';

ALTER TABLE subject_embedding 
ADD COLUMN updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

*/
}

?>

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
		white-space:nowrap;
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
	.highlight-match {
	    background-color: #f0f0f0;
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
            <option value="pe" selected>Perception Encoder</option>
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
                <th>Subject</th>
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
                //url: '/tags/tags.json.php', -- doesnt have a easy way to ensure only pick 'offical' subjects
		url: '/tags/subject.json.php', //will ignore url params anyway
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
                    data: { labels: query.replace(/,/g,';'), model: model }, //comma is used to seperate tags in this api!
                    dataType: 'json'
                }),
                // 2. Get images tagged with the original subject
                $.ajax({
                    url: 'https://www.geograph.org.uk/api-facetql-vector.php',
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
                        addResultRow(subject, query, stats);
			saveResultToServer(subject, query, stats, model);

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

	function saveResultToServer(subject, query, stats, model) {
	    $.post('?save=true', {
	        subject: subject,
	        query: query,
	        model: model, // Useful to distinguish which AI you used
	        stats: stats
	    }).done(function(response) {
	        console.log("Result saved successfully");
	    }).fail(function() {
	        console.error("Failed to save result");
	    });
	}

        function calculateStats(numbers) {
            if (numbers.length === 0) {
                return { min: 0, max: 0, avg: 0, stdDev: 0 };
            }

            const count = numbers.length;
            const min = Math.min(...numbers);
            const max = Math.max(...numbers);
            const sum = numbers.reduce((acc, val) => acc + val, 0);
            const avg = sum / numbers.length;

            const squaredDiffs = numbers.map(val => Math.pow(val - avg, 2));
            const avgSquaredDiff = squaredDiffs.reduce((acc, val) => acc + val, 0) / numbers.length;
            const stdDev = Math.sqrt(avgSquaredDiff);

            return { count, min, max, avg, stdDev };
        }

	// 1. Create a persistent store for baseline stats
	const baselineStatsStore = {};

	function addResultRow(subject, query, stats) {
	    const isMatch = subject === query;
	    let statsHtml = {};

	    const model = $modelSelect.val();
	    const dataAttrs = ` data-subject="${escapeHtml(subject)}" data-query="${escapeHtml(query)}" data-model="${escapeHtml(model)}"`;
            let btnText = '';

	    if (isMatch) {
	        // 2. Save baseline for future comparisons
	        baselineStatsStore[subject] = stats;

	        // Identity row: just format the numbers
	        ['min', 'max', 'avg', 'stdDev'].forEach(key => {
	            statsHtml[key] = stats[key].toFixed(4);
	        });

		// Baseline row gets "Looks OK" and "No Visual Matches"
                btnText = `<button class="btn-action" data-action="looks-ok"${dataAttrs}>Looks OK</button> 
                           <button class="btn-action" data-action="no-match"${dataAttrs}>No Visual Matches</button>`;

	    } else {
	        const baseline = baselineStatsStore[subject];

	        ['min', 'max', 'avg', 'stdDev'].forEach(key => {
	            const currentVal = stats[key];
	            let displayStr = currentVal.toFixed(4);

	            if (baseline) {
	                const baseVal = baseline[key];
	                // 3. Calculate % change
	                const percentChange = ((currentVal / baseVal) - 1) * 100;
	                const sign = percentChange > 0 ? '+' : '';
	                const color = percentChange > 0 ? 'color: #d9534f' : 'color: #5cb85c'; // Red for worse, Green for better

	                displayStr += ` <small style="${color}; font-weight: bold;">(${sign}${percentChange.toFixed(1)}%)</small>`;
	            }
	            statsHtml[key] = displayStr;
	        });

		// Engineered rows get "Suggest Good"
                btnText = `<button class="btn-action" data-action="suggest-good"${dataAttrs}>Suggest Good</button>`;
	    }

	    const rowClass = isMatch ? ' class="highlight-match"' : '';

	    const newRow = `<tr${rowClass}>
	        <td>${escapeHtml(subject)}</td>
	        <td>${escapeHtml(query)}</td>
	        <td>${statsHtml.min}</td>
	        <td>${statsHtml.max}</td>
	        <td>${statsHtml.avg}</td>
	        <td>${statsHtml.stdDev}</td>
	        <td>${btnText}</td>
	    </tr>`;

	    $resultsTableBody.append(newRow);
	}

	$resultsTableBody.on('click', '.btn-action', function() {
	    const btn = $(this);
	    const action = btn.data('action'); // looks-ok, no-match, or suggest-good
	    const data = {
	        subject: btn.data('subject'),
	        query: btn.data('query'),
	        model: btn.data('model'),
	        action: action
	    };

	    // Disable button to prevent double clicks
	    btn.prop('disabled', true).text('Saving...');

	    $.post('?update_candidate=true', data)
	        .done(function(response) {
	            btn.text('Saved!').css('background-color', '#5cb85c').css('color', 'white');
	            console.log("Action saved:", action, response);
	        })
	        .fail(function() {
	            btn.prop('disabled', false).text('Retry');
	            alert("Failed to save action");
	        });
	});


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
                //$resultsTableBody.empty();
                runAnalysis(selectedSubject, selectedSubject);
            }
        }

        // 2. When a subject is selected from the dropdown
        $subjectSelect.on('change', handleAnalysisTrigger);


        // 3. When the 'Run Analysis' button is clicked
        $runButton.on('click', function() {
            const query = $queryText.val().trim();
            const subject = $subjectSelect.val();

	    if (query == subject) { //selecting the subject should run the 'initial' query, dont need to run it explicitly
                alert('Please edit the query to try a different prompt');
                return;
            }

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
