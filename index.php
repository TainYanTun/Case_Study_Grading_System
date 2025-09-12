<?php
// Initialize grades.json if it doesn't exist
$gradesFile = 'grades.json';
if (!file_exists($gradesFile)) {
    $initialData = ['case_studies' => []];
    file_put_contents($gradesFile, json_encode($initialData, JSON_PRETTY_PRINT));
}

$message = '';
$messageType = '';

// Handle form submission
if ($_POST) {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $student = trim($_POST['student']);
    $grade = (int)$_POST['grade'];
    
    // Validation
    if ($id < 1 || $id > 25) {
        $message = 'ID must be between 1 and 25.';
        $messageType = 'error';
    } elseif (empty($title) || empty($student)) {
        $message = 'Title and Student Name are required.';
        $messageType = 'error';
    } elseif ($grade < 0 || $grade > 100) {
        $message = 'Grade must be between 0 and 100.';
        $messageType = 'error';
    } else {
        // Load existing data
        $data = json_decode(file_get_contents($gradesFile), true);
        
        // Check if case study with this ID already exists
        $updated = false;
        foreach ($data['case_studies'] as &$caseStudy) {
            if ($caseStudy['id'] == $id) {
                $caseStudy['title'] = $title;
                $caseStudy['student'] = $student;
                $caseStudy['grade'] = $grade;
                $updated = true;
                break;
            }
        }
        
        // If not found, add new case study
        if (!$updated) {
            $data['case_studies'][] = [
                'id' => $id,
                'title' => $title,
                'student' => $student,
                'grade' => $grade
            ];
        }
        
        // Sort by ID
        usort($data['case_studies'], function($a, $b) {
            return $a['id'] - $b['id'];
        });
        
        // Save to file
        if (file_put_contents($gradesFile, json_encode($data, JSON_PRETTY_PRINT))) {
            $message = $updated ? 'Case study updated successfully!' : 'Case study added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error saving data. Please try again.';
            $messageType = 'error';
        }
    }
}

// Load existing data to show current case studies
$data = json_decode(file_get_contents($gradesFile), true);
$existingCaseStudies = $data['case_studies'] ?? [];

