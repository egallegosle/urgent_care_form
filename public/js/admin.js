/**
 * Admin Dashboard JavaScript
 * Handles interactivity, charts, and AJAX operations
 */

// Utility Functions
const AdminUtils = {
    /**
     * Format date to readable string
     */
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    /**
     * Format date and time
     */
    formatDateTime: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    /**
     * Format phone number
     */
    formatPhone: function(phone) {
        const cleaned = ('' + phone).replace(/\D/g, '');
        const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/);
        if (match) {
            return '(' + match[1] + ') ' + match[2] + '-' + match[3];
        }
        return phone;
    },

    /**
     * Show loading spinner
     */
    showLoading: function(element) {
        element.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
    },

    /**
     * Show alert message
     */
    showAlert: function(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
            <i class="fas fa-${this.getAlertIcon(type)}"></i>
            ${message}
        `;

        const container = document.querySelector('.admin-container');
        container.insertBefore(alertDiv, container.firstChild);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.style.opacity = '0';
            setTimeout(() => alertDiv.remove(), 300);
        }, 5000);
    },

    getAlertIcon: function(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    },

    /**
     * Confirm action
     */
    confirm: function(message, callback) {
        if (window.confirm(message)) {
            callback();
        }
    },

    /**
     * Debounce function for search
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
};

// Chart Utilities
const ChartUtils = {
    colors: {
        primary: '#0066cc',
        success: '#28a745',
        warning: '#ffc107',
        danger: '#dc3545',
        info: '#17a2b8',
        secondary: '#6c757d'
    },

    /**
     * Create line chart
     */
    createLineChart: function(canvasId, labels, datasets) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets.map(ds => ({
                    ...ds,
                    borderWidth: 2,
                    tension: 0.4,
                    fill: false
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    },

    /**
     * Create bar chart
     */
    createBarChart: function(canvasId, labels, data, label = 'Data') {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    backgroundColor: this.colors.primary,
                    borderColor: this.colors.primary,
                    borderWidth: 1
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
                        beginAtZero: true
                    }
                }
            }
        });
    },

    /**
     * Create pie chart
     */
    createPieChart: function(canvasId, labels, data) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        this.colors.primary,
                        this.colors.success,
                        this.colors.warning,
                        this.colors.danger,
                        this.colors.info,
                        this.colors.secondary
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    },

    /**
     * Create doughnut chart
     */
    createDoughnutChart: function(canvasId, labels, data) {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        this.colors.primary,
                        this.colors.success,
                        this.colors.warning,
                        this.colors.danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
};

// Table Utilities
const TableUtils = {
    /**
     * Make table sortable
     */
    makeSortable: function(tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;

        const headers = table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.innerHTML += ' <i class="fas fa-sort"></i>';

            header.addEventListener('click', () => {
                this.sortTable(table, Array.from(headers).indexOf(header));
            });
        });
    },

    sortTable: function(table, columnIndex) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        const sortedRows = rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();

            // Try numeric comparison
            const aNum = parseFloat(aValue);
            const bNum = parseFloat(bValue);

            if (!isNaN(aNum) && !isNaN(bNum)) {
                return aNum - bNum;
            }

            // String comparison
            return aValue.localeCompare(bValue);
        });

        // Clear and re-append sorted rows
        tbody.innerHTML = '';
        sortedRows.forEach(row => tbody.appendChild(row));
    },

    /**
     * Add search functionality to table
     */
    addSearch: function(searchInputId, tableId) {
        const searchInput = document.getElementById(searchInputId);
        const table = document.getElementById(tableId);

        if (!searchInput || !table) return;

        searchInput.addEventListener('input', AdminUtils.debounce((e) => {
            const searchTerm = e.target.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }, 300));
    }
};

// Export Utilities
const ExportUtils = {
    /**
     * Export table to CSV
     */
    exportTableToCSV: function(tableId, filename = 'export.csv') {
        const table = document.getElementById(tableId);
        if (!table) return;

        let csv = [];

        // Get headers
        const headers = Array.from(table.querySelectorAll('thead th'))
            .map(th => th.textContent.trim());
        csv.push(headers.join(','));

        // Get rows
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cells = Array.from(row.querySelectorAll('td'))
                    .map(td => {
                        const text = td.textContent.trim();
                        // Escape quotes and wrap in quotes if contains comma
                        return text.includes(',') ? `"${text.replace(/"/g, '""')}"` : text;
                    });
                csv.push(cells.join(','));
            }
        });

        // Download
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    },

    /**
     * Print current page
     */
    printPage: function() {
        window.print();
    }
};

// Status Update Functions
const StatusManager = {
    /**
     * Update patient status
     */
    updatePatientStatus: function(patientId, newStatus, callback) {
        fetch('/admin/patients/update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                patient_id: patientId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                AdminUtils.showAlert('Status updated successfully', 'success');
                if (callback) callback(data);
            } else {
                AdminUtils.showAlert('Failed to update status: ' + data.message, 'error');
            }
        })
        .catch(error => {
            AdminUtils.showAlert('Error updating status', 'error');
            console.error('Error:', error);
        });
    }
};

// Auto-refresh for dashboard
let autoRefreshInterval = null;

function startAutoRefresh(intervalSeconds = 30) {
    stopAutoRefresh();
    autoRefreshInterval = setInterval(() => {
        location.reload();
    }, intervalSeconds * 1000);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add data-table class functionality
    document.querySelectorAll('.data-table').forEach(table => {
        // Add hover effect
        table.querySelectorAll('tbody tr').forEach(row => {
            row.style.cursor = 'pointer';
        });
    });

    // Initialize tooltips if any
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.title = element.getAttribute('data-tooltip');
    });

    // Close alerts on click
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.cursor = 'pointer';
        alert.addEventListener('click', () => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    });
});

// Export to global scope
window.AdminUtils = AdminUtils;
window.ChartUtils = ChartUtils;
window.TableUtils = TableUtils;
window.ExportUtils = ExportUtils;
window.StatusManager = StatusManager;
window.startAutoRefresh = startAutoRefresh;
window.stopAutoRefresh = stopAutoRefresh;
