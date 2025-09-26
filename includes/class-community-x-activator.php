<?php
/**
 * Fired during plugin activation
 *
 * @since      1.0.0
 * @package    Community_X
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Community_X
 */
class Community_X_Activator {

    /**
     * Plugin activation tasks.
     *
     * Creates database tables, sets default options, creates pages, and flushes rewrite rules.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create database tables
        self::create_database_tables();
        
        // Set default plugin options
        self::set_default_options();
        
        // Create community pages
        self::create_community_pages();
        
        // Create user roles
        self::create_user_roles();
        
        // Schedule events
        self::schedule_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        add_option('community_x_activated', true);
    }

    /**
     * Create database tables for the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function create_database_tables() {
        require_once COMMUNITY_X_PLUGIN_PATH . 'includes/class-community-x-database.php';
        
        $database = new Community_X_Database();
        $database->create_tables();
    }

    /**
     * Set default plugin options.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function set_default_options() {
        $default_options = array(
            'community_name' => 'Community X',
            'community_description' => 'A modern, engaging community platform',
            'allow_public_viewing' => 1,
            'allow_user_registration' => 1,
            'require_email_verification' => 1,
            'enable_profile_pictures' => 1,
            'enable_private_messaging' => 1,
            'enable_groups' => 1,
            'enable_notifications' => 1,
            'moderate_all_posts' => 0,
            'enable_spam_protection' => 1,
            'posts_per_page' => 10,
            'enable_frontend_submission' => 1,
            'default_user_role' => 'community_member',
            'community_page_id' => 0,
            'dashboard_page_id' => 0
        );

        foreach ($default_options as $key => $value) {
            add_option('community_x_' . $key, $value);
        }
    }

    /**
     * Create community pages.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function create_community_pages() {
        // Create main community page
        $community_page = wp_insert_post(array(
            'post_title'   => 'Community',
            'post_content' => '[community_x_main]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => 'community'
        ));

        if ($community_page && !is_wp_error($community_page)) {
            update_option('community_x_community_page_id', $community_page);
        }

        // Create member dashboard page
        $dashboard_page = wp_insert_post(array(
            'post_title'   => 'Community Dashboard',
            'post_content' => '[community_x_dashboard]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => 'community-dashboard'
        ));

        if ($dashboard_page && !is_wp_error($dashboard_page)) {
            update_option('community_x_dashboard_page_id', $dashboard_page);
        }

        // Create member directory page
        $directory_page = wp_insert_post(array(
            'post_title'   => 'Members',
            'post_content' => '[community_x_members]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => 'members'
        ));

        if ($directory_page && !is_wp_error($directory_page)) {
            update_option('community_x_directory_page_id', $directory_page);
        }
    }

    /**
     * Create custom user roles.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function create_user_roles() {
        // Community Member role
        add_role('community_member', 'Community Member', array(
            'read' => true,
            'community_create_post' => true,
            'community_edit_own_post' => true,
            'community_delete_own_post' => true,
            'community_comment' => true,
            'community_like' => true,
            'community_follow' => true,
            'community_message' => true,
            'community_edit_profile' => true
        ));

        // Community Moderator role
        add_role('community_moderator', 'Community Moderator', array(
            'read' => true,
            'community_create_post' => true,
            'community_edit_own_post' => true,
            'community_edit_any_post' => true,
            'community_delete_own_post' => true,
            'community_delete_any_post' => true,
            'community_moderate_posts' => true,
            'community_moderate_comments' => true,
            'community_ban_users' => true,
            'community_comment' => true,
            'community_like' => true,
            'community_follow' => true,
            'community_message' => true,
            'community_edit_profile' => true
        ));

        // Add capabilities to administrator
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
                $admin_role->add_cap($capability);
            }
        }
    }

    /**
     * Schedule plugin events.
     *
     * @since    1.0.0
     * @access   private
     */
    private static function schedule_events() {
        // Schedule daily cleanup
        if (!wp_next_scheduled('community_x_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'community_x_daily_cleanup');
        }

        // Schedule weekly digest
        if (!wp_next_scheduled('community_x_weekly_digest')) {
            wp_schedule_event(time(), 'weekly', 'community_x_weekly_digest');
        }
    }
}