<?php

require_once('geograph/global.inc.php');
require_once('geograph/uploadmanager.class.php');

init_session();

$USER->mustHavePerm('basic');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NanoGallery Pro</title>
    <script src="https://cdn.jsdelivr.net/npm/exifr@7.1.3/dist/lite.umd.js"></script>
    <script src="<?php echo smarty_modifier_revision("/mapper/geotools2.js"); ?>"></script>

    <style>
        :root { --bg: #000; --card: #1a1a1a; --text: #eee; --accent: #007AFF; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 0; }
        
        /* Header & Nav */
        header { sticky: top; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 0.5px solid #333; z-index: 100; position: sticky; top: 0; }
        .stats { font-size: 12px; color: #888; }
        
        /* Controls */
        .toolbar { padding: 10px; display: flex; gap: 8px; background: #111; }
        button { background: #2c2c2e; color: white; border: none; padding: 8px 14px; border-radius: 8px; font-weight: 500; cursor: pointer; }
        button.primary { background: var(--accent); }

        /* Gallery Layout */
        #gallery-root { padding: 10px; }
        .day-section { margin-bottom: 20px; }
        .day-header { font-size: 14px; font-weight: 600; margin: 0 0 10px 5px; color: #aaa; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .thumb-strip { display: flex; gap: 4px; overflow-x: auto; scroll-snap-type: x proximity; -webkit-overflow-scrolling: touch; }
        .thumb-wrapper { flex: 0 0 100px; width: 100px; height: 100px; background: #222; border-radius: 4px; overflow: hidden; scroll-snap-align: start; }
        .thumb-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: opacity 0.3s; }

        /* Modal */
        .modal { position: fixed; inset: 0; background: rgba(0,0,0,0.9); display: none; flex-direction: column; padding: 20px; z-index: 1000; }
        .modal.active { display: flex; }
        .modal-content { background: var(--card); border-radius: 12px; padding: 20px; max-height: 80vh; overflow-y: auto; }
        .folder-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #333; }
        .remove-btn { color: #ff3b30; font-size: 12px; }

        .hidden { display: none; }
    </style>
</head>
<body>

<header>
    <div>
        <strong>Gallery</strong>
        <div id="file-count" class="stats">Initializing...</div>
    </div>
    <button onclick="toggleModal('settings-modal')">Settings</button>
</header>

<div class="toolbar">
    <button id="group-btn" onclick="toggleGrouping()">Group: By Day</button>
    <button id="sort-btn" onclick="toggleSort()">Sort: Newest</button>
    <button id="reauth-btn" class="hidden primary" onclick="reauthAll()">Unlock Folders</button>
</div>

<script>
function toggleGrouping() {
    groupBy = (groupBy === 'day') ? 'gridref' : 'day';
    document.getElementById('group-btn').innerText = `Group: By ${groupBy === 'day' ? 'Day' : 'Location'}`;
    renderFullGallery();
}
</script>

<div id="gallery-root"></div>

<div id="settings-modal" class="modal">
    <div class="modal-content">
        <h3>Monitored Folders</h3>
        <div id="folder-list"></div>
        <hr style="border: 0.5px solid #333; margin: 20px 0;">
        <button class="primary" style="width:100%" onclick="addFolder()">+ Add New Folder</button>
        <button style="width:100%; margin-top:10px;" onclick="toggleModal('settings-modal')">Close</button>
    </div>
</div>

<script>
let db;
let sortOrder = 'desc'; // 'desc' = newest first
let isScanning = false;

const initDB = () => {
    return new Promise((resolve) => {
        const request = indexedDB.open('GalleryDB', 2);
        request.onupgradeneeded = (e) => {
            const db = e.target.result;
            if(!db.objectStoreNames.contains('folders')) db.createObjectStore('folders', { keyPath: 'path' });
            if(!db.objectStoreNames.contains('images')) {
                const s = db.createObjectStore('images', { keyPath: 'fileId' });
                s.createIndex('day', 'day', { unique: false });
                s.createIndex('date', 'date', { unique: false });
            }
        };
        request.onsuccess = (e) => { db = e.target.result; resolve(); };
    });
};

async function addFolder() {
    try {
        const handle = await window.showDirectoryPicker();
        await updateStore('folders', { path: handle.name, handle });
        refreshFolderList();
        scanDirectory(handle);
    } catch (e) {}
}

async function scanDirectory(dirHandle, rootPath = null) {
    isScanning = true;
    const targetPath = rootPath || dirHandle.name;
    const safePath = btoa(targetPath).replace(/=/g, '');
    let newFound = 0;

// FOR MOBILE: Re-verify permission specifically for this sub-handle
    // This often "refreshes" the token for the duration of this function
    const opts = { mode: 'read' };
    if ((await dirHandle.queryPermission(opts)) !== 'granted') {
        if ((await dirHandle.requestPermission(opts)) !== 'granted') {
            console.error("User denied access to " + targetPath);
            return;
        }
    }

    let folderRecord = await getFromStore('folders', targetPath);
    let currentCount = folderRecord?.count || 0;

    for await (const entry of dirHandle.values()) {
        if (entry.kind === 'file' && /\.(jpe?g|png|webp|avif)$/i.test(entry.name)) {
            const fileId = `${dirHandle.name}/${entry.name}`;
            if (await getFromStore('images', fileId)) continue;
console.log(entry.name);
            const file = await entry.getFile();
            let date = new Date(file.lastModified);
            let gridref = "Unknown Location";
            try {
                const meta = await exifr.parse(file, {
                    translateKeys: true,  // Keep this true so you get 'latitude'/'longitude'
                    translateValues: false // THIS is what gives you '1' instead of "Horizontal (normal)"
                 //   reviveValues: false     // This prevents it from turning date strings into JS Date objects
                });

                console.log(meta,file);

                // Waterfall: Original > Created > Modified > File System
                const rawDate = meta?.DateTimeOriginal || meta?.CreateDate || meta?.ModifyDate;

                if (rawDate) {
                    date = new Date(rawDate);
                }

// GridRef Logic
if (meta?.latitude && meta?.longitude) {
    const wgs84 = new GT_WGS84();
    wgs84.setDegrees(meta.latitude, meta.longitude);
    let grid = false;
    if (wgs84.isIreland2()) {
        grid = wgs84.getIrish(true);
    } else if (wgs84.isGreatBritain()) {
        grid = wgs84.getOSGB();
    }
    if (grid) {
        gridref = grid.getGridRef(2).replace(/ /g,''); // e.g. TQ33
    }
}

            } catch (e) { console.log(e); }

            const thumbBlob = await createThumbnail(file);
            const imgData = {
                fileId,
                day: date.toISOString().split('T')[0],
                date: date.getTime(),
gridref: gridref, // Store for sorting/grouping
                thumb: thumbBlob,
                handle: entry // This 'entry' is the FileSystemFileHandle
            };


            await updateStore('images', imgData);
            newFound++;

		currentCount++;
    const counterEl = document.getElementById(`count-${safePath}`);
            if (counterEl) {
                counterEl.innerText = `${currentCount} images indexed`;
            }
            
            // Update folder count in DB every 5 images to avoid heavy DB writes
            if (newFound % 5 === 0) {
                await incrementFolderCount(targetPath, 5);
                refreshFolderList(); 
            }

            appendToUI(imgData);
        } else if (entry.kind === 'directory') {
            await scanDirectory(entry, targetPath);
        }
    }
    
    // Final update for the remainder
    if (newFound % 5 !== 0) {
        await incrementFolderCount(targetPath, newFound % 5);
    }
    
    isScanning = false;
    refreshFolderList();
    updateStats();
}

async function incrementFolderCount(path, amount) {
    const folder = await getFromStore('folders', path);
    if (folder) {
        folder.count = (folder.count || 0) + amount;
        await updateStore('folders', folder);
    }
}

function appendToUI(img) {
    let dayContainer = document.getElementById(`day-${img.day}`);
    if (!dayContainer) {
        renderFullGallery(); // If it's a new day, we refresh the structure once
        return;
    }
    const strip = dayContainer.querySelector('.thumb-strip');
    const thumb = createThumbElement(img);
    sortOrder === 'desc' ? strip.prepend(thumb) : strip.appendChild(thumb);
}

// NEW: Global variable to track if we are filtered to one day
let filteredDay = null;

function createThumbElement(img) {
    const div = document.createElement('div');
    div.className = 'thumb-wrapper';
    const url = URL.createObjectURL(img.thumb);
    // Fixed the URL revoke by using window.URL and being more explicit
    div.innerHTML = `<img src="${url}" loading="lazy" onload="window.URL.revokeObjectURL(this.src)">`;
    
    // Add click handler for "Upload" (Get full DataURL)
    div.onclick = async () => {
        const fullFile = await img.handle.getFile();
        console.log("Full resolution file ready for upload:", fullFile.name);
        // You can now use FileReader or URL.createObjectURL(fullFile)
    };
    return div;
}
let groupBy = 'day'; // 'day' or 'gridref'

async function renderFullGallery() {
    const root = document.getElementById('gallery-root');
    const images = await getAllFromStore('images');
if (images.length === 0) {
    root.innerHTML = '<div id="drop-zone">No images found. Add a folder in Settings.</div>';
    updateStats();
    return;
}
    if (images.length === 0) return;

    // 1. Group images
    const groups = images.reduce((acc, img) => {
        const key = img[groupBy] || "Unknown";
        acc[key] = acc[key] || { items: [], latest: 0 };
        acc[key].items.push(img);
        // Track latest image in this group for group-sorting
        if (img.date > acc[key].latest) acc[key].latest = img.date;
        return acc;
    }, {});

    // 2. Sort the Groups themselves (based on the latest image in that group)
    const sortedGroupKeys = Object.keys(groups).sort((a, b) => {
        return sortOrder === 'desc' ? groups[b].latest - groups[a].latest : groups[a].latest - groups[b].latest;
    });

    root.innerHTML = '';
    
    // Add a toggle in your UI to call this with 'gridref'
    sortedGroupKeys.forEach(key => {
        const groupData = groups[key];
        // Sort images INSIDE the group
        groupData.items.sort((a, b) => sortOrder === 'desc' ? b.date - a.date : a.date - b.date);

        const sec = document.createElement('div');
        sec.className = 'day-section';
        sec.innerHTML = `
            <div class="day-header" onclick="focusGroup('${key}')">
                <span>${key} (${groupData.items.length})</span>
                <span style="font-size:10px; color:var(--accent)">VIEW</span>
            </div>
            <div class="thumb-strip"></div>
        `;
        
        const strip = sec.querySelector('.thumb-strip');
        groupData.items.forEach(img => strip.appendChild(createThumbElement(img)));
        root.appendChild(sec);
    });

    updateStats();
}

async function toggleSort() {
    sortOrder = sortOrder === 'desc' ? 'asc' : 'desc';
    document.getElementById('sort-btn').innerText = `Sort: ${sortOrder === 'desc' ? 'Newest' : 'Oldest'}`;
    renderFullGallery();
}

async function refreshFolderList() {
    const list = document.getElementById('folder-list');
    const folders = await getAllFromStore('folders');
    list.innerHTML = folders.length ? '' : '<p style="color:#666; padding:10px;">No folders added</p>';
    
    folders.forEach(f => {
        const item = document.createElement('div');
        item.className = 'folder-item';
	const safePath = btoa(f.path).replace(/=/g, ''); // Base64 to ensure valid ID
        // Show the count (default to 0 if not yet set)
        const count = f.count || 0;
        item.innerHTML = `
            <div style="display:flex; flex-direction:column;">
                <span style="font-weight:500;">📁 ${f.path}</span>
                <span id="count-${safePath}" style="font-size:11px; color:var(--accent);">${count} images indexed</span>
            </div>
            <button class="remove-btn" onclick="removeFolder('${f.path}')">Remove</button>
        `;
        list.appendChild(item);
    });
}

async function removeFolder(path) {
    if (!confirm(`Stop monitoring "${path}" and clear its thumbnails?`)) return;

    const tx = db.transaction(['folders', 'images'], 'readwrite');
    const folderStore = tx.objectStore('folders');
    const imageStore = tx.objectStore('images');

    // 1. Remove the folder from the monitored list
    folderStore.delete(path);

    // 2. Remove all images belonging to this folder
    // We use a IDBKeyRange to find all keys starting with "FolderName/"
    const range = IDBKeyRange.bound(path + "/", path + "/\uffff");
    const cursorRequest = imageStore.openCursor(range);

    cursorRequest.onsuccess = (e) => {
        const cursor = e.target.result;
        if (cursor) {
            cursor.delete();
            cursor.continue();
        }
    };

    tx.oncomplete = () => {
        console.log(`Cleaned up all cached data for: ${path}`);
        refreshFolderList();
        renderFullGallery();
    };
}

function toggleModal(id) {
    document.getElementById(id).classList.toggle('active');
    if(id === 'settings-modal') refreshFolderList();
}

async function updateStats() {
    const imgs = await getAllFromStore('images');
    document.getElementById('file-count').innerText = `${imgs.length} images indexed`;
}

// Reuse previous thumbnail & DB helper logic...
async function createThumbnail(file) {
    const bitmap = await createImageBitmap(file);
    const canvas = document.createElement('canvas');
    const MAX = 120;
    let w = bitmap.width, h = bitmap.height;
    if (w > h) { h *= MAX/w; w = MAX; } else { w *= MAX/h; h = MAX; }
    canvas.width = w; canvas.height = h;
    canvas.getContext('2d').drawImage(bitmap, 0, 0, w, h);
    return new Promise(r => canvas.toBlob(r, 'image/jpeg', 0.8));
}

function updateStore(s, d) { return new Promise(r => { const t = db.transaction(s, 'readwrite'); t.objectStore(s).put(d); t.oncomplete = r; }); }
function getFromStore(s, id) { return new Promise(r => { db.transaction(s).objectStore(s).get(id).onsuccess = e => r(e.target.result); }); }
function getAllFromStore(s) { return new Promise(r => { db.transaction(s).objectStore(s).getAll().onsuccess = e => r(e.target.result); }); }

async function reauthAll() {
    const folders = await getAllFromStore('folders');
    for (const f of folders) {
        if ((await f.handle.queryPermission()) === 'granted' || (await f.handle.requestPermission()) === 'granted') {
            scanDirectory(f.handle);
        }
    }
    document.getElementById('reauth-btn').classList.add('hidden');
}

window.onload = async () => {
    await initDB();
    const folders = await getAllFromStore('folders');
    if (folders.length > 0) document.getElementById('reauth-btn').classList.remove('hidden');
    renderFullGallery();
};
</script>
<a href="javascript:(function () {var script=document.createElement('script');script.src='//cdn.jsdelivr.net/npm/eruda';document.body.appendChild(script); script.onload = function () { eruda.init() } })();">dev</a>


</body>
</html>
