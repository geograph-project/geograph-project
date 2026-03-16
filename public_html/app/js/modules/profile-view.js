import { escapeHTML, navigateTo } from '/app/js/utils.js';

export function render() {
    return `
        <div class="view profile-view" style="text-align:center">

    	    <div id="statsCard" class="stats-grid"></div>

            <button id="edit-btn" class="btn btn-secondary hidden" data-route="/app/recent">Edit Recent Submissions</button>

		<form id="search-form" class="search-container">
		    <input type="search" name="q" placeholder="Search your submissions..." enterkeyhint="search">
		    <button type="submit" aria-label="Search">&#x1F50D;</button>
		</form>

            <div class="controls">
	        	Submissions:
                <label id="recent-label">
                    <input type="radio" name="view-filter" value="recent" checked> Last 3 Days <span id="counter"></span>
                </label>
                <label style="margin-left: 15px;">
                    <input type="radio" name="view-filter" value="all" id="all-checkbox"> Last <span id="counter2">100</span> Images
                </label>
            </div>

            <div id="submissions-grid" class="submissions-grid">
                <p>Fetching your photos...</p>
            </div>

            <dialog id="photo-modal" class="photo-modal">
                <div class="modal-content">
                    <h4 id="modal-title"></h4>
                    <img id="modal-img" src="" alt="Draft Preview">
                    <div class="modal-controls">
			<a href="#" id="full-page-link" target="_blank" class="btn">View Photo Page</a>
			<a href="#" id="edit-page-link" target="_blank" class="btn">Open Edit Page</a>
                        <button id="close-modal" class="btn btn-secondary">Close</button>
                    </div>
                </div>
            </dialog>
        </div>

	<br>
	<p>Only shows a submissions (including Pending) from last 3 days, view <a href="/profile.php" target="_blank">Full site Profile</a> for more.

	<p id="timestamp"></p>

<style>

.search-container {
    display: flex;
    gap: 8px; /* Space between input and button */
    width: 100%;
}

.search-container input {
    flex: 1; /* Makes the input grow to fill available space */
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
}

.search-container button {
    padding: 0 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    background: #f0f0f0;
    cursor: pointer;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
    margin-bottom: 6px;
}

.stat-tile {
    background: #f4f4f9;
    padding: 15px 10px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e0e0e0;
}

.stat-value {
    --display: block;
    font-size: 1.2rem;
    font-weight: bold;
    color: #333;
}

.stat-label {
    color: #666;
    font-size:0.75em;
    --text-transform: uppercase;
}

p#timestamp {
	padding:10px;
}

.controls {
    background: #f4f4f9;

}
</style>

    `;
}



async function loadSubmissions(filter = 'recent') {
    const gridContainer = document.getElementById('submissions-grid');
    const url = filter === 'all'
        ? '/app/submissions.json.php?thumbs=1&images=100'
        : '/app/submissions.json.php?thumbs=1';

    gridContainer.innerHTML = '<p>Loading...</p>';

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.length === 0) {
            if (filter = 'recent') {
                //well, if no results!
                document.getElementById('recent-label').style.display='none';
                document.getElementById('all-checkbox').checked = true;
		loadSubmissions('all')
                document.getElementById('edit-btn').style.display='none'; //currently only edits 'recent', not last 100 anyway
            } else {
		document.getElementById('search-form').style.display='none';
	    }
            gridContainer.innerHTML = '<p>No submissions found.</p>';
            return;
        }

        if (filter == 'recent')
		 document.getElementById('counter').textContent = `[${data.length}]`;
	else //might as well make the count accurate, if there are less than 100
		 document.getElementById('counter2').textContent = data.length;

        gridContainer.innerHTML = data.map(item => `
            <div class="submission-tile"
                 data-large="${item.thumbnail.replace(/_\d+x\d+/, '')}"
                 data-id="${item.gridimage_id}" title="${escapeHTML(item.title)}">
                <img src="${item.thumbnail}" loading="lazy" alt="${escapeHTML(item.title)}" draggable="false">
                <div class="tile-overlay"><span>${item.moderation_status} / ${item.grid_reference}</span></div>
            </div>
        `).join('');
    } catch (err) {
        gridContainer.innerHTML = '<p>Error loading gallery.</p>';
    }
}

async function displayStats() {
   const url = "/app/stats.json.php";
   try {
        const response = await fetch(url);
        const data = await response.json();

        const displayData = {
            "Total Images": data.images,
            "Personal Points": data.geosquares,
            "Firsts": data.points,
            "T-Points": data.tpoints,
            "Pending Uploads": data.pending
        };

        let htmlOutput = '';
        for (const [label, value] of Object.entries(displayData)) {
        	if (value > 0) {
                const formattedValue = value.toLocaleString();

    	        htmlOutput += `
    	            <div class="stat-tile">
    	                <span class="stat-value">${formattedValue}</span>
    	                <span class="stat-label">${label}</span>
            	    </div>`;
        	}
        }
        document.getElementById('statsCard').innerHTML = htmlOutput;

        // Add the timestamp note
        if (data.updated && htmlOutput.length) {
    	    // Calculate time difference
    	    const updatedDate = new Date(data.updated?.replace(' ', 'T'));
    	    const now = new Date();
	 	const diffInMinutes = Math.floor(now - updatedDate / (1000 * 60));
		const diffInHours = Math.floor(diffInMinutes / 60);

		let timeDisplay;
		if (diffInHours > 0) {
		    timeDisplay = `${diffInHours} hours ago`;
		} else {
		    timeDisplay = `${diffInMinutes} minutes ago`;
		}
		document.getElementById('timestamp').innerHTML = `<em>Stats updated ${timeDisplay}</em>`;
        }
   } catch (err) {
       console.log(err);
   }
}

export async function onMount() {
    // Initial Load
    loadSubmissions('recent');
    displayStats();

    document.getElementById('search-form').addEventListener('submit', (e) => {
        e.preventDefault();
        const query = e.target.elements.q.value;
        const userId = window.GEOGRAPH_USER_PREFERENCES['user_id'];

        navigateTo('/app/results', {param: `q=${encodeURIComponent(query)}&contributor=${encodeURIComponent(userId)}+Myself`});
    });

    // Handle Toggle Changes
    document.querySelectorAll('input[name="view-filter"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            loadSubmissions(e.target.value);
        });
    });

    const gridContainer = document.getElementById('submissions-grid');
    const modal = document.getElementById('photo-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalImg = document.getElementById('modal-img');
    const fullPageLink = document.getElementById('full-page-link');
    const editPageLink = document.getElementById('edit-page-link');

        // Delegate click events to the grid
        gridContainer.addEventListener('click', (e) => {
            const tile = e.target.closest('.submission-tile');
            if (!tile) return;

            const largeUrl = tile.dataset.large;
            const id = tile.dataset.id;

            // Update Modal
            modalTitle.textContent = tile.title;
            modalImg.src = largeUrl;
            fullPageLink.href = `/photo/${id}`;
            editPageLink.href = `/editimage.php?id=${id}`;

            modal.showModal(); // Opens as a top-layer backdrop
        });

        // Close logic
        document.getElementById('close-modal').addEventListener('click', () => modal.close());
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.close(); });
}
