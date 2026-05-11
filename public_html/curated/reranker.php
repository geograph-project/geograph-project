<?


require_once('geograph/global.inc.php');
init_session();

        $imagelist=new ImageList;
        $db = $imagelist->_getDB(true);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Vector Cluster Board</title>
    <style>
        :root {
            --primary: #2563eb;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text: #1e293b;
        }

        body {
            font-family: system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 20px;
        }

        header {
            max-width: 1200px;
            margin: 0 auto 20px auto;
            display: flex;
            gap: 2px;
            align-items: center;
        }

        input[type="text"] {
            flex-grow: 1;
            padding: 10px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
        }

        button {
            background-color: var(--primary);
            color: black;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 500;
        }

        button:hover {
            opacity: 0.9;
        }

        /* 3-Column Layout */
        .board {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 6px;
            max-width: 1600px;
            margin: 0 auto;
            align-items: start;
        }

        .column {
            background-color: #f1f5f9;
            border-radius: 12px;
            padding: 2px;
            min-height: 500px;
            border: 2px solid gray;
            transition: border-color 0.2s;
        }

        .column h2 {
            margin-top: 0;
            font-size: 18px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .anchor-count {
            font-size: 12px;
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .image-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 2px;
            margin-top: 2px;
        }

        /* Image Card styling */
        .image-card {
            background: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .image-card img {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            background: #e2e8f0;
            display:block; /* remove the whitespace */
        }

        .image-card p {
display:none;
            margin: 6px;
            font-size: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Action Buttons to move images */
        .move-actions {
            display: flex;
            border-top: 1px solid var(--border);
            padding:1px;
        }

        .move-btn {
            flex: 1;
            background: #f8fafc;
            border: none;
            padding: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: background 0.2s;
            color: gray;
        }

        .move-btn:hover {
            background: #e2e8f0;
        }

        .move-btn:not(:last-child) {
            border-right: 1px solid var(--border);
        }
    </style>

    <script src="/js/geograph-api-libs.js?<?php echo filemtime("../js/geograph-api-libs.js"); ?>"></script>
    <script src="/js/vector.class.js"></script>

    </head>
<body>

    <header>
        <select id="search-query">
        <? $labels = $db->getCol("select label,sum(score<10) bad,sum(score=10) ok,sum(score>10) good from curated1 where cosine is not null group by label having bad>1 and ok > 20");
        if (empty($_GET['label'])) $_GET['label'] = 'Mining';
        foreach($labels as $value) {
            printf('<option value="%s"%s>%s</option>',$value = htmlentities($value), ($_GET['label'] === $value)?' selected':'', $value);
        } ?>
        </select>
        <!--input type="text" id="search-query" placeholder="Enter query (e.g., 'castle by the sea')..." value="Mining"-->
        <button id="search-btn">Search & Cluster</button>
    </header>

    <div class="board">
        <div class="column" id="col-0">
            <h2>Bad <span class="anchor-count" id="count-col-0">0 anchors</span></h2>
            <div class="image-list" id="list-col-0"></div>
        </div>
        
        <div class="column" id="col-1">
            <h2>OK <span class="anchor-count" id="count-col-1">0 anchors</span></h2>
            <div class="image-list" id="list-col-1"></div>
        </div>

        <div class="column" id="col-2">
            <h2>Good <span class="anchor-count" id="count-col-2">0 anchors</span></h2>
            <div class="image-list" id="list-col-2"></div>
        </div>
    </div>

    <script>
        // --- State Management ---
        let allImages = []; // Array of processed image objects: { id, hash, title, realname, vector, element }
        
        // Anchors for each column: Column index -> Array of normalized vector instances/arrays
        const anchors = {
            0: [], // Left
            1: [], // Center (initial anchors land here)
            2: []  // Right
        };

        // --- Helper Functions ---
        
        // Standard Dot Product for normalized vectors (Cosine Similarity)
        function calculateSimilarity(vecA, vecB) {
            // Assumes normalized array of floats. If your library returns a custom object, 
            // extract the raw float array first.
            let dotProduct = 0;
            for (let i = 0; i < vecA.length; i++) {
                dotProduct += vecA[i] * vecB[i];
            }
            return dotProduct;
        }

        // --- Core Logic ---

        // Fetch data using Fetch API (replacing $.ajax)
        async function fetchResults(query) {
            const params = new URLSearchParams({
                label: query,
                select: 'id,hash,grid_reference,realname,title,image_vector,place',
                long: 1,
                utf: 1,
                limit: 100, // Fetching 100 to make the clustering feel alive
                model: 'pe' // or whatever your server default model is
            });

            try {
                const response = await fetch(`/curated/curated1.json.php?${params.toString()}`);
                const data = await response.json();
                return data.rows || [];
            } catch (error) {
                console.error("Error fetching images:", error);
                return [];
            }
        }

        // Initialize clustering state
        function initializeBoard(rows) {
            allImages = [];
            anchors[0] = [];
            anchors[1] = [];
            anchors[2] = [];

            let hasPreinitializedAnchors = false;

            // Process vectors using your library
            rows.forEach(image => {
                if (image.image_vector) {
                    try {
                        // Create and normalize vector array
                        const vectorObj = new EmbeddingVector(image.image_vector).normalize();

                        allImages.push({
                            id: image.id,
                            hash: image.hash,
                            title: image.title || 'Untitled',
                            realname: image.realname || 'Unknown',
                            vector: vectorObj
                        });

            			// Check if this image has a pre-assigned bucket from the database/API
		                const bucketVal = parseInt(image.bucket);
console.log(bucketVal);
        		        if (!isNaN(bucketVal) && isFinite(bucketVal) && bucketVal >= 0) {
		            		anchors[bucketVal].push(vectorObj);
   		                    hasPreinitializedAnchors = true;
            			}

                    } catch (e) {
                        console.error(`Skipping image ${image.id} due to vector parse error`, e);
                    }
                }
            });

            if (allImages.length === 0) {
                alert("No images with valid vector data found.");
                return;
            }

    	    if (!hasPreinitializedAnchors) {
	            // Pick 10 random images to act as the initial anchors for the CENTER column (Index 1)
        	    const shuffled = [...allImages].sort(() => 0.5 - Math.random());
	            const initialAnchorCount = Math.min(10, shuffled.length);

        	    for (let i = 0; i < initialAnchorCount; i++) {
                	anchors[1].push(shuffled[i].vector);
	            }
	        }

            // Re-evaluate positions (Initially all go to Center, as Col 0 and Col 2 have 0 anchors)
            evaluateAndRender();
        }

        // Evaluates every image's best column fit and renders them
        function evaluateAndRender() {

             const board = document.querySelector('.board');
             // LOCK HEIGHT: Freeze the board at its current height to prevent scroll collapse
             const currentHeight = board.scrollHeight;
             board.style.minHeight = `${currentHeight}px`;

            // Clear current UI lists
            const lists = {
                0: document.getElementById('list-col-0'),
                1: document.getElementById('list-col-1'),
                2: document.getElementById('list-col-2')
            };
            lists[0].innerHTML = '';
            lists[1].innerHTML = '';
            lists[2].innerHTML = '';

            // Update UI Anchor Counts
            document.getElementById('count-col-0').textContent = `${anchors[0].length} anchors`;
            document.getElementById('count-col-1').textContent = `${anchors[1].length} anchors`;
            document.getElementById('count-col-2').textContent = `${anchors[2].length} anchors`;

            allImages.forEach(image => {
                let bestColumn = 1; // Default to center if no anchors elsewhere
                let highestSimilarity = -1;

                // Check similarity against all anchors across all active columns
                [0, 1, 2].forEach(colIndex => {
                    anchors[colIndex].forEach(anchorVec => {
                        //pass the objects..
			//distance = anchorVec.distance(image.vector);
			//sim = anchorVec._dotProduct(image.vector)
                        const sim = calculateSimilarity(image.vector.vector, anchorVec.vector);
                        if (sim > highestSimilarity) {
                            highestSimilarity = sim;
                            bestColumn = colIndex;
                        }
                    });
                });

                // Generate and append the DOM element to the winning column
                const card = createImageCard(image, bestColumn);
                lists[bestColumn].appendChild(card);
            });

            // RELEASE HEIGHT: Let the page resize naturally to its new content height
            board.style.minHeight = '';
        }

        // Generates the Image Card Element
        function createImageCard(image, currentColumn) {
            const card = document.createElement('div');
            card.className = 'image-card';
            
            const imageUrl = getGeographUrl(image.id, image.hash, 'med').replace(/_213x160/,'_224XX224');

            let style = '';
            const isAnchor = anchors[currentColumn].some(anchorVec => anchorVec === image.vector);
            if (isAnchor)
                style = ' style="color:red;font-weight:bold;background-color:#ffdbdb;outline:1px solid black"';
            
            card.innerHTML = `
                <a href="https://www.geograph.org.uk/photo/${image.id}" target="_blank" title="${image.title} by ${image.realname}">
                    <img src="${imageUrl}" alt="${image.title}" loading="lazy">
                </a>
                <p>${image.title}</p>
                <div class="move-actions">
                    <button class="move-btn" data-target="0" ${currentColumn === 0?style:''}>Bad</button>
                    <button class="move-btn" data-target="1" ${currentColumn === 1?style:''}>OK</button>
                    <button class="move-btn" data-target="2" ${currentColumn === 2?style:''}>Good</button>
                </div>
            `;

            // Intercept manual reassignment triggers
            card.querySelectorAll('.move-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const targetCol = parseInt(e.target.getAttribute('data-target'));
                    moveImageAnchor(image, targetCol);
                });
            });

            return card;
        }

        // Handles manual adjustment: moves the vector representation into the chosen column's anchor collection
        function moveImageAnchor(image, targetColumnIndex) {
            // 1. Remove this specific image's vector from any other columns it might be anchoring (if applicable)
            [0, 1, 2].forEach(colIdx => {
                anchors[colIdx] = anchors[colIdx].filter(vec => vec !== image.vector);
            });

            // 2. Add this image's vector as a permanent anchor to the new target column
            anchors[targetColumnIndex].push(image.vector);

            // 3. Re-evaluate the entire board. Everything clusters automatically!
            evaluateAndRender();
        }

        // --- Event Listeners ---
        document.getElementById('search-btn').addEventListener('click', async () => {
            const query = document.getElementById('search-query').value.trim();
            if (!query) return;
            
            const rows = await fetchResults(query);
            initializeBoard(rows);
        });

        // Allow trigger search on Enter key
        document.getElementById('search-query').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                document.getElementById('search-btn').click();
            }
        });
    </script>
</body>
</html>
