<?php
/**
 * Database management for Community X
 *
 * @since      1.0.0
 * @package    Community_X
 */

/**
 * Handle all database operations for the plugin.
 *
 * @since      1.0.0
 * @package    Community_X
 */
class Community_X_Database {

    /**
     * Create database tables for the plugin.
     *
     * @since    1.0.0
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // User profiles extended data
        $table_user_profiles = $wpdb->prefix . 'community_x_user_profiles';
        $sql_profiles = "CREATE TABLE $table_user_profiles (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            bio text,
            location varchar(255),
            website varchar(255),
            social_links longtext,
            skills longtext,
            interests longtext,
            cover_image varchar(255),
            privacy_settings longtext,
            is_public tinyint(1) DEFAULT 1,
            points int DEFAULT 0,
            level varchar(50) DEFAULT 'Newbie',
            badge_count int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";

        // Community posts
        $table_posts = $wpdb->prefix . 'community_x_posts';
        $sql_posts = "CREATE TABLE $table_posts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            author_id bigint(20) NOT NULL,
            title text NOT NULL,
            content longtext NOT NULL,
            excerpt text,
            post_type varchar(50) DEFAULT 'discussion',
            category_id bigint(20),
            tags longtext,
            featured_image varchar(255),
            status varchar(20) DEFAULT 'published',
            is_public tinyint(1) DEFAULT 1,
            is_pinned tinyint(1) DEFAULT 0,
            is_locked tinyint(1) DEFAULT 0,
            view_count bigint(20) DEFAULT 0,
            like_count bigint(20) DEFAULT 0,
            comment_count bigint(20) DEFAULT 0,
            share_count bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY author_id (author_id),
            KEY category_id (category_id),
            KEY status (status),
            KEY is_public (is_public),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Post categories
        $table_categories = $wpdb->prefix . 'community_x_categories';
        $sql_categories = "CREATE TABLE $table_categories (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text,
            icon varchar(255),
            color varchar(7),
            parent_id bigint(20) DEFAULT 0,
            post_count bigint(20) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            sort_order int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY parent_id (parent_id),
            KEY is_active (is_active)
        ) $charset_collate;";

        // User interactions (likes, follows, bookmarks)
        $table_interactions = $wpdb->prefix . 'community_x_interactions';
        $sql_interactions = "CREATE TABLE $table_interactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            object_id bigint(20) NOT NULL,
            object_type varchar(50) NOT NULL,
            interaction_type varchar(50) NOT NULL,
            value varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_interaction (user_id, object_id, object_type, interaction_type),
            KEY user_id (user_id),
            KEY object_id (object_id),
            KEY object_type (object_type),
            KEY interaction_type (interaction_type)
        ) $charset_collate;";

        // Notifications
        $table_notifications = $wpdb->prefix . 'community_x_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            content text,
            action_url varchar(255),
            is_read tinyint(1) DEFAULT 0,
            data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            read_at datetime,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY type (type),
            KEY is_read (is_read),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Activity log
        $table_activity = $wpdb->prefix . 'community_x_activity';
        $sql_activity = "CREATE TABLE $table_activity (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            action varchar(100) NOT NULL,
            object_id bigint(20),
            object_type varchar(50),
            description text,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY action (action),
            KEY object_id (object_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Execute table creation
        dbDelta($sql_profiles);
        dbDelta($sql_posts);
        dbDelta($sql_categories);
        dbDelta($sql_interactions);
        dbDelta($sql_notifications);
        dbDelta($sql_activity);

        // Insert default categories
        $this->insert_default_categories();

        // Set database version
        update_option('community_x_db_version', '1.0.0');
    }

    /**
     * Insert default categories.
     *
     * @since    1.0.0
     * @access   private
     */
    private function insert_default_categories() {
        global $wpdb;

        $table_categories = $wpdb->prefix . 'community_x_categories';

        $default_categories = array(
            array(
                'name' => 'General Discussion',
                'slug' => 'general-discussion',
                'description' => 'General community discussions and topics',
                'icon' => 'fas fa-comments',
                'color' => '#6366f1',
                'sort_order' => 1
            ),
            array(
                'name' => 'Web Development',
                'slug' => 'web-development',
                'description' => 'Web development topics, tutorials, and discussions',
                'icon' => 'fas fa-code',
                'color' => '#10b981',
                'sort_order' => 2
            ),
            array(
                'name' => 'Design',
                'slug' => 'design',
                'description' => 'UI/UX design, graphics, and creative discussions',
                'icon' => 'fas fa-palette',
                'color' => '#f59e0b',
                'sort_order' => 3
            ),
            array(
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'Business strategies, entrepreneurship, and professional topics',
                'icon' => 'fas fa-briefcase',
                'color' => '#ef4444',
                'sort_order' => 4
            ),
            array(
                'name' => 'Technology',
                'slug' => 'technology',
                'description' => 'Latest tech news, gadgets, and innovations',
                'icon' => 'fas fa-microchip',
                'color' => '#8b5cf6',
                'sort_order' => 5
            ),
            array(
                'name' => 'Help & Support',
                'slug' => 'help-support',
                'description' => 'Get help and support from the community',
                'icon' => 'fas fa-question-circle',
                'color' => '#06b6d4',
                'sort_order' => 6
            )
        );

        foreach ($default_categories as $category) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_categories WHERE slug = %s",
                $category['slug']
            ));

            if (!$existing) {
                $wpdb->insert(
                    $table_categories,
                    $category,
                    array('%s', '%s', '%s', '%s', '%s', '%d')
                );
            }
        }
    }

    /**
     * Update database tables if needed.
     *
     * @since    1.0.0
     */
    public function update_tables() {
        $current_version = get_option('community_x_db_version', '0.0.0');
        
        if (version_compare($current_version, '1.0.0', '<')) {
            $this->create_tables();
        }
        
        // Future version updates can be handled here
        // if (version_compare($current_version, '1.1.0', '<')) {
        //     $this->update_to_v1_1_0();
        // }
    }

    /**
     * Drop all plugin tables.
     * Only called during uninstall.
     *
     * @since    1.0.0
     */
    public function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'community_x_activity',
            $wpdb->prefix . 'community_x_notifications', 
            $wpdb->prefix . 'community_x_interactions',
            $wpdb->prefix . 'community_x_categories',
            $wpdb->prefix . 'community_x_posts',
            $wpdb->prefix . 'community_x_user_profiles'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        delete_option('community_x_db_version');
    }

    /**
     * Get table name with prefix.
     *
     * @since    1.0.0
     * @param    string    $table_name    Table name without prefix.
     * @return   string                   Full table name with prefix.
     */
    public function get_table_name($table_name) {
        global $wpdb;
        return $wpdb->prefix . 'community_x_' . $table_name;
    }

    /**
     * Check if all required tables exist.
     *
     * @since    1.0.0
     * @return   bool    True if all tables exist, false otherwise.
     */
    public function tables_exist() {
        global $wpdb;

        $required_tables = array(
            'user_profiles',
            'posts', 
            'categories',
            'interactions',
            'notifications',
            'activity'
        );

        foreach ($required_tables as $table) {
            $table_name = $this->get_table_name($table);
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            
            if ($exists !== $table_name) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get database statistics.
     *
     * @since    1.0.0
     * @return   array    Array of database statistics.
     */
    public function get_stats() {
        global $wpdb;

        $stats = array();

        // Get user count
        $stats['total_users'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->users} u 
             JOIN {$wpdb->usermeta} um ON u.ID = um.user_id 
             WHERE um.meta_key = 'wp_capabilities' 
             AND (um.meta_value LIKE '%community_member%' OR um.meta_value LIKE '%community_moderator%')"
        );

        // Get posts count
        $posts_table = $this->get_table_name('posts');
        $stats['total_posts'] = $wpdb->get_var("SELECT COUNT(*) FROM $posts_table WHERE status = 'published'");
        $stats['pending_posts'] = $wpdb->get_var("SELECT COUNT(*) FROM $posts_table WHERE status = 'pending'");

        // Get interactions count
        $interactions_table = $this->get_table_name('interactions');
        $stats['total_likes'] = $wpdb->get_var("SELECT COUNT(*) FROM $interactions_table WHERE interaction_type = 'like'");
        $stats['total_follows'] = $wpdb->get_var("SELECT COUNT(*) FROM $interactions_table WHERE interaction_type = 'follow'");

        // Get notifications count
        $notifications_table = $this->get_table_name('notifications');
        $stats['unread_notifications'] = $wpdb->get_var("SELECT COUNT(*) FROM $notifications_table WHERE is_read = 0");

        // Get categories count
        $categories_table = $this->get_table_name('categories');
        $stats['total_categories'] = $wpdb->get_var("SELECT COUNT(*) FROM $categories_table WHERE is_active = 1");

        return $stats;
    }
}