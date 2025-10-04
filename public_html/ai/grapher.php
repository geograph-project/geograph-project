<?

require_once('geograph/global.inc.php');
init_session();


$smarty = new GeographPage;


$ri = (isset($_GET['ri']) && is_numeric($_GET['ri']))?intval($_GET['ri']):0;

$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):0;

$date = (isset($_GET['date']) && ctype_lower($_GET['date']))?$_GET['date']:'submitted';

$myriad = (isset($_GET['myriad']) && ctype_upper($_GET['myriad']))?$_GET['myriad']:'';

$year = isset($_GET['year']);



        $title = ($date == 'taken')?'Taken':'Submitted';
        $title = "Breakdown of Images by $title Date";

        $where = array();



        $db = GeographDatabaseConnection(true);
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


	if (!empty($_GET['mine'])) {
		$table=$db->GetAll("select imagetaken, length(title) as len FROM gridimage_search WHERE user_id = 3");

	} elseif (!empty($_GET['users'])) {

	        $column = 'signup_date';
	        if (isset($_GET['week'])) {
	                $from_date = "date(min($column))";
	                $group_date = "yearweek($column,1)";
	        } else {
	                $length = isset($_GET['month'])?10:7;  //month=0 means daily ;-0
			if ($year)
				$length=4;

	                $from_date = "substring( $column, 1, $length )";
	                $group_date = "substring( $column, 1, $length )";
	        }

	        $title = "Breakdown of User Signups over Time";

	        $table=$db->GetAll("
	        select
	        $from_date as `Date` ,
	        count(*) as `Signups`,
	        sum(user_stat.images>0) as `Who later Contribute`
	        from user
	        left join user_stat using (user_id)
	        where rights <> ''
	        group by $group_date
	        " );

	} else {
	        $column = ($date == 'taken')?'imagetaken':'submitted';

                //always, filter by $ri, even 0!
                $where[] = "reference_index=".$ri;
                $where[] = "type = ".$db->Quote($column);

		if ($year) {
			$where[] = "month = ''"; //to just get years!
			$xcol = "year";
		} else {
	                if ($date == 'taken') {
        	                $where[] = "month not like '%-00'"; //can be images with year, no month
                	}

	                $where[] = "month not like ''"; //the table is built with rollup, so has 'yearly' rows too!
			$xcol = "month";
		}

                $where_sql = " WHERE ".join(' AND ',$where);

                $table=$db->GetAll($sql = "SELECT
                $xcol AS `Date`,
                images AS `Images`,
                geographs AS `Geographs`,
                tpoints AS `TPoints`,
                points AS `First Points`,
                visitors AS `AllPoints`,
                personals AS `Personal Points`,
                images / squares AS `Depth`,
                squares AS `Different Gridsquares`,
                myriads as `Different Myriads`,
                hectads as `Different Hectads`,
                users as `Different Contributors`
                FROM `date_stat` WHERE ".join(' AND ',$where)."
                ORDER BY month");
	}

	$xkey = 'Date'; //todo, would be to auto-dtect

	$keys = array_keys($table[0]);
	$xkey = $keys[0];


////////////////////////////////////////////////////////
//currently only two lines changed!

//        const initialRawData = < ? echo json_encode($table); ? >;
//            xKey: '< ? echo $xkey; ? >',

?>
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
        /* Full Screen Wrapper Styles: takes over the entire viewport */
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
            overflow: hidden; /* Hide scrollbars */
        }
        /* Chart container fills the whole wrapper */
        .is-fullscreen .chart-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;

            height: 100vh;
            width: 100vw;
            border-radius: 0;
        }
        /* Hide all non-essential UI in full screen */
        .is-fullscreen .hide-on-fullscreen {
            display: none !important;
        }
        /* Float the exit button in the corner */
        .floating-button-wrapper {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1001; /* Above the chart */
        }
    </style>
