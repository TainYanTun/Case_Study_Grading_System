# Case Study Grading System

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

This is a **PHP-based Case Study Grading System** for tracking presentation grades. The system provides a web interface to enter, track, and analyze grades for up to 25 case study presentations.

This is a simple JSON-based system with comprehensive analytics dashboard that allows users to manage case studies with basic CRUD operations and view performance statistics.

## Common Development Commands

### Running the Application

```bash
# Start a local PHP development server from the project root
php -S localhost:8000

# Access the application at http://localhost:8000
```

### Testing and Validation

```bash
# Check PHP syntax for all files
find . -name "*.php" -exec php -l {} \;

# Validate JSON files
find . -name "*.json" -exec python -m json.tool {} \; > /dev/null

# Check file permissions (data files need write access)
ls -la grades.json
```

### Data Management

```bash
# View current grades data
cat grades.json | jq '.'

# Backup data files
cp grades.json grades_backup_$(date +%Y%m%d).json

# Reset all data (removes all case studies)
echo '{"case_studies": []}' > grades.json
```

## Architecture Overview

### System Architecture
- **Single-file application**: Main functionality in `index.php` and `summary.php`
- **Data storage**: Simple `grades.json` file with case study entries
- **Features**: Basic CRUD operations, analytics dashboard, export functionality
- **Client-side**: Embedded CSS/JS with Chart.js for visualizations
- **Security**: Input validation and HTML escaping for XSS protection

### Key Data Models

**Case Study:**
```json
{
    "id": 1,
    "title": "Case Study Title",
    "student": "Student Name",
    "grade": 85
}
```

**Complete grades.json structure:**
```json
{
    "case_studies": [
        {
            "id": 1,
            "title": "Marketing Strategy Analysis",
            "student": "John Smith",
            "grade": 85
        }
    ]
}
```

## Development Guidelines

### File Structure Patterns
- Uses JSON for data persistence (no database required)
- Single JSON file approach with `grades.json`
- All PHP files are self-contained with embedded CSS/JavaScript

### Data Handling
- JSON files require write permissions for the web server
- Always validate input data before persistence
- Implement proper HTML escaping to prevent XSS attacks
- Use atomic write operations when possible

### UI/UX Patterns
- Responsive design with GitHub-like styling
- Dark/light theme toggle functionality with localStorage persistence
- Chart.js integration for data visualization
- Form validation on both client and server side

## Application Features

### Main Application (index.php)
- **Entry point**: `index.php` (form input and dashboard)
- **Data validation**: ID must be 1-25, grade 0-100
- **CRUD operations**: Create, read, update case studies
- **Real-time statistics**: Total cases, class average display

### Analytics Dashboard (summary.php)
- **Comprehensive reporting**: Grade statistics, performance trends
- **Data visualization**: Charts and graphs using Chart.js
- **Export functionality**: JSON and CSV export capabilities
- **Performance analysis**: Grade distribution and student performance metrics

## Local Development Setup

1. Ensure PHP is installed (7.4+ recommended)
2. Clone the repository and navigate to desired version
3. Ensure write permissions on data directories
4. Start PHP development server
5. Access the application in a web browser

No additional dependencies or build steps are required - the system is designed to run with vanilla PHP.

## Common Issues and Solutions

**JSON file permission errors:**
```bash
chmod 664 Version_A/grades.json
chmod -R 664 Version_B/data/
```

**PHP version compatibility:**
- Code uses modern PHP syntax (null coalescing `??`, array functions)
- Minimum PHP 7.0 required, 7.4+ recommended

**Data corruption:**
- Always backup JSON files before major operations
- Use the atomic write functions provided in `lib.php` for Version B
- Validate JSON structure after manual edits
