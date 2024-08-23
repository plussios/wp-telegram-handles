<?php
/**
 * @package Telegram_Handles
 * @version 1.0.0
 */
/*
Plugin Name: Telegram Handles
Description: Allows users to store up to 5 Telegram handles and provides shortcode and block editor support.
Version: 1.0.0
Author: Stan Sidel
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function thb_get_max_handles_count() {
    return 5;
}

// MARK: - Register the user meta fields
function thp_register_user_meta() {
    for ($i = 1; $i <= thb_get_max_handles_count(); $i++) {
        register_meta('user', "telegram_handle_$i", [
            'type'         => 'string',
            'description'  => "Telegram Handle $i",
            'single'       => true,
            'show_in_rest' => true, // Important for block editor support
        ]);
    }
}
add_action('init', 'thp_register_user_meta');

// MARK: - Add fields to user profile
function thp_add_user_profile_fields($user) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'telegram_handles';
    $user_id = $user->ID;

    $handles = $wpdb->get_results($wpdb->prepare(
        "SELECT handle FROM $table_name WHERE user_id = %d ORDER BY id ASC",
        $user_id
    ));

    ?>
    <h3>Telegram Handles</h3>
    <table class="form-table">
        <?php for ($i = 1; $i <= thb_get_max_handles_count(); $i++): ?>
        <tr>
            <th><label for="telegram_handle_<?php echo $i; ?>">Telegram Handle <?php echo $i; ?></label></th>
            <td>
                <input type="text" name="telegram_handle_<?php echo $i; ?>" id="telegram_handle_<?php echo $i; ?>" value="<?php echo esc_attr($handles[$i - 1]->handle ?? ''); ?>" class="regular-text" />
            </td>
        </tr>
        <?php endfor; ?>
    </table>
    <?php
}
add_action('show_user_profile', 'thp_add_user_profile_fields');
add_action('edit_user_profile', 'thp_add_user_profile_fields');

// MARK: - Save the fields
function thp_save_user_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'telegram_handles';

    // Clear existing handles for this user
    $wpdb->delete($table_name, ['user_id' => $user_id]);

    // Insert the new handles
    for ($i = 1; $i <= thb_get_max_handles_count(); $i++) {
        $handle = sanitize_text_field($_POST["telegram_handle_$i"]);
        if (!empty($handle)) {
            $wpdb->insert($table_name, [
                'user_id' => $user_id,
                'handle'  => $handle
            ]);
        }
    }
}
add_action('personal_options_update', 'thp_save_user_profile_fields');
add_action('edit_user_profile_update', 'thp_save_user_profile_fields');

// MARK: - Register styles file

function thp_enqueue_styles() {
    wp_enqueue_style('thp-styles', plugins_url('styles.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'thp_enqueue_styles');

// MARK: - Register scripts

function thp_enqueue_scripts() {
    wp_enqueue_script('thp-ajax-script', plugins_url('ajax.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('thp-ajax-script', 'thp_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('thp_save_handles_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'thp_enqueue_scripts');

// MARK: - Handle AJAX

function thp_handle_ajax() {
    // Check nonce for security
    check_ajax_referer('thp_save_handles_nonce', 'security');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in to perform this action.'));
    }

    $user_id = get_current_user_id();
    global $wpdb;
    $table_name = $wpdb->prefix . 'telegram_handles';

    // Clear existing handles for this user
    $wpdb->delete($table_name, ['user_id' => $user_id]);

    // Insert the new handles
    for ($i = 1; $i <= 5; $i++) {
        $handle = sanitize_text_field($_POST["telegram_handle_$i"]);
        if (!empty($handle)) {
            $wpdb->insert($table_name, [
                'user_id' => $user_id,
                'handle'  => $handle
            ]);
        }
    }

    wp_send_json_success(array('message' => 'Your Telegram handles have been updated.'));
}
add_action('wp_ajax_thp_save_handles', 'thp_handle_ajax');

// MARK: - Shortcode to display and edit Telegram handles

function thp_telegram_handles_shortcode($atts) {
    $atts = shortcode_atts([], $atts, 'telegram_handles');

    if (!is_user_logged_in()) {
        return 'You need to be logged in to manage your Telegram handles.';
    }

    $user_id = get_current_user_id();
    global $wpdb;
    $table_name = $wpdb->prefix . 'telegram_handles';

    // Handle form submission with nonce verification
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['thp_save_handles'])) {
        check_admin_referer('thp_save_handles_nonce');

        // Clear existing handles for this user
        $wpdb->delete($table_name, ['user_id' => $user_id]);

        // Insert the new handles
        for ($i = 1; $i <= thb_get_max_handles_count(); $i++) {
            $handle = sanitize_text_field($_POST["telegram_handle_$i"]);
            if (!empty($handle)) {
                $wpdb->insert($table_name, [
                    'user_id' => $user_id,
                    'handle'  => $handle
                ]);
            }
        }

        // Feedback message
        echo '<div class="updated"><p>Your Telegram handles have been updated.</p></div>';
    }

    // Retrieve the current handles and ensure there are 5 slots
    $handles = $wpdb->get_results($wpdb->prepare(
        "SELECT handle FROM $table_name WHERE user_id = %d ORDER BY id ASC",
        $user_id
    ), ARRAY_A);

    // Convert results to a simple array
    $handles = array_column($handles, 'handle');

    // Ensure there are exactly 5 elements, filling with empty strings if necessary
    $handles = array_pad($handles, thb_get_max_handles_count(), '');

    // Form output
    ob_start();
    ?>
    <form id="thp-telegram-handles-form" method="POST" class="wp-block-group">
        <?php wp_nonce_field('thp_save_handles_nonce'); ?>
        <h3>Manage Your Telegram Handles</h3>
        <table class="form-table">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <tr class="form-field">
                <th><label for="telegram_handle_<?php echo $i; ?>">Telegram Handle <?php echo $i; ?></label></th>
                <td>
                    <input type="text" name="telegram_handle_<?php echo $i; ?>" id="telegram_handle_<?php echo $i; ?>" value="<?php echo esc_attr($handles[$i - 1]); ?>" class="regular-text input-text" />
                </td>
            </tr>
            <?php endfor; ?>
        </table>
        <div id="thp-message" style="display: none;" class="notice notice-success is-dismissible"></div>
        <p>
            <button type="submit" name="thp_save_handles" class="wp-block-button__link button-primary">Save Handles</button>
        </p>
    </form>
    <?php

    return ob_get_clean();
}
add_shortcode('telegram_handles', 'thp_telegram_handles_shortcode');

// MARK: - Register block for Gutenberg
function thp_register_block() {
    wp_register_script(
        'thp-block',
        plugins_url('block.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor'),
        filemtime(plugin_dir_path(__FILE__) . 'block.js')
    );

    register_block_type('thp/telegram-handles', array(
        'editor_script' => 'thp-block',
        'render_callback' => 'thp_telegram_handles_shortcode'
    ));
}
add_action('init', 'thp_register_block');

// MARK: - Create database for the Telegram handles

function thp_create_or_update_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'telegram_handles';
    $charset_collate = $wpdb->get_charset_collate();
    $current_version = '1.0'; // Define the current schema version of your plugin

    // Check the installed version
    $installed_version = get_option('thp_db_version');

    // Define the table schema
    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        handle varchar(255) NOT NULL,
        PRIMARY KEY  (id),
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    if ($installed_version !== $current_version) {
        dbDelta($sql); // Create or update the table

        // Apply any other necessary migrations here
        // For example, if upgrading from version 1.0 to 1.1:
        if ($installed_version && version_compare($installed_version, '1.1', '<')) {
            // $wpdb->query("ALTER TABLE $table_name ADD COLUMN new_column_name data_type;");
        }

        // Update the version in the options table
        update_option('thp_db_version', $current_version);
    }
}

register_activation_hook(__FILE__, 'thp_create_or_update_table');

// MARK: - Remove the Telegram handles table on plugin deactivation

function thp_remove_table() {
    // global $wpdb;
    // $table_name = $wpdb->prefix . 'telegram_handles';
    // $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

register_deactivation_hook(__FILE__, 'thp_remove_table');
