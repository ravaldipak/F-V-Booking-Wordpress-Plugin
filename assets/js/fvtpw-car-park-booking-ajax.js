jQuery(document).ready(function($) {
    $('#car-park-booking-form').submit(function(e) {
        e.preventDefault();

        console.log($('#submit-booking'));

        $('#submit-booking').val("Booking...");
        $('#submit-booking').prop('disabled', true);
        
        $('#success-message').text("");
        $('#error-message').text("");

        var formData = $(this).serialize();
        formData += '&action=fvtpw_car_park_booking_submit';
        formData += '&security=' + car_park_booking_ajax_object.nonce;

        $.ajax({
            type: 'POST',
            url: car_park_booking_ajax_object.ajax_url,
            data: formData,
            success: function(response) {
                var result = JSON.parse(response);
                if (result.status) {
                    $('#car-park-booking-form')[0].reset(); // Reset the form
                    // Display success message (you can use a better UI for this)
                    $('#success-message').text(result.message);
                } else {
                    // Display error message above the submit button
                    $('#error-message').text(result.message);
                }
                $('#submit-booking').val("Submit Booking");
                $('#submit-booking').prop('disabled', false);
            },
            error: function(xhr, status, error) {
                // Handle error (e.g., display error message)
                console.error('Error occurred:', error);
                $('#submit-booking').val("Submit Booking");
                $('#submit-booking').prop('disabled', false);
            }
        });

    });
});
