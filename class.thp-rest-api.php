<?php

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
        $table_name = $wpdb->prefix . 'telegram_handles';

        // Fetch all handles grouped by user_id
        $results = $wpdb->get_results("
            SELECT user_id, GROUP_CONCAT(handle SEPARATOR ', ') AS handles
            FROM $table_name
            GROUP BY user_id
        ");

        // Prepare the response
        $handles = array();
        foreach ($results as $row) {
            // $handles[] = array(
            //     'user_id' => $row->user_id,
            //     'handles' => explode(', ', $row->handles),
            // );
            $handles += explode(', ', $row->handles);
        }

        return new WP_REST_Response($handles, 200);
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
    
        // Replace with your actual username and password
        $valid_username = get_option('thp_api_username', THP_Admin::DEFAULT_API_USERNAME);
        $valid_password = get_option('thp_api_password', THP_Admin::DEFAULT_API_PASSWORD);
    
        if ($username !== $valid_username || $password !== $valid_password) {
            return new WP_Error('invalid_credentials', 'Invalid username or password', array('status' => 401));
        }
    
        return true;
    }
}
