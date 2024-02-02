<?php
/*
Plugin Name: Coupon Code Generator
Description: Generate and manage coupon codes with various features.
Version: 1.4
Author: Shohan Perera
*/

// Add a menu item in the WordPress admin dashboard
function coupon_code_generator_menu() {
    add_menu_page('Coupon Code Generator', 'Coupon Generator', 'manage_options', 'coupon_code_generator', 'coupon_code_generator_page');
    add_submenu_page('coupon_code_generator', 'Coupon Management', 'Coupon Management', 'manage_options', 'coupon_code_management', 'coupon_code_management_page');
}

add_action('admin_menu', 'coupon_code_generator_menu');

// Create custom table on plugin activation
function create_custom_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'coupons';

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        coupon_code varchar(255) NOT NULL,
        discount_type varchar(20) NOT NULL,
        discount_value varchar(20) NOT NULL,
        expiration_date date DEFAULT '0000-00-00',
        usage_limit int(11) DEFAULT 0,
        single_use tinyint(1) DEFAULT 0,
        auto_apply tinyint(1) DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_custom_table');

// Coupon Code Generator Page
function coupon_code_generator_page() {
    if (isset($_POST['generate_coupon'])) {
        // Handle form submission and generate the coupon code
        $coupon_code = sanitize_text_field($_POST['coupon_code']);
        $discount_type = sanitize_text_field($_POST['discount_type']);
        $discount_value = sanitize_text_field($_POST['discount_value']);
        $expiration_date = sanitize_text_field($_POST['expiration_date']);
        $usage_limit = intval($_POST['usage_limit']);
        $single_use = isset($_POST['single_use']) ? 1 : 0;
        $auto_apply = isset($_POST['auto_apply']) ? 1 : 0;

        // Add your coupon generation and management logic here
        // For simplicity, we'll just display the input values
        echo "Coupon Code: $coupon_code<br>";
        echo "Discount Type: $discount_type<br>";
        echo "Discount Value: $discount_value<br>";
        echo "Expiration Date: $expiration_date<br>";
        echo "Usage Limit: $usage_limit<br>";
        echo "Single Use: " . ($single_use ? 'Yes' : 'No') . "<br>";
        echo "Auto Apply: " . ($auto_apply ? 'Yes' : 'No') . "<br>";

        // Save coupon details to the database
        save_coupon_to_database($coupon_code, $discount_type, $discount_value, $expiration_date, $usage_limit, $single_use, $auto_apply);

        // Send email notification
        send_coupon_email($coupon_code, $expiration_date);

        // Track and display coupon usage
        track_coupon_usage($coupon_code);
    }

    // Display the coupon code generator form
    ?>
    <div class="wrap">
        <h1>Coupon Code Generator</h1>
        <form method="post" action="">
            <label for="coupon_code">Coupon Code:</label>
            <input type="text" name="coupon_code" id="coupon_code" required><br>

            <label for="discount_type">Discount Type:</label>
            <select name="discount_type" id="discount_type">
                <option value="percentage">Percentage Discount</option>
                <option value="fixed_amount">Fixed Amount Discount</option>
            </select><br>

            <label for="discount_value">Discount Value:</label>
            <input type="text" name="discount_value" id="discount_value" required><br>

            <label for="expiration_date">Expiration Date:</label>
            <input type="date" name="expiration_date" id="expiration_date"><br>

            <label for="usage_limit">Usage Limit:</label>
            <input type="number" name="usage_limit" id="usage_limit" min="1"><br>

            <label for="single_use">Single Use:</label>
            <input type="checkbox" name="single_use" id="single_use"><br>

            <label for="auto_apply">Auto Apply:</label>
            <input type="checkbox" name="auto_apply" id="auto_apply"><br>

            <input type="submit" name="generate_coupon" value="Generate Coupon">
        </form>
    </div>
    <?php
}

// Coupon Management Page
function coupon_code_management_page() {
    // Retrieve coupon list from the custom database table
    $coupons = get_coupons_from_database();

    // Display a table with coupon details and management options
    ?>
    <div class="wrap">
        <h1>Coupon Management</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Coupon Code</th>
                    <th>Discount Type</th>
                    <th>Discount Value</th>
                    <th>Expiration Date</th>
                    <th>Usage Limit</th>
                    <th>Single Use</th>
                    <th>Auto Apply</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon) : ?>
                    <tr>
                        <td><?php echo $coupon->coupon_code; ?></td>
                        <td><?php echo $coupon->discount_type; ?></td>
                        <td><?php echo $coupon->discount_value; ?></td>
                        <td><?php echo $coupon->expiration_date; ?></td>
                        <td><?php echo $coupon->usage_limit; ?></td>
                        <td><?php echo $coupon->single_use ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $coupon->auto_apply ? 'Yes' : 'No'; ?></td>
                        <td>
                            <a href="#">Edit</a> |
                            <a href="#">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Function to save coupon details to the custom database table
function save_coupon_to_database($coupon_code, $discount_type, $discount_value, $expiration_date, $usage_limit, $single_use, $auto_apply) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'coupons';

    $wpdb->insert(
        $table_name,
        array(
            'coupon_code' => $coupon_code,
            'discount_type' => $discount_type,
            'discount_value' => $discount_value,
            'expiration_date' => $expiration_date,
            'usage_limit' => $usage_limit,
            'single_use' => $single_use,
            'auto_apply' => $auto_apply,
        )
    );
}

// Function to retrieve coupon list from the custom database table
function get_coupons_from_database() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'coupons';

    return $wpdb->get_results("SELECT * FROM $table_name");
}

// ... (more code)

function enqueue_plugin_styles() {
    wp_enqueue_style('plugin-styles', plugins_url('coupon-code-generator.css', __FILE__));
}

add_action('admin_enqueue_scripts', 'enqueue_plugin_styles');
?>
