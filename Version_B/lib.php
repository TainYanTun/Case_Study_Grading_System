<?php
// Helper functions for the Networking Case Study application

// Initialize data files if they don't exist
function init_data_files() {
    $files = [
        'students.json' => [],
        'data.json' => [
            ['id' => 'cs-001', 'title' => 'Subnetting Lab'],
            ['id' => 'cs-002', 'title' => 'Routing Protocols'],
            ['id' => 'cs-003', 'title' => 'Network Security'],
            ['id' => 'cs-004', 'title' => 'VLAN Configuration'],
            ['id' => 'cs-005', 'title' => 'TCP/IP Analysis']
        ],
        'assignments.json' => ['assigned_at' => '', 'pairs' => []],
        'grades.json' => [],
        'analytics.json' => [
            'page_views' => [],
            'repo_views' => [],
            'assignments' => [],
            'grades_submitted' => []
        ]
    ];
    
    foreach ($files as $file => $default_data) {
        if (!file_exists($file)) {
            write_json($file, $default_data);
        }
    }
}

// Read JSON file
function read_json($file) {
    if (!file_exists($file)) {
        return [];
    }
    
    $content = file_get_contents($file);
    return json_decode($content, true) ?? [];
}

// Write to JSON file atomically
function write_json($file, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT);
    $tmp_file = $file . '.tmp';
    
    file_put_contents($tmp_file, $json);
    rename($tmp_file, $file);
}

// Escape HTML output
function escape_html($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Record analytics event
function record_analytics($event, $meta = []) {
    $analytics = read_json('analytics.json');
    $timestamp = date('c');
    
    switch ($event) {
        case 'page_view':
            $analytics['page_views'][] = [
                'page' => $meta['page'] ?? '/',
                'ts' => $timestamp
            ];
            break;
            
        case 'repo_view':
            $case_id = $meta['case_id'] ?? 'unknown';
            if (!isset($analytics['repo_views'][$case_id])) {
                $analytics['repo_views'][$case_id] = 0;
            }
            $analytics['repo_views'][$case_id]++;
            break;
            
        case 'assignment':
            $analytics['assignments'][] = [
                'ts' => $timestamp,
                'by' => $meta['by'] ?? 'admin'
            ];
            break;
            
        case 'grade_submitted':
            $analytics['grades_submitted'][] = [
                'student' => $meta['student'] ?? '',
                'ts' => $timestamp
            ];
            break;
    }
    
    write_json('analytics.json', $analytics);
}

// Add a new student
function add_student() {
    if (empty($_POST['student_name'])) {
        return;
    }
    
    $student_name = trim($_POST['student_name']);
    $students = read_json('students.json');
    
    if (!in_array($student_name, $students)) {
        $students[] = $student_name;
        write_json('students.json', $students);
    }
}

// Assign case studies to students
function assign_case_studies() {
    $students = read_json('students.json');
    $case_studies = read_json('data.json');
    $assignments = read_json('assignments.json');
    
    if (!empty($assignments['pairs'])) {
        return; // Already assigned
    }
    
    // Shuffle case studies and assign to students
    shuffle($case_studies);
    $pairs = [];
    
    foreach ($students as $i => $student) {
        if (isset($case_studies[$i])) {
            $pairs[] = [
                'student' => $student,
                'case_id' => $case_studies[$i]['id']
            ];
        }
    }
    
    $assignments = [
        'assigned_at' => date('c'),
        'pairs' => $pairs
    ];
    
    write_json('assignments.json', $assignments);
    record_analytics('assignment', ['by' => 'admin']);
}

// Submit a grade for a student
function submit_grade() {
    if (empty($_POST['student']) || empty($_POST['technical_accuracy']) || 
        empty($_POST['clarity']) || empty($_POST['answering']) || empty($_POST['understanding'])) {
        return;
    }
    
    $student = $_POST['student'];
    $technical = (int)$_POST['technical_accuracy'];
    $clarity = (int)$_POST['clarity'];
    $answering = (int)$_POST['answering'];
    $understanding = (int)$_POST['understanding'];
    
    // Validate scores are within range
    $scores = [$technical, $clarity, $answering, $understanding];
    foreach ($scores as $score) {
        if ($score < 40 || $score > 100) {
            return;
        }
    }
    
    $average = ($technical + $clarity + $answering + $understanding) / 4;
    
    $grades = read_json('grades.json');
    $grades[$student] = [
        'case_id' => get_assigned_case($student),
        'grades' => [
            'technical_accuracy' => $technical,
            'clarity' => $clarity,
            'answering' => $answering,
            'understanding' => $understanding
        ],
        'average' => round($average, 2)
    ];
    
    write_json('grades.json', $grades);
    record_analytics('grade_submitted', ['student' => $student]);
}

// Get the case study assigned to a student
function get_assigned_case($student) {
    $assignments = read_json('assignments.json');
    
    foreach ($assignments['pairs'] as $pair) {
        if ($pair['student'] === $student) {
            return $pair['case_id'];
        }
    }
    
    return 'Unknown';
}

// Get activity in the last 24 hours
function get_last_24h_activity($analytics) {
    $count = 0;
    $24h_ago = time() - 86400;
    
    // Count page views in last 24h
    foreach ($analytics['page_views'] as $view) {
        if (strtotime($view['ts']) >= $24h_ago) {
            $count++;
        }
    }
    
    // Count assignments in last 24h
    foreach ($analytics['assignments'] as $assignment) {
        if (strtotime($assignment['ts']) >= $24h_ago) {
            $count++;
        }
    }
    
    // Count grade submissions in last 24h
    foreach ($analytics['grades_submitted'] as $grade) {
        if (strtotime($grade['ts']) >= $24h_ago) {
            $count++;
        }
    }
    
    return $count;
}

// Get top 5 case studies by views
function get_top_case_studies($analytics) {
    $views = $analytics['repo_views'];
    arsort($views);
    return array_slice($views, 0, 5, true);
}

// Get color class for grade display
function get_grade_color($grade) {
    if ($grade >= 90) return 'grade-a';
    if ($grade >= 80) return 'grade-b';
    if ($grade >= 70) return 'grade-c';
    if ($grade >= 60) return 'grade-d';
    return 'grade-f';
}