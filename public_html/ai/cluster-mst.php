<?php

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;


$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$name = "St Mary's Church";

if (!empty($_GET['name']))
	$name = $_GET['name'];

$name = $db->Quote($name);
$data = $db->getAll($sql = "select formal_name as entity,gridimage_id,grid_reference,wgs84_lat,wgs84_long
	from gridimage_named inner join gridimage_search using (gridimage_id)
	where formal_name LIKE $name AND type IN ('depict','within') LIMIT 1000");

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MST Spatial Clustering Demo</title>

  <!-- Leaflet CSS & JS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <!-- Chart.js for Histogram -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    body { font-family: sans-serif; margin: 0; padding: 20px; background: #f4f6f8; }
    .container { display: flex; flex-direction: column; gap: 20px; max-width: 1200px; margin: 0 auto; }
    .panel { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .controls { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
    #map { height: 1000px; border-radius: 8px; }
    .stats { font-size: 0.9em; color: #555; }
    input[type=range] { flex-grow: 1; }
  </style>
</head>
<body>

<div class="container">
  <h2>MST Cluster Analyzer</h2>

  <div class="panel">
    <h3>1. Edge Length Histogram</h3>
    <canvas id="histogramChart" height="80"></canvas>
  </div>

  <div class="panel">
    <div class="controls">
      <label for="threshold"><b>Distance Cutoff Threshold:</b> <span id="thresh-val">50</span> km</label>
      <input type="range" id="threshold" min="0.1" max="100" value="50" step="0.1">
    </div>
    <div class="stats">
      Clusters Found: <strong id="cluster-count">0</strong> | 
      Edges Retained: <strong id="edge-count">0</strong> / <span id="total-edges">0</span>
    </div>
  </div>

  <div class="panel">
    <h3>2. Dynamic Cluster Map</h3>
    <div id="map"></div>
  </div>
</div>

<script>
// --- SAMPLE DATA INPUT ---
// Replace or overwrite this `data` array with your PHP json_encode output
const data = <? echo json_encode($data); ?>;

// --- 1. GEOGRAPHIC DISTANCE CALCULATOR (Haversine Formula in km) ---
function haversine(lat1, lon1, lat2, lon2) {
  const R = 6371; // Earth radius in km
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon/2) * Math.sin(dLon/2);
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

// --- 2. DISJOINT SET (UNION-FIND) FOR KRUSKAL'S ALGORITHM ---
class DisjointSet {
  constructor(size) {
    this.parent = Array.from({length: size}, (_, i) => i);
  }
  find(i) {
    if (this.parent[i] === i) return i;
    return this.parent[i] = this.find(this.parent[i]); // Path compression
  }
  union(i, j) {
    const rootI = this.find(i);
    const rootJ = this.find(j);
    if (rootI !== rootJ) {
      this.parent[rootI] = rootJ;
      return true;
    }
    return false;
  }
}

// --- 3. COMPUTE MINIMUM SPANNING TREE (MST) ---
function buildMST(nodes) {
  const edges = [];
  const n = nodes.length;

  // Generate all pairwise edges
  for (let i = 0; i < n; i++) {
    for (let j = i + 1; j < n; j++) {
      const dist = haversine(
        parseFloat(nodes[i].wgs84_lat), parseFloat(nodes[i].wgs84_long),
        parseFloat(nodes[j].wgs84_lat), parseFloat(nodes[j].wgs84_long)
      );
      edges.push({ u: i, v: j, weight: dist });
    }
  }

  // Sort edges by weight ascending
  edges.sort((a, b) => a.weight - b.weight);

  const ds = new DisjointSet(n);
  const mstEdges = [];

  for (const edge of edges) {
    if (ds.union(edge.u, edge.v)) {
      mstEdges.push(edge);
      if (mstEdges.length === n - 1) break;
    }
  }
  return mstEdges;
}

// --- 4. COLOR GENERATOR FOR CLUSTERS ---
function getClusterColor(id) {
  const colors = ["#e6194B", "#3cb44b", "#ffe119", "#4363d8", "#f58231", "#911eb4", "#42d4f4", "#f032e6", "#bfef45", "#fabed4"];
  return colors[id % colors.length];
}

// --- INITIALIZE APPLICATION ---
const mstEdges = buildMST(data);

// Map Setup
const map = L.map('map').setView([53.5, -2.5], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

let mapLayerGroup = L.layerGroup().addTo(map);

// Set max threshold on slider based on max edge length
const maxEdgeWeight = Math.ceil(Math.max(...mstEdges.map(e => e.weight)));
const slider = document.getElementById('threshold');
//slider.max = maxEdgeWeight;
slider.value = Math.round(maxEdgeWeight * 0.3); // Default threshold at 30%

// --- 5. RENDER LOGARITHMIC HISTOGRAM ---
function createHistogram(edges) {
  const weights = edges.map(e => e.weight);
  const minW = Math.min(...weights);
  const maxW = Math.max(...weights);

  // Set a tiny floor (e.g. 10 meters / 0.01 km) so log10 doesn't break on zero or ultra-close points
  const logMin = Math.log10(Math.max(0.01, minW));
  const logMax = Math.log10(maxW);

  const binCount = 20;
  const logStep = (logMax - logMin) / binCount;

  const bins = Array(binCount).fill(0);
  const labels = [];
  const boundaries = [];

  // Pre-calculate logarithmic bin boundaries
  for (let i = 0; i <= binCount; i++) {
    boundaries.push(Math.pow(10, logMin + i * logStep));
  }

  // Helper to format distances nicely in meters vs km
  const formatDist = (km) => {
    return km < 1 ? `${Math.round(km * 1000)}m` : `${km.toFixed(1)}km`;
  };

  for (let i = 0; i < binCount; i++) {
    labels.push(`${formatDist(boundaries[i])} - ${formatDist(boundaries[i + 1])}`);
  }

  // Populate bins based on logarithmic scale
  weights.forEach(w => {
    const val = Math.max(0.01, w);
    let index = Math.floor((Math.log10(val) - logMin) / logStep);
    if (index >= binCount) index = binCount - 1;
    if (index < 0) index = 0;
    bins[index]++;
  });

  const ctx = document.getElementById('histogramChart').getContext('2d');
  
  // Destroy old instance if re-rendering dynamically
  if (window.myChart) { window.myChart.destroy(); }

  window.myChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Edge Count',
        data: bins,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    },
    options: {
      plugins: {
        tooltip: {
          callbacks: {
            title: (items) => `Range: ${items[0].label}`
          }
        }
      },
      scales: {
        x: { 
          title: { display: true, text: 'Edge Length (Logarithmic scale)' },
          ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 }
        },
        y: { title: { display: true, text: 'Frequency' }, beginAtZero: true }
      }
    }
  });
}

// --- 6. UPDATE CLUSTERS AND MAP ---
function updateClusters(threshold) {
  mapLayerGroup.clearLayers();

  const n = data.length;
  const ds = new DisjointSet(n);
  const filteredEdges = [];

  // Filter MST edges by threshold
  mstEdges.forEach(edge => {
    if (edge.weight <= threshold) {
      ds.union(edge.u, edge.v);
      filteredEdges.push(edge);
    }
  });

  // Assign cluster IDs to points
  const clusterMap = new Map();
  let nextClusterId = 0;
  const pointClusterIds = [];

  for (let i = 0; i < n; i++) {
    const root = ds.find(i);
    if (!clusterMap.has(root)) {
      clusterMap.set(root, nextClusterId++);
    }
    pointClusterIds[i] = clusterMap.get(root);
  }

  // Draw MST Edges on Map
  filteredEdges.forEach(edge => {
    const u = data[edge.u];
    const v = data[edge.v];
    const color = getClusterColor(pointClusterIds[edge.u]);
    L.polyline([
      [u.wgs84_lat, u.wgs84_long],
      [v.wgs84_lat, v.wgs84_long]
    ], { color: color, weight: 3, opacity: 0.7 }).addTo(mapLayerGroup);
  });

  // Draw Point Markers on Map
  data.forEach((point, i) => {
    const clusterId = pointClusterIds[i];
    const color = getClusterColor(clusterId);

    L.circleMarker([point.wgs84_lat, point.wgs84_long], {
      radius: 7,
      fillColor: color,
      color: "#000",
      weight: 1,
      opacity: 1,
      fillOpacity: 0.8
    })
    .bindPopup(`<b>${point.entity}</b><br>ID: ${point.gridimage_id}<br>Cluster: ${clusterId}`)
    .addTo(mapLayerGroup);
  });

  // Update UI Stats
  document.getElementById('thresh-val').innerText = threshold;
  document.getElementById('cluster-count').innerText = clusterMap.size;
  document.getElementById('edge-count').innerText = filteredEdges.length;
  document.getElementById('total-edges').innerText = mstEdges.length;
}

// --- EVENT LISTENERS & INIT ---
createHistogram(mstEdges);
updateClusters(parseFloat(slider.value));

slider.addEventListener('input', (e) => {
  updateClusters(parseFloat(e.target.value));
});
</script>

</body>
</html>
