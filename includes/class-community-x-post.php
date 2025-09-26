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
    public static function create_post($data) {
        global $wpdb;

        if (!current_user_can('community_create_post')) {
            return new WP_Error('permission_denied', __('You do not have permission to create posts.', 'community-x'));
        }

        $table_name = $wpdb->prefix . 'community_x_posts';

        $post_data = array(
            'author_id'     => get_current_user_id(),
            'title'         => sanitize_text_field($data['title']),
            'content'       => wp_kses_post($data['content']),
            'category_id'   => isset($data['category_id']) ? intval($data['category_id']) : 0,
            'tags'          => isset($data['tags']) ? json_encode(array_map('sanitize_text_field', $data['tags'])) : json_encode([]),
            'status'        => get_option('community_x_moderate_all_posts', 0) ? 'pending' : 'published',
            'created_at'    => current_time('mysql'),
            'updated_at'    => current_time('mysql'),
        );

        $result = $wpdb->insert($table_name, $post_data);

        if ($result === false) {
            return new WP_Error('db_error', __('Could not insert post into the database.', 'community-x'));
        }

        $post_id = $wpdb->insert_id;

        // Log activity
        Community_X_User::log_user_activity($post_data['author_id'], 'post_created', $post_id, 'post');

        return $post_id;
    }

    /**
     * Get a single post by its ID.
     *
     * @param int $post_id The ID of the post.
     * @return array|null Post data as an associative array, or null if not found.
     */
    public static function get_post($post_id) {
        global $wpdb;
        $posts_table = $wpdb->prefix . 'community_x_posts';
        $users_table = $wpdb->users;
        $profiles_table = $wpdb->prefix . 'community_x_user_profiles';
        $categories_table = $wpdb->prefix . 'community_x_categories';

        $sql = $wpdb->prepare("
            SELECT 
                p.*, 
                u.display_name as author_name, 
                u.user_login as author_login,
                prof.location as author_location,
                cat.name as category_name,
                cat.slug as category_slug,
                cat.color as category_color
            FROM $posts_table p
            LEFT JOIN $users_table u ON p.author_id = u.ID
            LEFT JOIN $profiles_table prof ON p.author_id = prof.user_id
            LEFT JOIN $categories_table cat ON p.category_id = cat.id
            WHERE p.id = %d
        ", $post_id);

        $post = $wpdb->get_row($sql, ARRAY_A);

        if ($post) {
            $post['tags'] = !empty($post['tags']) ? json_decode($post['tags'], true) : [];
        }

        return $post;
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