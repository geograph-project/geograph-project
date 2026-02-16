<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rank/Sample Tester</title>
    <style>
        :root {
            --primary: #2563eb;
            --bg: #f8fafc;
            --card-bg: #ffffff;
        }
        body { font-family: system-ui, sans-serif; background: var(--bg); margin: 20px; color: #1e293b; }
        
        .controls { 
            background: var(--card-bg); padding: 20px; border-radius: 8px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;
        }
        
        .control-group { display: flex; flex-direction: column; gap: 5px; }
        label { font-weight: bold; font-size: 0.85rem; }
        
        .sort-options { display: flex; gap: 10px; align-items: center; }
        .sort-item { display: flex; align-items: center; gap: 4px; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; font-size: 0.9rem; }

        #grid { 
            display: grid; grid-template-columns: repeat(auto-fill, 250px); 
            gap: 15px; justify-content: center; 
        }

        .thumb-card { 
            width: 250px; height: 250px; position: relative; overflow: hidden; 
            border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .thumb-card:hover { transform: scale(1.02); }
        
        .thumb-card img { width: 100%; height: 100%; object-fit: cover; }
        
        .title-overlay { 
            position: absolute; bottom: 0; right: 0; 
            background: rgba(0, 0, 0, 0.6); color: white; 
            padding: 4px 8px; font-size: 12px; max-width: 80%;
            border-top-left-radius: 4px; pointer-events: none;
        }

        .stats { margin-bottom: 10px; font-size: 0.9rem; color: #64748b; }
    </style>
</head>
<body>

    <h2>Algorithm Ranking Tester</h2>

    <div class="controls">
        <div class="control-group">
            <label>Sample File</label>
            <select id="fileSelector">
                <option value="">Select a file...</option>
                <option value="data1.json">Data Set 1</option>
                <option value="data2.json">Data Set 2</option>
            </select>
        </div>

        <div class="control-group">
            <label>Max Display</label>
            <input type="number" id="sampleSize" value="25" min="1" max="75" style="width: 60px;">
        </div>

        <div class="control-group">
            <label>Max Distance (m)</label>
            <input type="number" id="distFilter" value="1000" step="50">
        </div>

        <div class="control-group">
            <label>Sort Orders (RRF Combined)</label>
            <div class="sort-options" id="sortChecks">
                <div class="sort-item"><input type="checkbox" value="score_desc"> Score (High)</div>
                <div class="sort-item"><input type="checkbox" value="distance_asc"> Dist (Low)</div>
                <div class="sort-item"><input type="checkbox" value="age_asc"> Age (New)</div>
            </div>
        </div>
    </div>

    <div class="stats" id="statsDisplay">Select a file to begin...</div>
    <div id="grid"></div>

<script>
    let rawData = [];
    const k = 60; // RRF constant (standard is 60)

    const els = {
        file: document.getElementById('fileSelector'),
        grid: document.getElementById('grid'),
        size: document.getElementById('sampleSize'),
        dist: document.getElementById('distFilter'),
        sorts: document.querySelectorAll('#sortChecks input'),
        stats: document.getElementById('statsDisplay')
    };

    // Initialize Listeners
    els.file.addEventListener('change', loadData);
    els.size.addEventListener('input', render);
    els.dist.addEventListener('input', render);
    els.sorts.forEach(cb => cb.addEventListener('change', render));

    async function loadData() {
        if (!els.file.value) return;
        try {
            const response = await fetch(els.file.value);
            rawData = await response.json();
            render();
        } catch (err) {
            console.error("Error loading JSON:", err);
            els.stats.innerText = "Error loading file.";
        }
    }

    function calculateRRF(data, activeSorts) {
        // If no sort selected, return data as is (defaulting to 'sequence' in render)
        if (activeSorts.length === 0) return data;

        // Create ranking maps for each active sort
        const rankMaps = activeSorts.map(sortKey => {
            const [field, direction] = sortKey.split('_');
            const sorted = [...data].sort((a, b) => {
                return direction === 'asc' ? a[field] - b[field] : b[field] - a[field];
            });
            
            const map = new Map();
            sorted.forEach((item, index) => map.set(item.gridimage_id, index + 1));
            return map;
        });

        // Calculate RRF Score: Sum of 1 / (k + rank)
        return data.map(item => {
            let rrfScore = 0;
            rankMaps.forEach(map => {
                const rank = map.get(item.gridimage_id);
                rrfScore += 1 / (k + rank);
            });
            return { ...item, _rrf: rrfScore };
        }).sort((a, b) => b._rrf - a._rrf); // Higher RRF score = better rank
    }

    function render() {
        if (!rawData.length) return;

        // 1. Filter
        let filtered = rawData.filter(item => item.distance < parseInt(els.dist.value));

        // 2. Sort
        const activeSorts = Array.from(els.sorts).filter(cb => cb.checked).map(cb => cb.value);
        
        let sorted;
        if (activeSorts.length > 0) {
            sorted = calculateRRF(filtered, activeSorts);
        } else {
            sorted = filtered.sort((a, b) => a.sequence - b.sequence);
        }

        // 3. Sample Size Capping
        const limit = Math.min(Math.max(parseInt(els.size.value) || 1, 1), 75);
        const displayList = sorted.slice(0, limit);

        // 4. Build UI
        els.stats.innerText = `Showing ${displayList.length} of ${filtered.length} filtered results (Total: ${rawData.length})`;
        
        els.grid.innerHTML = displayList.map(item => `
            <a href="/photo/${item.gridimage_id}" class="thumb-card">
                <img src="${item.thumb}" alt="${item.title}" loading="lazy">
                <div class="title-overlay">${item.title}</div>
            </a>
        `).join('');
    }
</script>
</body>
</html>
