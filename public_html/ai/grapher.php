<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive X/Y Chart Generator</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <!-- Load Plotly.js -->
    <script src="https://cdn.plot.ly/plotly-2.30.0.min.js"></script>
    <!-- Load Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* Custom Styles for full screen and chart container */
        body {
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
        }
        .chart-container {
            min-height: 500px;
            width: 100%;
            height: 80vh; /* Default height */
            transition: height 0.3s ease;
        }
        .is-fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1000;
            padding: 0;
            margin: 0;
            background-color: #f7f7f7;
            overflow: auto;
        }
        .is-fullscreen .chart-container {
            height: 100vh;
        }
    </style>
</head>
<body class="bg-gray-100 p-4 sm:p-8">

    <div id="app-wrapper" class="max-w-7xl mx-auto bg-white rounded-xl shadow-2xl p-6 sm:p-10 transition-all duration-300">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Interactive Data Visualization Tool</h1>

        <!-- Configuration Panel -->
        <div id="config-panel" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8 p-4 bg-gray-50 rounded-lg shadow-inner">

            <!-- Chart Type Selector -->
            <div>
                <label for="chartType" class="block text-xs font-medium text-gray-500 mb-1">Chart Type</label>
                <select id="chartType" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="scatter" data-mode="lines">Line</option>
                    <option value="scatter" data-mode="markers">Scatter (Dots)</option>
                    <option value="scatter" data-mode="lines+markers">Line & Dots</option>
                    <option value="bar">Bar</option>
                    <option value="area">Area (Stacked Line)</option>
                </select>
            </div>

            <!-- Stacking Mode -->
            <div>
                <label for="stackMode" class="block text-xs font-medium text-gray-500 mb-1">Stacking</label>
                <select id="stackMode" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="none">None</option>
                    <option value="stack">Stack (Bars & Area)</option>
                    <!-- Plotly handles streamgraph-like behavior for 'area' traces when 'stack' is enabled -->
                </select>
            </div>

            <!-- Y-Axis Scale -->
            <div>
                <label for="yScale" class="block text-xs font-medium text-gray-500 mb-1">Y-Axis Scale</label>
                <select id="yScale" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="linear">Linear</option>
                    <option value="log">Logarithmic</option>
                </select>
            </div>
            
            <!-- NEW: X-Axis Spacing -->
            <div>
                <label for="xAxisType" class="block text-xs font-medium text-gray-500 mb-1">X-Axis Spacing</label>
                <select id="xAxisType" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="auto">Timeline (Continuous)</option>
                    <option value="category">Compact (Categorical)</option>
                </select>
            </div>

            <!-- X-Axis Sort -->
            <div>
                <label for="xSort" class="block text-xs font-medium text-gray-500 mb-1">X-Axis Sort</label>
                <select id="xSort" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="original">Original Order</option>
                    <option value="x_asc">X Value (Ascending)</option>
                    <option value="x_desc">X Value (Descending)</option>
                    <!-- Y Sort options will be populated dynamically -->
                </select>
            </div>

            <!-- Full Screen Button -->
            <div class="col-span-1 flex items-end">
                <button id="fullScreenToggle" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-colors duration-200 flex items-center justify-center text-sm">
                    <span id="fullscreen-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-maximize-2 mr-2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><path d="M21 3 14 10"/><path d="M3 21 10 14"/></svg>
                    </span>
                    Full Screen
                </button>
            </div>
            
            <!-- Show/Hide Grid Toggle -->
            <div class="col-span-1 flex items-end">
                <button id="gridToggle" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-colors duration-200 flex items-center justify-center text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-table-properties mr-2"><path d="M15 3H3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/><path d="M10 3v14"/><path d="M1 9h16"/><path d="M21 10h-2"/><path d="M21 14h-2"/><path d="M21 18h-2"/><path d="M21 22h-2"/><path d="M21 6h-2"/></svg>
                    <span id="grid-text">Hide Grid</span>
                </button>
            </div>

        </div>

        <!-- Y-Series Toggles (populated dynamically) -->
        <div id="y-series-toggles" class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded-r-lg shadow-md">
            <p class="text-sm font-semibold text-yellow-800 mb-2">Y-Axis Series (Select to Plot):</p>
            <div id="toggle-container" class="flex flex-wrap gap-3">
                <!-- Checkboxes will be inserted here -->
            </div>
        </div>

        <!-- Chart Area -->
        <div id="plotly-graph" class="chart-container bg-gray-50 border border-gray-200 rounded-lg shadow-xl overflow-hidden"></div>
    </div>

    <!-- JavaScript Logic -->
    <script type="module">
        // --- CHARTING CORE LOGIC ---

        // 1. Initial Sample Data (Mimics adodb ->getAll query result with json_encode())
        const initialRawData = [
            // Daily data: Full Date X-Axis
            { day: "2024-04-10", views: 500, clicks: 10, users: 5 },
            { day: "2024-04-11", views: 750, clicks: 12, users: 6 },
            { day: "2024-04-12", views: 1200, clicks: 25, users: 8 },
            { day: "2024-04-13", views: 200, clicks: 5, users: 2 }, // Sparse day
            { day: "2024-04-14", views: 1500, clicks: 35, users: 10 },
            // Monthly/Yearly data: Mixed date formats
            { day: "2024-03", views: 10000, clicks: 50, users: 20 },
            { day: "2023", views: 50000, clicks: 200, users: 50 },
            // Categorical X-Axis example (for testing non-date/numeric sort)
            { day: "Alpha", views: 900, clicks: 22, users: 7 },
            { day: "Gamma", views: 100, clicks: 1, users: 1 },
            { day: "Beta", views: 300, clicks: 8, users: 3 },
        ];

        // Configuration state
        const config = {
            data: initialRawData,
            xKey: 'day', // The first column name from the query
            yKeys: [],    // Populated dynamically from data keys (excluding xKey)
            activeYKeys: [], // Which Y-series are currently selected
            chartType: 'scatter',
            chartMode: 'lines',
            stackMode: 'none',
            yScale: 'linear',
            xSort: 'original',
            showGrid: true,
            isFullScreen: false,
            // NEW: Configuration for X-Axis type/spacing
            xAxisType: 'auto', // 'auto' for continuous, 'category' for compact
        };

        // UI Element References
        const chartDiv = document.getElementById('plotly-graph');
        const chartTypeSelect = document.getElementById('chartType');
        const stackModeSelect = document.getElementById('stackMode');
        const yScaleSelect = document.getElementById('yScale');
        const xAxisTypeSelect = document.getElementById('xAxisType'); // NEW REFERENCE
        const xSortSelect = document.getElementById('xSort');
        const toggleContainer = document.getElementById('toggle-container');
        const appWrapper = document.getElementById('app-wrapper');

        // 2. Initialization: Determine Y-Keys and build UI toggles
        function initializeUI() {
            if (config.data.length === 0) return;

            // Get all keys from the first object, excluding the X-key
            const allKeys = Object.keys(config.data[0]);
            config.yKeys = allKeys.filter(key => key !== config.xKey);
            
            // Set all Y-keys to active initially
            config.activeYKeys = config.yKeys;

            // Build Y-Series Toggles
            toggleContainer.innerHTML = '';
            config.yKeys.forEach(key => {
                const isSelected = config.activeYKeys.includes(key);
                const button = document.createElement('button');
                button.textContent = key;
                button.setAttribute('data-key', key);
                button.className = `y-toggle px-3 py-1 rounded-full text-sm font-medium transition-colors duration-150 ${
                    isSelected ? 'bg-blue-500 text-white hover:bg-blue-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                }`;
                button.addEventListener('click', toggleYSeries);
                toggleContainer.appendChild(button);
            });

            // Populate X-Sort options with Y-Keys for sorting by Y-value
            config.yKeys.forEach(key => {
                const option = document.createElement('option');
                option.value = `y_asc_${key}`;
                option.textContent = `Sort by ${key} (Asc)`;
                xSortSelect.appendChild(option);
            });
            config.yKeys.forEach(key => {
                const option = document.createElement('option');
                option.value = `y_desc_${key}`;
                option.textContent = `Sort by ${key} (Desc)`;
                xSortSelect.appendChild(option);
            });
            
            // Initial chart render
            renderChart();
        }

        // 3. Data Sorting and Transformation
        function preprocessData() {
            let sortedData = [...config.data];

            const sortValue = xSortSelect.value;
            const isYSort = sortValue.startsWith('y_');
            const sortKey = isYSort ? sortValue.substring(sortValue.indexOf('_') + 1) : config.xKey;
            const isAsc = sortValue.endsWith('_asc') || sortValue === 'x_asc' || sortValue === 'original';

            if (sortValue === 'original') {
                // Do nothing, already a copy
            } else {
                sortedData.sort((a, b) => {
                    let valA = a[sortKey];
                    let valB = b[sortKey];

                    // Numeric/Date parsing for X or Y sorting
                    if (!isNaN(Date.parse(valA)) && !isNaN(Date.parse(valB)) && sortKey === config.xKey) {
                        // Date comparison for X-axis
                        valA = Date.parse(valA);
                        valB = Date.parse(valB);
                    } else if (!isNaN(valA) && !isNaN(valB)) {
                        // Numeric comparison
                        valA = Number(valA);
                        valB = Number(valB);
                    } else if (typeof valA === 'string' && typeof valB === 'string') {
                        // Alphabetical comparison
                        return isAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
                    }
                    
                    // Default comparison (numeric or date)
                    if (valA < valB) return isAsc ? -1 : 1;
                    if (valA > valB) return isAsc ? 1 : -1;
                    return 0;
                });
            }

            // Transform data into Plotly's structure (arrays of X and Y values)
            const xValues = sortedData.map(d => d[config.xKey]);
            
            const plotlyTraces = config.yKeys
                .filter(key => config.activeYKeys.includes(key)) // Filter by active Y keys
                .map(key => {
                    const yValues = sortedData.map(d => d[key]);
                    
                    let trace = {
                        x: xValues,
                        y: yValues,
                        name: key.charAt(0).toUpperCase() + key.slice(1), // Title case for legend
                        // Determine base type: 'scatter' for line/dots/area, or 'bar'
                        type: config.chartType === 'area' ? 'scatter' : config.chartType, 
                    };

                    // Only add scatter-specific properties if it's not a bar chart
                    if (config.chartType !== 'bar') {
                        trace.mode = chartTypeSelect.options[chartTypeSelect.selectedIndex].getAttribute('data-mode');
                        trace.line = { shape: 'spline' }; // Smooth lines for a nicer look
                        trace.marker = { size: 8, opacity: 0.8, line: { width: 1, color: 'white' } };
                    }


                    if (config.chartType === 'area') {
                        // Configure for Streamgraph-like Area Stack
                        trace.fill = 'tonexty';
                        trace.stackgroup = 'one';
                        // Line shape is already set above
                    }

                    if (config.chartType === 'bar') {
                        // Bar-specific marker style
                        trace.marker = { opacity: 0.8 };
                        // Note: Stacking is handled by the layout barmode setting
                    }

                    return trace;
                });

            return plotlyTraces;
        }

        // 4. Render Chart Function
        function renderChart() {
            const data = preprocessData();
            
            const isStacked = config.stackMode === 'stack';
            const isBar = config.chartType === 'bar';

            const layout = {
                title: `Analysis: ${config.xKey} vs. ${config.activeYKeys.join(', ')}`,
                autosize: true,
                height: config.isFullScreen ? window.innerHeight : 500,
                margin: { l: 60, r: 20, t: 80, b: 60 },
                hovermode: 'x unified', // Excellent for showing all series values at one X-point
                // This is where bar stacking is controlled
                barmode: (isBar && isStacked) ? 'stack' : 'group',
                
                xaxis: {
                    title: config.xKey.charAt(0).toUpperCase() + config.xKey.slice(1),
                    gridcolor: config.showGrid ? '#e0e0e0' : 'transparent',
                    zerolinecolor: config.showGrid ? '#e0e0e0' : 'transparent',
                    automargin: true,
                    // Apply X-Axis Spacing setting: 'auto' for continuous, 'category' for compact list
                    type: config.xAxisType, 
                },
                
                yaxis: {
                    title: 'Value',
                    type: config.yScale, // 'linear' or 'log'
                    gridcolor: config.showGrid ? '#e0e0e0' : 'transparent',
                    zerolinecolor: config.showGrid ? '#e0e0e0' : 'transparent',
                    automargin: true,
                    rangemode: 'tozero' // Start from zero for linear scale
                },
                legend: {
                    orientation: "h",
                    xanchor: "center",
                    x: 0.5,
                    y: 1.05
                },
                paper_bgcolor: '#ffffff',
                plot_bgcolor: '#ffffff',
            };

            const plotOptions = {
                responsive: true,
                displayModeBar: true, // Allows native Plotly tools (zoom, pan, download)
                modeBarButtonsToRemove: ['sendDataToCloud'],
                scrollZoom: true // Enable zoom on mouse scroll
            };

            Plotly.react(chartDiv, data, layout, plotOptions);
        }

        // 5. Event Handlers
        function updateConfig(key, value) {
            config[key] = value;
            renderChart();
        }

        function toggleYSeries(event) {
            const key = event.target.getAttribute('data-key');
            if (config.activeYKeys.includes(key)) {
                config.activeYKeys = config.activeYKeys.filter(k => k !== key);
                event.target.classList.remove('bg-blue-500', 'hover:bg-blue-600', 'text-white');
                event.target.classList.add('bg-gray-200', 'hover:bg-gray-300', 'text-gray-700');
            } else {
                config.activeYKeys.push(key);
                event.target.classList.remove('bg-gray-200', 'hover:bg-gray-300', 'text-gray-700');
                event.target.classList.add('bg-blue-500', 'hover:bg-blue-600', 'text-white');
            }
            renderChart();
        }

        // Listener setup
        chartTypeSelect.addEventListener('change', (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            updateConfig('chartType', selectedOption.value);
            updateConfig('chartMode', selectedOption.getAttribute('data-mode'));
        });

        stackModeSelect.addEventListener('change', (e) => updateConfig('stackMode', e.target.value));
        yScaleSelect.addEventListener('change', (e) => updateConfig('yScale', e.target.value));
        xAxisTypeSelect.addEventListener('change', (e) => updateConfig('xAxisType', e.target.value)); // NEW LISTENER
        xSortSelect.addEventListener('change', () => renderChart());

        document.getElementById('gridToggle').addEventListener('click', () => {
            config.showGrid = !config.showGrid;
            document.getElementById('grid-text').textContent = config.showGrid ? 'Hide Grid' : 'Show Grid';
            renderChart();
        });

        document.getElementById('fullScreenToggle').addEventListener('click', () => {
            config.isFullScreen = !config.isFullScreen;
            const icon = document.getElementById('fullscreen-icon');
            const buttonText = document.getElementById('fullScreenToggle');
            
            if (config.isFullScreen) {
                appWrapper.classList.add('is-fullscreen');
                icon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-minimize-2 mr-2"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><path d="M10 14 21 3"/><path d="M3 21 14 10"/></svg>`;
                buttonText.lastChild.textContent = 'Exit Full Screen';
            } else {
                appWrapper.classList.remove('is-fullscreen');
                icon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-maximize-2 mr-2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><path d="M21 3 14 10"/><path d="M3 21 10 14"/></svg>`;
                buttonText.lastChild.textContent = 'Full Screen';
            }
            // Re-render to adjust the chart size for the new container dimensions
            setTimeout(renderChart, 10);
        });
        
        // Handle resizing (important for responsive charts)
        window.addEventListener('resize', () => {
            if (config.isFullScreen) {
                // For full screen, update layout height
                renderChart();
            } else {
                // Otherwise, use Plotly's built-in resize function
                Plotly.relayout(chartDiv, { autosize: true, height: 500 });
            }
        });


        // 6. Start the application
        window.onload = initializeUI;
    </script>
</body>
</html>
