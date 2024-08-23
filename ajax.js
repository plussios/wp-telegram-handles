(function ($) {
    $(document).ready(function () {
        var isThpSubmitting = false;
        $('#thp-telegram-handles-form').on('submit', function (e) {
            e.preventDefault();

            if (isThpSubmitting) { return }

            isThpSubmitting = true;

            var formData = $(this).serialize(); // Serialize form data
            console.log('Form submitted'); // Debugging statement

            $.ajax({
                url: thp_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=thp_save_handles', // Add the action to the data
                success: function (response) {
                    console.log('Success:', response); // Debugging statement
                    $('#thp-message').html(response.data.message).fadeIn().delay(3000).fadeOut();
                    isThpSubmitting = false;
                },
                error: function () {
                    console.log('Error occurred'); // Debugging statement
                    $('#thp-message').html('<p>An error occurred. Please try again.</p>').fadeIn().delay(3000).fadeOut();
                    isThpSubmitting = false;
                }
            });
        });
    });
})(jQuery);
