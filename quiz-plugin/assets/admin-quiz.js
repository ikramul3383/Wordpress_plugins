jQuery(document).ready(function ($) {
    // Add Account Number
    $('#account-form').submit(function (e) {
        e.preventDefault();
        $('#loader').show();

        $.ajax({
            url: quiz_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'add_account_number',
                security: quiz_ajax.quiz_nonce,
                account_number: $('#account_number').val(),
            },
            success: function (response) {
                alert(response.data);
                $('#loader').hide();
                location.reload(); // Reload the page to show the updated entries
            },
            error: function () {
                alert('An error occurred.');
                $('#loader').hide();
            },
        });
    });

    // Import CSV
    $('#csv-import-form').submit(function (e) {
        e.preventDefault();
        $('#import-loader').show();

        var formData = new FormData(this);
        formData.append('action', 'import_csv'); // This is the action that tells WordPress which function to run
        formData.append('security', quiz_ajax.quiz_nonce); // Add the nonce for security

        $.ajax({
            url: quiz_ajax.ajax_url, // Use localized AJAX URL
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                alert(response.data); // Show the success message
                $('#import-loader').hide();
                location.reload(); // Reload the page to show the updated entries
            },
            error: function () {
                alert('An error occurred.');
                $('#import-loader').hide();
            },
        });
    });


    $('#export-csv').click(function () {
        // Get current date in YYYY-MM-DD format
        var currentDate = new Date();
        var formattedDate = currentDate.getFullYear() + '-'
            + (currentDate.getMonth() + 1).toString().padStart(2, '0') + '-'
            + currentDate.getDate().toString().padStart(2, '0');

        // Append the date to the URL
        var exportUrl = quiz_ajax.ajax_url + '?action=export_csv&security=' + quiz_ajax.quiz_nonce + '&date=' + formattedDate;

        // Redirect to the export URL
        window.location = exportUrl;
    });


    // Delete Entry
    $(document).on('click', '.delete-entry', function () {
        if (confirm('Are you sure you want to delete this entry?')) {
            var entryId = $(this).data('id');

            $.ajax({
                url: quiz_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_account_number',
                    security: quiz_ajax.quiz_nonce,
                    id: entryId,
                },
                success: function (response) {
                    // alert(response.data);
                    location.reload(); // Reload the page to update the table
                },
                error: function () {
                    alert('An error occurred.');
                },
            });
        }
    });
});
