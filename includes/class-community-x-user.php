<?php
/**
 * User management functionality
 *
 * @since      1.0.0
 * @package    Community_X
 */

/**
 * Handle all user-related functionality
 *
 * @since      1.0.0
 * @package    Community_X
 */
class Community_X_User {

    /**
     * Initialize user functionality
     *
     * @since    1.0.0
     */
    public static function init() {
        add_action('user_register', array(__CLASS__, 'handle_user_registration'));
        add_action('wp_ajax_community_x_update_profile', array(__CLASS__, 'ajax_update_profile'));
        add_action('wp_ajax_community_x_upload_avatar', array(__CLASS__, 'ajax_upload_avatar'));
        add_action('wp_ajax_nopriv_community_x_register', array(__CLASS__, 'ajax_register_user'));
        add_filter('wp_insert_user_data', array(__CLASS__, 'validate_user_data'), 10, 3);
    }

    /**
     * Handle user registration
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     */
    public static function handle_user_registration($user_id) {
        // Set default user role if enabled
        $default_role = get_option('community_x_default_user_role', 'community_member');
        
        if ($default_role === 'community_member') {
            $user = new WP_User($user_id);
            $user->set_role('community_member');
        }

        // Create user profile entry
        self::create_user_profile($user_id);

        // Send welcome email if enabled
        if (get_option('community_x_send_welcome_email', 1)) {
            self::send_welcome_email($user_id);
        }

        // Log registration activity
        self::log_user_activity($user_id, 'user_registered');
    }

    /**
     * Create user profile entry
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     */
    public static function create_user_profile($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'community_x_user_profiles';

        $default_profile = array(
            'user_id' => $user_id,
            'bio' => '',
            'location' => '',
            'website' => '',
            'social_links' => json_encode(array()),
            'skills' => json_encode(array()),
            'interests' => json_encode(array()),
            'cover_image' => '',
            'privacy_settings' => json_encode(array(
                'profile_visibility' => 'public',
                'email_visibility' => 'private',
                'activity_visibility' => 'public'
            )),
            'is_public' => 1,
            'points' => 0,
            'level' => 'Newbie',
            'badge_count' => 0
        );

        $wpdb->insert($table_name, $default_profile);
    }

