export function render() {
    return `
        <div class="view uploads-view">
            <h2>Uploaded Images</h2>
            <p>These images are uploaded but not yet submitted to the archive.</p>
            
            <div id="uploads-grid" class="submissions-grid">
                <p>Loading your drafts...</p>
            </div>

            <dialog id="photo-modal" class="photo-modal">
                <div class="modal-content">
                    <img id="modal-img" src="" alt="Draft Preview">
                    <div class="modal-controls">
                        <a id="finish-submission-link" href="#" class="demo-btn">Finish Submission</a>
                        <button id="close-modal" class="demo-btn secondary">Close</button>
                    </div>
                </div>
            </dialog>
        </div>
    `;
}

export async function onMount() {
    const gridContainer = document.getElementById('uploads-grid');
    const modal = document.getElementById('photo-modal');
    const modalImg = document.getElementById('modal-img');
    const finishLink = document.getElementById('finish-submission-link');

    try {
        const response = await fetch('/stuff/uploads.json.php');
        const data = await response.json();

        if (data.length === 0) {
            gridContainer.innerHTML = '<p>No pending uploads found.</p>';
            return;
        }

        gridContainer.innerHTML = data.map(item => {
            const thumbUrl = `/submit.php?preview=${item.transfer_id}`;
            const displayLabel = item.grid_reference || ''; //No Gridref';
            
            return `
                <div class="submission-tile" 
                     data-preview="${thumbUrl}" 
                     data-id="${item.transfer_id}">
                    <img src="${thumbUrl}" loading="lazy" alt="Draft">
                    <div class="tile-overlay"><span>${displayLabel}</span></div>
                </div>
            `;
        }).join('');

        // Reuse the click delegation logic
        gridContainer.addEventListener('click', (e) => {
            const tile = e.target.closest('.submission-tile');
            if (!tile) return;

            const previewUrl = tile.dataset.preview;
            const id = tile.dataset.id;

            // In this case, the preview URL is likely the best we have
            modalImg.src = previewUrl; 
            // Assuming finish page follows this pattern:
            finishLink.href = `/submit2.php?transfer_id=${id}`;
            
            modal.showModal();
        });

        // Close handlers
        document.getElementById('close-modal').addEventListener('click', () => modal.close());
        modal.addEventListener('click', (e) => { if (e.target === modal) modal.close(); });

    } catch (err) {
        console.error("Uploads fetch error:", err);
        gridContainer.innerHTML = '<p>Error loading drafts.</p>';
    }
}
