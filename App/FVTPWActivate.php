<?php
namespace FVTPWplugin;
class FVTPWActivate{
    public function __construct(){}

    public function FVTPW_activate() {
        if (post_type_exists('fv_booking')) {
            return;
        }
    
        // Register custom post type
        register_post_type('fv_booking', array(
            'labels' => array(
                'name' => __('F&V Bookings'),
                'singular_name' => __('F&V Booking'),
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'fv-booking'),
            // Add other arguments as needed
        ));

        $default_options = array(
            'new_booking_message' => 'You get new booking' . "\n" .
                'Name : {{name}}' . "\n" .
                'Email : {{email}}' . "\n" .
                'Phone : {{phone}}' . "\n" .
                'Start Date : {{start_date}}' . "\n" .
                'Start Time : {{start_time}}' . "\n" .
                'Exit Date : {{exit_date}}' . "\n" .
                'Exit Time : {{exit_time}}' . "\n" .
                'Slot : {{slot}}' . "\n" .
                '{{notes}}',
            'change_status_message' => 'Your Booking Status changed to {{status}}'
        );
    
        // Add default options
        foreach ($default_options as $option_key => $option_value) {
            add_option($option_key, $option_value);
        }
    }

    
}
?>