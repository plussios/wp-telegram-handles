<?php

if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

class THP_REST_API {
	/**
	 * Register the REST API routes.
	 */
	public static function init() {
		if ( ! function_exists( 'register_rest_route' ) ) {
			// The REST API wasn't integrated into core until 4.4, and we support 4.0+ (for now).
			return false;
		}

        register_rest_route('thp/v1', '/telegram-handles', array(
            'methods'  => 'GET',
            'callback' => array('THP_REST_API', 'thp_get_telegram_handles'),
            'permission_callback' => array('THP_REST_API', 'thp_basic_authentication_check'),
        ));
    }

    public static function thp_get_telegram_handles() {
        global $wpdb;
        $max_handles_count = get_option('thp_max_handles', THP_Admin::DEFAULT_MAX_HANDLES);

        $membership_filter_active = is_plugin_active('simple-membership/simple-wp-membership.php');
        $simple_membership_id = get_option('thp_simple_membership_id', '');

        if ($membership_filter_active && $simple_membership_id != '') {
            $results = self::thp_get_handles_simple_membership($simple_membership_id);
        } else {
            $results = self::thp_get_handles_no_filter();
        }

        // Prepare the response
        $handles = array();
        foreach ($results as $row) {
            $user_handles = explode(', ', $row->handles);
            $handles = array_merge($handles, array_slice($user_handles, 0, $max_handles_count));
        }

        return new WP_REST_Response($handles, 200);
    }

    public static function thp_get_handles_no_filter() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_handles';
        // Fetch all handles grouped by user_id
        $results = $wpdb->get_results("
            SELECT user_id, GROUP_CONCAT(handle SEPARATOR ', ') AS handles
            FROM $table_name
            GROUP BY user_id
        ");

        return $results;
    }

    public static function thp_get_handles_simple_membership($membership_id) {
        $allow_admins = true;
        global $wpdb;
        $table_name = $wpdb->prefix . 'telegram_handles';
        $membership_table = $wpdb->prefix . 'swpm_members_tbl';
        $users_table = $wpdb->prefix . 'users';

        // Get user IDs of admins
        $admin_user_ids = $allow_admins ? get_users(array(
            'role'    => 'administrator',
            'fields'  => 'ID',
        )) : [];

        // Convert array of admin IDs to a comma-separated string
        $admin_user_ids_str = implode(',', $admin_user_ids);

        // Query to get handles of users with the given membership ID
        $results = $wpdb->get_results($wpdb->prepare( "
        SELECT h.user_id, GROUP_CONCAT(h.handle SEPARATOR ', ') AS handles
        FROM $table_name h
        INNER JOIN $users_table u ON h.user_id = u.`ID`
        LEFT JOIN $membership_table m ON u.user_login = m.user_name
        WHERE m.membership_level = %d AND m.account_state = \"active\" OR h.user_id IN ($admin_user_ids_str)
        GROUP BY h.user_id, u.user_login
        ", $membership_id));

        return $results;
    }

    public static function thp_basic_authentication_check($request) {
        $headers = apache_request_headers();
    
        if (!isset($headers['Authorization'])) {
            return new WP_Error('missing_authentication', 'Authorization header is missing', array('status' => 401));
        }
    
        $auth_header = $headers['Authorization'];
        if (strpos($auth_header, 'Basic ') !== 0) {
            return new WP_Error('invalid_authentication', 'Invalid authorization method', array('status' => 401));
        }
    
        // Extract the base64-encoded credentials
        $encoded_credentials = substr($auth_header, 6);
        $decoded_credentials = base64_decode($encoded_credentials);
        list($username, $password) = explode(':', $decoded_credentials);
    
        $valid_username = get_option('thp_api_username', THP_Admin::DEFAULT_API_USERNAME);
        $valid_password = get_option('thp_api_password', THP_Admin::DEFAULT_API_PASSWORD);
    
        if ($username !== $valid_username || $password !== $valid_password) {
            return new WP_Error('invalid_credentials', 'Invalid username or password', array('status' => 401));
        }
    
        return true;
    }
}
