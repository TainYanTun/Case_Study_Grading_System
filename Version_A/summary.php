<?php
// Load grades data
$gradesFile = 'grades.json';
$data = ['case_studies' => []];

if (file_exists($gradesFile)) {
    $data = json_decode(file_get_contents($gradesFile), true);
}

$caseStudies = $data['case_studies'] ?? [];

// Calculate statistics
$totalCases = count($caseStudies);
$averageGrade = 0;
$highestGrade = 0;
$lowestGrade = 100;
$highestStudent = '';
$lowestStudent = '';
$medianGrade = 0;

if ($totalCases > 0) {
    $grades = array_column($caseStudies, 'grade');
    $totalGrades = array_sum($grades);
    $averageGrade = round($totalGrades / $totalCases, 2);
    
    // Calculate median
    sort($grades);
    $middle = floor($totalCases / 2);
    $medianGrade = $totalCases % 2 ? $grades[$middle] : ($grades[$middle - 1] + $grades[$middle]) / 2;
    
    foreach ($caseStudies as $case) {
        if ($case['grade'] > $highestGrade) {
            $highestGrade = $case['grade'];
            $highestStudent = $case['student'];
        }
        
        if ($case['grade'] < $lowestGrade) {
            $lowestGrade = $case['grade'];
            $lowestStudent = $case['student'];
        }
    }
} else {
    $lowestGrade = 0;
}

// Grade distribution
$gradeRanges = [
    'A (90-100)' => ['count' => 0, 'color' => '#1f883d'],
    'B (80-89)' => ['count' => 0, 'color' => '#bf8700'],
    'C (70-79)' => ['count' => 0, 'color' => '#bc4c00'],
    'D (60-69)' => ['count' => 0, 'color' => '#cf222e'],
    'F (0-59)' => ['count' => 0, 'color' => '#a40e26']
];

foreach ($caseStudies as $case) {
    $grade = $case['grade'];
    if ($grade >= 90) $gradeRanges['A (90-100)']['count']++;
    elseif ($grade >= 80) $gradeRanges['B (80-89)']['count']++;
    elseif ($grade >= 70) $gradeRanges['C (70-79)']['count']++;
    elseif ($grade >= 60) $gradeRanges['D (60-69)']['count']++;
    else $gradeRanges['F (0-59)']['count']++;
}

