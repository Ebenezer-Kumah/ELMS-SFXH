// script.js - Employee Leave Management System JavaScript


import { createClient } from '@supabase/supabase-js'

const supabaseUrl = 'https://cdrdtizfmehslttsbjtc.supabase.co'
const supabaseKey = process.env.SUPABASE_KEY
const supabase = createClient(supabaseUrl, supabaseKey)

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar functionality


    
    const sidebar = document.getElementById('sidebar');
    const mainContainer = document.getElementById('mainContainer');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const menuToggle = document.getElementById('menuToggle');
    
    // Check if sidebar state is saved in localStorage
    const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    // Set initial state
    if (isSidebarCollapsed && sidebar && mainContainer) {
        sidebar.classList.add('collapsed');
        mainContainer.classList.add('sidebar-collapsed');
    }
    
    // Toggle sidebar collapse/expand
    if (sidebarToggle && sidebar && mainContainer) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContainer.classList.toggle('sidebar-collapsed');
            
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }
    
    // Mobile menu toggle
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show-mobile');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth < 992 && 
            sidebar && 
            sidebar.classList.contains('show-mobile') && 
            !sidebar.contains(event.target) && 
            event.target !== menuToggle) {
            sidebar.classList.remove('show-mobile');
        }
    });
    
    // Auto-close sidebar on mobile after navigation
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992 && sidebar) {
                sidebar.classList.remove('show-mobile');
            }
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992 && sidebar) {
            sidebar.classList.remove('show-mobile');
        }
    });
    
    // Date validation for forms
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        input.min = today;
        
        // Add change event to update dependent date fields
        if (input.id === 'start_date') {
            input.addEventListener('change', function() {
                const endDate = document.getElementById('end_date');
                if (endDate) {
                    endDate.min = this.value;
                    if (endDate.value && endDate.value < this.value) {
                        endDate.value = this.value;
                    }
                }
            });
        }
    });
    
    // Confirm before deleting or performing critical actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this?')) {
                e.preventDefault();
            }
        });
    });
    
    // Toggle functionality for filters or additional options
    const toggleButtons = document.querySelectorAll('.toggle-btn');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-target');
            const element = document.getElementById(target);
            if (element) {
                element.classList.toggle('hidden');
            }
        });
    });
    
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to current button and content
            this.classList.add('active');
            const targetContent = document.getElementById(tabId);
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
    
    // Modal functionality
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        const closeBtn = modal.querySelector('.close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Edit buttons for modals
    const editButtons = document.querySelectorAll('.btn-edit');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetModal = this.getAttribute('data-modal');
            const modal = document.getElementById(targetModal);
            if (modal) {
                // Fill modal with data from the button's data attributes
                const dataAttributes = this.dataset;
                for (const key in dataAttributes) {
                    const input = modal.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'checkbox') {
                            input.checked = dataAttributes[key] === 'true';
                        } else {
                            input.value = dataAttributes[key];
                        }
                    }
                }
                modal.style.display = 'block';
            }
        });
    });
    
    // Form validation for date ranges
    const dateRangeForms = document.querySelectorAll('form');
    dateRangeForms.forEach(form => {
        const startDateInput = form.querySelector('input[name="start_date"]');
        const endDateInput = form.querySelector('input[name="end_date"]');
        
        if (startDateInput && endDateInput) {
            form.addEventListener('submit', function(e) {
                if (startDateInput.value && endDateInput.value) {
                    if (new Date(startDateInput.value) > new Date(endDateInput.value)) {
                        e.preventDefault();
                        alert('End date must be after start date');
                    }
                }
            });
        }
    });
    
    // Notification functionality
    const notificationBell = document.querySelector('.notification-bell');
    
    if (notificationBell) {
        notificationBell.addEventListener('click', function() {
            // This would typically show a notification dropdown
            alert('Notifications feature would be implemented here. In a real application, this would show pending leave requests that need approval.');
        });
        
        // Simulate fetching notification count (in a real app, this would be an AJAX call)
        setTimeout(() => {
            // Random notification count for demo purposes
            const notificationCount = Math.floor(Math.random() * 5);
            const countElement = document.querySelector('.notification-count');
            
            if (countElement) {
                if (notificationCount > 0) {
                    countElement.textContent = notificationCount;
                    countElement.style.display = 'flex';
                } else {
                    countElement.style.display = 'none';
                }
            }
        }, 1000);
    }
    
    // Chart initialization
    if (typeof Chart !== 'undefined') {
        const chartCanvases = document.querySelectorAll('canvas');
        chartCanvases.forEach(canvas => {
            const ctx = canvas.getContext('2d');
            const chartType = canvas.getAttribute('data-chart-type') || 'bar';
            const chartData = JSON.parse(canvas.getAttribute('data-chart-data') || '{}');
            
            if (Object.keys(chartData).length > 0) {
                new Chart(ctx, {
                    type: chartType,
                    data: chartData,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        });
    }
    
    // Export functionality
    const exportButtons = document.querySelectorAll('.btn-export');
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const format = this.getAttribute('data-format') || 'csv';
            alert(`Export functionality would be implemented here. This would export data in ${format.toUpperCase()} format.`);
        });
    });
    
    // Print functionality
    const printButtons = document.querySelectorAll('.btn-print');
    printButtons.forEach(button => {
        button.addEventListener('click', function() {
            window.print();
        });
    });
    
    // Auto-calculate leave days when dates change
    const startDateFields = document.querySelectorAll('input[name="start_date"]');
    const endDateFields = document.querySelectorAll('input[name="end_date"]');
    
    function calculateAndDisplayDays() {
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const daysDisplay = document.getElementById('calculated_days');
        
        if (startDate && endDate && daysDisplay) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            
            if (start && end && start <= end) {
                const days = calculateWeekdays(startDate.value, endDate.value);
                daysDisplay.textContent = days;
            } else {
                daysDisplay.textContent = '0';
            }
        }
    }
    
    if (startDateFields.length > 0 && endDateFields.length > 0) {
        startDateFields.forEach(input => {
            input.addEventListener('change', calculateAndDisplayDays);
        });
        
        endDateFields.forEach(input => {
            input.addEventListener('change', calculateAndDisplayDays);
        });
    }
});

