jQuery(document).ready(function($) {
    let currentPage = 1;
    let debounceTimer;

    // Function to load logs
    function loadLogs(page = 1) {
        const level = $('#log-level-filter').val();
        const source = $('#log-source-filter').val();
        const search = $('#log-search').val();

        $('#logs-content').html('<div class="loading">Loading logs...</div>');

        $.ajax({
            url: datolabAutoSEO.ajaxurl,
            type: 'GET',
            data: {
                action: 'datolab_get_logs',
                nonce: datolabAutoSEO.nonce,
                level: level,
                source: source,
                search: search,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    displayLogs(response.data);
                } else {
                    $('#logs-content').html('<div class="error">Error loading logs</div>');
                }
            },
            error: function() {
                $('#logs-content').html('<div class="error">Error loading logs</div>');
            }
        });
    }

    // Function to display logs
    function displayLogs(data) {
        const logsHtml = data.logs.map(function(log) {
            return `
                <div class="log-entry">
                    <span class="timestamp">${log.timestamp}</span>
                    <span class="level ${log.level}">${log.level}</span>
                    <span class="message">${escapeHtml(log.message)}</span>
                </div>
            `;
        }).join('');

        let paginationHtml = '';
        if (data.pagination.total_pages > 1) {
            paginationHtml = '<div class="pagination">';
            
            // Previous button
            if (data.pagination.current_page > 1) {
                paginationHtml += `<button data-page="${data.pagination.current_page - 1}">Previous</button>`;
            }

            // Page numbers
            for (let i = 1; i <= data.pagination.total_pages; i++) {
                if (
                    i === 1 || 
                    i === data.pagination.total_pages || 
                    (i >= data.pagination.current_page - 2 && i <= data.pagination.current_page + 2)
                ) {
                    paginationHtml += `<button data-page="${i}" ${i === data.pagination.current_page ? 'class="active"' : ''}>${i}</button>`;
                } else if (
                    i === data.pagination.current_page - 3 || 
                    i === data.pagination.current_page + 3
                ) {
                    paginationHtml += '<span>...</span>';
                }
            }

            // Next button
            if (data.pagination.current_page < data.pagination.total_pages) {
                paginationHtml += `<button data-page="${data.pagination.current_page + 1}">Next</button>`;
            }

            paginationHtml += '</div>';
        }

        $('#logs-content').html(logsHtml + paginationHtml);
    }

    // Helper function to escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Event handlers
    $('#refresh-logs').on('click', function() {
        loadLogs(currentPage);
    });

    $('#clear-logs').on('click', function() {
        if (!confirm('Are you sure you want to clear all logs?')) {
            return;
        }

        $.ajax({
            url: datolabAutoSEO.ajaxurl,
            type: 'POST',
            data: {
                action: 'datolab_clear_logs',
                nonce: datolabAutoSEO.nonce
            },
            success: function(response) {
                if (response.success) {
                    loadLogs(1);
                } else {
                    alert('Error clearing logs');
                }
            },
            error: function() {
                alert('Error clearing logs');
            }
        });
    });

    $('#download-logs').on('click', function() {
        const logs = $('#logs-content').text();
        const blob = new Blob([logs], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'datolab-auto-seo-logs.txt';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    });

    // Handle filter changes
    $('#log-level-filter, #log-source-filter').on('change', function() {
        currentPage = 1;
        loadLogs(currentPage);
    });

    // Handle search with debounce
    $('#log-search').on('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            currentPage = 1;
            loadLogs(currentPage);
        }, 500);
    });

    // Handle pagination clicks
    $(document).on('click', '.pagination button', function() {
        currentPage = parseInt($(this).data('page'));
        loadLogs(currentPage);
    });

    // Initial load
    loadLogs(1);
});
