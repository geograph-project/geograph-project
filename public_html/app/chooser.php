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
        .toolbar { padding: 10px; display: flex; gap: 8px; background: #111; scroll-margin-top: 60px; }
        button { background: #2c2c2e; color: white; border: none; padding: 8px 14px; border-radius: 8px; font-weight: 500; cursor: pointer; }
        button.primary { background: var(--accent); }

        /* Gallery Layout */
        #gallery-root { padding: 10px; }
        .day-section { margin-bottom: 20px; }
        .day-header { font-size: 14px; font-weight: 600; margin: 0 0 10px 5px; color: #aaa; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .thumb-strip { display: flex; gap: 4px; overflow-x: auto; scroll-snap-type: x proximity; -webkit-overflow-scrolling: touch; }
        .thumb-wrapper { flex: 0 0 100px; width: 100px; height: 100px; background: #222; border-radius: 4px; overflow: hidden; scroll-snap-align: start; }
        .thumb-wrapper img { width: 100%; height: 100%; object-fit: contain; transition: opacity 0.3s; }

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
        <strong>Not Yet Uploaded</strong>
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
        <button class="primary" style="width:100%" onclick="startFolderScan()">+ Add New Folder</button>
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

////////////////////////////////////////////
// main scan process

async function startFolderScan() {
    try {
        const dirHandle = await window.showDirectoryPicker();
        
        // 1. Setup Folder Record
        await updateStore('folders', { path: dirHandle.name, handle: dirHandle, count: 0 });
        await refreshFolderList();

        const safePath = btoa(dirHandle.name).replace(/=/g, '');
        const counterEl = document.getElementById(`count-${safePath}`);
        if (counterEl) counterEl.innerText = 'Scanning folder...';

        // 2. THE CRAWL: Fast collection of File objects
        const fileQueue = [];
        await fastCrawl(dirHandle, dirHandle.name, fileQueue);

        if (fileQueue.length === 0) {
            if (counterEl) counterEl.innerText = 'No new images found.';
            return;
        }

        if (counterEl) counterEl.innerText = `Processing ${fileQueue.length} images...`;

        // 3. THE PROCESS: Thumbnails & EXIF
        await processFileQueue(fileQueue, dirHandle.name);
        
    } catch (e) {
        console.error("Scan failed", e);
        if (e.name === 'NotAllowedError') {
            alert("Security timeout. Mobile Chrome requires you to stay on the page while it starts the scan.");
        }
    }
}

//simple version, ro rescan an exicting directory, for reauth. Still needs to use fastCrawl
async function scanDirectory(dirHandle) {
    const fileQueue = [];
    const targetPath = dirHandle.name;
    const opts = { mode: 'read' };

    try {
        // 1. Check/Request Permission
        if ((await dirHandle.queryPermission(opts)) !== 'granted') {
            if ((await dirHandle.requestPermission(opts)) !== 'granted') {
                console.error("Access denied to " + targetPath);
                return;
            }
        }

        // 2. Identify the counter element for feedback
        const safePath = btoa(targetPath).replace(/=/g, '');
        const counterEl = document.getElementById(`count-${safePath}`);
        if (counterEl) counterEl.innerText = 'Re-scanning...';

        // 3. Run the fast collection
        await fastCrawl(dirHandle, targetPath, fileQueue);

        if (fileQueue.length > 0) {
            // 4. Fire and forget the processing so the UI stays responsive
            processFileQueue(fileQueue, targetPath);
        } else {
            if (counterEl) counterEl.innerText = 'Up to date.';
        }
    } catch (err) {
        console.error("Scan Directory Error:", err);
    }
}


//this is intended to quickly gather files, for later processing. the permission expires if dont use it quickly!
async function fastCrawl(dirHandle, path, fileQueue) {
    for await (const entry of dirHandle.values()) {
        if (entry.kind === 'file' && /\.(jpe?g|png|webp|avif)$/i.test(entry.name)) {
            const fileId = `${path}/${entry.name}`;
            const existing = await getFromStore('images', fileId);

            if (!existing) {
                try {
                    // CRITICAL: Get the File object NOW while we have permission
                    const file = await entry.getFile(); 
                    fileQueue.push({ file, handle: entry, fileId });
                } catch (err) {
                    console.error("Failed to grab file early:", entry.name);
                }
            }
        } else if (entry.kind === 'directory') {
            await fastCrawl(entry, `${path}/${entry.name}`, fileQueue);
        }
    }
}



async function processFileQueue(fileQueue, rootPath) {
    isScanning = true;
    const safePath = btoa(rootPath).replace(/=/g, '');
    let folderRecord = await getFromStore('folders', rootPath);
    let currentCount = folderRecord?.count || 0;

    for (const item of fileQueue) {
        // We already have the 'item.file' object, no more 'getFile()' calls needed!
        try {
            const meta = await exifr.parse(item.file, { translateKeys: true, translateValues: false });
            let date = new Date(meta?.DateTimeOriginal || meta?.CreateDate || item.file.lastModified);

            let gridref = "Unknown Location";
            if (meta?.latitude && meta?.longitude) {
                const wgs84 = new GT_WGS84();
                wgs84.setDegrees(meta.latitude, meta.longitude);
                let grid = wgs84.isIreland2() ? wgs84.getIrish(true) : (wgs84.isGreatBritain() ? wgs84.getOSGB() : null);
                if (grid) gridref = grid.getGridRef(2).replace(/ /g,'');
            }
                /*    const match = item.file.name.match(/_([A-Z]{1,2}\d{4,10})\./i);
             if (!exifData.hasGeo || match[1].length > 7) {
                let wgs84 = GT_WGS84.parseGridRef(match[1]);
                if (wgs84 && wgs84.status === 'OK') {
                    exifData.lat = wgs84.latitude;
                    exifData.long = wgs84.longitude;
                //actually as want gridref not lat/long, dont need GT_WGS84

                                                         //todo, perhaps a bit fragile computing 4fig GR outself!
                                                        $e = substr($m[2],0,strlen($m[2])/2);
                                                        $n = substr($m[2],strlen($m[2])/2);
                                                        $row['grid_reference'] = $m[1].substr($e,0,2).substr($n,0,2);

Geotools.getGrid = function (gridref) ... (returns right grid-object)

            */


            const thumbBlob = await createThumbnail(item.file);

            const imgData = {
                fileId: item.fileId,
                day: date.toISOString().split('T')[0],
                date: date.getTime(),
                gridref: gridref,
                thumb: thumbBlob,
                handle: item.handle // Store the handle for future uploads
            };

            await updateStore('images', imgData);
            currentCount++;

            // Update UI
            const counterEl = document.getElementById(`count-${safePath}`);
            if (counterEl) counterEl.innerText = `${currentCount} images indexed`;
            appendToUI(imgData); //just add one image, without redrawing!

            if (currentCount % 10 === 0) {
                folderRecord.count = currentCount;
                await updateStore('folders', folderRecord);
                refreshFolderList();
            }
        } catch (err) {
            console.error("Processing error:", err);
        }
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

/////////////////////////////////////////////
// Render Functions

let filteredDay = null;
let groupBy = 'day'; // 'day' or 'gridref'

function createThumbElement(img) {
    const div = document.createElement('div');
    div.className = 'thumb-wrapper';

    // Add CSS class if already submitted
    if (img.uploadStatus) {
        div.classList.add('already-submitted');
        div.style.border = img.uploadStatus.match === 'exact' ? '3px solid #2ecc71' : '3px solid #f1c40f';
    }

    const url = URL.createObjectURL(img.thumb);
    // Fixed the URL revoke by using window.URL and being more explicit
    div.innerHTML = `<img src="${url}" loading="lazy" onload="window.URL.revokeObjectURL(this.src)">`;
    
    // Add click handler for "Upload" (Get full DataURL)
    div.onclick = () => {
        if (img.uploadStatus) {
            const info = img.uploadStatus.info;
            const msg = img.uploadStatus.match === 'exact' 
                ? `Already uploaded: ${info.title}`
                : `Close match (${img.uploadStatus.dist} bits): ${info.title}`;
            
            if (confirm(`${msg}\n\nView on Geograph?`)) {
                window.open(`https://www.geograph.org.uk/photo/${info.id}`, '_blank');
            }
        } else {
            // Trigger your upload logic here...
        }
    };

    return div;
}

function focusGroup(day) {
    filteredDay = day;
    document.querySelector('.toolbar').scrollIntoView({ behavior: 'smooth' });
    renderFullGallery();
}

async function renderFullGallery() {
    const root = document.getElementById('gallery-root');
    const images = await getAllFromStore('images');

    if (images.length === 0) {
        root.innerHTML = '<div id="drop-zone">No images found. Add a folder in Settings.</div>';
        updateStats();
        return;
    }

    root.innerHTML = '';

    // If we are in "Focus Mode", show a Back button
    if (filteredDay) {
        const backBtn = document.createElement('button');
        backBtn.innerText = "< Show All Images";
        backBtn.style.margin = "0 0 15px 5px";
        backBtn.onclick = () => { filteredDay = null; renderFullGallery(); };
        root.appendChild(backBtn);
    }

    // 1. Group images
    let groups = images.reduce((acc, img) => {
        const key = img[groupBy] || "Unknown";
        acc[key] = acc[key] || { items: [], latest: 0 };
        acc[key].items.push(img);
        // Track latest image in this group for group-sorting
        if (img.date > acc[key].latest) acc[key].latest = img.date;
        return acc;
    }, {});

    let sortedGroupKeys;
    if (filteredDay) {
        sortedGroupKeys = [filteredDay];
    } else {
        // 2. Sort the Groups themselves (based on the latest image in that group)
        sortedGroupKeys = Object.keys(groups).sort((a, b) => {
            return sortOrder === 'desc' ? groups[b].latest - groups[a].latest : groups[a].latest - groups[b].latest;
        });
    }

    // Add a toggle in your UI to call this with 'gridref'
    sortedGroupKeys.forEach(key => {
        const groupData = groups[key];
        // Sort images INSIDE the group
        groupData.items.sort((a, b) => sortOrder === 'desc' ? b.date - a.date : a.date - b.date);

        const sec = document.createElement('div');
        sec.id = `day-${key}`; //no longer technically just days!
        sec.className = 'day-section';
        sec.innerHTML = `
            <div class="day-header" onclick="focusGroup('${key}')">
                <span>${key} (${groupData.items.length})</span>
                ${(!filteredDay && groupData.items.length>3)?`<span style="font-size:10px; color:var(--accent)">VIEW</span>`:''}
            </div>
            <div class="thumb-strip"></div>
        `;

        const strip = sec.querySelector('.thumb-strip');

        // If focused, we might want to wrap the images instead of a horizontal strip
        if (filteredDay) {
            strip.style.flexWrap = "wrap";
            strip.style.overflowX = "hidden";
        }

        groupData.items.forEach(img => strip.appendChild(createThumbElement(img)));
        root.appendChild(sec);
    });

    updateStats();
}

//function to render just one image - adding to current gallery, without full reload
function appendToUI(img) {
    let dayContainer = document.getElementById(`day-${img[groupBy]}`);
    if (!dayContainer && !filteredDay) {
        renderFullGallery(); // If it's a new day, we refresh the structure once
        return;
    }
    const strip = dayContainer.querySelector('.thumb-strip');
    const thumb = createThumbElement(img);
    sortOrder === 'desc' ? strip.prepend(thumb) : strip.appendChild(thumb);
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
                <span style="font-weight:500;">${f.path}</span>
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

////////////////////////////////////////////////////////////////
// general functions and events

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
