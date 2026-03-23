import AppState from '/app/js/app-state.js';
import { escapeHTML } from '/app/js/utils.js';

export function render() {
    return `
        <div class="view uploads-view">
            <div class="controls">
                <select id="sort-select">
                    <option value="uploaded-desc">Uploaded (Newest)</option>
                    <option value="uploaded-asc">Uploaded (Oldest)</option>
                    <option value="taken-desc">Date Taken (Newest)</option>
                    <option value="taken-asc">Date Taken (Oldest)</option>
                    <option value="grid-asc">Grid Ref (A-Z)</option>
                    <option value="grid-desc">Grid Ref (Z-A)</option>
                </select>
                <span>
                    <button id="toggle-delete-mode" class="btn btn-secondary" style="width:inherit">Select for Deletion</button>
                    <button id="delete-btn" class="btn btn-danger hidden">Delete Selected</button>
                </span>
            </div>
            <h4 style="text-align:center" id="title-prompt">Tap/click an uploaded image to submit...</h4>
            <form id="uploads-grid" class="submissions-grid"></form>
            <div id="selection-controls" class="controls hidden">
                <button id="select-all-btn" class="btn btn-secondary">Select All</button>
                <button id="deselect-all-btn" class="btn btn-secondary">Deselect All</button>
            </div>
        </div>
    `;
}

let currentData = [];
let isDeleteMode = false;

export async function onMount() {
    const gridContainer = document.getElementById('uploads-grid');
    const sortSelect = document.getElementById('sort-select');
    const deleteToggle = document.getElementById('toggle-delete-mode');
    const deleteBtn = document.getElementById('delete-btn');
    const titlePrompt = document.getElementById('title-prompt');
    const selectionControls = document.getElementById('selection-controls');

    sortSelect.value = AppState.getPreference('uploadedSort', 'uploaded-desc');

    const response = await fetch('/app/uploads.json.php');
    currentData = await response.json();

    //only show if something to sort!
    sortSelect.classList.toggle('hidden', currentData.length<2);

    // Render logic
    const updateGrid = () => {
        if (currentData.length == 0) {
            gridContainer.innerHTML = `No Images! <button class="btn btn-primary" data-route="/app/upload">Upload Image(s)</button>`;
            return;
        }
	if (currentData.length == 200 && !isDeleteMode)
		titlePrompt.textContent = "Showing 200 random items. Recent items may be hidden. Use the 'Select for Deletion' button to remove images you don't intend to submit, and refresh the list.";

        // 1. Sort based on select value
        const val = sortSelect.value;

	AppState.setPreference('uploadedSort', val);

        const sorted = [...currentData].sort((a, b) => {
            if (val === 'uploaded-asc') return a.uploaded - b.uploaded;
            if (val === 'uploaded-desc') return b.uploaded - a.uploaded;
            if (val === 'taken-asc') return a.image_taken - b.image_taken;
            if (val === 'taken-desc') return b.image_taken - a.image_taken;
            if (val === 'grid-asc') return (a.grid_reference || '').localeCompare(b.grid_reference || '');
            if (val === 'grid-desc') return (b.grid_reference || '').localeCompare(a.grid_reference || '');
            return 0;
        });

        //if in delete mode, need to remeber which are ticked (if any!) so can stay ticked!
        const selectedIds = Array.from(document.querySelectorAll('.delete-check:checked')).map(c => c.value);

        // 2. Map items with conditional checkbox
        gridContainer.innerHTML = sorted.map(item => {
            const escapedItem = JSON.stringify(item).replace(/"/g, '&quot;');
            return `
                <div class="submission-tile"
                    ${isDeleteMode ? '':` data-route="/app/submit" data-message="${escapedItem}"`}
                    data-id="${item.transfer_id}">
                    ${isDeleteMode ? `<input type="checkbox" class="delete-check" value="${item.transfer_id}" ${selectedIds.includes(item.transfer_id)?'checked':''}>` : ''}
                    <img src="/submit.php?preview=${item.transfer_id}" draggable="false">
                    <div class="tile-overlay"><span>${escapeHTML(item.grid_reference || '')}</span></div>
                </div>
            `;
        }).join('')
    };

    // Listeners
    sortSelect.onchange = updateGrid;

    deleteToggle.onclick = () => {
        isDeleteMode = !isDeleteMode;
        deleteToggle.textContent = isDeleteMode ? "Cancel Selection" : "Select for Deletion";
        titlePrompt.textContent = isDeleteMode ? "Select image(s) to delete (rather than submit)...":"Tap/click an uploaded image to submit...";
        deleteBtn.classList.toggle('hidden', !isDeleteMode);
        selectionControls.classList.toggle('hidden', !isDeleteMode);
        updateGrid();
    };

    deleteBtn.onclick = async () => {
        const selectedIds = Array.from(document.querySelectorAll('.delete-check:checked')).map(c => c.value);
        if(selectedIds.length === 0) return alert("Select images first");

        //Confirm and process
        if (confirm(`Delete ${selectedIds.length} images from temporary uploads area?`)) {
            await performDelete(selectedIds);

            deleteToggle.click(); //turn off delete mode!

            //if there are no images (all deleted)
            if (gridContainer.children.length == 0) {
                gridContainer.innerHTML = `<button class="btn btn-primary" data-route="/app/upload">Proceed to Upload</button>`;
                deleteToggle.classList.add('hidden');
            }
        }
    };

    gridContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('delete-check')) {
            return;
        }

        // Find the closest parent tile
        const tile = e.target.closest('.submission-tile');
        if (!tile) return;

        // If in delete mode, toggle the checkbox inside the tile
        if (isDeleteMode) {
            const checkbox = tile.querySelector('.delete-check');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
            }
        }
    });

    document.getElementById('select-all-btn').onclick = () => {
        document.querySelectorAll('.delete-check').forEach(cb => cb.checked = true);
    };
    document.getElementById('deselect-all-btn').onclick = () => {
        document.querySelectorAll('.delete-check').forEach(cb => cb.checked = false);
    };

    updateGrid(); // Initial render
}


async function performDelete(selectedIds) {
    const BATCH_SIZE = 50;
    const total = selectedIds.length;
    const deleteBtn = document.getElementById('delete-btn');
     deleteBtn.textContent = `Processing...`;

    // Split the array into chunks of 50
    const batches = Math.ceil(selectedIds.length/BATCH_SIZE);
    for (let i = 0; i < total; i += BATCH_SIZE) {
        const batch = selectedIds.slice(i, i + BATCH_SIZE);

        try {
            // Send batch to your PHP endpoint
            const response = await fetch('/app/uploads.json.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: batch })
            });

            if (!response.ok) throw new Error(`Batch ${i/BATCH_SIZE + 1} failed`);

            // Remove only after the server confirms success for this batch
            batch.forEach(id => {
                const tile = document.querySelector(`.submission-tile[data-id="${id}"]`);
                if (tile) tile.remove();
            });
            currentData = currentData.filter(item => !batch.includes(item.transfer_id));

            deleteBtn.textContent = `deleted batch ${i/BATCH_SIZE + 1}/${batches}`;
            console.log(`Successfully deleted batch ${i/BATCH_SIZE + 1}`);
        } catch (err) {
            console.error("Delete error:", err);
            alert(`Failed to delete some items. Error: ${err.message}`);
            break; // Stop processing further batches
        }
    }
    // Now trigger the alert
    setTimeout(() => {
        alert("Deletion process complete.");
        deleteBtn.textContent = `Delete Selected`;
    }, 50); // Small buffer to ensure the repaint happens
}
