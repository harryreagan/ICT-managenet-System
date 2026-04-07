/**
 * Live Search Handler
 * Automatically submits search forms as user types with debouncing
 */

class LiveSearch {
    constructor(inputSelector, formSelector, debounceMs = 300) {
        this.input = document.querySelector(inputSelector);
        this.form = document.querySelector(formSelector);
        this.debounceMs = debounceMs;
        this.debounceTimer = null;

        if (this.input && this.form) {
            this.init();
        }
    }

    init() {
        this.input.addEventListener('input', (e) => {
            clearTimeout(this.debounceTimer);

            this.debounceTimer = setTimeout(() => {
                this.form.submit();
            }, this.debounceMs);
        });

        // Clear search on ESC key
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.input.value = '';
                this.form.submit();
            }
        });
    }
}

// Auto-initialize for common search patterns
document.addEventListener('DOMContentLoaded', function () {
    // Look for search forms with data-live-search attribute
    const liveForms = document.querySelectorAll('form[data-live-search]');

    liveForms.forEach(form => {
        const searchInput = form.querySelector('input[name="search"]');
        if (searchInput) {
            const debounce = form.dataset.liveSearchDebounce || 300;
            new LiveSearch(
                `#${searchInput.id || 'input[name="search"]'}`,
                `#${form.id || 'form[data-live-search]'}`,
                parseInt(debounce)
            );
        }
    });
});
