<?php
/**
 * Advanced Search Functionality
 *
 * @since      1.0.0
 * @package    Community_X
 */

class Community_X_Search {

    /**
     * Initialize search functionality
     */
    public static function init() {
        add_action('wp_ajax_community_x_search', array(__CLASS__, 'ajax_search'));
        add_action('wp_ajax_nopriv_community_x_search', array(__CLASS__, 'ajax_search'));
        add_action('wp_ajax_community_x_search_suggestions', array(__CLASS__, 'ajax_search_suggestions'));
        add_action('wp_ajax_nopriv_community_x_search_suggestions', array(__CLASS__, 'ajax_search_suggestions'));
    }

    /**
     * Perform comprehensive search
     */
    public static function search($query, $filters = array()) {
        $defaults = array(
            'type' => 'all', // all, posts, users, categories
            'category_id' => null,
            'author_id' => null,
            'date_from' => null,
            'date_to' => null,
            'tags' => array(),
            'sort_by' => 'relevance', // relevance, date, popularity
            'per_page' => 20,
            'page' => 1
        );
        
        $filters = wp_parse_args($filters, $defaults);
        $results = array();
        
        if ($filters['type'] === 'all' || $filters['type'] === 'posts') {
            $results['posts'] = self::search_posts($query, $filters);
        }
        
        if ($filters['type'] === 'all' || $filters['type'] === 'users') {
            $results['users'] = self::search_users($query, $filters);
        }
        
        if ($filters['type'] === 'all' || $filters['type'] === 'categories') {
            $results['categories'] = self::search_categories($query);
        }
        
        return $results;
    }

    /**
     * Search posts with advanced filtering
     */
    public static function search_posts($query, $filters = array()) {
        global $wpdb;
        
        $posts_table = $wpdb->prefix . 'community_x_posts';
        $users_table = $wpdb->users;
        $categories_table = $wpdb->prefix . 'community_x_categories';
        
        $where_conditions = array("p.status = 'published'");
        $where_values = array();
        
        // Text search
        if (!empty($query)) {
            $search_term = '%' . $wpdb->esc_like($query) . '%';
            $where_conditions[] = "(p.title LIKE %s OR p.content LIKE %s OR p.tags LIKE %s)";
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        // Category filter
        if (!empty($filters['category_id'])) {
            $where_conditions[] = "p.category_id = %d";
            $where_values[] = $filters['category_id'];
        }
        
        // Author filter
        if (!empty($filters['author_id'])) {
            $where_conditions[] = "p.author_id = %d";
            $where_values[] = $filters['author_id'];
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "p.created_at >= %s";
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "p.created_at <= %s";
            $where_values[] = $filters['date_to'];
        }
        
        // Tags filter
        if (!empty($filters['tags']) && is_array($filters['tags'])) {
            $tag_conditions = array();
            foreach ($filters['tags'] as $tag) {
                $tag_conditions[] = "p.tags LIKE %s";
                $where_values[] = '%' . $wpdb->esc_like($tag) . '%';
            }
            if (!empty($tag_conditions)) {
                $where_conditions[] = '(' . implode(' OR ', $tag_conditions) . ')';
            }
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Order by
        $order_clause = "p.created_at DESC";
        switch ($filters['sort_by']) {
            case 'popularity':
                $order_clause = "p.like_count DESC, p.view_count DESC";
                break;
            case 'date':
                $order_clause = "p.created_at DESC";
                break;
            case 'relevance':
                if (!empty($query)) {
                    // Simple relevance scoring
                    $order_clause = "
                        (CASE 
                            WHEN p.title LIKE %s THEN 3
                            WHEN p.content LIKE %s THEN 2
                            WHEN p.tags LIKE %s THEN 1
                            ELSE 0
                        END) DESC, p.created_at DESC";
                    $relevance_term = '%' . $wpdb->esc_like($query) . '%';
                    array_unshift($where_values, $relevance_term, $relevance_term, $relevance_term);
                }
                break;
        }
        
        // Pagination
        $offset = ($filters['page'] - 1) * $filters['per_page'];
        $limit = $filters['per_page'];
        
        $sql = "
            SELECT p.*, u.display_name as author_name, u.user_login as author_login,
                   c.name as category_name, c.color as category_color, c.icon as category_icon
            FROM $posts_table p
            LEFT JOIN $users_table u ON p.author_id = u.ID
            LEFT JOIN $categories_table c ON p.category_id = c.id
            WHERE $where_clause
            ORDER BY $order_clause
            LIMIT $limit OFFSET $offset
        ";
        
        if (!empty($where_values)) {
            $results = $wpdb->get_results($wpdb->prepare($sql, $where_values), ARRAY_A);
        } else {
            $results = $wpdb->get_results($sql, ARRAY_A);
        }
        
        // Process results
        foreach ($results as &$post) {
            if (!empty($post['tags'])) {
                $post['tags'] = json_decode($post['tags'], true) ?: array();
            }
            
            // Add search relevance highlighting
            if (!empty($query)) {
                $post['highlighted_title'] = self::highlight_search_terms($post['title'], $query);
                $post['highlighted_excerpt'] = self::highlight_search_terms(
                    wp_trim_words(strip_tags($post['content']), 30),
                    $query
                );
            }
        }
        
        return $results;
    }

    /**
     * Search users
     */
    public static function search_users($query, $filters = array()) {
        $search_args = array(
            'search' => $query,
            'per_page' => $filters['per_page'],
            'page' => $filters['page'],
            'public_only' => !is_user_logged_in()
        );
        
        return Community_X_User::search_users($search_args);
    }

    /**
     * Search categories
     */
    public static function search_categories($query) {
        global $wpdb;
        $categories_table = $wpdb->prefix . 'community_x_categories';
        
        if (empty($query)) {
            return Community_X_Category::get_all();
        }
        
        $search_term = '%' . $wpdb->esc_like($query) . '%';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $categories_table 
             WHERE (name LIKE %s OR description LIKE %s) 
             AND is_active = 1
             ORDER BY name ASC",
            $search_term, $search_term
        ), ARRAY_A);
    }