    /**
     * Get user profile data
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   array|null         Profile data or null
     */
    public static function get_user_profile($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'community_x_user_profiles';

        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d",
            $user_id
        ), ARRAY_A);

        if ($profile) {
            // Decode JSON fields
            $profile['social_links'] = json_decode($profile['social_links'], true) ?: array();
            $profile['skills'] = json_decode($profile['skills'], true) ?: array();
            $profile['interests'] = json_decode($profile['interests'], true) ?: array();
            $profile['privacy_settings'] = json_decode($profile['privacy_settings'], true) ?: array();
        }

        return $profile;
    }

    /**
     * Update user profile
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID
     * @param    array    $data       Profile data
     * @return   bool                 Success status
     */
    public static function update_user_profile($user_id, $data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'community_x_user_profiles';

        // Sanitize data
        $sanitized_data = array();

        if (isset($data['bio'])) {
            $sanitized_data['bio'] = sanitize_textarea_field($data['bio']);
        }

        if (isset($data['location'])) {
            $sanitized_data['location'] = sanitize_text_field($data['location']);
        }

        if (isset($data['website'])) {
            $sanitized_data['website'] = esc_url_raw($data['website']);
        }

        if (isset($data['social_links']) && is_array($data['social_links'])) {
            $social_links = array();
            foreach ($data['social_links'] as $platform => $url) {
                $social_links[sanitize_key($platform)] = esc_url_raw($url);
            }
            $sanitized_data['social_links'] = json_encode($social_links);
        }

        if (isset($data['skills']) && is_array($data['skills'])) {
            $skills = array_map('sanitize_text_field', $data['skills']);
            $sanitized_data['skills'] = json_encode($skills);
        }

        if (isset($data['interests']) && is_array($data['interests'])) {
            $interests = array_map('sanitize_text_field', $data['interests']);
            $sanitized_data['interests'] = json_encode($interests);
        }

        if (isset($data['is_public'])) {
            $sanitized_data['is_public'] = (int) $data['is_public'];
        }

        if (isset($data['privacy_settings']) && is_array($data['privacy_settings'])) {
            $privacy = array();
            foreach ($data['privacy_settings'] as $key => $value) {
                $privacy[sanitize_key($key)] = sanitize_text_field($value);
            }
            $sanitized_data['privacy_settings'] = json_encode($privacy);
        }

        $sanitized_data['updated_at'] = current_time('mysql');

        $result = $wpdb->update(
            $table_name,
            $sanitized_data,
            array('user_id' => $user_id),
            null,
            array('%d')
        );

        if ($result !== false) {
            // Log activity
            self::log_user_activity($user_id, 'profile_updated');
            return true;
        }

        return false;
    }

    /**
     * Get user avatar URL
     *
     * @since    1.0.0
     * @param    int       $user_id    User ID
     * @param    string    $size       Avatar size
     * @return   string                Avatar URL
     */
    public static function get_user_avatar($user_id, $size = 'medium') {
        $avatar_id = get_user_meta($user_id, 'community_x_avatar', true);
        
        if ($avatar_id) {
            $avatar_url = wp_get_attachment_image_url($avatar_id, $size);
            if ($avatar_url) {
                return $avatar_url;
            }
        }

        // Fallback to Gravatar
        $user = get_user_by('id', $user_id);
        if ($user) {
            return get_avatar_url($user->user_email, array('size' => 150));
        }

        return '';
    }

    /**
     * Get user stats
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   array              User stats
     */
    public static function get_user_stats($user_id) {
        global $wpdb;

        $posts_table = $wpdb->prefix . 'community_x_posts';
        $interactions_table = $wpdb->prefix . 'community_x_interactions';

        // Get post count
        $post_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $posts_table WHERE author_id = %d AND status = 'published'",
            $user_id
        ));

        // Get likes received
        $likes_received = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $interactions_table i 
             JOIN $posts_table p ON i.object_id = p.id 
             WHERE p.author_id = %d AND i.interaction_type = 'like' AND i.object_type = 'post'",
            $user_id
        ));

        // Get followers count
        $followers_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $interactions_table 
             WHERE object_id = %d AND interaction_type = 'follow' AND object_type = 'user'",
            $user_id
        ));

        // Get following count  
        $following_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $interactions_table 
             WHERE user_id = %d AND interaction_type = 'follow' AND object_type = 'user'",
            $user_id
        ));

        return array(
            'posts' => (int) $post_count,
            'likes_received' => (int) $likes_received,
            'followers' => (int) $followers_count,
            'following' => (int) $following_count
        );
    }

    /**
     * Search users
     *
     * @since    1.0.0
     * @param    array    $args    Search arguments
     * @return   array             Search results
     */
    public static function search_users($args = array()) {
        global $wpdb;

        $defaults = array(
            'search' => '',
            'skills' => array(),
            'location' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'registered',
            'order' => 'DESC',
            'public_only' => true
        );

        $args = wp_parse_args($args, $defaults);

        $users_table = $wpdb->users;
        $profiles_table = $wpdb->prefix . 'community_x_user_profiles';

        $where_conditions = array();
        $where_values = array();

        // Base query
        $sql = "SELECT DISTINCT u.ID, u.display_name, u.user_email, u.user_registered, p.*
                FROM $users_table u 
                LEFT JOIN $profiles_table p ON u.ID = p.user_id";

        // Search by name or email
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_conditions[] = "(u.display_name LIKE %s OR u.user_email LIKE %s)";
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        // Filter by skills
        if (!empty($args['skills']) && is_array($args['skills'])) {
            $skills_conditions = array();
            foreach ($args['skills'] as $skill) {
                $skills_conditions[] = "p.skills LIKE %s";
                $where_values[] = '%' . $wpdb->esc_like($skill) . '%';
            }
            if (!empty($skills_conditions)) {
                $where_conditions[] = '(' . implode(' OR ', $skills_conditions) . ')';
            }
        }

        // Filter by location
        if (!empty($args['location'])) {
            $where_conditions[] = "p.location LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($args['location']) . '%';
        }

        // Public profiles only
        if ($args['public_only']) {
            $where_conditions[] = "(p.is_public = 1 OR p.is_public IS NULL)";
        }

        // Build WHERE clause
        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(' AND ', $where_conditions);
        }

        // Order by
        $allowed_orderby = array('registered', 'name', 'posts', 'points');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'registered';
        
        switch ($orderby) {
            case 'name':
                $sql .= " ORDER BY u.display_name " . ($args['order'] === 'ASC' ? 'ASC' : 'DESC');
                break;
            case 'posts':
                $sql .= " ORDER BY p.post_count " . ($args['order'] === 'ASC' ? 'ASC' : 'DESC');
                break;
            case 'points':
                $sql .= " ORDER BY p.points " . ($args['order'] === 'ASC' ? 'ASC' : 'DESC');
                break;
            default:
                $sql .= " ORDER BY u.user_registered " . ($args['order'] === 'ASC' ? 'ASC' : 'DESC');
        }

        // Pagination
        $offset = ($args['page'] - 1) * $args['per_page'];
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $args['per_page'], $offset);

        // Execute query
        if (!empty($where_values)) {
            $results = $wpdb->get_results($wpdb->prepare($sql, $where_values), ARRAY_A);
        } else {
            $results = $wpdb->get_results($sql, ARRAY_A);
        }

        // Process results
        foreach ($results as &$user) {
            if (isset($user['social_links'])) {
                $user['social_links'] = json_decode($user['social_links'], true) ?: array();
            }
            if (isset($user['skills'])) {
                $user['skills'] = json_decode($user['skills'], true) ?: array();
            }
            if (isset($user['interests'])) {
                $user['interests'] = json_decode($user['interests'], true) ?: array();
            }
        }

        return $results;
    }

    /**
     * AJAX handler for profile updates
     *
     * @since    1.0.0
     */
    public static function ajax_update_profile() {
        check_ajax_referer('community_x_public_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('You must be logged in to update your profile.');
        }

        $user_id = get_current_user_id();
        
        if (!current_user_can('community_edit_profile') && $user_id != $user_id) {
            wp_send_json_error('You do not have permission to edit this profile.');
        }

        $data = $_POST['profile_data'];
        
        if (self::update_user_profile($user_id, $data)) {
            wp_send_json_success('Profile updated successfully!');
        } else {
            wp_send_json_error('Failed to update profile. Please try again.');
        }
    }

    /**
     * Send welcome email to new user
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     */
    private static function send_welcome_email($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) return;

        $subject = sprintf(__('Welcome to %s!', 'community-x'), 
                          get_option('community_x_community_name', 'Community X'));
        
        $message = sprintf(
            __('Hi %s,

Welcome to our community! We\'re excited to have you join us.

Here are some things you can do to get started:

- Complete your profile: %s
- Browse the community: %s
- Connect with other members: %s

If you have any questions, feel free to reach out to our community team.

Welcome aboard!

The %s Team', 'community-x'),
            $user->display_name,
            home_url('/community-dashboard/'),
            home_url('/community/'),
            home_url('/members/'),
            get_option('community_x_community_name', 'Community X')
        );

        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Log user activity
     *
     * @since    1.0.0
     * @param    int       $user_id       User ID
     * @param    string    $action        Action performed
     * @param    int       $object_id     Object ID (optional)
     * @param    string    $object_type   Object type (optional)
     */
    public static function log_user_activity($user_id, $action, $object_id = null, $object_type = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'community_x_activity';

        $activity_data = array(
            'user_id' => $user_id,
            'action' => $action,
            'object_id' => $object_id,
            'object_type' => $object_type,
            'ip_address' => self::get_user_ip(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'], 0, 255)
        );

        $wpdb->insert($table_name, $activity_data);
    }

    /**
     * Get user IP address
     *
     * @since    1.0.0
     * @return   string    IP address
     */
    private static function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    /**
     * Validate user data before insertion
     *
     * @since    1.0.0
     * @param    array    $data      User data
     * @param    bool     $update    Whether this is an update
     * @param    int      $user_id   User ID for updates
     * @return   array               Validated data
     */
    public static function validate_user_data($data, $update, $user_id) {
        // Add custom validation here if needed
        return $data;
    }
}

// Initialize user functionality
Community_X_User::init();