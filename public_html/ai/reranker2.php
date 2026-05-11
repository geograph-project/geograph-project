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
            padding: 2px;
        }

        header {
            max-width: 1200px;
            margin: 0 auto 20px auto;
            display: flex;
            gap: 6px;
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
    /* We will set grid-template-columns dynamically in JS based on bucket count */
    gap: 6px;
    max-width: 100%;
    margin: 0 auto;
    align-items: start;
}

        .column {
            background-color: #f1f5f9;
            border-radius: 12px;
            padding: 2px;
            min-height: 500px;
            border: 0px dashed transparent;
            transition: border-color 0.2s;
        }

.column-header-input {
    font-size: 18px;
    font-weight: bold;
    border: none;
    background: transparent;
    color: var(--text);
    width: 70%;
    padding: 2px 4px;
    border-radius: 4px;
    font-family: inherit;
    transition: background 0.2s;
}

.column-header-input:hover {
    background: rgba(0, 0, 0, 0.05);
}

.column-header-input:focus {
    background: #ffffff;
    outline: 2px solid var(--primary);
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
            gap: 6px;
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
aspect-ratio: 1 / 1; /* Forces a perfect square */
            object-fit: cover;
            background: #e2e8f0;
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
    flex-wrap: wrap;
    border-top: 1px solid var(--border);
    background: #f8fafc;
}

.move-btn {
    flex: 1;
    min-width: 30px;
    background: transparent;
    border: none;
    padding: 6px 4px;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}