// Function to calculate leave days excluding weekends
function calculateWeekdays(startDate, endDate) {
    if (!startDate || !endDate) return 0;
    
    const start = new Date(startDate);
    const end = new Date(endDate);
    let count = 0;
    
    // Validate dates
    if (start > end) return 0;
    
    // Create a copy of start date to avoid modifying the original
    const current = new Date(start);
    
    // Loop through each day
    while (current <= end) {
        const dayOfWeek = current.getDay();
        // Skip weekends (0 = Sunday, 6 = Saturday)
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            count++;
        }
        current.setDate(current.getDate() + 1);
    }
    
    return count;
}

// Form validation for leave application
function validateLeaveForm() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const reason = document.getElementById('reason');
    const leaveType = document.getElementById('leave_type');
    
    if (!startDate || !endDate || !reason || !leaveType) {
        alert('Please fill in all required fields');
        return false;
    }
    
    if (!startDate.value || !endDate.value || !reason.value || !leaveType.value) {
        alert('Please fill in all required fields');
        return false;
    }
    
    if (new Date(startDate.value) > new Date(endDate.value)) {
        alert('End date must be after start date');
        return false;
    }
    
    const weekdays = calculateWeekdays(startDate.value, endDate.value);
    const balanceElement = document.querySelector('.leave-balance strong');
    
    if (balanceElement) {
        const balance = parseInt(balanceElement.textContent);
        
        if (weekdays > balance) {
            alert(`You are requesting ${weekdays} days but only have ${balance} days available`);
            return false;
        }
    }
    
    return true;
}

// Function to filter table data
function filterTable(tableId, searchId) {
    const table = document.getElementById(tableId);
    const search = document.getElementById(searchId);
    
    if (!table || !search) return;
    
    const filter = search.value.toLowerCase();
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
}

// Function to sort table by column
function sortTable(tableId, columnIndex, isNumeric = false) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        if (isNumeric) {
            return parseFloat(aValue) - parseFloat(bValue);
        } else {
            return aValue.localeCompare(bValue);
        }
    });
    
    // Remove existing rows
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }
    
    // Add sorted rows
    rows.forEach(row => {
        tbody.appendChild(row);
    });
}

// Function to show/hide elements
function toggleElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.toggle('hidden');
    }
}

// Function to show notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    
    // Add styles if not already added
    if (!document.querySelector('#notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 4px;
                color: white;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: space-between;
                min-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            .notification-info { background: #3498db; }
            .notification-success { background: #27ae60; }
            .notification-warning { background: #f39c12; }
            .notification-error { background: #e74c3c; }
            .notification button {
                background: none;
                border: none;
                color: white;
                font-size: 20px;
                cursor: pointer;
                margin-left: 15px;
            }
        `;
        document.head.appendChild(styles);
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Function to update leave balance display
function updateLeaveBalanceDisplay() {
    const balanceElement = document.querySelector('.leave-balance strong');
    if (balanceElement) {
        // This would typically make an AJAX request to get the latest balance
        console.log('Leave balance display would be updated here');
    }
}

// Initialize tooltips
function initTooltips() {
    const elements = document.querySelectorAll('[data-tooltip]');
    elements.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
            
            this.addEventListener('mouseleave', function() {
                tooltip.remove();
            });
        });
    });
}

// Call initialization functions when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
    updateLeaveBalanceDisplay();
});