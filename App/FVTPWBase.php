<?php

namespace FVTPWplugin;

ini_set('display_errors', '1');

class FVTPWBase
{
    public function __construct()
    {
        add_action('init', [$this, 'fvtpw_custom_post_type_registration']);
        add_action('init', [$this, 'register_custom_post_status']);
        add_action('wp_enqueue_scripts', [$this, "fvtpw_car_park_booking_enqueue_scripts"]);

        add_action('admin_enqueue_scripts', [$this, "fvtpw_car_park_booking_enqueue_scripts"]);

        add_action('wp_ajax_fvtpw_car_park_booking_submit', [$this, 'fvtpw_car_park_booking_ajax_submit']);
        add_action('wp_ajax_nopriv_fvtpw_car_park_booking_submit', [$this, 'fvtpw_car_park_booking_ajax_submit']);

        add_action('admin_post_approve_post', [$this, 'handle_approve_post_action']);
        add_action('admin_post_deny_post', [$this, 'handle_deny_post_action']);
        add_action('admin_post_complete_post', [$this, "handle_complete_post_action"]);

        add_shortcode('car_park_booking_form', [$this, "fvtpw_car_park_booking_shortcode"]);
        add_filter('manage_fv_booking_posts_columns', [$this, "add_custom_status_column"]);
        add_action('manage_fv_booking_posts_custom_column', [$this, 'populate_custom_status_column'], 10, 2);

        add_action('add_meta_boxes', [$this, 'fvtpw_booking_meta_box']);
        add_action('save_post_fv_booking', [$this, 'fvtpw_save_booking_meta_box_data']);
        add_filter('post_row_actions', [$this, 'fvtpw_custom_post_row_actions'], 10, 2);



    }

