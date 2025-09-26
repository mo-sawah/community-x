<?php
/**
 * User Interactions Management
 *
 * @since      1.0.0
 * @package    Community_X
 */

class Community_X_Interactions {

    /**
     * Initialize interactions functionality
     */
    public static function init() {
        add_action('wp_ajax_community_x_like_post', array(__CLASS__, 'ajax_like_post'));
        add_action('wp_ajax_community_x_unlike_post', array(__CLASS__, 'ajax_unlike_post'));
        add_action('wp_ajax_community_x_bookmark_post', array(__CLASS__, 'ajax_bookmark_post'));
        add_action('wp_ajax_community_x_unbookmark_post', array(__CLASS__, 'ajax_unbookmark_post'));
        add_action('wp_ajax_community_x_follow_user', array(__CLASS__, 'ajax_follow_user'));
        add_action('wp_ajax_community_x_unfollow_user', array(__CLASS__, 'ajax_unfollow_user'));
    }

    /**
     * Like a post
     */
    public static function like_post($post_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id || !current_user_can('community_like')) {
            return false;
        }

        global $wpdb;
        $interactions_table = $wpdb->prefix . 'community_x_interactions';
        $posts_table = $wpdb->prefix . 'community_x_posts';

        // Check if already liked
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $interactions_table 
             WHERE user_id = %d AND object_id = %d 
             AND object_type = 'post' AND interaction_type = 'like'",
            $user_id, $post_id
        ));

        if ($existing) {
            return false; // Already liked
        }

        // Insert like
        $result = $wpdb->insert(
            $interactions_table,
            array(
                'user_id' => $user_id,
                'object_id' => $post_id,
                'object_type' => 'post',
                'interaction_type' => 'like',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );

        if ($result) {
            // Update post like count
            $wpdb->query($wpdb->prepare(
                "UPDATE $posts_table SET like_count = like_count + 1 WHERE id = %d",
                $post_id
            ));

            // Log activity
            Community_X_User::log_user_activity($user_id, 'post_liked', $post_id, 'post');

            // Create notification for post author
            $post = Community_X_Post::get_post($post_id);
            if ($post && $post['author_id'] != $user_id) {
                self::create_notification($post['author_id'], 'post_liked', array(
                    'actor_id' => $user_id,
                    'post_id' => $post_id,
                    'post_title' => $post['title']
                ));
            }

            return true;
        }

        return false;
    }

    /**
     * Unlike a post
     */
    public static function unlike_post($post_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        global $wpdb;
        $interactions_table = $wpdb->prefix . 'community_x_interactions';
        $posts_table = $wpdb->prefix . 'community_x_posts';

        $result = $wpdb->delete(
            $interactions_table,
            array(
                'user_id' => $user_id,
                'object_id' => $post_id,
                'object_type' => 'post',
                'interaction_type' => 'like'
            ),
            array('%d', '%d', '%s', '%s')
        );

        if ($result) {
            // Update post like count
            $wpdb->query($wpdb->prepare(
                "UPDATE $posts_table SET like_count = GREATEST(like_count - 1, 0) WHERE id = %d",
                $post_id
            ));

            // Log activity
            Community_X_User::log_user_activity($user_id, 'post_unliked', $post_id, 'post');

            return true;
        }

        return false;
    }

    /**
     * Check if user has liked a post
     */
    public static function has_user_liked_post($post_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        global $wpdb;
        $interactions_table = $wpdb->prefix . 'community_x_interactions';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $interactions_table 
             WHERE user_id = %d AND object_id = %d 
             AND object_type = 'post' AND interaction_type = 'like'",
            $user_id, $post_id
        ));

        return $count > 0;
    }

    /**
     * Bookmark a post
     */
    public static function bookmark_post($post_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        global $wpdb;
        $interactions_table = $wpdb->prefix . 'community_x_interactions';

        // Check if already bookmarked
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $interactions_table 
             WHERE user_id = %d AND object_id = %d 
             AND object_type = 'post' AND interaction_type = 'bookmark'",
            $user_id, $post_id
        ));

        if ($existing) {
            return false;
        }

        $result = $wpdb->insert(
            $interactions_table,
            array(
                'user_id' => $user_id,
                'object_id' => $post_id,
                'object_type' => 'post',
                'interaction_type' => 'bookmark',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );

        if ($result) {
            Community_X_User::log_user_activity($user_id, 'post_bookmarked', $post_id, 'post');
            return true;
        }

        return false;
    }

    /**
     * Get user's activity feed
     */
    public static function get_user_activity_feed($user_id, $limit = 20) {
        global $wpdb;
        
        $activity_table = $wpdb->prefix . 'community_x_activity';
        $posts_table = $wpdb->prefix . 'community_x_posts';
        $users_table = $wpdb->users;
        
        // Get following list
        $following_ids = self::get_user_following_ids($user_id);
        $following_ids[] = $user_id; // Include own activities
        
        if (empty($following_ids)) {
            return array();
        }
        
        $following_list = implode(',', array_map('intval', $following_ids));
        
        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name as user_name, u.user_login,
                    p.title as post_title, p.id as post_id
             FROM $activity_table a
             LEFT JOIN $users_table u ON a.user_id = u.ID
             LEFT JOIN $posts_table p ON a.object_id = p.id AND a.object_type = 'post'
             WHERE a.user_id IN ($following_list)
             AND a.action IN ('post_created', 'post_liked', 'user_followed')
             ORDER BY a.created_at DESC
             LIMIT %d",
            $limit
        ), ARRAY_A);
        
        return $activities;
    }

    /**
     * Get list of user IDs that a user is following
     */
    public static function get_user_following_ids($user_id) {
        global $wpdb;
        $interactions_table = $wpdb->prefix . 'community_x_interactions';
        
        $following_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT object_id FROM $interactions_table 
             WHERE user_id = %d AND object_type = 'user' AND interaction_type = 'follow'",
            $user_id
        ));
        
        return array_map('intval', $following_ids);
    }

    /**
     * Create notification
     */
    private static function create_notification($user_id, $type, $data) {
        global $wpdb;
        $notifications_table = $wpdb->prefix . 'community_x_notifications';
        
        $actor_user = get_user_by('id', $data['actor_id']);
        if (!$actor_user) return;
        
        $title = '';
        $content = '';
        $action_url = '';
        
        switch ($type) {
            case 'post_liked':
                $title = __('Your post was liked', 'community-x');
                $content = sprintf(__('%s liked your post "%s"'), $actor_user->display_name, $data['post_title']);
                $action_url = home_url('/community/post/' . $data['post_id'] . '/');
                break;
                
            case 'user_followed':
                $title = __('New follower', 'community-x');
                $content = sprintf(__('%s started following you'), $actor_user->display_name);
                $action_url = home_url('/community/member/' . $actor_user->user_login . '/');
                break;
        }
        
        if ($title && $content) {
            $wpdb->insert(
                $notifications_table,
                array(
                    'user_id' => $user_id,
                    'type' => $type,
                    'title' => $title,
                    'content' => $content,
                    'action_url' => $action_url,
                    'data' => json_encode($data),
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
    }

    /**
     * AJAX Handlers
     */
    public static function ajax_like_post() {
        check_ajax_referer('community_x_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to like posts.', 'community-x'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (self::has_user_liked_post($post_id)) {
            // Unlike the post
            if (self::unlike_post($post_id)) {
                wp_send_json_success(array(
                    'action' => 'unliked',
                    'message' => __('Post unliked', 'community-x')
                ));
            }
        } else {
            // Like the post
            if (self::like_post($post_id)) {
                wp_send_json_success(array(
                    'action' => 'liked',
                    'message' => __('Post liked!', 'community-x')
                ));
            }
        }
        
        wp_send_json_error(__('Action failed. Please try again.', 'community-x'));
    }

    public static function ajax_bookmark_post() {
        check_ajax_referer('community_x_public_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to bookmark posts.', 'community-x'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (self::bookmark_post($post_id)) {
            wp_send_json_success(array(
                'message' => __('Post bookmarked!', 'community-x')
            ));
        } else {
            wp_send_json_error(__('Failed to bookmark post.', 'community-x'));
        }
    }
}

// Initialize interactions
Community_X_Interactions::init();