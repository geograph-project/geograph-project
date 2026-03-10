import { escapeHTML } from '/app/js/utils.js';

export function render() {
    return `
        <div class="view uploads-view">
            <h2>Uploaded Images</h2>
            <p>These images are uploaded but not yet submitted to the archive.</p>

            <button id="next-button" class="btn btn-primary">Submit First Image Now</button>

            <div id="uploads-grid" class="submissions-grid">
                <p>Loading your drafts...</p>
            </div>

            <dialog id="photo-modal" class="photo-modal">
                <div class="modal-content">
                    <img id="modal-img" src="" alt="Draft Preview">
                    <div class="modal-controls">
                        <button id="finish-submission" class="btn btn-primary">Finish Submission</button>
                        <button id="close-modal" class="btn btn-secondary">Close</button>
                    </div>
                </div>
            </dialog>
        </div>
    `;
}

export async function onMount() {
    const gridContainer = document.getElementById('uploads-grid');
    const btn = document.getElementById('next-button');
    const modal = document.getElementById('photo-modal');
    const modalImg = document.getElementById('modal-img');
    const finishLink = document.getElementById('finish-submission');

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
	btn.dataset.message = `transfer_id=${data[0].transfer_id}`;

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
                    <img src="${thumbUrl}" loading="lazy" alt="Draft">
                    <div class="tile-overlay"><span>${escapeHTML(displayLabel)}</span></div>
                </div>
            `;
        }).join('');

return;

        // Reuse the click delegation logic
        gridContainer.addEventListener('click', (e) => {
            const tile = e.target.closest('.submission-tile');
            if (!tile) return;

            const previewUrl = tile.dataset.preview;
            const id = tile.dataset.id;

            modalImg.src = previewUrl;

	    finishLink.dataset.route = "/app/submit";
	    finishLink.dataset.message = `transfer_id=${id}`;

            modal.showModal();
        });

        // Close handlers
        finishLink.addEventListener('click', () => modal.close());
        document.getElementById('close-modal').addEventListener('click', () => modal.close());
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.close(); });

    } catch (err) {
        console.error("Uploads fetch error:", err);
        gridContainer.innerHTML = '<p>Error loading drafts.</p>';
    }
}
