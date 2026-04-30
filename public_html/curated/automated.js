
// 1. Setup the Dialog container (run once)
const modal = document.createElement('dialog');
modal.id = 'curation-modal';
modal.innerHTML = `
  <div class="modal-content">
    <button id="modal-close-btn" class="close-btn">&#10005;</button>
    
    <div class="modal-main-view">
      <button id="prev-btn" class="nav-btn">&lt;</button>
      <div class="image-container">
        <img id="modal-large-img" src="" alt="Full size">
        <div id="modal-rating-placeholder"></div>
      </div>
      <button id="next-btn" class="nav-btn">&gt;</button>
    </div>

    <hr>
    <h3>Similar Images</h3>
    <div class="thumb-grid">
       <p style="color: #666;">Loading similar images...</p>
    </div>
  </div>
`;
document.body.appendChild(modal);

// A place to store results for images NOT visible in the background grid
const shadowContainer = document.createElement('div');
shadowContainer.id = 'shadow-results-container';
shadowContainer.style.display = 'none';
document.forms['theForm'].appendChild(shadowContainer); // Append to your main form

let currentIndex = 0;
let allThumbs = Array.from(document.querySelectorAll('.thumb-card img'));

// 2. The Core Function to Update Modal Content
function openCurationModal(index) {
  currentIndex = index;
  const thumb = allThumbs[currentIndex];
  const container = thumb.closest('.thumb-card');
  
  // Get Large Image URL
  const largeUrl = thumb.src.replace(/_\d+x\d+/, '');
  document.getElementById('modal-large-img').src = largeUrl;

  // Replicate Rating Bar
  const originalRating = container.querySelector('.rating-bar');
  const placeholder = document.getElementById('modal-rating-placeholder');
  placeholder.innerHTML = ''; // Clear previous
  
  if (originalRating) {
    const clonedRating = originalRating.cloneNode(true);
    const timestamp = Date.now(); // Unique string for this modal instance

    // Sync the radio buttons (unique names for the modal)
    const originalChecked = originalRating.querySelector('input:checked');

    if (originalChecked) {
      const valueToMatch = originalChecked.value;
      clonedRating.querySelectorAll('input').forEach(input => {

        const label = clonedRating.querySelector(`label[for="${input.id}"]`);
        const newId = `modal-${input.id}-${timestamp}`;

	// 1. Give the input a unique Name and ID for the modal
        input.name = "modal-rating";
        input.id = newId;

        //Update the corresponding Label to point to the new ID
        if (label) { label.setAttribute('for', newId); }

        if (input.value === valueToMatch) input.checked = true;

        // Bonus: Update the background form when modal rating changes
        input.addEventListener('change', (e) => {
          const mainInput = originalRating.querySelector(`input[value="${e.target.value}"]`);
          if (mainInput) mainInput.checked = true;
        });
      });
    }
    placeholder.appendChild(clonedRating);
  }

  //don't await!
  loadSimilarImages(thumb.src);

  modal.showModal();
}

// 3. Event Listeners
document.addEventListener('click', (e) => {
  const thumbLink = e.target.closest('a[href*="/photo/"]');
  if (thumbLink) {
    e.preventDefault();
    const clickedImg = thumbLink.querySelector('img');
    const idx = allThumbs.indexOf(clickedImg);
    openCurationModal(idx);
  }
});

document.getElementById('modal-close-btn').addEventListener('click', () => {
  saveModalResults(); // Save state before moving
  modal.close();
});

// Navigation logic
document.getElementById('prev-btn').onclick = () => {
  saveModalResults(); // Save state before moving
  if (currentIndex > 0) openCurationModal(currentIndex - 1);
};

document.getElementById('next-btn').onclick = () => {
  saveModalResults(); // Save state before moving
  if (currentIndex < allThumbs.length - 1) openCurationModal(currentIndex + 1);
};

// Close modal when clicking backdrop
modal.addEventListener('click', (e) => {
	if (e.target === modal) {
		saveModalResults();
		modal.close();
	}
});

// When closing via Backdrop or Escape key
modal.addEventListener('close', () => {
  saveModalResults();
});

