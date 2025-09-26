<?php
/**
 * Fired during plugin deactivation
 *
 * @since      1.0.0
 * @package    Community_X
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Community_X
 */
class Community_X_Deactivator {

    /**
     * Plugin deactivation tasks.
     *
     * Cleans up scheduled events and flushes rewrite rules.
     * Note: We don't remove data or pages on deactivation as users might reactivate.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Remove activation flag
        delete_option('community_x_activated');
        
        // Clear any cached data
        self::clear_cache();
    }

    /**
     * Clear all scheduled events.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function clear_scheduled_events() {
        // Clear daily cleanup
        $timestamp = wp_next_scheduled('community_x_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'community_x_daily_cleanup');
        }

        // Clear weekly digest
        $timestamp = wp_next_scheduled('community_x_weekly_digest');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'community_x_weekly_digest');
        }

        // Clear all scheduled events for this plugin
        wp_clear_scheduled_hook('community_x_daily_cleanup');
        wp_clear_scheduled_hook('community_x_weekly_digest');
    }

    /**
     * Clear cached data.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function clear_cache() {
        // Clear WordPress object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        // Clear any plugin-specific transients
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
    }

    /**
     * Clean up plugin data on uninstall.
     * This method is called from the uninstall.php file.
     *
     * @since    1.0.0
     */
    public static function uninstall() {
        // Remove plugin options
        self::remove_plugin_options();
        
        // Remove user roles and capabilities
        self::remove_user_roles();
        
        // Remove custom pages
        self::remove_custom_pages();
        
        // Drop database tables (optional - commented out by default)
        // self::drop_database_tables();
    }

    /**
     * Remove all plugin options.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function remove_plugin_options() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                'community_x_%'
            )
        );
    }

    /**
     * Remove custom user roles.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function remove_user_roles() {
        // Remove custom roles
        remove_role('community_member');
        remove_role('community_moderator');

        // Remove capabilities from administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $capabilities = array(
                'community_manage_settings',
                'community_manage_users',
                'community_view_analytics',
                'community_moderate_all',
                'community_create_post',
                'community_edit_any_post',
                'community_delete_any_post',
                'community_moderate_posts',
                'community_moderate_comments',
                'community_ban_users'
            );
            
            foreach ($capabilities as $capability) {
                $admin_role->remove_cap($capability);
            }
        }
    }

    /**
     * Remove custom pages created by the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function remove_custom_pages() {
        $page_ids = array(
            get_option('community_x_community_page_id'),
            get_option('community_x_dashboard_page_id'),
            get_option('community_x_directory_page_id')
        );

        foreach ($page_ids as $page_id) {
            if ($page_id && is_numeric($page_id)) {
                wp_delete_post($page_id, true);
            }
        }
    }

    /**
     * Drop database tables.
     * Uncomment this method call in uninstall() if you want to remove all data.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function drop_database_tables() {
        require_once COMMUNITY_X_PLUGIN_PATH . 'includes/class-community-x-database.php';
        
        $database = new Community_X_Database();
        $database->drop_tables();
    }
}