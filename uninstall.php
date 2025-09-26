<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @since      1.0.0
 * @package    Community_X
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Only proceed if user has proper permissions
 */
if (!current_user_can('activate_plugins')) {
    exit;
}

/**
 * Check if this is the correct plugin being uninstalled
 */
if (__FILE__ != WP_UNINSTALL_PLUGIN) {
    exit;
}

/**
 * Define plugin constants if not already defined
 */
if (!defined('COMMUNITY_X_PLUGIN_PATH')) {
    define('COMMUNITY_X_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

/**
 * Load required files
 */
require_once COMMUNITY_X_PLUGIN_PATH . 'includes/class-community-x-deactivator.php';

/**
 * Run uninstall cleanup
 * 
 * This will:
 * - Remove all plugin options
 * - Remove custom user roles and capabilities  
 * - Remove custom pages created by the plugin
 * - Optionally remove database tables (uncomment if desired)
 */
Community_X_Deactivator::uninstall();

/**
 * Additional cleanup for multisite
 */
if (is_multisite()) {
    // Get all sites in network
    $sites = get_sites(array(
        'number' => 0,
        'fields' => 'ids'
    ));

    foreach ($sites as $site_id) {
        switch_to_blog($site_id);
        
        // Remove plugin options for this site
        delete_option('community_x_settings');
        delete_option('community_x_community_page_id');
        delete_option('community_x_dashboard_page_id');
        delete_option('community_x_directory_page_id');
        delete_option('community_x_db_version');
        delete_option('community_x_activated');
        
        // Remove any transients
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_community_x_%'
            )
        );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_community_x_%'
            )
        );
        
        restore_current_blog();
    }
}

/**
 * Clear any cached data
 */
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

/**
 * Log uninstall for debugging (optional)
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Community X plugin has been completely uninstalled.');
}