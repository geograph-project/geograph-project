import { escapeHTML } from '/app/js/utils.js';

export function render() {
    return `
        <div class="view uploads-view">
            <h2>Uploaded Images</h2>
            <p>These images are uploaded but not yet submitted to the archive.</p>

            <button id="next-button" class="btn btn-primary hidden">Submit First Image Now</button>

            <div id="uploads-grid" class="submissions-grid">
                <p>Loading your drafts...</p>
            </div>
        </div>
    `;
}

export async function onMount() {
    const gridContainer = document.getElementById('uploads-grid');
    const btn = document.getElementById('next-button');

    try {
        const response = await fetch('/app/uploads.json.php');
        const data = await response.json();

        if (data.length === 0) {
            gridContainer.innerHTML = '<p>No pending uploads found.</p>';
  	        btn.style.display ='none';
            return;
        }

        // Sort data by 'uploaded' in descending order (newest first) - todo add option!
        data.sort((a, b) => b.uploaded - a.uploaded);

        btn.dataset.route = "/app/submit";
        btn.dataset.message = `transfer_id=${data[0].transfer_id}`; //todo should actulyl be the full escapedItem!

        gridContainer.innerHTML = data.map(item => {
            const thumbUrl = `/submit.php?preview=${item.transfer_id}`;
            const displayLabel = item.grid_reference || ''; //No Gridref';

    	    const itemString = JSON.stringify(item);
	        const escapedItem = itemString.replace(/"/g, '&quot;');

            return `
                <div class="submission-tile"
  		             data-route="/app/submit" data-message="${escapedItem}"
                     data-preview="${thumbUrl}"
                     data-id="${item.transfer_id}">
                    <img src="${thumbUrl}" loading="lazy" alt="Draft" draggable="false">
                    <div class="tile-overlay"><span>${escapeHTML(displayLabel)}</span></div>
                </div>
            `;
        }).join('');

    } catch (err) {
        console.error("Uploads fetch error:", err);
        gridContainer.innerHTML = '<p>Error loading drafts.</p>';
    }
}
