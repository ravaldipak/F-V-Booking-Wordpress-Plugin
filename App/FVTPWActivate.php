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
    }

    
}
?>