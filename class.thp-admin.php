<?php

class THP_Admin {

    const DEFAULT_MAX_HANDLES = 5;
    const DEFAULT_API_USERNAME = 'admin';
    const DEFAULT_API_PASSWORD = 'password';

	private static $initiated = false;

    public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

    private static function init_hooks() {
        self::$initiated = true;

        add_action('admin_menu', array('THP_Admin', 'thp_add_admin_menu'));
    }

    public static function thp_add_admin_menu() {
        add_menu_page(
            'Telegram Handles Settings',
            'Telegram Handles',
            'manage_options',
            'thp-settings',
            array('THP_Admin', 'thp_settings_page'),
            'dashicons-admin-generic',
            100
        );
    }

    public static function thp_settings_page() {
        // Check if the user is allowed to manage options
        if (!current_user_can('manage_options')) {
            return;
        }
    
        // Save settings if the form is submitted
        if (isset($_POST['thp_save_settings'])) {
            check_admin_referer('thp_save_settings_nonce');
    
            update_option('thp_max_handles', intval($_POST['thp_max_handles']));
            update_option('thp_api_username', sanitize_text_field($_POST['thp_api_username']));
            update_option('thp_api_password', sanitize_text_field($_POST['thp_api_password']));
            update_option('thp_simple_membership_id', sanitize_text_field($_POST['thp_simple_membership_id']));
    
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
    
        // Get the current values
        $max_handles = get_option('thp_max_handles', self::DEFAULT_MAX_HANDLES);
        $api_username = get_option('thp_api_username', self::DEFAULT_API_USERNAME);
        $api_password = get_option('thp_api_password', self::DEFAULT_API_PASSWORD);
        $simple_membership_id = get_option('thp_simple_membership_id', '');
    
        ?>
        <div class="wrap">
            <h1>Telegram Handles Settings</h1>
            <form method="POST">
                <?php wp_nonce_field('thp_save_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="thp_max_handles">Maximum Telegram Handles</label></th>
                        <td>
                            <input type="number" name="thp_max_handles" id="thp_max_handles" value="<?php echo esc_attr($max_handles); ?>" class="small-text" />
                            <p class="description">Set the maximum number of Telegram handles users can specify.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="thp_api_username">API Username</label></th>
                        <td>
                            <input type="text" name="thp_api_username" id="thp_api_username" value="<?php echo esc_attr($api_username); ?>" class="regular-text" />
                            <p class="description">Set the username for the API authentication.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="thp_api_password">API Password</label></th>
                        <td>
                            <input type="password" name="thp_api_password" id="thp_api_password" value="<?php echo esc_attr($api_password); ?>" class="regular-text" />
                            <p class="description">Set the password for the API authentication.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="thp_simple_membership_id">Simple Membership</label></th>
                        <td>
                            <input type="text" name="thp_simple_membership_id" id="thp_simple_membership_id" value="<?php echo esc_attr($simple_membership_id); ?>" class="regular-text" />
                            <p class="description">Set the WP Simple Membership Level ID.</p>
                        </td>
                    </tr>
                </table>
                <p><input type="submit" name="thp_save_settings" value="Save Settings" class="button-primary" /></p>
            </form>
        </div>
        <?php
    }    
}