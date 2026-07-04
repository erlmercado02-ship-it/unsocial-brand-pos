// Get current date
function updateDate() {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const today = new Date().toLocaleDateString('en-US', options);
    const timeElement = document.getElementById('current-date');
    if (timeElement) {
        timeElement.textContent = today;
    }
}

// Show specific section
function showSection(sectionId) {
    // Hide all sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });

    // Show selected section
    const selectedSection = document.getElementById(sectionId);
    if (selectedSection) {
        selectedSection.classList.add('active');
    }

    // Update active nav item
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.classList.remove('active');
    });
    
    event.target.closest('.nav-item')?.classList.add('active');
}

// Simple chart rendering (using canvas)
function createSalesChart() {
    const canvas = document.getElementById('sales-chart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const data = [2500, 3200, 2800, 4100, 3800, 4500, 5200];
    const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const barWidth = canvas.width / data.length;
    const maxValue = Math.max(...data);
    const scale = (canvas.height - 50) / maxValue;

    // Clear canvas
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Draw axes
    ctx.strokeStyle = '#e0e0e0';
    ctx.beginPath();
    ctx.moveTo(40, 20);
    ctx.lineTo(40, canvas.height - 40);
    ctx.lineTo(canvas.width, canvas.height - 40);
    ctx.stroke();

    // Draw bars
    ctx.fillStyle = '#ff6b6b';
    data.forEach((value, index) => {
        const barHeight = value * scale;
        const x = 50 + index * (barWidth - 10);
        const y = canvas.height - 40 - barHeight;
        ctx.fillRect(x, y, barWidth - 15, barHeight);
    });

    // Draw labels
    ctx.fillStyle = '#333';
    ctx.font = '12px Arial';
    ctx.textAlign = 'center';
    labels.forEach((label, index) => {
        const x = 50 + index * (barWidth - 10) + (barWidth - 15) / 2;
        ctx.fillText(label, x, canvas.height - 20);
    });
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    updateDate();
    createSalesChart();
    
    // Show dashboard section by default
    showSection('dashboard');
    
    // Update date every minute
    setInterval(updateDate, 60000);
});

// Handle navigation with arrow keys (accessibility)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        // Could implement close modal functionality
    }
});

// Log system info
console.log('Unsocial Brand POS System Dashboard loaded');
console.log('Location: San Antonio, Cavite City');