<?php
/**
 * Post management functionality
 *
 * @since      1.0.0
 * @package    Community_X
 */

class Community_X_Post {

    /**
     * Create a new community post.
     *
     * @param array $data Post data.
     * @return int|WP_Error The new post ID on success, or WP_Error on failure.
     */
    /**
     * Enhanced post creation with better validation and security
     */
    public static function create_post($data) {
        global $wpdb;

        if (!current_user_can('community_create_post')) {
            return new WP_Error('permission_denied', __('You do not have permission to create posts.', 'community-x'));
        }

        // Enhanced validation
        if (empty($data['title']) || strlen(trim($data['title'])) < 3) {
            return new WP_Error('invalid_title', __('Title must be at least 3 characters long.', 'community-x'));
        }

        if (empty($data['content']) || strlen(trim($data['content'])) < 10) {
            return new WP_Error('invalid_content', __('Content must be at least 10 characters long.', 'community-x'));
        }

        if (strlen($data['title']) > 200) {
            return new WP_Error('title_too_long', __('Title must be less than 200 characters.', 'community-x'));
        }

        // Check for spam (basic implementation)
        if (self::is_spam_content($data['title'], $data['content'])) {
            return new WP_Error('spam_detected', __('Your post appears to be spam. Please review and try again.', 'community-x'));
        }

        // Check rate limiting
        if (!self::check_rate_limit(get_current_user_id())) {
            return new WP_Error('rate_limit', __('You are posting too frequently. Please wait a moment before posting again.', 'community-x'));
        }

        $table_name = $wpdb->prefix . 'community_x_posts';

        // Determine status based on moderation settings
        $status = 'published';
        if (get_option('community_x_moderate_all_posts', 0) && !current_user_can('community_moderate_posts')) {
            $status = 'pending';
        }

        $post_data = array(
            'author_id'     => get_current_user_id(),
            'title'         => sanitize_text_field($data['title']),
            'content'       => wp_kses_post($data['content']),
            'excerpt'       => wp_trim_words(strip_tags($data['content']), 30),
            'category_id'   => isset($data['category_id']) ? intval($data['category_id']) : 0,
            'tags'          => isset($data['tags']) && is_array($data['tags']) ? 
                            json_encode(array_map('sanitize_text_field', $data['tags'])) : 
                            json_encode([]),
            'status'        => $status,
            'created_at'    => current_time('mysql'),
            'updated_at'    => current_time('mysql'),
        );

        $result = $wpdb->insert($table_name, $post_data);

        if ($result === false) {
            return new WP_Error('db_error', __('Could not insert post into the database.', 'community-x'));
        }

        $post_id = $wpdb->insert_id;

        // Update category post count
        if ($post_data['category_id'] > 0) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}community_x_categories SET post_count = post_count + 1 WHERE id = %d",
                $post_data['category_id']
            ));
        }

        // Log activity
        Community_X_User::log_user_activity($post_data['author_id'], 'post_created', $post_id, 'post');

        // Send notification to moderators if pending
        if ($status === 'pending') {
            self::notify_moderators_new_post($post_id);
        }

        return $post_id;
    }

    /**
     * Basic spam detection
     */
    private static function is_spam_content($title, $content) {
        // Implement basic spam detection
        $spam_keywords = array('viagra', 'casino', 'lottery', 'winner', 'congratulations');
        $text = strtolower($title . ' ' . $content);
        
        foreach ($spam_keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }
        
        // Check for excessive links
        if (substr_count($content, 'http') > 3) {
            return true;
        }
        
        return false;
    }

    /**
     * Check rate limiting for post creation
     */
    private static function check_rate_limit($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'community_x_posts';
        
        // Check posts in last 5 minutes
        $recent_posts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE author_id = %d AND created_at > %s",
            $user_id,
            date('Y-m-d H:i:s', strtotime('-5 minutes'))
        ));
        
        // Allow max 3 posts per 5 minutes
        return $recent_posts < 3;
    }

    /**
     * Notify moderators of new pending post
     */
    private static function notify_moderators_new_post($post_id) {
        // Get all moderators
        $moderators = get_users(array(
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key'     => 'wp_capabilities',
                    'value'   => 'community_moderator',
                    'compare' => 'LIKE'
                ),
                array(
                    'key'     => 'wp_capabilities',
                    'value'   => 'administrator',
                    'compare' => 'LIKE'
                )
            )
        ));
        
        $post = self::get_post($post_id);
        if (!$post) return;
        
        foreach ($moderators as $moderator) {
            // You could send email notifications here
            // For now, we'll just create a database notification
            global $wpdb;
            $notifications_table = $wpdb->prefix . 'community_x_notifications';
            
            $wpdb->insert($notifications_table, array(
                'user_id' => $moderator->ID,
                'type' => 'post_pending',
                'title' => __('New post pending review', 'community-x'),
                'content' => sprintf(__('"%s" by %s needs review'), $post['title'], $post['author_name']),
                'action_url' => admin_url('admin.php?page=community-x-posts&status=pending'),
                'created_at' => current_time('mysql')
            ));
        }
    }

    /**
     * Get a list of posts.
     *
     * @param array $args Arguments for filtering and pagination.
     * @return array Array of post objects.
     */
    public static function get_posts($args = []) {
        global $wpdb;
        
        $defaults = [
            'per_page' => get_option('community_x_posts_per_page', 10),
            'page' => 1,
            'category_id' => null,
            'author_id' => null,
            'search' => '',
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];
        $args = wp_parse_args($args, $defaults);

        $posts_table = $wpdb->prefix . 'community_x_posts';
        $users_table = $wpdb->users;
        $categories_table = $wpdb->prefix . 'community_x_categories';

        $sql = "
            SELECT p.*, u.display_name as author_name, u.user_login as author_login, cat.name as category_name, cat.slug as category_slug, cat.color as category_color
            FROM $posts_table p
            LEFT JOIN $users_table u ON p.author_id = u.ID
            LEFT JOIN $categories_table cat ON p.category_id = cat.id
            WHERE p.status = 'published'
        ";

        if (!empty($args['category_id'])) {
            $sql .= $wpdb->prepare(" AND p.category_id = %d", $args['category_id']);
        }
        if (!empty($args['author_id'])) {
            $sql .= $wpdb->prepare(" AND p.author_id = %d", $args['author_id']);
        }
        if (!empty($args['search'])) {
            $sql .= $wpdb->prepare(" AND (p.title LIKE %s OR p.content LIKE %s)", '%' . $wpdb->esc_like($args['search']) . '%', '%' . $wpdb->esc_like($args['search']) . '%');
        }

        $sql .= " ORDER BY p." . esc_sql($args['orderby']) . " " . esc_sql($args['order']);
        $offset = ($args['page'] - 1) * $args['per_page'];
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['per_page'], $offset);

        $results = $wpdb->get_results($sql, ARRAY_A);
        
        foreach ($results as &$row) {
            $row['tags'] = !empty($row['tags']) ? json_decode($row['tags'], true) : [];
        }

        return $results;
    }
    
    /**
     * Count total posts matching criteria.
     *
     * @param array $args
     * @return int
     */
    public static function count_posts($args = []) {
        global $wpdb;
        $posts_table = $wpdb->prefix . 'community_x_posts';

        $sql = "SELECT COUNT(id) FROM $posts_table WHERE status = 'published'";

        if (!empty($args['category_id'])) {
            $sql .= $wpdb->prepare(" AND category_id = %d", $args['category_id']);
        }
        
        return (int) $wpdb->get_var($sql);
    }
}