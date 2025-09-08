// assets/script.js
document.addEventListener('DOMContentLoaded', function() {
    // Navigation
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links and sections
            navLinks.forEach(l => l.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Show corresponding section
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).classList.add('active');
        });
    });
    
    // Grade form validation
    const gradeForm = document.getElementById('grading-form');
    if (gradeForm) {
        gradeForm.addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input[type="number"]');
            let valid = true;
            
            inputs.forEach(input => {
                const value = parseInt(input.value);
                if (isNaN(value) || value < 40 || value > 100) {
                    valid = false;
                    input.style.borderColor = '#f85149';
                } else {
                    input.style.borderColor = '';
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('All grades must be between 40 and 100.');
            }
        });
    }
});