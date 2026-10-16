// ===========================
// UIU CANTEEN - UTILITIES
// ===========================

/**
 * Modal Management
 */
const Modal = {
    open(selector) {
        const overlay = document.querySelector(selector);
        if (overlay) {
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },
    
    close(selector) {
        const overlay = document.querySelector(selector);
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    },
    
    closeAll() {
        document.querySelectorAll('.modal-overlay.active').forEach(overlay => {
            overlay.classList.remove('active');
        });
        document.body.style.overflow = 'auto';
    }
};

/**
 * Alert/Toast Management
 */
const Alert = {
    show(message, type = 'info', duration = 4000) {
        const container = document.querySelector('.alerts-container') || this._createContainer();
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <span class="alert-icon">${this._getIcon(type)}</span>
            <div class="alert-content">
                <p>${message}</p>
            </div>
            <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        container.appendChild(alert);
        
        if (duration > 0) {
            setTimeout(() => alert.remove(), duration);
        }
    },
    
    success(message, duration = 4000) {
        this.show(message, 'success', duration);
    },
    
    error(message, duration = 5000) {
        this.show(message, 'danger', duration);
    },
    
    warning(message, duration = 4000) {
        this.show(message, 'warning', duration);
    },
    
    info(message, duration = 4000) {
        this.show(message, 'info', duration);
    },
    
    _getIcon(type) {
        const icons = {
            success: '✓',
            danger: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        return icons[type] || 'ℹ';
    },
    
    _createContainer() {
        const container = document.createElement('div');
        container.className = 'alerts-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            max-width: 400px;
        `;
        document.body.appendChild(container);
        return container;
    }
};

/**
 * Confirmation Dialog
 */
function showConfirmation(message, onConfirm, onCancel = null) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay active';
    overlay.innerHTML = `
        <div class="modal confirmation-modal">
            <div class="modal-header">
                <h2>Confirm Action</h2>
                <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">&times;</button>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()">Cancel</button>
                <button class="btn btn-danger" onclick="this.closest('.modal-overlay').remove(); (${onConfirm.toString().slice(13, -1)}())">Confirm</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.remove();
            if (onCancel) onCancel();
        }
    });
}

/**
 * Form Utilities
 */
const Form = {
    /**
     * Clear all form errors
     */
    clearErrors(formSelector) {
        const form = document.querySelector(formSelector);
        if (form) {
            form.querySelectorAll('.form-error').forEach(el => el.remove());
            form.querySelectorAll('input, textarea, select').forEach(el => {
                el.style.borderColor = '';
            });
        }
    },
    
    /**
     * Show field error
     */
    showError(fieldName, message, formSelector = null) {
        const field = formSelector ? 
            document.querySelector(formSelector + ` [name="${fieldName}"]`) :
            document.querySelector(`[name="${fieldName}"]`);
        
        if (field) {
            field.style.borderColor = 'var(--error)';
            const error = document.createElement('div');
            error.className = 'form-error';
            error.textContent = message;
            field.parentElement.appendChild(error);
        }
    },
    
    /**
     * Validate email
     */
    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    /**
     * Validate required fields
     */
    validateRequired(fields) {
        const errors = {};
        fields.forEach(field => {
            const value = typeof field === 'string' ? 
                document.querySelector(`[name="${field}"]`)?.value :
                field.value;
            if (!value || value.trim() === '') {
                errors[field.name || field] = 'This field is required';
            }
        });
        return Object.keys(errors).length === 0 ? null : errors;
    }
};

/**
 * Table Utilities
 */
const Table = {
    /**
     * Make table sortable
     */
    makeSortable(tableSelector) {
        const table = document.querySelector(tableSelector);
        if (!table) return;
        
        const headers = table.querySelectorAll('th');
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                
                const isAscending = header.classList.toggle('sort-asc');
                header.classList.toggle('sort-desc', !isAscending);
                
                // Remove sort classes from other headers
                headers.forEach((h, i) => {
                    if (i !== index) {
                        h.classList.remove('sort-asc', 'sort-desc');
                    }
                });
                
                rows.sort((a, b) => {
                    const aValue = a.children[index].textContent.trim();
                    const bValue = b.children[index].textContent.trim();
                    
                    const aNum = parseFloat(aValue);
                    const bNum = parseFloat(bValue);
                    
                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return isAscending ? aNum - bNum : bNum - aNum;
                    }
                    
                    return isAscending ? 
                        aValue.localeCompare(bValue) : 
                        bValue.localeCompare(aValue);
                });
                
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    },
    
    /**
     * Filter table rows
     */
    filterTable(tableSelector, searchTerm, columnIndex = 0) {
        const table = document.querySelector(tableSelector);
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cell = row.children[columnIndex];
            if (cell) {
                const text = cell.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm.toLowerCase()) ? '' : 'none';
            }
        });
    }
};

/**
 * Loader Utilities
 */
const Loader = {
    show(message = 'Loading...') {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.id = 'loader-overlay';
        overlay.innerHTML = `
            <div style="text-align: center;">
                <div class="loading-spinner"></div>
                <p style="margin-top: 16px; color: var(--text);">${message}</p>
            </div>
        `;
        document.body.appendChild(overlay);
    },
    
    hide() {
        const loader = document.getElementById('loader-overlay');
        if (loader) loader.remove();
    }
};

/**
 * Cookie Utilities
 */
const Cookie = {
    set(name, value, days = 7) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        document.cookie = name + "=" + encodeURIComponent(value) + ";" + expires + ";path=/";
    },
    
    get(name) {
        const nameEQ = name + "=";
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            cookie = cookie.trim();
            if (cookie.indexOf(nameEQ) === 0) {
                return decodeURIComponent(cookie.substring(nameEQ.length));
            }
        }
        return null;
    },
    
    remove(name) {
        this.set(name, "", -1);
    }
};

/**
 * Local Storage Utilities
 */
const Storage = {
    set(key, value) {
        localStorage.setItem(key, JSON.stringify(value));
    },
    
    get(key) {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : null;
    },
    
    remove(key) {
        localStorage.removeItem(key);
    },
    
    clear() {
        localStorage.clear();
    }
};

/**
 * Format Utilities
 */
const Format = {
    /**
     * Format price
     */
    price(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },
    
    /**
     * Format date
     */
    date(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },
    
    /**
     * Format time
     */
    time(dateString) {
        return new Date(dateString).toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    /**
     * Format date and time
     */
    dateTime(dateString) {
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    /**
     * Capitalize string
     */
    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    },
    
    /**
     * Truncate string
     */
    truncate(str, length = 50) {
        return str.length > length ? str.substring(0, length) + '...' : str;
    }
};

/**
 * Network Utilities
 */
const API = {
    /**
     * Fetch with error handling
     */
    async fetch(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },
    
    get(url) {
        return this.fetch(url, { method: 'GET' });
    },
    
    post(url, data) {
        return this.fetch(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },
    
    put(url, data) {
        return this.fetch(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },
    
    delete(url) {
        return this.fetch(url, { method: 'DELETE' });
    }
};

/**
 * Initialize on page load
 */
document.addEventListener('DOMContentLoaded', function () {
    console.log('UIU Canteen - Utilities loaded');
    
    // Close modals on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            Modal.closeAll();
        }
    });
    
    // Close modal when clicking overlay
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // Set active nav item
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-item, .sidebar-link').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
        }
    });
});
