<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Vector Cluster Board</title>
    <style>
        :root {
            --primary: #a5bef3;
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

--position: sticky;
--bottom: 20px; /* Pins the BOTTOM of the column instead of the top */
    --align-self: end; /* Aligns the columns to the bottom of the grid row */


/* CRITICAL: Must be visible so the sticky child can pop out of it */
    overflow: visible;
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
.add-text-anchor-btn {
    display: block;
    width: 100%;
    margin-top: 8px;
    background-color: #3b82f6;
    color: white;
    border: none;
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 4px;
    cursor: pointer;
    text-align: center;
    transition: background 0.2s;
        display:none !important; /* button doesnt work yet, hide it in the demo */
}

.add-text-anchor-btn:hover {
    background-color: #1d4ed8;
}

        .column h2 {
            margin-top: 0;
            font-size: 18px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;

position:sticky;
top:2px;
left:0;
z-index:10;
background-color:#eee;

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
            display: block;
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
    <button id="export-btn" style="background-color: #10b981;">Export Data</button>
</header>

<div id="export-container" style="display: none; max-width: 1200px; margin: 0 auto 20px auto;">
    <h3 style="margin-top: 0;">Exported Session State</h3>
    <textarea id="export-textarea" style="width: 100%; height: 200px; font-family: monospace; padding: 10px; border: 1px solid var(--border); border-radius: 8px; box-sizing: border-box; resize: vertical;"></textarea>
</div>

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

        const isGenericName = /^Bucket \d+$/i.test(bucketNames[i]);
        const buttonHTML = !isGenericName 
            ? `<button class="add-text-anchor-btn" data-index="${i}">Add "${escapeHtml(bucketNames[i])}" as anchor</button>` 
            : '';

        const col = document.createElement('div');
        col.className = 'column';
        col.id = `col-${i}`;
        col.innerHTML = `
            <h2>
                <div style="display: flex; flex-direction: column; width: 100%;">
                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                        <input type="text" 
                               class="column-header-input" 
                               id="header-input-${i}" 
                               value="${bucketNames[i]}" 
                               placeholder="Rename bucket..."
                               title="Click to rename this bucket">
                        <span class="anchor-count" id="count-col-${i}">0 anchors</span>
                    </div>
                    <div id="text-anchor-container-${i}">${buttonHTML}</div>
                </div>
            </h2>
            <div class="image-list" id="list-col-${i}"></div>
        `;
        board.appendChild(col);

        // Listen for typing and save the name instantly to our state array
        const headerInput = col.querySelector(`#header-input-${i}`);
        const anchorBtnContainer = col.querySelector(`#text-anchor-container-${i}`);

        headerInput.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            bucketNames[i] = val || `Bucket ${i + 1}`;
            
            // Dynamically show/hide the "Add as anchor" button as they type
            const isGeneric = /^Bucket \d+$/i.test(bucketNames[i]) || bucketNames[i] === '';
            if (!isGeneric) {
                anchorBtnContainer.innerHTML = `<button class="add-text-anchor-btn" data-index="${i}">Add "${escapeHtml(bucketNames[i])}" as anchor</button>`;
                
                // Wire up the new button's event listener
                anchorBtnContainer.querySelector('.add-text-anchor-btn').addEventListener('click', () => {
                    addTextAnchor(bucketNames[i], i);
                });
            } else {
                anchorBtnContainer.innerHTML = '';
            }
        });

        headerInput.addEventListener('focus', (e) => {
            const input = e.target;
            const isGenericName = /^Bucket \d+$/i.test(input.value);
            if (isGenericName) {
                input.select(); // Instantly selects all text for immediate overtyping
            }
        });

        // Bind the button listener if it was loaded with a custom name already
        const initialBtn = anchorBtnContainer.querySelector('.add-text-anchor-btn');
        if (initialBtn) {
            initialBtn.addEventListener('click', () => {
                addTextAnchor(bucketNames[i], i);
            });
        }

        // --- NEW: Double-click to Clear Anchors ---
        const counterSpan = col.querySelector(`#count-col-${i}`);
        counterSpan.addEventListener('dblclick', () => {
            clearBucketAnchors(i);
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
                vectorObj.type = 'image';
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

    const board = document.getElementById('board-container');
    // LOCK HEIGHT: Freeze the board at its current height to prevent scroll collapse
    const currentHeight = board.scrollHeight;
    board.style.minHeight = `${currentHeight}px`;

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
                let sim = calculateSimilarity(image.vector.vector, anchorVec.vector);

                // --- THE FIX ---
                // If it's a text anchor, boost its similarity score to bridge the Modality Gap
                if (anchorVec.type === 'text') {
                    // --- PERCEPTION ENCODER SCALING ---
                    const peNoiseBaseline = 0.15; // Unrelated images sit below this

                    if (sim > peNoiseBaseline) {
                        // Extract the signal *above* the baseline
                        const signal = sim - peNoiseBaseline;

                        // Heavily amplify the signal and add a calibrated boost
                        // This stretches the 0.15 - 0.35 range out to 0.30 - 0.80
                        sim = (signal * 2.5) + 0.30; 
                    } else {
                        // Punish noise so signs don't drift in
                        sim = sim - 0.20; 
                    }
                }

                if (sim > highestSimilarity) {
                    highestSimilarity = sim;
                    bestColumn = colIndex;
                }
            });
        }

        const card = createImageCard(image, bestColumn);
        lists[bestColumn].appendChild(card);
    });

    // RELEASE HEIGHT: Let the page resize naturally to its new content height
    // We wrap this in a 0ms timeout to ensure the browser has fully drawn the new cards first
    setTimeout(() => {
        board.style.minHeight = '';
    }, 10);

}

