export function render() {
    return `
        <div class="view profile-view">
            <h2>Your Submissions</h2>
            <div id="submissions-grid" class="submissions-grid">
                <p>Fetching your photos...</p>
            </div>

            <dialog id="photo-modal" class="photo-modal">
                <div class="modal-content">
                    <img id="modal-img" src="" alt="Large view">
                    <div class="modal-controls">
                        <a id="full-page-link" href="#" target="_blank" class="demo-btn">View Full Page</a>
                        <button id="close-modal" class="demo-btn secondary">Close</button>
                    </div>
                </div>
            </dialog>
        </div>
    `;
}

export async function onMount() {
    const gridContainer = document.getElementById('submissions-grid');
    const modal = document.getElementById('photo-modal');
    const modalImg = document.getElementById('modal-img');
    const fullPageLink = document.getElementById('full-page-link');

    try {
        const response = await fetch('/stuff/submissions.json.php?thumbs=1');
        const data = await response.json();

        gridContainer.innerHTML = data.map(item => `
            <div class="submission-tile" 
                 data-large="${item.thumbnail.replace(/_\d+x\d+/, '')}" 
                 data-id="${item.gridimage_id}">
                <img src="${item.thumbnail}" loading="lazy" alt="${item.title}">
                <div class="tile-overlay"><span>${item.grid_reference}</span></div>
            </div>
        `).join('');

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

    } catch (err) {
        gridContainer.innerHTML = '<p>Error loading gallery.</p>';
    }
}
