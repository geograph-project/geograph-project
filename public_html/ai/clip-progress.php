<?php

require_once 'geograph/global.inc.php';


// Get a database connection
$db = GeographDatabaseConnection();

$model = 'clip';
if (!empty($_GET['model']) && preg_match('/^\w+$/',$_GET['model']))
	$model = $_GET['model'];

// Fetch daily stats
$dailyStatsQuery = "SELECT `day`, `count` FROM `embedding_progress_$model` ORDER BY `day`";
$dailyStats = $db->GetAll($dailyStatsQuery);

// Fetch total images
$totalImagesQuery = "SELECT `images` FROM `user_stat` WHERE `user_id` = 0";
$totalImages = $db->GetOne($totalImagesQuery);

// Process data
$labels = [];
$data = [];
$cumulativeData = [];
$cumulativeTotal = 0;

foreach ($dailyStats as $row) {
    $labels[] = $row['day'];
    $data[] = (int)$row['count'];
    $cumulativeTotal += (int)$row['count'];
    $cumulativeData[] = $cumulativeTotal;
}

// --- Linear Regression for Trend Line ---
$n = count($cumulativeData);
if ($n > 1) {
    $x = range(1, $n);
    $y = $cumulativeData;

    $sumX = array_sum($x);
    $sumY = array_sum($y);
    $sumX2 = 0;
    $sumXY = 0;

    for ($i = 0; $i < $n; $i++) {
        $sumX2 += $x[$i] * $x[$i];
        $sumXY += $x[$i] * $y[$i];
    }

    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;

    // Now create the trendline data
    $trendData = [];
    for ($i = 0; $i < $n; $i++) {
        $trendData[] = round($slope * $x[$i] + $intercept);
    }

    // Predict completion date
    if ($slope > 0 && $cumulativeTotal < $totalImages) {
        $remainingImages = $totalImages - $cumulativeTotal;
        $daysToCompletion = $remainingImages / $slope;
        $lastDate = new DateTime(end($labels));
        $completionDate = $lastDate->add(new DateInterval('P' . round($daysToCompletion) . 'D'));
        $completionDateStr = $completionDate->format('Y-m-d');
    } else {
        $completionDateStr = "N/A (progress is not increasing)";
    }

} else {
    $trendData = [];
    $completionDateStr = "N/A (not enough data)";
}


?>
<!DOCTYPE html>
<html>
<head>
    <title>Embedding Progress</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Embedding Progress</h1>
    <div style="width: 80%; margin: auto;">
        <canvas id="progressChart"></canvas>
    </div>
    <p>Total Images Processed: <?php echo number_format($cumulativeTotal); ?></p>
    <p>Total Images to Process: <?php echo number_format($totalImages); ?></p>
    <p>Percentage Complete: <?php echo round(($cumulativeTotal / $totalImages) * 100, 2); ?>%</p>
    <p>Predicted Completion Date: <?php echo $completionDateStr; ?></p>

    <script>
        const ctx = document.getElementById('progressChart');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [
                    {
                        label: 'Daily Processed',
                        data: <?php echo json_encode($data); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Cumulative Progress',
                        data: <?php echo json_encode($cumulativeData); ?>,
                        type: 'line',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: false
                    },
                    {
                        label: 'Trend Line',
                        data: <?php echo json_encode($trendData); ?>,
                        type: 'line',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        fill: false,
                        pointRadius: 0, // No points on the trend line
                        borderDash: [5, 5] // Dashed line
                    }
                ]
            },
            options: {
                scales: {
                    y: {
			min: 0,
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
