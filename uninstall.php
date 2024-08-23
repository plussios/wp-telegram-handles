<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

global $wpdb;
$table_name = $wpdb->prefix . 'telegram_handles';

// Drop the table if the plugin is uninstalled
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Optionally, delete the stored version option
delete_option('thp_db_version');
