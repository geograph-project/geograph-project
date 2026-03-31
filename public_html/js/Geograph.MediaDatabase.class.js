class MediaDatabase {
    constructor() {
        this.DB_NAME = "GalleryDB"; //use same database as existing
        this.DB_VERSION = 7;
    	//chooser uses 'folders' and 'images' stores, which are hardcoded
        this.STORE_NAME = "uploads"; //store for media history
	    this.db = null;
    }

    /**
     * Internal helper to open the connection and handle migrations.
     * Returns the existing connection if already open.
     */
    async #openDB() {
        // 1. If we already have an open connection, use it!
        if (this.db) return this.db;

        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.DB_NAME, this.DB_VERSION);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

        		// 1. Folders Store (For Chooser)
                if (!db.objectStoreNames.contains('folders')) {
                    db.createObjectStore('folders', { keyPath: 'path' });
                }

                // 2. Images Store (For Chooser)
                if (!db.objectStoreNames.contains('images')) {
                    const s = db.createObjectStore('images', { keyPath: 'fileId' });
                    s.createIndex('day', 'day', { unique: false });
                    s.createIndex('date', 'date', { unique: false });
                }

        		// 3. Upload History Store
                let store;
                if (!db.objectStoreNames.contains(this.STORE_NAME)) {
                    store = db.createObjectStore(this.STORE_NAME, { keyPath: "filename" });
                } else {
                    store = event.target.transaction.objectStore(this.STORE_NAME);
                }

                // Setup Indexes
                if (!store.indexNames.contains("hasGeo")) {
                    store.createIndex("hasGeo", "hasGeo", { unique: false });
                }
                if (!store.indexNames.contains("lastActivity")) {
                    store.createIndex("lastActivity", "lastActivity", { unique: false });
                }
            };

            request.onsuccess = () => {
		        this.db = event.target.result;

        		// Close connection if another tab needs to upgrade the database
                this.db.onversionchange = () => {
                    this.db.close();
                    this.db = null;
                    console.warn("Database version changed elsewhere. Connection closed.");
                };

		        resolve(request.result);
    	    };

            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Normalizes dates for consistent sorting
     * Converts "YYYY:MM:DD HH:MM:SS" -> "YYYY-MM-DDTHH:MM:SS"
     */
    #toSortableDate(dateValue) {
        if (!dateValue) return new Date().toISOString();
        if (typeof dateValue === 'string' && dateValue.includes(':') && !dateValue.includes('-')) {
            return dateValue.replace(/:/g, (match, offset) => (offset < 10 ? '-' : match)).replace(' ', 'T');
        }
        return dateValue;
    }

    /////////////////////////////////////////////////////////////
    // function for working with uploads store

    /**
     * Core "UPSERT" function
     */
    async updateMediaHistory(filename, options = {}) {
        const { uploadId = null, exifData = null, status = 'uploaded' } = options;
        const db = await this.#openDB();

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(this.STORE_NAME, "readwrite");
            const store = transaction.objectStore(this.STORE_NAME);

            const getRequest = store.get(filename);

            getRequest.onsuccess = () => {
                const existingRecord = getRequest.result || {};
                const now = new Date().toISOString();

                // Determine the most relevant date for sorting
                const rawDate = exifData?.date
                             || existingRecord?.exifData?.date
                             || existingRecord?.taken
                             || now;

                const updatedRecord = {
                    ...existingRecord,
                    filename,
                    exifData: exifData || existingRecord.exifData || null,
                    hasGeo: (exifData?.hasGeo || existingRecord.exifData?.hasGeo || false)?1:0, // Copy to top level!
                    status: status,
                    lastActivity: this.#toSortableDate(rawDate)
                };

                if (status === 'uploaded') {
                    updatedRecord.upload_id = uploadId;
                    updatedRecord.uploaded = now;
                } else if (status === 'taken') {
                    updatedRecord.taken = now;
                }

                const putRequest = store.put(updatedRecord);
                putRequest.onsuccess = () => resolve(updatedRecord);
                putRequest.onerror = () => reject("Error saving record");
            };
        });
    }

    /**
     * Records the event of a photo being captured.
     * Sets status to 'taken' and initializes geo-data.
     */
    async savePhotoTaken(filename, lat, long) {
        const exif = {
            hasGeo: Number.isFinite(lat) && Number.isFinite(long),
            lat: lat,
            long: long,
            date: null, // Will be filled if exif data is parsed later
            orientation: null
        };

        return this.updateMediaHistory(filename, {
            status: 'taken',
            exifData: exif
        });
    }

    /**
     * Records the event of a photo being successfully uploaded.
     * Sets status to 'uploaded' and stores the remote ID.
     */
    async savePhotoUpload(filename, uploadId) {
        return this.updateMediaHistory(filename, {
            status: 'uploaded',
            uploadId: uploadId
        });
    }

    /**
     * Find a single record by filename
     */
    async findRecord(filename) {
        const db = await this.#openDB();
        return new Promise((resolve) => {
            const transaction = db.transaction(this.STORE_NAME, "readonly");
            const store = transaction.objectStore(this.STORE_NAME);
            const request = store.get(filename);
            request.onsuccess = () => resolve(request.result || null);
        });
    }

    /**
     * Returns ALL records in the database (specifically the uploads store!)
     * ... more general getAllFromStore will get from a named store!
     */
    async getAllUploads() {
        const db = await this.#openDB();
        return new Promise((resolve) => {
            const transaction = db.transaction(this.STORE_NAME, "readonly");
            const store = transaction.objectStore(this.STORE_NAME);
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
        });
    }

    /**
     * Map View Query: WHERE hasGeo = true
     */
    async getGeoHistory() {
        const db = await this.#openDB();
        return new Promise((resolve) => {
            const transaction = db.transaction(this.STORE_NAME, "readonly");
            const store = transaction.objectStore(this.STORE_NAME);
            const index = store.index("hasGeo");
            const request = index.getAll(1);
            request.onsuccess = () => resolve(request.result);
        });
    }

    /**
     * History View Query: ORDER BY lastActivity DESC LIMIT x
     */
    async getRecentHistory(limit = 1000) {
        const db = await this.#openDB();
        return new Promise((resolve) => {
            const transaction = db.transaction(this.STORE_NAME, "readonly");
            const store = transaction.objectStore(this.STORE_NAME);
            const index = store.index("lastActivity");
            const results = [];

            const cursorRequest = index.openCursor(null, 'prev');
            cursorRequest.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor && results.length < limit) {
                    results.push(cursor.value);
                    cursor.continue();
                } else {
                    resolve(results);
                }
            };
        });
    }

    /**
     * Keeps the database size under control
     */
    async pruneHistory(keepCount = 1000) {
        const db = await this.#openDB();
        const transaction = db.transaction(this.STORE_NAME, "readwrite");
        const store = transaction.objectStore(this.STORE_NAME);
        const index = store.index("lastActivity");

        const countRequest = store.count();
        countRequest.onsuccess = () => {
            const toDelete = countRequest.result - keepCount;
            if (toDelete <= 0) return;

            let deleted = 0;
            const deleteCursor = index.openCursor(null, 'next'); // Oldest first
            deleteCursor.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor && deleted < toDelete) {
                    cursor.delete();
                    deleted++;
                    cursor.continue();
                }
            };
        };
    }

    /**
     * Removes a specific record from the history by filename.
     */
    async deleteRecord(filename) {
        const db = await this.#openDB();
        return new Promise((resolve, reject) => {
            const transaction = db.transaction(this.STORE_NAME, "readwrite");
            const store = transaction.objectStore(this.STORE_NAME);

            const request = store.delete(filename);

            request.onsuccess = () => {
                console.log(`Record ${filename} deleted.`);
                resolve(true);
            };

            request.onerror = () => {
                console.error(`Failed to delete ${filename}`);
                reject(request.error);
            };
        });
    }

    /////////////////////////////////////////////////
    // methods used by chooser.php

    async updateStore(storeName, data) {
        const db = await this.#openDB();
        return new Promise(r => {
            const tx = db.transaction(storeName, 'readwrite');
            tx.objectStore(storeName).put(data);
            tx.oncomplete = r;
        });
    }

    async getFromStore(storeName, id) {
        const db = await this.#openDB();
        return new Promise(r => {
            db.transaction(storeName).objectStore(storeName).get(id).onsuccess = e => r(e.target.result);
        });
    }

    async getAllFromStore(storeName) {
        const db = await this.#openDB();
        return new Promise(r => {
            db.transaction(storeName).objectStore(storeName).getAll().onsuccess = e => r(e.target.result);
        });
    }

    //helper function to specifically reset muted on all records
    async resetMutedImages() {
        const db = await this.#openDB();
        const tx = db.transaction('images', 'readwrite');
        const store = tx.objectStore('images');

        store.openCursor().onsuccess = (e) => {
            const cursor = e.target.result;
            if (cursor) {
                const data = cursor.value;
                if (data.muted) {
                    delete data.muted;
                    cursor.update(data);
                }
                cursor.continue();
            }
        };

        return new Promise(resolve => {
            tx.oncomplete = () => {
                resolve();
            };
        });
    }

    /**
     * Removes a folder and all associated images from the cache.
     * @param {string} path - The folder path to remove.
     */
    async removeFolder(path) {
        const db = await this.#openDB();
        
        return new Promise((resolve, reject) => {
            // We need 'readwrite' access to both stores
            const tx = db.transaction(['folders', 'images'], 'readwrite');
            const folderStore = tx.objectStore('folders');
            const imageStore = tx.objectStore('images');

            // 1. Remove the folder entry
            folderStore.delete(path);

            // 2. Remove all images starting with "path/" 
            // using the high-point character \uffff to catch all sub-files
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
                resolve(true);
            };

            tx.onerror = (e) => {
                console.error("Folder removal failed:", e.target.error);
                reject(e.target.error);
            };
        });
    }

    /////////////////////////////////////////////////
    // Nuke the entire database

    async wipeAllData() {
        if (this.db) {
            this.db.close();
            this.db = null;
        }
        return new Promise((resolve, reject) => {
            const req = indexedDB.deleteDatabase(this.DB_NAME);
            req.onsuccess = () => resolve();
            req.onerror = () => reject();
            req.onblocked = () => resolve(); // Often deleted anyway
        });
    }

    /////////////////////////////////////////////////

    /**
     * Migrates data from localStorage to IndexedDB
     * Run this once during app initialization.
     */
    async migrateFromLocalStorage(STORAGE_KEY) {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (!stored) return;

        try {
            const history = JSON.parse(stored);
            if (!Array.isArray(history)) return;

            const db = await this.#openDB();
            const tx = db.transaction(this.STORE_NAME, 'readwrite');
            const store = tx.objectStore(this.STORE_NAME);

            for (const item of history) {
                // Map old structure to new structure if needed
                const record = {
                    filename: item.filename,
                    upload_id: item.upload_id,
                    uploaded: item.uploaded,
                    status: 'uploaded',
                    // Use the uploaded date as the sort key for migrated items
                    lastActivity: this.#toSortableDate(item.uploaded)
                };
                store.put(record);
            }

            tx.oncomplete = () => {
                console.log(`Migrated ${history.length} items from localStorage.`);
                localStorage.removeItem(STORAGE_KEY); // Clean up
            };
        } catch (e) {
            console.error("Migration failed:", e);
        }
    }

}


/* USAGE

// 1. Create a single instance for the app
const dbHistory = new MediaDatabase();

async function uploadImage(item) {
    try {
        const response = await fetch(...); // upload the image
        const result = await response.json();
        
        if (result && result.success) {
            // 2. Use the instance and await the result
            await dbHistory.updateMediaHistory(item.file.name, {
                status: 'uploaded', 
                uploadId: result.upload_id, 
                exifData: item.exifData
            });
            console.log("History updated!");
        }
    } catch (err) {
        console.error("Upload or DB save failed", err);
    }
}

*/