</head>
<body class="bg-gray-100 p-4 sm:p-8">

    <div id="app-wrapper" class="max-w-7xl mx-auto bg-white rounded-xl shadow-2xl p-6 sm:p-10 transition-all duration-300">
        
        <!-- Header, hidden in full screen -->
        <h1 id="app-header" class="text-3xl font-bold text-gray-800 mb-6 hide-on-fullscreen"><? echo $title; ?></h1>

        <!-- Full Screen Button - Isolated for floating functionality -->
        <div id="fullScreenToggleWrapper" class="flex justify-end mb-4">
            <button id="fullScreenToggle" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-colors duration-200 flex items-center justify-center text-sm">
                <span id="fullscreen-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-maximize-2 mr-2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><path d="M21 3 14 10"/><path d="M3 21 10 14"/></svg>
                </span>
                Full Screen
            </button>
        </div>

        <!-- Configuration Panel - Hidden in full screen -->
        <div id="config-panel" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8 p-4 bg-gray-50 rounded-lg shadow-inner hide-on-fullscreen">

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
            
            <!-- X-Axis Spacing -->
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

            <!-- Show/Hide Grid Toggle -->
            <div class="col-span-1 flex items-end">
                <button id="gridToggle" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition-colors duration-200 flex items-center justify-center text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-table-properties mr-2"><path d="M15 3H3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2z"/><path d="M10 3v14"/><path d="M1 9h16"/><path d="M21 10h-2"/><path d="M21 14h-2"/><path d="M21 18h-2"/><path d="M21 22h-2"/><path d="M21 6h-2"/></svg>
                    <span id="grid-text">Hide Grid</span>
                </button>
            </div>

        </div>

        <!-- Y-Series Toggles - Hidden in full screen -->
        <div id="y-series-toggles" class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded-r-lg shadow-md hide-on-fullscreen">
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
        const initialRawData = <?php echo json_encode($table); ?>;

        // Configuration state
        const config = {
            data: initialRawData,
            xKey: '<? echo $xkey; ?>',
            yKeys: [],
            activeYKeys: [],
            chartType: 'scatter',
            chartMode: 'lines',
            stackMode: 'none',
            yScale: 'linear',
            xSort: 'original',
            showGrid: true,
            isFullScreen: false,
            xAxisType: 'auto',
        };

        // UI Element References
        const chartDiv = document.getElementById('plotly-graph');
        const chartTypeSelect = document.getElementById('chartType');
        const stackModeSelect = document.getElementById('stackMode');
        const yScaleSelect = document.getElementById('yScale');
        const xAxisTypeSelect = document.getElementById('xAxisType');
        const xSortSelect = document.getElementById('xSort');
        const toggleContainer = document.getElementById('toggle-container');
        const appWrapper = document.getElementById('app-wrapper');
        const fullScreenToggleWrapper = document.getElementById('fullScreenToggleWrapper');

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

        // 3. Data Sorting and Transformation (same as previous version)
        function preprocessData() {
            let sortedData = [...config.data];

            const sortValue = xSortSelect.value;
            const isYSort = sortValue.startsWith('y_');

            // --- FIX: Robustly determine sortKey and isAsc based on sortValue format ---
            let sortKey = config.xKey;
            let isAsc = true; // Default for 'original' and 'x_asc'

            if (sortValue === 'original' || sortValue === 'x_asc') {
                sortKey = config.xKey;
                isAsc = true;
            } else if (sortValue === 'x_desc') {
                sortKey = config.xKey;
                isAsc = false;
            } else if (sortValue.startsWith('y_')) {
                // Y-Sort format: 'y_asc_key' or 'y_desc_key'
                const parts = sortValue.split('_'); 
                const direction = parts[1]; // 'asc' or 'desc'
                // Join the rest of the parts to get the original key name (e.g., 'series_A')
                sortKey = parts.slice(2).join('_');
                isAsc = (direction === 'asc');
            }
            // --- END FIX ---

            if (sortValue === 'original') {
                // Do nothing, already a copy
            } else {
                sortedData.sort((a, b) => {
                    let valA = a[sortKey];
                    let valB = b[sortKey];

                    if (!isNaN(Date.parse(valA)) && !isNaN(Date.parse(valB)) && sortKey === config.xKey) {
                        valA = Date.parse(valA);
                        valB = Date.parse(valB);
                    } else if (!isNaN(valA) && !isNaN(valB)) {
                        valA = Number(valA);
                        valB = Number(valB);
                    } else if (typeof valA === 'string' && typeof valB === 'string') {
                        return isAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
                    }
                    
                    if (valA < valB) return isAsc ? -1 : 1;
                    if (valA > valB) return isAsc ? 1 : -1;
                    return 0;
                });
            }

console.log(sortedData);


            const xValues = sortedData.map(d => d[config.xKey]);
            
            const plotlyTraces = config.yKeys
                .filter(key => config.activeYKeys.includes(key))
                .map(key => {
                    const yValues = sortedData.map(d => d[key]);
                    
                    let trace = {
                        x: xValues,
                        y: yValues,
                        name: key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' '),
                        type: config.chartType === 'area' ? 'scatter' : config.chartType, 
                    };

                    if (config.chartType !== 'bar') {
                        trace.mode = chartTypeSelect.options[chartTypeSelect.selectedIndex].getAttribute('data-mode');
                        trace.line = { shape: 'spline' };
                        trace.marker = { size: 8, opacity: 0.8, line: { width: 1, color: 'white' } };
                    }

                    if (config.chartType === 'area') {
                        trace.fill = 'tonexty';
                        if (config.stackMode === 'stack') {
                            trace.stackgroup = 'one';
                        } else {
                            delete trace.stackgroup;
                        }
                    }

                    if (config.chartType === 'bar') {
                        trace.marker = { opacity: 0.8 };
                    }

                    return trace;
                });

            return plotlyTraces;
        }

        // 4. Render Chart Function (same as previous version, ensures dynamic sizing)
        function renderChart() {
            const data = preprocessData();
            
            const isStacked = config.stackMode === 'stack';

            const layout = {
                title: `Analysis: ${config.xKey} vs. ${config.activeYKeys.join(', ')}`,
                autosize: true,
                // Crucial for fullscreen: set height dynamically
                height: config.isFullScreen ? window.innerHeight : 500,
                margin: { l: 60, r: 20, t: 80, b: 60 },
                hovermode: 'x unified',
                barmode: (config.chartType === 'bar' && isStacked) ? 'stack' : 'group',
                
                xaxis: {
                    title: config.xKey.charAt(0).toUpperCase() + config.xKey.slice(1),
                    gridcolor: config.showGrid ? '#e0e0e0' : 'transparent',
                    zerolinecolor: config.showGrid ? '#e0e0e0' : 'transparent',
                    automargin: true,
                    type: config.xAxisType, 
                },
                
                yaxis: {
                    title: 'Value',
                    type: config.yScale,
                    gridcolor: config.showGrid ? '#e0e0e0' : 'transparent',
                    zerolinecolor: config.showGrid ? '#e0e0e0' : 'transparent',
                    automargin: true,
                    rangemode: 'tozero'
                },
                legend: {
                    orientation: "h",
                    xanchor: "center",
                    x: 0.5,
                    y: 1.05
                },
                paper_bgcolor: config.isFullScreen ? '#f7f7f7' : '#ffffff', // Use body color for full screen background
                plot_bgcolor: '#ffffff',
            };

            const plotOptions = {
                responsive: true,
                displayModeBar: true,
                modeBarButtonsToRemove: ['sendDataToCloud'],
                scrollZoom: true
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

        // Full Screen Toggle Handler (Updated to hide all surrounding UI)
        document.getElementById('fullScreenToggle').addEventListener('click', () => {
            config.isFullScreen = !config.isFullScreen;
            const icon = document.getElementById('fullscreen-icon');
            const buttonText = document.getElementById('fullScreenToggle').lastChild;
            
            if (config.isFullScreen) {
                appWrapper.classList.add('is-fullscreen');
                
                // Float the button over the chart
                fullScreenToggleWrapper.classList.add('floating-button-wrapper');
                fullScreenToggleWrapper.classList.remove('mb-4');
                
                // Update icon and text to Exit
                icon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-minimize-2 mr-2"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><path d="M10 14 21 3"/><path d="M3 21 14 10"/></svg>`;
                buttonText.textContent = 'Exit Full Screen';
            } else {
                appWrapper.classList.remove('is-fullscreen');
                
                // Restore button position
                fullScreenToggleWrapper.classList.remove('floating-button-wrapper');
                fullScreenToggleWrapper.classList.add('mb-4');

                // Update icon and text to Maximize
                icon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-maximize-2 mr-2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><path d="M21 3 14 10"/><path d="M3 21 10 14"/></svg>`;
                buttonText.textContent = 'Full Screen';
            }
            
            // Re-render to adjust the chart size for the new container dimensions
            // A slight delay ensures the CSS updates take effect before Plotly recalculates dimensions.
            setTimeout(renderChart, 10);
        });

        // Listener setup for configuration controls
        chartTypeSelect.addEventListener('change', (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            updateConfig('chartType', selectedOption.value);
            updateConfig('chartMode', selectedOption.getAttribute('data-mode'));
        });

        stackModeSelect.addEventListener('change', (e) => updateConfig('stackMode', e.target.value));
        yScaleSelect.addEventListener('change', (e) => updateConfig('yScale', e.target.value));
        xAxisTypeSelect.addEventListener('change', (e) => updateConfig('xAxisType', e.target.value));
        xSortSelect.addEventListener('change', () => renderChart());

        document.getElementById('gridToggle').addEventListener('click', () => {
            config.showGrid = !config.showGrid;
            document.getElementById('grid-text').textContent = config.showGrid ? 'Hide Grid' : 'Show Grid';
            renderChart();
        });

        
        // Handle resizing (important for responsive charts)
        window.addEventListener('resize', () => {
            if (config.isFullScreen) {
                // For full screen, update layout height
                renderChart();
            } else {
                // Otherwise, use Plotly's built-in resize function
                // Note: Plotly.react/relayout usually handles this if autosize:true is set
                Plotly.relayout(chartDiv, { autosize: true, height: 500 });
            }
        });


        // 6. Start the application
        window.onload = initializeUI;
    </script>
</body>
</html>