// Calculate quick stats for mini dashboard
$totalCases = count($existingCaseStudies);
$averageGrade = 0;
if ($totalCases > 0) {
    $totalGrades = array_sum(array_column($existingCaseStudies, 'grade'));
    $averageGrade = round($totalGrades / $totalCases, 1);
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Study Manager</title>
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
            justify-content: between;
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

        .theme-toggle {
            position: absolute;
            right: 24px;
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
            margin-bottom: 24px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .page-description {
            color: var(--color-fg-muted);
            font-size: 16px;
        }

        .layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (max-width: 1024px) {
            .layout {
                grid-template-columns: 1fr;
            }
        }

        .main-content {
            background: var(--color-canvas-overlay);
            border: 1px solid var(--color-border-default);
            border-radius: 12px;
            overflow: hidden;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .card {
            background: var(--color-canvas-overlay);
            border: 1px solid var(--color-border-default);
            border-radius: 12px;
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--color-border-muted);
            background: var(--color-canvas-subtle);
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--color-fg-default);
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--color-border-default);
            border-radius: 6px;
            font-size: 14px;
            background: var(--color-canvas-default);
            color: var(--color-fg-default);
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--color-accent-fg);
            box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.12);
        }

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
            line-height: 1.5;
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

        .btn-block {
            width: 100%;
            justify-content: center;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: #dafbe1;
            border: 1px solid #b4f1c2;
            color: var(--color-success-fg);
        }

        .alert-error {
            background: #ffebe9;
            border: 1px solid #ffc1bc;
            color: var(--color-danger-fg);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .stat-item {
            text-align: center;
            padding: 16px;
            background: var(--color-canvas-subtle);
            border-radius: 8px;
            border: 1px solid var(--color-border-muted);
        }

        .stat-number {
            font-size: 24px;
            font-weight: 600;
            color: var(--color-accent-fg);
            display: block;
        }

        .stat-label {
            font-size: 12px;
            color: var(--color-fg-muted);
            margin-top: 4px;
        }

        .chart-container {
            position: relative;
            height: 200px;
            margin-bottom: 16px;
        }

        .case-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .case-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--color-border-muted);
        }

        .case-item:last-child {
            border-bottom: none;
        }

        .case-id {
            background: var(--color-accent-fg);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            min-width: 32px;
            text-align: center;
        }

        .case-info {
            flex: 1;
            min-width: 0;
        }

        .case-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 2px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .case-student {
            font-size: 12px;
            color: var(--color-fg-muted);
        }

        .case-grade {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
        }

        .grade-a { background: #dafbe1; color: var(--color-success-fg); }
        .grade-b { background: #fff8e1; color: #bf8700; }
        .grade-c { background: #fff4e6; color: #bc4c00; }
        .grade-d { background: #ffebe9; color: var(--color-danger-fg); }
        .grade-f { background: #ffebe9; color: var(--color-danger-fg); }

        .navigation {
            background: var(--color-canvas-subtle);
            border: 1px solid var(--color-border-default);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--color-fg-muted);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-chart-line"></i>
                Case Study Manager
            </div>
            <button class="theme-toggle" onclick="toggleTheme()">
                <i class="fas fa-sun"></i> Light
            </button>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Add Case Study</h1>
            <p class="page-description">Input presentation details and grades for comprehensive tracking</p>
        </div>

        <div class="layout">
            <div class="main-content">
                <div class="card-header">
                    <i class="fas fa-plus-circle"></i>
                    New Case Study Entry
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label" for="id">Case Study ID</label>
                            <select name="id" id="id" class="form-control" required>
                                <option value="">Select ID (1-25)</option>
                                <?php for ($i = 1; $i <= 25; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo (isset($_POST['id']) && $_POST['id'] == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="title">Case Study Title</label>
                            <input type="text" name="title" id="title" class="form-control" required 
                                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                   placeholder="Enter case study title">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="student">Student Name</label>
                            <input type="text" name="student" id="student" class="form-control" required 
                                   value="<?php echo isset($_POST['student']) ? htmlspecialchars($_POST['student']) : ''; ?>"
                                   placeholder="Enter student name">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="grade">Grade (0-100)</label>
                            <input type="number" name="grade" id="grade" class="form-control" min="0" max="100" required 
                                   value="<?php echo isset($_POST['grade']) ? htmlspecialchars($_POST['grade']) : ''; ?>"
                                   placeholder="Enter grade">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i>
                            Save Case Study
                        </button>
                    </form>
                </div>
            </div>

            <div class="sidebar">
                <!-- Quick Stats -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar"></i>
                        Quick Stats
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $totalCases; ?></span>
                                <span class="stat-label">Total Cases</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $totalCases > 0 ? $averageGrade.'%' : '—'; ?></span>
                                <span class="stat-label">Average</span>
                            </div>
                        </div>
                        
                        <?php if ($totalCases > 0): ?>
                            <div class="chart-container">
                                <canvas id="miniChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Cases -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list"></i>
                        Recent Cases (<?php echo min($totalCases, 10); ?>)
                    </div>
                    <div class="card-body">
                        <?php if ($totalCases > 0): ?>
                            <div class="case-list">
                                <?php 
                                $recentCases = array_slice(array_reverse($existingCaseStudies), 0, 10);
                                foreach ($recentCases as $case): 
                                    $gradeClass = 'grade-f';
                                    if ($case['grade'] >= 90) $gradeClass = 'grade-a';
                                    elseif ($case['grade'] >= 80) $gradeClass = 'grade-b';
                                    elseif ($case['grade'] >= 70) $gradeClass = 'grade-c';
                                    elseif ($case['grade'] >= 60) $gradeClass = 'grade-d';
                                ?>
                                    <div class="case-item">
                                        <div class="case-id"><?php echo $case['id']; ?></div>
                                        <div class="case-info">
                                            <div class="case-title"><?php echo htmlspecialchars($case['title']); ?></div>
                                            <div class="case-student"><?php echo htmlspecialchars($case['student']); ?></div>
                                        </div>
                                        <div class="case-actions">
                                            <div class="case-grade <?php echo $gradeClass; ?>"><?php echo $case['grade']; ?>%</div>
                                            <a href="?delete=<?php echo $case['id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this case study?')"
                                               title="Delete case study">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-folder-open"></i>
                                <p>No cases added yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="navigation">
            <a href="summary.php" class="btn btn-secondary">
                <i class="fas fa-chart-line"></i>
                View Analytics & Summary
            </a>
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
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        if (savedTheme === 'dark') {
            document.querySelector('.theme-toggle').innerHTML = '<i class="fas fa-sun"></i> Light';
        }

        // Initialize mini chart if data exists
        <?php if ($totalCases > 0): ?>
            const gradeData = <?php echo json_encode(array_column($existingCaseStudies, 'grade')); ?>;
            
            const ctx = document.getElementById('miniChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: gradeData.map((_, i) => i + 1),
                    datasets: [{
                        label: 'Grades',
                        data: gradeData,
                        borderColor: '#0969da',
                        backgroundColor: 'rgba(9, 105, 218, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>