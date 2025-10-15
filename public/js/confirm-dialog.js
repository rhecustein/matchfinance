// ============================================
// Enhanced Confirm Dialog Component
// ============================================

document.addEventListener('alpine:init', () => {
    Alpine.data('confirmDialog', () => ({
        show: false,
        title: '',
        message: '',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        type: 'warning', // warning, danger, success, info
        confirmCallback: null,
        
        open(options) {
            this.title = options.title || 'Are you sure?';
            this.message = options.message || '';
            this.confirmText = options.confirmText || 'Confirm';
            this.cancelText = options.cancelText || 'Cancel';
            this.type = options.type || 'warning';
            this.confirmCallback = options.onConfirm || null;
            this.show = true;
        },
        
        close() {
            this.show = false;
        },
        
        confirm() {
            if (this.confirmCallback) {
                this.confirmCallback();
            }
            this.close();
        },
        
        getIconClass() {
            const icons = {
                warning: 'fas fa-exclamation-triangle text-yellow-400',
                danger: 'fas fa-exclamation-circle text-red-400',
                success: 'fas fa-check-circle text-green-400',
                info: 'fas fa-info-circle text-blue-400'
            };
            return icons[this.type] || icons.warning;
        },
        
        getConfirmButtonClass() {
            const classes = {
                warning: 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500',
                danger: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
                success: 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
                info: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'
            };
            return classes[this.type] || classes.warning;
        }
    }));
});

// ============================================
// Global Confirm Function
// ============================================

window.showConfirm = function(options) {
    const event = new CustomEvent('open-confirm', { detail: options });
    window.dispatchEvent(event);
};

// ============================================
// Form Confirmation Helper
// ============================================

function setupFormConfirmations() {
    // Rematch All
    document.querySelectorAll('[data-confirm-rematch]')?.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            showConfirm({
                title: 'Reset All Categorization?',
                message: 'This will clear all existing category and account matches. All transactions will be rematched using current keywords. This action cannot be undone.',
                confirmText: 'Yes, Rematch All',
                cancelText: 'Cancel',
                type: 'danger',
                onConfirm: () => this.submit()
            });
        });
    });
    
    // Verify All Matched
    document.querySelectorAll('[data-confirm-verify-matched]')?.forEach(form => {
        const count = form.dataset.count || '0';
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            showConfirm({
                title: 'Verify All Categorized Transactions?',
                message: `This will mark ${count} categorized transactions as verified. You can unverify them later if needed.`,
                confirmText: `Yes, Verify ${count} Transactions`,
                cancelText: 'Cancel',
                type: 'success',
                onConfirm: () => this.submit()
            });
        });
    });
    
    // Verify High Confidence
    document.querySelectorAll('[data-confirm-verify-confidence]')?.forEach(form => {
        const count = form.dataset.count || '0';
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            showConfirm({
                title: 'Verify High Confidence Transactions?',
                message: `This will mark ${count} transactions with confidence ≥80% as verified. These are likely to be accurate matches.`,
                confirmText: `Yes, Verify ${count} Transactions`,
                cancelText: 'Cancel',
                type: 'success',
                onConfirm: () => this.submit()
            });
        });
    });
    
    // Unreconcile
    document.querySelectorAll('[data-confirm-unreconcile]')?.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            showConfirm({
                title: 'Unreconcile This Statement?',
                message: 'This will mark the statement as not reconciled. You can reconcile it again later.',
                confirmText: 'Yes, Unreconcile',
                cancelText: 'Cancel',
                type: 'warning',
                onConfirm: () => this.submit()
            });
        });
    });
    
    // Delete
    document.querySelectorAll('[data-confirm-delete]')?.forEach(form => {
        const itemName = form.dataset.itemName || 'item';
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            showConfirm({
                title: 'Delete This Item?',
                message: `Are you sure you want to delete this ${itemName}? This action cannot be undone.`,
                confirmText: 'Yes, Delete',
                cancelText: 'Cancel',
                type: 'danger',
                onConfirm: () => this.submit()
            });
        });
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', setupFormConfirmations);

// Reinitialize on Livewire/AJAX updates
document.addEventListener('livewire:load', setupFormConfirmations);