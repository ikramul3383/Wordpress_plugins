jQuery(document).ready(function($) {
    var debounceTimer;

    function add_loader() {
        // Dynamically add the loader to the body
        $('body').append(`
            <div class="ajax-loader">
                <div class="loader"></div>
            </div>
        `);
    }

    function remove_loader() {
        // Remove the loader from the DOM
        $('.ajax-loader').remove();
    }

    function ajaxRequest(action, data, onSuccess) {
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: Object.assign({ action: action }, data),
            beforeSend: function() {
                add_loader(); // Show the loader before the request
            },
            success: function(response) {
                if (onSuccess) {
                    onSuccess(response);
                }
            },
            complete: function() {
                remove_loader(); // Remove the loader after the request completes
            },
            error: function() {
                remove_loader(); // Ensure the loader is removed in case of error
            }
        });
    }

    function loadTableListing(table) {
        if (table) {
            ajaxRequest('get_table_listing', { table: table }, function(response) {
                $('#table-listing').html(response);
            });
        } else {
            $('#table-listing').empty();
        }
    }

    function searchTable(column, searchValue, table) {
        if (table && column) {
            ajaxRequest('table_search', { table: table, column: column, search_value: searchValue }, function(response) {
                $('#table-data').html(response);
            });
        }
    }

    function sortTable(table, sortColumn, sortOrder) {
        if (table && sortColumn) {
            ajaxRequest('get_table_listing', { table: table, sort_column: sortColumn, sort_order: sortOrder }, function(response) {
                $('#table-listing').html(response);
            });
        }
    }

    // Load table listing on table selection
    $('#table-dropdown').change(function() {
        var table = $(this).val();
        loadTableListing(table);
    });

    // Handle column search with debounce
    $(document).on('keyup', '.column-search', function() {
        clearTimeout(debounceTimer);  // Clear the previous debounce timer

        var column = $(this).data('column');
        var searchValue = $(this).val();
        var table = $('#table-dropdown').val();

        debounceTimer = setTimeout(function() {  // Set a new debounce timer
            if (searchValue) {
                searchTable(column, searchValue, table);
            } else {
                loadTableListing(table); // Call loadTableListing if searchValue is empty
            }
        }, 500);  // 500ms delay before the AJAX request is sent
    });

    // Handle sorting via AJAX
    $(document).on('click', '.sort-column', function(e) {
        e.preventDefault(); // Prevent default link behavior
        var table = $('#table-dropdown').val();
        var sortColumn = $(this).data('column');
        var sortOrder = $(this).data('order');

        sortTable(table, sortColumn, sortOrder);
    });
});