async function loadSimilarImages(thumbSrc) {
  const gridElement = document.querySelector('#curation-modal .thumb-grid');
  gridElement.innerHTML = '<p>Searching for similar images...</p>';

  // 1. Extract ID from src using your regex
  const match = thumbSrc.match(/\d\/(\d{6,})_\w{8}/);
  if (!match) {
    gridElement.innerHTML = '<p>Could not extract ID for lookup.</p>';
    return;
  }
  const photoId = match[1];

  // 2. Build API URL
  const apiUrl = `https://api.geograph.org.uk/api-facetql-vector.php?long=1&select=id%2Cuser_id%2Crealname%2Cgrid_reference%2Ctitle%2Chash%2Ctakenyear%2Coriginal&limit=30&utf=1&label=%5Bid%3A${photoId}%5D&model=pe`;

  try {
    const response = await fetch(apiUrl);
    const data = await response.json();

    if (data.rows && data.rows.length > 0) {
      gridElement.innerHTML = ''; // Clear loading text

      data.rows.forEach(row => {
        if (row.id == photoId) //very good chance the original image will be included in visual results.
           return;
	const existingShadow = shadowContainer.querySelector(`input[name="result[${row.id}]"]`);
	const existingMain = document.querySelector(`input[name="result[${row.id}]"]:checked`);

	// Determine if this should be checked on render
	const currentValue = existingShadow ? existingShadow.value : (existingMain ? existingMain.value : null);
        const isChecked = (val) => (currentValue === val ? 'checked' : '');

        const item = document.createElement('div');
        item.className = 'thumb-card';
        item.innerHTML = `
          <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank" title="${escapeHtml(row.title)} by ${escapeHtml(row.realname)}">
            <img src="${getGeographUrl(row.id, row.hash, 'med')}" alt="${escapeHtml(row.title)}" loading="lazy">
          </a>
          ${getSizeLabel(row.original)}
	  ${row.takenyear>1000?`<div class="year-label">${row.takenyear}</div>`:''}

    <div class='rating-bar'>
        <input type='radio' id='bad2-${row.id}' name='result2[${row.id}]' value='bad' ${isChecked('bad')}>
        <label for='bad2-${row.id}' class='label-bad'>&#128078;</label>

        <input type='radio' id='ok2-${row.id}' name='result2[${row.id}]' value='ok' ${isChecked('ok')}>
        <label for='ok2-${row.id}' class='label-ok'>OK</label>

        <input type='radio' id='good2-${row.id}' name='result2[${row.id}]' value='good' ${isChecked('good')}>
        <label for='good2-${row.id}' class='label-good'>&#128077;</label>
    </div>
        `;
        gridElement.appendChild(item);
      });
    } else {
      gridElement.innerHTML = '<p>No similar images found.</p>';
    }
  } catch (err) {
    console.error("API Error:", err);
    gridElement.innerHTML = '<p>Error loading similar images.</p>';
  }
}

function getSizeLabel(width) {
  const w = parseInt(width);
  if (w >= 3000) return `<div class="size-label">3000px+</div>`;
  if (w >= 1600) return `<div class="size-label">1600px+</div>`;
  if (w >= 1024) return `<div class="size-label">1024px+</div>`;
  if (w == 1024) return `<div class="size-label">1024px</div>`;
  return ''; // Show nothing if below 1024
}


function saveModalResults() {
  const modalRadios = document.querySelectorAll('#curation-modal .rating-bar input:checked');
  
  modalRadios.forEach(modalRadio => {
    // Extract the ID from the modal's input name/id
    // Assuming name format: sim-res[123456] or result[123456]
    const photoIdMatch = modalRadio.name.match(/\[(\d+)\]/);
    if (!photoIdMatch) return;
    
    const photoId = photoIdMatch[1];
    const ratingValue = modalRadio.value;

    // 1. Try to find the radio in the background grid
    const mainRadio = document.querySelector(`.thumb-grid input[name="result[${photoId}]"][value="${ratingValue}"]`);

    if (mainRadio) {
      // Case A: Image is in the background grid
      mainRadio.checked = true;
      mainRadio.dispatchEvent(new Event('change', { bubbles: true }));
    } else {
      // Case B: Image is NOT in the grid (it came from the 'Similar' API)
      let hiddenInput = shadowContainer.querySelector(`input[name="result[${photoId}]"]`);
      
      if (!hiddenInput) {
        hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = `result[${photoId}]`;
        shadowContainer.appendChild(hiddenInput);
      }
      hiddenInput.value = ratingValue;
    }
  });
}
