// Admin Panel JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    
    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', function() {
            adminSidebar.classList.toggle('active');
        });
    }

    // Alert auto-dismiss
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 3000);
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                showAlert('Please fill in all required fields', 'error');
            }
        });
    });

    // Image preview
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                const preview = document.querySelector(`#${input.id}-preview`);
                
                if (preview) {
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                }
            }
        });
    });

    // Initialize order management
    initializeOrderManagement();
});

// Utility Functions
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
        ${message}
    `;
    
    document.body.insertBefore(alertDiv, document.body.firstChild);
    
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}

function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Chart initialization (if Chart.js is included)
function initializeCharts() {
    if (typeof Chart === 'undefined') return;

    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: '#4f46e5',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    }

    // Products Chart
    const productsCtx = document.getElementById('productsChart');
    if (productsCtx) {
        new Chart(productsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Electronics', 'Clothing', 'Accessories'],
                datasets: [{
                    data: [12, 19, 3],
                    backgroundColor: [
                        '#4f46e5',
                        '#10b981',
                        '#f59e0b'
                    ]
                }]
            }
        });
    }
}

// Table sorting
function sortTable(table, column, type = 'string') {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const direction = table.dataset.sortDirection === 'asc' ? -1 : 1;

    rows.sort((a, b) => {
        let aValue = a.cells[column].textContent.trim();
        let bValue = b.cells[column].textContent.trim();

        if (type === 'number') {
            aValue = parseFloat(aValue.replace(/[^0-9.-]+/g, ''));
            bValue = parseFloat(bValue.replace(/[^0-9.-]+/g, ''));
        }

        if (aValue < bValue) return -1 * direction;
        if (aValue > bValue) return 1 * direction;
        return 0;
    });

    rows.forEach(row => tbody.appendChild(row));
    table.dataset.sortDirection = direction === 1 ? 'asc' : 'desc';
}

// Data filtering
function filterTable(input, table) {
    const term = input.value.toLowerCase();
    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
}

// Export data to CSV
function exportToCSV(table, filename) {
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td, th');
        const rowData = Array.from(cells).map(cell => {
            return `"${cell.textContent.trim()}"`;
        });
        csv.push(rowData.join(','));
    });

    const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `${filename}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Order Management Functions
function initializeOrderManagement() {
    const modal = document.getElementById('orderDetailsModal');
    if (!modal) return;

    const closeBtn = modal.querySelector('.close-modal');
    if (closeBtn) {
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Initialize status update handlers
    const statusForms = document.querySelectorAll('.status-update-form');
    statusForms.forEach(form => {
        const select = form.querySelector('.status-select');
        if (select) {
            // Store initial value
            select.setAttribute('data-current', select.value);
            
            select.onchange = function() {
                const newStatus = this.value;
                const currentStatus = this.getAttribute('data-current');
                
                if (confirm(`Are you sure you want to change the order status from ${currentStatus} to ${newStatus}?`)) {
                    this.setAttribute('data-current', newStatus);
                    form.submit();
                } else {
                    this.value = currentStatus; // Reset to previous value if cancelled
                }
            }
        }
    });
}

function viewOrderDetails(orderData) {
    const order = typeof orderData === 'string' ? JSON.parse(orderData) : orderData;
    const modal = document.getElementById('orderDetailsModal');
    
    if (!modal) return;
    
    const modalOrderId = document.getElementById('modalOrderId');
    const modalOrderDate = document.getElementById('modalOrderDate');
    const modalCustomerName = document.getElementById('modalCustomerName');
    const modalCustomerEmail = document.getElementById('modalCustomerEmail');
    const modalOrderItems = document.getElementById('modalOrderItems');
    
    if (modalOrderId) modalOrderId.textContent = String(order.id).padStart(6, '0');
    if (modalOrderDate) modalOrderDate.textContent = new Date(order.order_date).toLocaleString();
    if (modalCustomerName) modalCustomerName.textContent = `${order.first_name} ${order.last_name}`;
    if (modalCustomerEmail) modalCustomerEmail.textContent = order.email;
    
    if (modalOrderItems) {
        const items = order.order_items ? order.order_items.split(', ') : [];
        modalOrderItems.innerHTML = items.map(item => `
            <div class="order-item">
                <span class="order-item-details">${item}</span>
            </div>
        `).join('');
    }
    
    modal.style.display = 'block';
}

// Initialize all interactive elements
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if available
    initializeCharts();

    // Initialize sortable tables
    document.querySelectorAll('.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const table = this.closest('table');
            const columnIndex = Array.from(this.parentElement.children).indexOf(this);
            const type = this.dataset.type || 'string';
            sortTable(table, columnIndex, type);
        });
    });

    // Initialize filters
    document.querySelectorAll('.table-filter').forEach(input => {
        input.addEventListener('input', function() {
            const table = document.querySelector(this.dataset.table);
            filterTable(this, table);
        });
    });
});