.move-btn:hover {
    background: var(--primary);
    color: white;
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
    <input type="text" id="search-query" placeholder="Enter query..." value="mining">
    
    <label for="bucket-count" style="font-weight: 500; font-size: 14px;">Buckets:</label>
    <select id="bucket-count" style="padding: 10px; border-radius: 8px; border: 1px solid var(--border); font-size: 16px;">
        <option value="2">2 Columns</option>
        <option value="3" selected>3 Columns</option>
        <option value="4">4 Columns</option>
        <option value="5">5 Columns</option>
        <option value="6">6 Columns</option>
    </select>

    <button id="search-btn">Search & Cluster</button>
</header>

<div class="board" id="board-container"></div>

    <script>

// --- State Management ---
let allImages = []; 
let numBuckets = 3; // Default
let anchors = {};   // Will look like: { 0: [...], 1: [...], 2: [...] }
let bucketNames = []; // Array to store custom names: ['Unsorted', 'Open Pit', 'Underground']

// Set up the columns in the DOM dynamically
function setupBoardDOM() {
    const board = document.getElementById('board-container');
    board.innerHTML = '';
    
    // Dynamically set CSS Grid columns based on bucket count
    board.style.gridTemplateColumns = `repeat(${numBuckets}, 1fr)`;

    for (let i = 0; i < numBuckets; i++) {
        // Ensure a name exists for this bucket index
        if (!bucketNames[i]) {
            bucketNames[i] = `Bucket ${i + 1}`;
        }

        const col = document.createElement('div');
        col.className = 'column';
        col.id = `col-${i}`;
        col.innerHTML = `
            <h2><input type="text" 
                       class="column-header-input" 
                       id="header-input-${i}" 
                       value="${bucketNames[i]}" 
                       placeholder="Rename bucket..."
                       title="Click to rename this bucket">
 <span class="anchor-count" id="count-col-${i}">0 anchors</span></h2>
            <div class="image-list" id="list-col-${i}"></div>
        `;
        board.appendChild(col);

        // Listen for typing and save the name instantly to our state array
        const headerInput = col.querySelector(`#header-input-${i}`);
        headerInput.addEventListener('input', (e) => {
            bucketNames[i] = e.target.value;
        });
        headerInput.addEventListener('focus', (e) => {
            const input = e.target;
            const isGenericName = /^Bucket \d+$/i.test(input.value);
            if (isGenericName) {
                input.select(); // Instantly selects all text for immediate overtyping
            }
        });
    }
}

// Initialize dynamic board state
function initializeBoard(rows) {
    allImages = [];
    anchors = {};
    
    // Read user selection for bucket count
    numBuckets = parseInt(document.getElementById('bucket-count').value);
    setupBoardDOM();

    // Initialize anchor arrays for each active bucket
    for (let i = 0; i < numBuckets; i++) {
        anchors[i] = [];
    }

    // Process vectors
    rows.forEach(image => {
        if (image.image_vector) {
            try {
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

    // Distribute 10 random initial anchors into 0
    const shuffled = [...allImages].sort(() => 0.5 - Math.random());
    const initialAnchorCount = Math.min(10, shuffled.length);
    
    for (let i = 0; i < initialAnchorCount; i++) {
        anchors[0].push(shuffled[i].vector);
    }

    evaluateAndRender();
}

// Evaluates positions against dynamic buckets and renders
function evaluateAndRender() {
    // Clear and prepare column lists
    const lists = {};
    for (let i = 0; i < numBuckets; i++) {
        lists[i] = document.getElementById(`list-col-${i}`);
        lists[i].innerHTML = '';
        
        // Update counts
        document.getElementById(`count-col-${i}`).textContent = `${anchors[i].length} anchors`;
    }

    allImages.forEach(image => {
        let bestColumn = 0;
        let highestSimilarity = -1;

        // Compare against anchors in all active columns
        for (let colIndex = 0; colIndex < numBuckets; colIndex++) {
            anchors[colIndex].forEach(anchorVec => {
                // Using your exact direct array dot-product logic
                const sim = calculateSimilarity(image.vector.vector, anchorVec.vector);
                if (sim > highestSimilarity) {
                    highestSimilarity = sim;
                    bestColumn = colIndex;
                }
            });
        }

        const card = createImageCard(image);
        lists[bestColumn].appendChild(card);
    });
}

// Generates Image Card with dynamic, numbered action buttons
function createImageCard(image) {
    const card = document.createElement('div');
    card.className = 'image-card';
    
    const imageUrl = getGeographUrl(image.id, image.hash, 'med').replace(/_213x160/,'_224XX224');;
    
    // Generate move buttons dynamically based on how many buckets are active
    let actionButtonsHTML = '';
    for (let i = 0; i < numBuckets; i++) {
        actionButtonsHTML += `<button class="move-btn" data-target="${i}">${i + 1}</button>`;
    }
    
    card.innerHTML = `
        <a href="https://www.geograph.org.uk/photo/${image.id}" target="_blank" title="${image.title} by ${image.realname}">
            <img src="${imageUrl}" alt="${image.title}" loading="lazy">
        </a>
        <p>${image.title}</p>
        <div class="move-actions">
            ${actionButtonsHTML}
        </div>
    `;

    // Add event listeners to the dynamic buttons
    card.querySelectorAll('.move-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetCol = parseInt(e.target.getAttribute('data-target'));
            moveImageAnchor(image, targetCol);
        });
    });

    return card;
}

// Moves vector representation to chosen column's anchor collection
function moveImageAnchor(image, targetColumnIndex) {
    // 1. Remove this specific image's vector from any other active columns
    for (let i = 0; i < numBuckets; i++) {
        anchors[i] = anchors[i].filter(vec => vec !== image.vector);
    }

    // 2. Add it to the target column
    anchors[targetColumnIndex].push(image.vector);

    // 3. Recalculate
    evaluateAndRender();
    
    // 4. Make it easier to rename
    const headerInput = document.getElementById(`header-input-${targetColumnIndex}`);
    const isGenericName = /^Bucket \d+$/i.test(headerInput.value);
    if (isGenericName) {
        //immidately give focus for easy renameing!
        headerInput.focus();
        headerInput.select(); // Explicitly highlight to guarantee immediate overtyping
    }
}

// Listen for dynamic bucket changes to adjust columns without losing current progress
document.getElementById('bucket-count').addEventListener('change', (e) => {
    const nextBuckets = parseInt(e.target.value);
    
    if (nextBuckets < numBuckets) {
        // --- SCALE DOWN ---
        // Clean up and delete anchors in columns that no longer exist
        for (let i = nextBuckets; i < numBuckets; i++) {
            delete anchors[i];
        }
    } else if (nextBuckets > numBuckets) {
        // --- SCALE UP ---
        // Initialize the new columns as empty anchor pools
        for (let i = numBuckets; i < nextBuckets; i++) {
            anchors[i] = [];
        }
    }

    // Update state to the new count and redraw the HTML structure
    numBuckets = nextBuckets;
    setupBoardDOM();

    // Re-evaluate positions:
    // If we scaled down, orphaned images will immediately find new homes in the remaining active columns.
    if (allImages.length > 0) {
        evaluateAndRender();
    }
});


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
