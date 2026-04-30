
// 1. Setup the Dialog container (run once)
const modal = document.createElement('dialog');
modal.id = 'curation-modal';
modal.innerHTML = `
  <div class="modal-content">
    <button id="modal-close-btn" class="close-btn">&#10005;</button>
    
    <div class="modal-main-view">
      <button id="prev-btn" class="nav-btn">&lt;</button>
      <div class="image-container">
        <a href="/photo/">
           <img id="modal-large-img" src="" alt="Full size">
        </a>
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
  let thumb = allThumbs[currentIndex];

  // Get Large Image URL
  const largeUrl = thumb.src.replace(/_\d+x\d+/, '');
  const photoId = thumb.src.match(/\d\/(\d{6,})_\w{8}/)[1];

  const largeImg = document.getElementById('modal-large-img');
  largeImg.src = largeUrl;
  largeImg.parentElement.href = `https://www.geograph.org.uk/photo/${photoId}`;
  largeImg.title = thumb.alt; //actully better to put it direct into title for a tooltip

  // 2. Get the current rating value from anywhere (Main Grid or Shadow)
  const existingShadow = shadowContainer.querySelector(`input[name="result[${photoId}]"]`);
  const existingMain = document.querySelector(`.main-grid .thumb-card input[name="result[${photoId}]"]:checked`);
  const currentVal = existingShadow ? existingShadow.value : (existingMain ? existingMain.value : null);

  // 3. Render the rating bar from scratch
  const placeholder = document.getElementById('modal-rating-placeholder');
  placeholder.innerHTML = generateRatingHTML(photoId, 'modal-main', currentVal);

  // 4. Manual sync logic (since it's not a clone anymore)
  placeholder.querySelectorAll('input').forEach(input => {
    input.addEventListener('change', (e) => {
      syncBackToSource(photoId, e.target.value);
    });
  });

  //don't await!
  loadSimilarImages(thumb.src);

  if (modal.open) {
    //if clicka similar image, scroll back to top
    modal.scrollTo({
      top: 0,
      behavior: 'smooth' // 'smooth' for a nice transition, or 'instant' for speed
    });
  } else {
    modal.showModal();
  }
}

function generateRatingHTML(photoId, instancePrefix, currentVal = null) {
  const isChecked = (val) => (currentVal === val ? 'checked' : '');
  return `
    <div class='rating-bar' data-photo-id="${photoId}">
        <input type='radio' id='bad-${instancePrefix}-${photoId}' name='result2[${photoId}]' value='bad' ${isChecked('bad')}>
        <label for='bad-${instancePrefix}-${photoId}' class='label-bad'>&#128078;</label>

        <input type='radio' id='ok-${instancePrefix}-${photoId}' name='result2[${photoId}]' value='ok' ${isChecked('ok')}>
        <label for='ok-${instancePrefix}-${photoId}' class='label-ok'>OK</label>

        <input type='radio' id='good-${instancePrefix}-${photoId}' name='result2[${photoId}]' value='good' ${isChecked('good')}>
        <label for='good-${instancePrefix}-${photoId}' class='label-good'>&#128077;</label>
    </div>
  `;
}

///////////////////////////////////////////////////////////////
// 3. Event Listeners

document.addEventListener('click', (e) => {
  if (e.target.id === 'modal-large-img') {
    return; // Don't preventDefault, let the browser open the link
  }

  const thumbLink = e.target.closest('a[href*="/photo/"]');
  if (thumbLink) {
    e.preventDefault();

    if (e.target.closest('.modal-content')) { //if clicking a thumb INSIDE the modal
       //... need to redefine allThumbs to be the current similar images!
       allThumbs = Array.from(document.querySelectorAll('.modal-content .thumb-card img'));
    } else {
       //otherwise reset to the main document thumbs
       allThumbs = Array.from(document.querySelectorAll('.main-grid .thumb-card img'));
    }

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

///////////////////////////////////////////////////////////////

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

        const item = document.createElement('div');
        item.className = 'thumb-card';
        item.innerHTML = `
          <a href="https://www.geograph.org.uk/photo/${row.id}" target="_blank" title="${escapeHtml(row.title)} by ${escapeHtml(row.realname)}">
            <img src="${getGeographUrl(row.id, row.hash, 'med')}" alt="${escapeHtml(row.title)}" loading="lazy">
          </a>
          ${getSizeLabel(row.original)}
	  ${row.takenyear>1000?`<div class="year-label">${row.takenyear}</div>`:''}

          ${generateRatingHTML(row.id, 'sim', currentValue)}
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

    syncBackToSource(photoId, ratingValue);
  });
}


function syncBackToSource(photoId, value) {
  // Update the background grid if it exists
  const mainInput = document.querySelector(`.main-grid .thumb-card input[name="result[${photoId}]"][value="${value}"]`);
  if (mainInput) {
    mainInput.checked = true;
    mainInput.dispatchEvent(new Event('change', { bubbles: true }));
  } else {
    // otherwise update the shadow container (for the API-sourced images)
    let hiddenInput = shadowContainer.querySelector(`input[name="result[${photoId}]"]`);
    if (!hiddenInput) {
      hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = `result[${photoId}]`;
      shadowContainer.appendChild(hiddenInput);
    }
    hiddenInput.value = value;
  }
}