// Performance trends (for line chart)
$trendData = [];
if ($totalCases > 0) {
    usort($caseStudies, function($a, $b) {
        return $a['id'] - $b['id'];
    });
    $trendData = array_map(function($case) {
        return ['x' => $case['id'], 'y' => $case['grade']];
    }, $caseStudies);
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Case Study Manager</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-canvas-default: #ffffff;
            --color-canvas-subtle: #f6f8fa;
            --color-border-default: #d1d9e0;
            --color-border-muted: #d8dee4;
            --color-neutral-muted: #656d76;
            --color-fg-default: #1f2328;
            --color-fg-muted: #656d76;
            --color-accent-fg: #0969da;
            --color-success-fg: #1a7f37;
            --color-danger-fg: #d1242f;
            --color-done-fg: #8250df;
            --color-btn-bg: #f6f8fa;
            --color-btn-border: #d1d9e0;
            --color-btn-hover-bg: #f3f4f6;
            --color-btn-active-bg: #ebf0f4;
            --color-btn-primary-bg: #1f883d;
            --color-btn-primary-hover-bg: #1a7f37;
            --color-canvas-overlay: #ffffff;
            --color-overlay-shadow: rgba(31, 35, 40, 0.12);
        }

        [data-theme="dark"] {
            --color-canvas-default: #0d1117;
            --color-canvas-subtle: #161b22;
            --color-border-default: #30363d;
            --color-border-muted: #21262d;
            --color-neutral-muted: #7d8590;
            --color-fg-default: #e6edf3;
            --color-fg-muted: #7d8590;
            --color-accent-fg: #2f81f7;
            --color-success-fg: #3fb950;
            --color-danger-fg: #f85149;
            --color-done-fg: #a5a5ff;
            --color-btn-bg: #21262d;
            --color-btn-border: #30363d;
            --color-btn-hover-bg: #30363d;
            --color-btn-active-bg: #282e36;
            --color-btn-primary-bg: #238636;
            --color-btn-primary-hover-bg: #2ea043;
            --color-canvas-overlay: #161b22;
            --color-overlay-shadow: rgba(0, 0, 0, 0.24);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans', Helvetica, Arial, sans-serif;
            background-color: var(--color-canvas-default);
            color: var(--color-fg-default);
            line-height: 1.5;
        }

        .header {
            background-color: var(--color-canvas-subtle);
            border-bottom: 1px solid var(--color-border-default);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 600;
            color: var(--color-fg-default);
        }

        .logo i {
            color: var(--color-accent-fg);
        }

        .header-nav {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .theme-toggle {
            background: var(--color-btn-bg);
            border: 1px solid var(--color-btn-border);
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            color: var(--color-fg-default);
            font-size: 14px;
        }

        .theme-toggle:hover {
            background: var(--color-btn-hover-bg);
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px;
        }

        .page-header {
            margin-bottom: 32px;
        }

        .page-title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .page-description {
            color: var(--color-fg-muted);
            font-size: 16px;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--color-canvas-overlay);
            border: 1px solid var(--color-border-default);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--color-overlay-shadow) 0px 8px 24px;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--color-fg-muted);
            font-size: 14px;
            font-weight: 500;
        }

        .stat-detail {
            font-size: 12px;
            color: var(--color-fg-muted);
            margin-top: 4px;
        }

        .stat-card.total .stat-number { color: var(--color-accent-fg); }
        .stat-card.average .stat-number { color: var(--color-success-fg); }
        .stat-card.highest .stat-number { color: #bf8700; }
        .stat-card.lowest .stat-number { color: var(--color-danger-fg); }

        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        @media (max-width: 1024px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--color-canvas-overlay);
            border: 1px solid var(--color-border-default);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--color-border-muted);
            background: var(--color-canvas-subtle);
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 24px;
        }

        .chart-container {
            position: relative;
            height: 400px;
        }

        .chart-container.small {
            height: 300px;
        }

        .table-responsive {
            overflow-x: auto;
            margin: -24px;
            padding: 24px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .data-table th {
            background: var(--color-canvas-subtle);
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            border-bottom: 1px solid var(--color-border-muted);
            white-space: nowrap;
        }

        .data-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--color-border-muted);
        }

        .data-table tr:hover {
            background: var(--color-canvas-subtle);
        }

        .grade-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 48px;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .grade-a { background: #dafbe1; color: var(--color-success-fg); }
        .grade-b { background: #fff8e1; color: #bf8700; }
        .grade-c { background: #fff4e6; color: #bc4c00; }
        .grade-d { background: #ffebe9; color: var(--color-danger-fg); }
        .grade-f { background: #ffebe9; color: var(--color-danger-fg); }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: 1px solid;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }

        .btn-primary {
            background: var(--color-btn-primary-bg);
            border-color: var(--color-btn-primary-bg);
            color: #ffffff;
        }

        .btn-primary:hover {
            background: var(--color-btn-primary-hover-bg);
            border-color: var(--color-btn-primary-hover-bg);
        }

        .btn-secondary {
            background: var(--color-btn-bg);
            border-color: var(--color-btn-border);
            color: var(--color-fg-default);
        }

        .btn-secondary:hover {
            background: var(--color-btn-hover-bg);
        }

        .navigation {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--color-fg-muted);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 12px;
        }

        .progress-bar {
            background: var(--color-canvas-subtle);
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin: 8px 0;
        }

        .progress-fill {
            height: 100%;
            background: var(--color-accent-fg);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--color-border-muted);
        }

        .metric-row:last-child {
            border-bottom: none;
        }

        .metric-label {
            font-weight: 500;
        }

        .metric-value {
            font-weight: 600;
            color: var(--color-accent-fg);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-chart-line"></i>
                Case Study Analytics
            </div>
            <div class="header-nav">
                <button class="theme-toggle" onclick="toggleTheme()">
                    <i class="fas fa-moon"></i> Dark
                </button>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">📊 Analytics Dashboard</h1>
            <p class="page-description">Comprehensive overview of case study performance and trends</p>
        </div>

        <?php if ($totalCases > 0): ?>
            <!-- Statistics Overview -->
            <div class="stats-overview">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $totalCases; ?></div>
                    <div class="stat-label">Total Case Studies</div>
                    <div class="stat-detail"><?php echo round(($totalCases / 25) * 100, 1); ?>% of 25 possible</div>
                </div>

                <div class="stat-card average">
                    <div class="stat-number"><?php echo $averageGrade; ?>%</div>
                    <div class="stat-label">Average Grade</div>
                    <div class="stat-detail">Median: <?php echo round($medianGrade, 1); ?>%</div>
                </div>

                <div class="stat-card highest">
                    <div class="stat-number"><?php echo $highestGrade; ?>%</div>
                    <div class="stat-label">Highest Grade</div>
                    <div class="stat-detail"><?php echo htmlspecialchars($highestStudent); ?></div>
                </div>

                <div class="stat-card lowest">
                    <div class="stat-number"><?php echo $lowestGrade; ?>%</div>
                    <div class="stat-label">Lowest Grade</div>
                    <div class="stat-detail"><?php echo htmlspecialchars($lowestStudent); ?></div>
                </div>
            </div>

            <!-- Analytics Charts -->
            <div class="analytics-grid">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i>
                        Performance Trends
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-pie"></i>
                        Grade Distribution
                    </div>
                    <div class="card-body">
                        <div class="chart-container small">
                            <canvas id="distributionChart"></canvas>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <?php foreach ($gradeRanges as $range => $data): ?>
                                <?php $percentage = $totalCases > 0 ? ($data['count'] / $totalCases) * 100 : 0; ?>
                                <div class="metric-row">
                                    <span class="metric-label"><?php echo $range; ?></span>
                                    <span class="metric-value"><?php echo $data['count']; ?> (<?php echo round($percentage, 1); ?>%)</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: <?php echo $data['color']; ?>;"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-table"></i>
                    All Case Studies
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Student</th>
                                <th>Grade</th>
                                <th>Letter</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($caseStudies as $case): ?>
                                <?php
                                $letterGrade = 'F';
                                $gradeClass = 'grade-f';
                                $performance = 'Below Average';
                                
                                if ($case['grade'] >= 90) { 
                                    $letterGrade = 'A'; 
                                    $gradeClass = 'grade-a'; 
                                    $performance = 'Excellent';
                                } elseif ($case['grade'] >= 80) { 
                                    $letterGrade = 'B'; 
                                    $gradeClass = 'grade-b'; 
                                    $performance = 'Good';
                                } elseif ($case['grade'] >= 70) { 
                                    $letterGrade = 'C'; 
                                    $gradeClass = 'grade-c'; 
                                    $performance = 'Average';
                                } elseif ($case['grade'] >= 60) { 
                                    $letterGrade = 'D'; 
                                    $gradeClass = 'grade-d'; 
                                    $performance = 'Below Average';
                                }
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $case['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($case['title']); ?></td>
                                    <td><?php echo htmlspecialchars($case['student']); ?></td>
                                    <td>
                                        <span class="grade-badge <?php echo $gradeClass; ?>">
                                            <?php echo $case['grade']; ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="grade-badge <?php echo $gradeClass; ?>">
                                            <?php echo $letterGrade; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $performance; ?></td>
                                    <td class="action-cell">
                                        <a href="?delete=<?php echo $case['id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this case study?\\n\\nTitle: <?php echo addslashes($case['title']); ?>\\nStudent: <?php echo addslashes($case['student']); ?>')"
                                           title="Delete case study">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-chart-bar"></i>
                <h3>No Data Available</h3>
                <p>Start by adding some case studies to see comprehensive analytics and trends.</p>
            </div>
        <?php endif; ?>

        <div class="navigation">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add Case Studies
            </a>
            <?php if ($totalCases > 0): ?>
                <a href="?export=json" class="btn btn-secondary" onclick="return confirm('Download grades data as JSON?')">
                    <i class="fas fa-download"></i>
                    Export Data
                </a>
                <a href="?export=csv" class="btn btn-secondary" onclick="return confirm('Download grades data as CSV?')">
                    <i class="fas fa-file-csv"></i>
                    Export CSV
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Theme toggle functionality
        function toggleTheme() {
            const html = document.documentElement;
            const themeToggle = document.querySelector('.theme-toggle');
            const currentTheme = html.getAttribute('data-theme');
            
            if (currentTheme === 'dark') {
                html.setAttribute('data-theme', 'light');
                themeToggle.innerHTML = '<i class="fas fa-moon"></i> Dark';
                localStorage.setItem('theme', 'light');
            } else {
                html.setAttribute('data-theme', 'dark');
                themeToggle.innerHTML = '<i class="fas fa-sun"></i> Light';
                localStorage.setItem('theme', 'dark');
            }
            
            // Update charts for new theme
            if (window.trendChart) {
                updateChartsForTheme();
            }
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        if (savedTheme === 'dark') {
            document.querySelector('.theme-toggle').innerHTML = '<i class="fas fa-sun"></i> Light';
        }

        // Chart colors based on theme
        function getChartColors() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            return {
                gridColor: isDark ? '#30363d' : '#d1d9e0',
                textColor: isDark ? '#e6edf3' : '#1f2328',
                primaryColor: isDark ? '#2f81f7' : '#0969da',
                backgroundColors: [
                    isDark ? '#3fb950' : '#1a7f37',
                    isDark ? '#d29922' : '#bf8700',
                    isDark ? '#f85149' : '#bc4c00',
                    isDark ? '#ff7b72' : '#cf222e',
                    isDark ? '#ff9492' : '#a40e26'
                ]
            };
        }

        function updateChartsForTheme() {
            const colors = getChartColors();
            
            // Update trend chart
            if (window.trendChart) {
                window.trendChart.options.scales.x.grid.color = colors.gridColor;
                window.trendChart.options.scales.y.grid.color = colors.gridColor;
                window.trendChart.options.scales.x.ticks.color = colors.textColor;
                window.trendChart.options.scales.y.ticks.color = colors.textColor;
                window.trendChart.data.datasets[0].borderColor = colors.primaryColor;
                window.trendChart.data.datasets[0].backgroundColor = colors.primaryColor + '20';
                window.trendChart.update();
            }

            // Update distribution chart
            if (window.distributionChart) {
                window.distributionChart.data.datasets[0].backgroundColor = colors.backgroundColors;
                window.distributionChart.options.plugins.legend.labels.color = colors.textColor;
                window.distributionChart.update();
            }
        }

        // Initialize charts if data exists
        <?php if ($totalCases > 0): ?>
            const colors = getChartColors();
            
            // Trend Chart
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            const trendData = <?php echo json_encode($trendData); ?>;
            
            window.trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: 'Grade Performance',
                        data: trendData,
                        borderColor: colors.primaryColor,
                        backgroundColor: colors.primaryColor + '20',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: colors.primaryColor,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    scales: {
                        x: {
                            type: 'linear',
                            title: {
                                display: true,
                                text: 'Case Study ID',
                                color: colors.textColor
                            },
                            grid: {
                                color: colors.gridColor
                            },
                            ticks: {
                                color: colors.textColor,
                                stepSize: 1
                            }
                        },
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Grade (%)',
                                color: colors.textColor
                            },
                            grid: {
                                color: colors.gridColor
                            },
                            ticks: {
                                color: colors.textColor
                            }
                        }
                    }
                }
            });

            // Distribution Chart
            const distributionCtx = document.getElementById('distributionChart').getContext('2d');
            const distributionLabels = <?php echo json_encode(array_keys($gradeRanges)); ?>;
            const distributionValues = <?php echo json_encode(array_column($gradeRanges, 'count')); ?>;
            
            window.distributionChart = new Chart(distributionCtx, {
                type: 'doughnut',
                data: {
                    labels: distributionLabels,
                    datasets: [{
                        data: distributionValues,
                        backgroundColor: colors.backgroundColors,
                        borderWidth: 2,
                        borderColor: colors.gridColor
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: colors.textColor,
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.raw / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.raw + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    cutout: '50%'
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>

<?php
// Handle export functionality
if (isset($_GET['export']) && $totalCases > 0) {
    if ($_GET['export'] === 'json') {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="case_studies_export_' . date('Y-m-d') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    } elseif ($_GET['export'] === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="case_studies_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Title', 'Student', 'Grade', 'Letter Grade']);
        
        foreach ($caseStudies as $case) {
            $letterGrade = 'F';
            if ($case['grade'] >= 90) $letterGrade = 'A';
            elseif ($case['grade'] >= 80) $letterGrade = 'B';
            elseif ($case['grade'] >= 70) $letterGrade = 'C';
            elseif ($case['grade'] >= 60) $letterGrade = 'D';
            
            fputcsv($output, [
                $case['id'],
                $case['title'],
                $case['student'],
                $case['grade'],
                $letterGrade
            ]);
        }
        
        fclose($output);
        exit;
    }
}
?>