    public function add_custom_status_column($columns)
    {
        $new_columns = array();

        // Add the custom status column after the title
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['booking_status'] = __('Status');
                $new_columns['start_date'] = __('Start Date');
                $new_columns['start_time'] = __('Start Time');
                $new_columns['exit_date'] = __('Exit Date');
                $new_columns['exit_time'] = __('Exit Time');
            }
        }

        return $new_columns;
    }

    public function populate_custom_status_column($column, $post_id)
    {
        if ($column === 'booking_status') {
            $status = get_post_status($post_id);

            switch ($status) {
                case 'publish':
                    echo '<span style="color:green; font-weight:bold;">Active</span>';
                    break;
                case 'draft':
                    echo '<span style="color:red; font-weight:bold;">Pending</span>';
                    break;
                case 'complete':
                    echo '<span style="color:green; font-weight:bold;">Complete</span>';
                    break;
                default:
                    echo ucfirst($status);
                    break;
            }
        } elseif ($column === 'start_date') {
            $start_date = get_post_meta($post_id, 'start_date', true);
            echo esc_html(date('j F Y', strtotime($start_date)));
        } elseif ($column === 'start_time') {
            $start_time = get_post_meta($post_id, 'start_time', true);
            echo esc_html(date('h:i A', strtotime($start_time)));
        } elseif ($column === 'exit_date') {
            $exit_date = get_post_meta($post_id, 'exit_date', true);
            echo esc_html(date('j F Y', strtotime($exit_date)));
        } elseif ($column === 'exit_time') {
            $exit_time = get_post_meta($post_id, 'exit_time', true);
            echo esc_html(date('h:i A', strtotime($exit_time)));
        }
    }



    public function register_custom_post_status()
    {
        register_post_status(
            'complete',
            array(
                'label' => _x('Complete', 'post'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Complete <span class="count">(%s)</span>', 'Complete <span class="count">(%s)</span>'),
                'post_type' => array('fv_booking'),
            )
        );
    }

    public function fvtpw_car_park_booking_shortcode()
    {
        ob_start();
        $this->car_park_booking_form();
        return ob_get_clean();
    }

    public function fvtpw_custom_post_row_actions($actions, $post)
    {
        if ($post->post_type === 'fv_booking') {
            // Check if the post status is not 'publish'
            if ($post->post_status !== 'publish' && $post->post_status !== 'complete') {
                // Add 'Approve' action link
                $approve_url = admin_url("admin-post.php?action=approve_post&post_id=$post->ID");
                $actions['accept'] = "<a href='$approve_url'>Approve</a>";

                // Add 'Deny' action link
                $deny_url = admin_url("admin-post.php?action=deny_post&post_id=$post->ID");
                $actions['deny'] = "<a href='$deny_url'>Deny</a>";
            }

            if ($post->post_status !== 'complete') {
                // Add 'Complete' action link
                $complete_url = admin_url("admin-post.php?action=complete_post&post_id=$post->ID");
                $actions['complete'] = "<a href='$complete_url'>Complete</a>";
            }

        }

        return $actions;
    }

    public function handle_approve_post_action()
    {
        if (isset($_GET['post_id'])) {
            $post_id = absint($_GET['post_id']);
            $post = get_post($post_id);
            if ($post && $post->post_type === 'fv_booking') {
                // Update post status to 'publish'
                wp_update_post(
                    array(
                        'ID' => $post_id,
                        'post_status' => 'publish'
                    )
                );

                // Send email to booking user
                $user_email = get_post_meta($post_id, 'email', true);
                $subject = 'Booking Approved';
                $message = "Your booking has been approved.\n\n";
                $headers = 'From: ' . get_option('admin_email') . '\r\n';
                wp_mail($user_email, $subject, $message, $headers);
            }
        }
        wp_redirect(admin_url('edit.php?post_type=fv_booking'));
        exit;
    }

    public function handle_deny_post_action()
    {
        if (isset($_GET['post_id'])) {
            $post_id = absint($_GET['post_id']);
            $post = get_post($post_id);
            if ($post && $post->post_type === 'fv_booking') {
                // Delete the post
                $user_email = get_post_meta($post_id, 'email', true);

                wp_delete_post($post_id, true);

                // Send email to booking user
                $subject = 'Booking Denied';
                $message = "Your booking has been denied.\n\n";
                $headers = 'From: ' . get_option('admin_email') . '\r\n';
                wp_mail($user_email, $subject, $message, $headers);
            }
        }
        wp_redirect(admin_url('edit.php?post_type=fv_booking'));
        exit;
    }

    public function handle_complete_post_action()
    {
        // Get the post ID
        $post_id = $_GET['post_id'];

        // Update the post status to 'complete'
        wp_update_post(
            array(
                'ID' => $post_id,
                'post_status' => 'complete'
            )
        );

        // Send email to booking user
        $user_email = get_post_meta($post_id, 'email', true);
        $subject = 'Booking Completed';
        $message = "Your booking has been completed.\n\n";
        $headers = 'From: ' . get_option('admin_email') . '\r\n';
        wp_mail($user_email, $subject, $message, $headers);

        // Redirect back to the post list page
        wp_redirect(admin_url('edit.php?post_type=fv_booking'));
        exit;
    }

    public function fvtpw_booking_meta_box()
    {
        add_meta_box(
            'fv_booking_meta_box',    // Meta box ID
            'Booking Details',        // Meta box title
            [$this, 'fvtpw_booking_meta_box_callback'], // Callback function to display content
            'fv_booking',             // Post type
            'normal',                 // Context
            'high'                    // Priority
        );
    }

    public function fvtpw_booking_meta_box_callback($post)
    {
        // Retrieve meta values
        $name = get_post_meta($post->ID, 'name', true);
        $email = get_post_meta($post->ID, 'email', true);
        $phone = get_post_meta($post->ID, 'phone', true);
        $start_date = get_post_meta($post->ID, 'start_date', true);
        $start_time = get_post_meta($post->ID, 'start_time', true);
        $exit_date = get_post_meta($post->ID, 'exit_date', true);
        $exit_time = get_post_meta($post->ID, 'exit_time', true);
        $notes = get_post_meta($post->ID, 'notes', true);
        $slot = get_post_meta($post->ID, 'slot', true); // Retrieve slot value

        // Output editable fields
        ?>
        <div id="car-park-booking-form-style">
            <div class="name_email">
                <div class="form_field_wrapper">
                    <label for="name"><strong>Name:</strong></label>
                    <input type="text" id="name" name="name" value="<?php echo esc_attr($name); ?>">
                </div>
                <div class="form_field_wrapper">
                    <label for="email"><strong>Email:</strong></label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>">
                </div>
            </div>
            <div class="form_field_wrapper phone_number">
                <label for="phone"><strong>Phone:</strong></label>
                <input type="tel" id="phone" name="phone" value="<?php echo esc_attr($phone); ?>">
            </div>
            <div class="timing_slot">
                <div class="start_time">
                    <div class="form_field_wrapper">
                        <label for="start_date"><strong>Start Date:</strong></label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                    </div>
                    <div class="form_field_wrapper">
                        <label for="start_time"><strong>Start Time:</strong></label>
                        <input type="time" id="start_time" name="start_time" value="<?php echo esc_attr($start_time); ?>">
                    </div>
                </div>
                <div class="end_time">
                    <div class="form_field_wrapper">
                        <label for="exit_date"><strong>Exit Date:</strong></label>
                        <input type="date" id="exit_date" name="exit_date" value="<?php echo esc_attr($exit_date); ?>">
                    </div>
                    <div class="form_field_wrapper">
                        <label for="exit_time"><strong>Exit Time:</strong></label>
                        <input type="time" id="exit_time" name="exit_time" value="<?php echo esc_attr($exit_time); ?>">
                    </div>
                </div>
                <div class="slots">
                    <div class="form_field_wrapper">
                        <label for="slot"><strong>Slot:</strong></label>
                        <input type="text" id="slot" name="slot" value="<?php echo esc_attr($slot); ?>">
                    </div>
                </div>
            </div>
            <div class="form_field_wrapper">
                <label for="notes"><strong>Notes:</strong></label>
                <textarea id="notes" name="notes"><?php echo esc_textarea($notes); ?></textarea>
            </div>
        </div>
        <?php
    }

    public function fvtpw_save_booking_meta_box_data($post_id)
    {
        // Check if nonce is set
        if (!isset($_POST['meta-box-order-nonce'])) {
            return;
        }

        // Check if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }


        // Update meta values
        $meta_fields = array('name', 'email', 'phone', 'start_date', 'start_time', 'exit_date', 'exit_time', 'slot', 'notes');
        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function fvtpw_custom_post_type_registration()
    {
        // Check if the post type is already registered to avoid conflicts
        if (post_type_exists('fv_booking')) {
            return;
        }
        // die();
        // Register custom post type


        $labels = array(
            'name' => _x('F&V Bookings', 'post type general name'),
            'singular_name' => _x('F&V Booking', 'post type singular name'),
            'add_new' => _x('Add New', 'F&V Booking'),
            'add_new_item' => __('Add New F&V Booking'),
            'edit_item' => __('Edit F&V Booking'),
            'new_item' => __('New F&V Booking'),
            'all_items' => __('All F&V Bookings'),
            'view_item' => __('View F&V Booking'),
            'search_items' => __('Search F&V Bookings'),
            'not_found' => __('No F&V Bookings found'),
            'not_found_in_trash' => __('No F&V Bookings found in the Trash'),
            'parent_item_colon' => '',
            'menu_name' => 'F&V Bookings'
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => 'themes',
            'rewrite' => array('slug' => 'fv_park_booking'),
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'description' => 'Displays F&V Bookings and their ratings',
            'show_ui' => true,
            'show_in_menu' => true,
        );

        // $args = array(
        //     'labels' => array(
        //         'name' => __('F&V Bookings'),
        //         'singular_name' => __('F&V Booking'),
        //     ),
        //     'public' => true,
        //     'has_archive' => true,
        //     'rewrite' => array('slug' => 'fv-booking'),
        //     // Add other arguments as needed
        // );

        // Register custom post type
        register_post_type('fv_booking', $args);
    }

    public function fvtpw_car_park_booking_enqueue_scripts()
    {
        // Enqueue jQuery
        wp_enqueue_script('jquery');

        // Enqueue custom script for handling AJAX submission
        wp_enqueue_script('fvtpw-car-park-booking-ajax', FVTPW_DIR_URI . 'assets/js/fvtpw-car-park-booking-ajax.js', array('jquery'), '1.0', true);

        // Enqueue custom style
        wp_enqueue_style('fvtpw-car-park-booking-style', FVTPW_DIR_URI . 'assets/css/admin_style.css', '', '1.0', '');

        // Localize the script with the form nonce
        wp_localize_script('fvtpw-car-park-booking-ajax', 'car_park_booking_ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('car_park_booking_ajax_nonce')));
    }

    public function fvtpw_car_park_booking_ajax_submit()
    {

        $status = ["status" => false, "message" => ""];

        if (!check_ajax_referer('car_park_booking_ajax_nonce', 'security')) {
            $status = ["status" => false, "message" => "Nonce Not Verify"];
            echo json_encode($status);
            wp_die();
        }

        // Define field names
        $fields = array(
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'start_date' => 'Start Date',
            'start_time' => 'Start Time',
            'exit_date' => 'Exit Date',
            'exit_time' => 'Exit Time',
            'slot' => 'Slot'
        );


        // Validate form fields
        if (wp_verify_nonce($_POST['car_park_booking_nonce'], 'car_park_booking_form_submit')) {
            // Check if any field is empty
            foreach ($fields as $field_key => $field_label) {
                if (empty($_POST[$field_key])) {
                    $status = ["status" => false, "message" => ucfirst(str_replace('_', ' ', $field_key)) . " is required."];
                    echo json_encode($status);
                    wp_die();
                }
            }

            // Sanitize and save form data
            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $phone = sanitize_text_field($_POST['phone']);
            $start_date = sanitize_text_field($_POST['start_date']);
            $start_time = sanitize_text_field($_POST['start_time']);
            $exit_date = sanitize_text_field($_POST['exit_date']);
            $exit_time = sanitize_text_field($_POST['exit_time']);
            $slot = sanitize_text_field($_POST['slot']);
            $notes = sanitize_textarea_field($_POST['notes']);

            // Check if slot is already booked
            global $wpdb;
            $existing_slot_posts = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT pm.meta_value
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'fv_booking' AND p.post_status = 'publish' AND pm.meta_key = 'slot' AND pm.meta_value = %s",
                    $slot
                )
            );

            if (!empty($existing_slot_posts)) {
                $status = ["status" => false, "message" => "Slot $slot is already booked."];
                echo json_encode($status);
                wp_die();
            }

            // Create post
            $post_title = $name . " ( " . $slot . " )";
            $post_content = ''; // You can set content here if needed
            $post_id = wp_insert_post(
                array(
                    'post_title' => $post_title,
                    'post_content' => $post_content,
                    'post_type' => 'fv_booking',
                    'post_status' => 'draft'
                ),
                true
            );


            // Save form data in post meta
            if ($post_id) {
                update_post_meta($post_id, 'name', $name);
                update_post_meta($post_id, 'email', $email);
                update_post_meta($post_id, 'phone', $phone);
                update_post_meta($post_id, 'start_date', $start_date);
                update_post_meta($post_id, 'start_time', $start_time);
                update_post_meta($post_id, 'exit_date', $exit_date);
                update_post_meta($post_id, 'exit_time', $exit_time);
                update_post_meta($post_id, 'slot', $slot);
                update_post_meta($post_id, 'notes', $notes);

                // Send email to admin
                $admin_email = get_option('admin_email');
                $subject = 'New Car Park Booking';
                $message = "A new car park booking has been made.\n\n";
                $message .= "Name: $name\n";
                $message .= "Email: $email\n";
                $message .= "Phone: $phone\n";
                $message .= "Start Date: $start_date\n";
                $message .= "Start Time: $start_time\n";
                $message .= "Exit Date: $exit_date\n";
                $message .= "Exit Time: $exit_time\n";
                $message .= "Slot: $slot\n";
                $message .= "Notes: $notes\n";
                $headers = 'From: ' . $email . '\r\n';

                wp_mail($admin_email, $subject, $message, $headers);

                $status = ["status" => true, "message" => "Form submitted successfully."];
            } else {
                $status = ["status" => false, "message" => "Failed to create booking."];
            }

            echo json_encode($status);
            wp_die();
        }

        $status = ["status" => false, "message" => "Form submission failed."];
        echo json_encode($status);
        wp_die();
    }
    // Function to output the booking form
    public function car_park_booking_form()
    {
        require_once (FVTPW_DIR_PATH . "/templates/fvtpw_booking_form.php");
    }

}
?>