// Generates Image Card with dynamic, numbered action buttons
function createImageCard(image, currentColumn = -1) {
    const card = document.createElement('div');
    card.className = 'image-card';
    
    const imageUrl = getGeographUrl(image.id, image.hash, 'med').replace(/_213x160/,'_224XX224');;
    
    // Generate move buttons dynamically based on how many buckets are active
    let actionButtonsHTML = '';
    for (let i = 0; i < numBuckets; i++) {
        let style = '';
        if (i === currentColumn) {
            const isAnchor = anchors[currentColumn].some(anchorVec => anchorVec === image.vector);
	    if (isAnchor)
                style = 'color:red;font-weight:bold;background-color:#ffdbdb;outline:1px solid black';
        }
        actionButtonsHTML += `<button class="move-btn" data-target="${i}" style="${style}">${i + 1}</button>`;
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

function clearBucketAnchors(targetColumnIndex) {
    // 1. Safety check: Count how many other buckets have at least 1 anchor
    let otherBucketsWithAnchors = 0;
    for (let i = 0; i < numBuckets; i++) {
        if (i !== targetColumnIndex && anchors[i] && anchors[i].length > 0) {
            otherBucketsWithAnchors++;
        }
    }

    // 2. If no other bucket has an anchor, abort and warn the user
    if (otherBucketsWithAnchors === 0) {
        alert("Cannot clear! You must leave at least one anchor on the board, otherwise the clustering algorithm has nothing to compare against.");
        return;
    }

    // 3. Clear the array for the chosen column
    anchors[targetColumnIndex] = [];

    // 4. Recalculate and re-render the board immediately
    evaluateAndRender();
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

function exportSessionState() {
    let output = '';

    // We will group our image results into buckets using the exact same evaluation logic
    const categorizedImages = {};
    for (let i = 0; i < numBuckets; i++) {
        categorizedImages[i] = {
            anchors: [],
            others: []
        };
    }

    // Step 1: Assign every image to its closest winning bucket
    allImages.forEach(image => {
        let bestColumn = 0;
        let highestSimilarity = -1;

        for (let colIndex = 0; colIndex < numBuckets; colIndex++) {
            if (anchors[colIndex].length === 0) continue;

            anchors[colIndex].forEach(anchorVec => {
                let sim = calculateSimilarity(image.vector.vector, anchorVec.vector);

                if (anchorVec.type === 'text') {
                    const peNoiseBaseline = 0.15;
                    if (sim > peNoiseBaseline) {
                        const signal = sim - peNoiseBaseline;
                        sim = (signal * 2.5) + 0.30; 
                    } else {
                        sim = sim - 0.20; 
                    }
                }

                if (sim > highestSimilarity) {
                    highestSimilarity = sim;
                    bestColumn = colIndex;
                }
            });
        }

        // Step 2: Check if this image vector is in the column's anchors list
        // Note: Comparing the image vector instance reference directly with the anchor vector instance references
        const isAnchor = anchors[bestColumn].some(anchorVec => anchorVec === image.vector);

        if (isAnchor) {
            categorizedImages[bestColumn].anchors.push(image.id);
        } else {
            categorizedImages[bestColumn].others.push(image.id);
        }
    });

    // Step 3: Loop through all buckets and compile the final text string
    for (let i = 0; i < numBuckets; i++) {
        const bucketLabel = bucketNames[i] || `Bucket ${i + 1}`;
        output += `label: ${bucketLabel}\n`;

        // Export any text anchors first
        const textAnchors = anchors[i]
            .filter(anchorVec => anchorVec.type === 'text')
            // Using the value from the UI input as the label name
            .map(anchorVec => document.getElementById(`header-input-${i}`)?.value || 'Text Anchor');

        if (textAnchors.length > 0) {
            output += `text-anchors: ${textAnchors.map(label => `[${label}]`).join(' ')}\n`;
        }

        // Format Image Anchors
        const formattedImgAnchors = categorizedImages[i].anchors.map(id => `[[[${id}]]]`).join(' ');
        output += `anchors: ${formattedImgAnchors || 'None'}\n`;

        // Format Other Images
        const formattedOthers = categorizedImages[i].others.map(id => `[[[${id}]]]`).join(' ');
        output += `others: ${formattedOthers || 'None'}\n\n`;
    }

    // Step 4: Show the textarea and populate it
    const container = document.getElementById('export-container');
    const textarea = document.getElementById('export-textarea');
    
    textarea.value = output.trim();
    container.style.display = 'block';
    
    // Auto-scroll the page up to focus on the newly opened export block
    container.scrollIntoView({ behavior: 'smooth' });
}

// Bind click listener
document.getElementById('export-btn').addEventListener('click', exportSessionState);



        // --- Helper Functions ---

// Simple helper to prevent HTML injection in dynamic labels
function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

async function addTextAnchor(label, bucketIndex) {
    label = label.replace(/,/g,' '); //comma seperates differnet labels

    const params = new URLSearchParams({
        labels: label,
        model: 'pe' // Matching your image query model
    });

    try {
        const response = await fetch(`/finder/label-vectors.json.php?${params.toString()}`);
        const labelVectorsData = await response.json();
        
        // Ensure we got vector data back for our label
        if (labelVectorsData && labelVectorsData[label]) {
            try {
                // Initialize and normalize the vector object
                const textVectorObj = new EmbeddingVector(labelVectorsData[label]).normalize();
                textVectorObj.type = 'text'; //need to know it from text.

                // Push the new text anchor to this bucket!
                anchors[bucketIndex].push(textVectorObj);
                
                // Instantly re-cluster everything based on this new zero-shot target
                evaluateAndRender();
                
                // Optional: Remove the button once clicked so they don't spam it
                const btnContainer = document.getElementById(`text-anchor-container-${bucketIndex}`);
                if (btnContainer) btnContainer.innerHTML = '<span style="font-size: 11px; color: green; font-weight: normal; margin-top: 4px;">Text vector added as anchor</span>';
                
            } catch (e) {
                console.error(`Could not parse returned vector for "${label}":`, e);
                alert(`Error parsing vector for "${label}".`);
            }
        } else {
            alert(`No vector representation found for "${label}".`);
        }
    } catch (error) {
        console.error("Error fetching text vector:", error);
        alert("Failed to fetch label vector from API.");
    }
}

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
