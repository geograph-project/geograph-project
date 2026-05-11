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
            gap: 10px;
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
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            align-items: start;
        }

        .column {
            background-color: #f1f5f9;
            border-radius: 12px;
            padding: 16px;
            min-height: 500px;
            border: 2px dashed transparent;
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
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 12px;
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
            height: 100px;
            object-fit: cover;
            background: #e2e8f0;
        }

        .image-card p {
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
        }

        .move-btn {
            flex: 1;
            background: #f8fafc;
            border: none;
            padding: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: background 0.2s;
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
        <input type="text" id="search-query" placeholder="Enter query (e.g., 'castle by the sea')..." value="coast">
        <button id="search-btn">Search & Cluster</button>
    </header>

    <div class="board">
        <div class="column" id="col-0">
            <h2>Column 1 <span class="anchor-count" id="count-col-0">0 anchors</span></h2>
            <div class="image-list" id="list-col-0"></div>
        </div>
        
        <div class="column" id="col-1">
            <h2>Column 2 <span class="anchor-count" id="count-col-1">0 anchors</span></h2>
            <div class="image-list" id="list-col-1"></div>
        </div>

        <div class="column" id="col-2">
            <h2>Column 3 <span class="anchor-count" id="count-col-2">0 anchors</span></h2>
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
                const response = await fetch(`/api-facetql-vector.php?${params.toString()}`);
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
                    } catch (e) {
                        console.error(`Skipping image ${image.id} due to vector parse error`, e);
                    }
                }
            });

            if (allImages.length === 0) {
                alert("No images with valid vector data found.");
                return;
            }

            // Pick 10 random images to act as the initial anchors for the CENTER column (Index 1)
            const shuffled = [...allImages].sort(() => 0.5 - Math.random());
            const initialAnchorCount = Math.min(10, shuffled.length);
            
            for (let i = 0; i < initialAnchorCount; i++) {
                anchors[1].push(shuffled[i].vector);
            }

            // Re-evaluate positions (Initially all go to Center, as Col 0 and Col 2 have 0 anchors)
            evaluateAndRender();
        }

        // Evaluates every image's best column fit and renders them
        function evaluateAndRender() {
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
                const card = createImageCard(image);
                lists[bestColumn].appendChild(card);
            });
        }

        // Generates the Image Card Element
        function createImageCard(image) {
            const card = document.createElement('div');
            card.className = 'image-card';
            
            const imageUrl = getGeographUrl(image.id, image.hash, 'med');
            
            card.innerHTML = `
                <a href="https://www.geograph.org.uk/photo/${image.id}" target="_blank" title="${image.title} by ${image.realname}">
                    <img src="${imageUrl}" alt="${image.title}" loading="lazy">
                </a>
                <p>${image.title}</p>
                <div class="move-actions">
                    <button class="move-btn" data-target="0">&lt; Left</button>
                    <button class="move-btn" data-target="1">&middot;</button>
                    <button class="move-btn" data-target="2">Right &gt;</button>
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
