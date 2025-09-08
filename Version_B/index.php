<?php
// Include helper functions
require_once 'lib.php';

// Record page view for analytics
record_analytics('page_view', ['page' => '/']);

// Initialize data files if they don't exist
init_data_files();

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_student'])) {
        add_student();
    } elseif (isset($_POST['assign_case_studies'])) {
        assign_case_studies();
    } elseif (isset($_POST['submit_grade'])) {
        submit_grade();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Networking Case Study Assignment</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="logo">NetCase</div>
            <ul>
                <li><a href="#students" class="nav-link active">Students</a></li>
                <li><a href="#assignments" class="nav-link">Assignments</a></li>
                <li><a href="#grades" class="nav-link">Grades</a></li>
                <li><a href="#analytics" class="nav-link">Analytics</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="content">
            <!-- Students Section -->
            <section id="students" class="section active">
                <h2>Student Management</h2>
                <form method="POST" class="card">
                    <div class="form-group">
                        <label for="student_name">Student Name</label>
                        <input type="text" id="student_name" name="student_name" required>
                    </div>
                    <button type="submit" name="add_student" class="btn-primary">Add Student</button>
                </form>
                
                <div class="card">
                    <h3>Registered Students</h3>
                    <ul class="student-list">
                        <?php
                        $students = read_json('students.json');
                        foreach ($students as $student) {
                            echo '<li>' . escape_html($student) . '</li>';
                        }
                        ?>
                    </ul>
                </div>
            </section>

            <!-- Assignments Section -->
            <section id="assignments" class="section">
                <h2>Case Study Assignments</h2>
                <?php
                $assignments = read_json('assignments.json');
                if (!empty($assignments['pairs'])) {
                    echo '<div class="card">';
                    echo '<p>Assignments were created on ' . escape_html($assignments['assigned_at']) . '</p>';
                    echo '<table class="assignments-table">';
                    echo '<tr><th>Student</th><th>Case Study</th></tr>';
                    foreach ($assignments['pairs'] as $pair) {
                        echo '<tr>';
                        echo '<td>' . escape_html($pair['student']) . '</td>';
                        echo '<td>' . escape_html($pair['case_id']) . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    echo '</div>';
                } else {
                    echo '<div class="card">';
                    echo '<form method="POST">';
                    echo '<p>No assignments have been created yet.</p>';
                    echo '<button type="submit" name="assign_case_studies" class="btn-primary">Assign Case Studies</button>';
                    echo '</form>';
                    echo '</div>';
                }
                ?>
            </section>

            <!-- Grades Section -->
            <section id="grades" class="section">
                <h2>Grading System</h2>
                <div class="card">
                    <form method="POST" id="grading-form">
                        <div class="form-group">
                            <label for="student_select">Select Student</label>
                            <select id="student_select" name="student" required>
                                <option value="">Choose a student...</option>
                                <?php
                                $students = read_json('students.json');
                                foreach ($students as $student) {
                                    echo '<option value="' . escape_html($student) . '">' . escape_html($student) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="technical_accuracy">Technical Accuracy (40-100)</label>
                            <input type="number" id="technical_accuracy" name="technical_accuracy" min="40" max="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="clarity">Clarity (40-100)</label>
                            <input type="number" id="clarity" name="clarity" min="40" max="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="answering">Answering Ability (40-100)</label>
                            <input type="number" id="answering" name="answering" min="40" max="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="understanding">Understanding (40-100)</label>
                            <input type="number" id="understanding" name="understanding" min="40" max="100" required>
                        </div>
                        
                        <button type="submit" name="submit_grade" class="btn-primary">Submit Grade</button>
                    </form>
                </div>
                
                <div class="card">
                    <h3>Current Grades</h3>
                    <table class="grades-table">
                        <tr>
                            <th>Student</th>
                            <th>Technical</th>
                            <th>Clarity</th>
                            <th>Answering</th>
                            <th>Understanding</th>
                            <th>Average</th>
                        </tr>
                        <?php
                        $grades = read_json('grades.json');
                        foreach ($grades as $student => $data) {
                            $avg = $data['average'];
                            $color_class = get_grade_color($avg);
                            
                            echo '<tr>';
                            echo '<td>' . escape_html($student) . '</td>';
                            echo '<td>' . escape_html($data['grades']['technical_accuracy']) . '</td>';
                            echo '<td>' . escape_html($data['grades']['clarity']) . '</td>';
                            echo '<td>' . escape_html($data['grades']['answering']) . '</td>';
                            echo '<td>' . escape_html($data['grades']['understanding']) . '</td>';
                            echo '<td class="' . $color_class . '">' . escape_html($avg) . '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </table>
                </div>
            </section>

            <!-- Analytics Section -->
            <section id="analytics" class="section">
                <h2>Analytics</h2>
                <div class="card">
                    <h3>Usage Statistics</h3>
                    <?php
                    $analytics = read_json('analytics.json');
                    $page_views = count($analytics['page_views']);
                    $last_24h = get_last_24h_activity($analytics);
                    $top_case_studies = get_top_case_studies($analytics);
                    
                    echo '<p>Total Page Views: ' . $page_views . '</p>';
                    echo '<p>Activity in last 24h: ' . $last_24h . ' events</p>';
                    
                    echo '<h4>Top 5 Case Studies</h4>';
                    if (!empty($top_case_studies)) {
                        echo '<ul>';
                        foreach ($top_case_studies as $case_id => $views) {
                            echo '<li>' . escape_html($case_id) . ': ' . $views . ' views</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<p>No case study views recorded yet.</p>';
                    }
                    ?>
                    
                    <a href="download_analytics.php" class="btn-secondary">Download Analytics JSON</a>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/script.js"></script>
</body>
</html>