    /**
     * Get search suggestions
     */
    public static function get_suggestions($query, $limit = 5) {
        global $wpdb;
        
        $suggestions = array();
        
        // Recent popular searches (you'd implement this with a searches table)
        
        // Popular tags
        $posts_table = $wpdb->prefix . 'community_x_posts';
        $tags_data = $wpdb->get_results(
            "SELECT tags FROM $posts_table 
             WHERE tags IS NOT NULL AND tags != '' AND tags != '[]'
             ORDER BY like_count DESC, created_at DESC
             LIMIT 100",
            ARRAY_A
        );
        
        $all_tags = array();
        foreach ($tags_data as $row) {
            $tags = json_decode($row['tags'], true);
            if (is_array($tags)) {
                $all_tags = array_merge($all_tags, $tags);
            }
        }
        
        $tag_counts = array_count_values($all_tags);
        arsort($tag_counts);
        
        foreach ($tag_counts as $tag => $count) {
            if (stripos($tag, $query) !== false) {
                $suggestions[] = array(
                    'type' => 'tag',
                    'text' => $tag,
                    'count' => $count
                );
                
                if (count($suggestions) >= $limit) break;
            }
        }
        
        return $suggestions;
    }

    /**
     * Highlight search terms in text
     */
    private static function highlight_search_terms($text, $query) {
        $terms = explode(' ', $query);
        
        foreach ($terms as $term) {
            if (strlen(trim($term)) > 2) {
                $text = preg_replace(
                    '/(' . preg_quote(trim($term), '/') . ')/i',
                    '<mark>$1</mark>',
                    $text
                );
            }
        }
        
        return $text;
    }

    /**
     * AJAX search handler
     */
    public static function ajax_search() {
        $query = sanitize_text_field($_POST['query'] ?? '');
        $type = sanitize_text_field($_POST['type'] ?? 'all');
        $page = max(1, intval($_POST['page'] ?? 1));
        
        $filters = array(
            'type' => $type,
            'page' => $page,
            'per_page' => 10
        );
        
        // Additional filters
        if (!empty($_POST['category_id'])) {
            $filters['category_id'] = intval($_POST['category_id']);
        }
        
        if (!empty($_POST['sort_by'])) {
            $filters['sort_by'] = sanitize_text_field($_POST['sort_by']);
        }
        
        $results = self::search($query, $filters);
        
        wp_send_json_success($results);
    }

    /**
     * AJAX suggestions handler
     */
    public static function ajax_search_suggestions() {
        $query = sanitize_text_field($_POST['query'] ?? '');
        
        if (strlen($query) < 2) {
            wp_send_json_success(array());
        }
        
        $suggestions = self::get_suggestions($query);
        
        wp_send_json_success($suggestions);
    }
}

// Initialize search
Community_X_Search::init();