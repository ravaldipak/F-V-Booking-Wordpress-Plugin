<form id="car-park-booking-form" method="post">
    <div>
        <label for="name">Name:</label>
        <input type="text" name="name" id="name" required>
    </div>
    <div>
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>
    </div>
    <div>
        <label for="phone">Phone Number:</label>
        <input type="tel" name="phone" id="phone" required>
    </div>
    <div>
        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" id="start_date" required>
    </div>
    <div>
        <label for="start_time">Start Time:</label>
        <input type="time" name="start_time" id="start_time" required>
    </div>
    <div>
        <label for="exit_date">Exit Date:</label>
        <input type="date" name="exit_date" id="exit_date" required>
    </div>
    <div>
        <label for="exit_time">Exit Time:</label>
        <input type="time" name="exit_time" id="exit_time" required>
    </div>
    <?php
        // Query all published posts of custom post type "fv_booking"
        $args = array(
            'post_type' => 'fv_booking',
            'post_status' => 'publish',
            'posts_per_page' => -1 // Retrieve all posts
        );

        $query = new WP_Query($args);

        // Array to store booked slots
        $booked_slots = array();

        // Check if there are any posts
        if ($query->have_posts()) {
            // Loop through each post
            while ($query->have_posts()) {
                $query->the_post();
                // Get the slot value for each post
                $slot = get_post_meta(get_the_ID(), 'slot', true);
                // Add the slot to the booked slots array
                $booked_slots[] = $slot;
            }
            // Reset post data
            wp_reset_postdata();
        }

        // Generate select options dynamically
        ?>
        <div>
            <label for="slot">Slots:</label>
            <select name="slot" id="slot" required>
                <option value="">Select Slots</option>
                <?php for ($i = 1; $i <= 35; $i++) :
                    // Check if the slot is booked
                    if (!in_array($i, $booked_slots)) :
                ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                <?php
                    endif;
                endfor; ?>
            </select>
        </div>

    <div>
        <label for="notes">Notes:</label>
        <textarea name="notes" id="notes"></textarea>
    </div>
    <div id="error-message" style="color: red;"></div>
    <div id="success-message" style="color: green;"></div>
    <div>
        <?php wp_nonce_field('car_park_booking_form_submit', 'car_park_booking_nonce'); ?>
        <input type="submit" name="submit_booking" id="submit-booking" value="Submit Booking">
    </div>
</form>
