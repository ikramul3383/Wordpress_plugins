jQuery(document).ready(function($) {
    // Track clicks
    $(document).on('click', function(e) {
        var data = {
            action: 'track_user_click',
            url: window.location.href,
            x: e.pageX,
            y: e.pageY
        };

        $.post(ajaxurl, data)
            .fail(function() {
                console.error('Click tracking failed.');
            });
    });

    // Track scroll events
    $(window).on('scroll', function() {
        var scrollTop = $(this).scrollTop();
        var scrollHeight = $(document).height();
        var windowHeight = $(window).height();
        var scrollPercentage = (scrollTop / (scrollHeight - windowHeight)) * 100;

        var data = {
            action: 'track_user_scroll',
            url: window.location.href,
            scroll_percentage: scrollPercentage
        };

        $.post(ajaxurl, data)
            .fail(function() {
                console.error('Scroll tracking failed.');
            });
    });

    // Track form submissions
    $(document).on('submit', 'form', function(e) {
        e.preventDefault(); // Prevent actual submission for tracking
        var formData = $(this).serialize();
        var data = {
            action: 'track_form_submission',
            url: window.location.href,
            form_data: formData
        };

        $.post(ajaxurl, data)
            .done(function() {
                e.currentTarget.submit(); // Submit the form after tracking
            })
            .fail(function() {
                console.error('Form submission tracking failed.');
                e.currentTarget.submit(); // Ensure form submission proceeds even if tracking fails
            });
    });

    // Track page redirects
    $(window).on('beforeunload', function() {
        var data = {
            action: 'track_page_redirect',
            url: window.location.href
        };

        // Use navigator.sendBeacon for better reliability on page unload
        navigator.sendBeacon(ajaxurl, new URLSearchParams(data));
    });
});
