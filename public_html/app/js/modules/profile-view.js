import { escapeHTML } from '/app/js/utils.js';

export function render() {
    return `
        <div class="view profile-view">
            <h2>Your Submissions / <a href="#" data-route="/app/recent">Review</a></h2>

            <div class="controls" style="margin-bottom: 20px;">
                <label>
                    <input type="radio" name="view-filter" value="recent" checked> Last 3 Days <span id="counter"></span>
                </label>
                <label style="margin-left: 15px;">
                    <input type="radio" name="view-filter" value="all"> Last 100 Images
                </label>
            </div>

            <div id="submissions-grid" class="submissions-grid">
                <p>Fetching your photos...</p>
            </div>
        </div>

	<br>
	<p>Only shows a submissions (including Pending) from last 3 days, view <a href="/profile.php">Full site Profile</a> for more.
    `;
}

async function loadSubmissions(filter = 'recent') {
    const gridContainer = document.getElementById('submissions-grid');
    const url = filter === 'all'
        ? '/stuff/submissions.json.php?thumbs=1&images=100'
        : '/stuff/submissions.json.php?thumbs=1';

    gridContainer.innerHTML = '<p>Loading...</p>';

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.length === 0) {
            gridContainer.innerHTML = '<p>No submissions found.</p>';
            return;
        }

        if (filter == 'recent')
		 document.getElementById('counter').textContent = `[${data.length}]`;

        gridContainer.innerHTML = data.map(item => `
            <div class="submission-tile"
                 data-large="${item.thumbnail.replace(/_\d+x\d+/, '')}"
                 data-id="${item.gridimage_id}" title="${escapeHTML(item.title)}">
                <img src="${item.thumbnail}" loading="lazy" alt="${escapeHTML(item.title)}">
                <div class="tile-overlay"><span>${item.moderation_status} / ${item.grid_reference}</span></div>
            </div>
        `).join('');
    } catch (err) {
        gridContainer.innerHTML = '<p>Error loading gallery.</p>';
    }
}

export async function onMount() {
    // Initial Load
    loadSubmissions('recent');

    // Handle Toggle Changes
    document.querySelectorAll('input[name="view-filter"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            loadSubmissions(e.target.value);
        });
    });

    const gridContainer = document.getElementById('submissions-grid');

        // Delegate click events to the grid
        gridContainer.addEventListener('click', (e) => {
            const tile = e.target.closest('.submission-tile');
            if (!tile) return;

            const largeUrl = tile.dataset.large;
            const id = tile.dataset.id;

            // Update Modal
            modalImg.src = largeUrl;
            fullPageLink.href = `/photo/${id}`;

            modal.showModal(); // Opens as a top-layer backdrop
        });

        // Close logic
        document.getElementById('close-modal').addEventListener('click', () => modal.close());
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.close(); });
}
