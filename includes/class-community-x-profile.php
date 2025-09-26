<?php
/**
 * Profile management functionality
 *
 * @since      1.0.0
 * @package    Community_X
 */

/**
 * Handle profile display and management
 *
 * @since      1.0.0
 * @package    Community_X
 */
class Community_X_Profile {

    /**
     * Initialize profile functionality
     *
     * @since    1.0.0
     */
    public static function init() {
        add_action('wp_ajax_community_x_follow_user', array(__CLASS__, 'ajax_follow_user'));
        add_action('wp_ajax_community_x_unfollow_user', array(__CLASS__, 'ajax_unfollow_user'));
        add_action('wp_ajax_community_x_update_avatar', array(__CLASS__, 'ajax_update_avatar'));
        add_shortcode('community_x_profile_card', array(__CLASS__, 'profile_card_shortcode'));
        add_shortcode('community_x_profile_stats', array(__CLASS__, 'profile_stats_shortcode'));
    }

    /**
     * Display user profile page
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   string             Profile HTML
     */
    public static function display_profile($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return '<p>' . __('User not found.', 'community-x') . '</p>';
        }

        $profile = Community_X_User::get_user_profile($user_id);
        $stats = Community_X_User::get_user_stats($user_id);
        $is_own_profile = (get_current_user_id() == $user_id);
        $can_view = self::can_view_profile($user_id);

        if (!$can_view) {
            return '<p>' . __('This profile is private.', 'community-x') . '</p>';
        }

        ob_start();
        ?>
        <div class="community-x-profile" data-user-id="<?php echo esc_attr($user_id); ?>">
            <!-- Profile Header -->
            <div class="profile-header">
                <?php if (!empty($profile['cover_image'])): ?>
                    <div class="cover-image" style="background-image: url('<?php echo esc_url($profile['cover_image']); ?>');">
                    </div>
                <?php else: ?>
                    <div class="cover-image default-cover">
                    </div>
                <?php endif; ?>
                
                <div class="profile-info">
                    <div class="avatar-section">
                        <div class="user-avatar">
                            <img src="<?php echo esc_url(Community_X_User::get_user_avatar($user_id, 'large')); ?>" 
                                 alt="<?php echo esc_attr($user->display_name); ?>" />
                        </div>
                        
                        <?php if ($is_own_profile): ?>
                            <button class="edit-avatar-btn" title="<?php _e('Change Avatar', 'community-x'); ?>">
                                <i class="fas fa-camera"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="user-details">
                        <h1 class="user-name"><?php echo esc_html($user->display_name); ?></h1>
                        
                        <?php if (!empty($profile['location'])): ?>
                            <p class="user-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo esc_html($profile['location']); ?>
                            </p>
                        <?php endif; ?>
                        
                        <p class="user-level">
                            <i class="fas fa-star"></i>
                            <?php echo esc_html($profile['level'] ?? 'Member'); ?>
                            <span class="points">(<?php echo number_format($profile['points'] ?? 0); ?> points)</span>
                        </p>
                        
                        <p class="member-since">
                            <?php printf(__('Member since %s', 'community-x'), 
                                       date_i18n(get_option('date_format'), strtotime($user->user_registered))); ?>
                        </p>
                    </div>
                    
                    <div class="profile-actions">
                        <?php if ($is_own_profile): ?>
                            <a href="#edit-profile" class="btn btn-primary edit-profile-btn">
                                <i class="fas fa-edit"></i> <?php _e('Edit Profile', 'community-x'); ?>
                            </a>
                        <?php elseif (is_user_logged_in()): ?>
                            <?php if (self::is_following($user_id)): ?>
                                <button class="btn btn-secondary unfollow-btn" data-user-id="<?php echo $user_id; ?>">
                                    <i class="fas fa-user-minus"></i> <?php _e('Unfollow', 'community-x'); ?>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary follow-btn" data-user-id="<?php echo $user_id; ?>">
                                    <i class="fas fa-user-plus"></i> <?php _e('Follow', 'community-x'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if (get_option('community_x_enable_private_messaging', 1)): ?>
                                <a href="#message" class="btn btn-secondary message-btn" data-user-id="<?php echo $user_id; ?>">
                                    <i class="fas fa-envelope"></i> <?php _e('Message', 'community-x'); ?>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="profile-content">
                <div class="profile-main">
                    <!-- About Section -->
                    <?php if (!empty($profile['bio'])): ?>
                        <div class="profile-section about-section">
                            <h3><i class="fas fa-user"></i> <?php _e('About', 'community-x'); ?></h3>
                            <div class="section-content">
                                <p><?php echo wp_kses_post(nl2br($profile['bio'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Skills Section -->
                    <?php if (!empty($profile['skills'])): ?>
                        <div class="profile-section skills-section">
                            <h3><i class="fas fa-cogs"></i> <?php _e('Skills', 'community-x'); ?></h3>
                            <div class="section-content">
                                <div class="skills-list">
                                    <?php foreach ($profile['skills'] as $skill): ?>
                                        <span class="skill-tag"><?php echo esc_html($skill); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Interests Section -->
                    <?php if (!empty($profile['interests'])): ?>
                        <div class="profile-section interests-section">
                            <h3><i class="fas fa-heart"></i> <?php _e('Interests', 'community-x'); ?></h3>
                            <div class="section-content">
                                <div class="interests-list">
                                    <?php foreach ($profile['interests'] as $interest): ?>
                                        <span class="interest-tag"><?php echo esc_html($interest); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Recent Activity Section -->
                    <div class="profile-section activity-section">
                        <h3><i class="fas fa-clock"></i> <?php _e('Recent Activity', 'community-x'); ?></h3>
                        <div class="section-content">
                            <?php echo self::get_user_recent_activity($user_id); ?>
                        </div>
                    </div>
                </div>

                <div class="profile-sidebar">
                    <!-- Stats Section -->
                    <div class="profile-section stats-section">
                        <h3><i class="fas fa-chart-bar"></i> <?php _e('Statistics', 'community-x'); ?></h3>
                        <div class="section-content">
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo number_format($stats['posts']); ?></span>
                                    <span class="stat-label"><?php _e('Posts', 'community-x'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo number_format($stats['likes_received']); ?></span>
                                    <span class="stat-label"><?php _e('Likes', 'community-x'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo number_format($stats['followers']); ?></span>
                                    <span class="stat-label"><?php _e('Followers', 'community-x'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo number_format($stats['following']); ?></span>
                                    <span class="stat-label"><?php _e('Following', 'community-x'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links Section -->
                    <?php if (!empty($profile['social_links'])): ?>
                        <div class="profile-section social-section">
                            <h3><i class="fas fa-share-alt"></i> <?php _e('Social Links', 'community-x'); ?></h3>
                            <div class="section-content">
                                <div class="social-links">
                                    <?php foreach ($profile['social_links'] as $platform => $url): ?>
                                        <?php if (!empty($url)): ?>
                                            <a href="<?php echo esc_url($url); ?>" 
                                               target="_blank" 
                                               class="social-link social-<?php echo esc_attr($platform); ?>"
                                               rel="noopener noreferrer">
                                                <i class="fab fa-<?php echo esc_attr($platform); ?>"></i>
                                                <?php echo esc_html(ucfirst($platform)); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Website Section -->
                    <?php if (!empty($profile['website'])): ?>
                        <div class="profile-section website-section">
                            <h3><i class="fas fa-globe"></i> <?php _e('Website', 'community-x'); ?></h3>
                            <div class="section-content">
                                <a href="<?php echo esc_url($profile['website']); ?>" 
                                   target="_blank" 
                                   class="website-link"
                                   rel="noopener noreferrer">
                                    <?php echo esc_html($profile['website']); ?>
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($is_own_profile): ?>
            <!-- Profile Edit Modal -->
            <div id="edit-profile-modal" class="community-modal" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><?php _e('Edit Profile', 'community-x'); ?></h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <?php echo self::get_profile_edit_form($user_id, $profile); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php
        return ob_get_clean();
    }

    /**
     * Get profile edit form
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID
     * @param    array    $profile    Profile data
     * @return   string               Form HTML
     */
    public static function get_profile_edit_form($user_id, $profile) {
        ob_start();
        ?>
        <form id="edit-profile-form" class="community-form">
            <?php wp_nonce_field('community_x_edit_profile', 'edit_profile_nonce'); ?>
            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>" />

            <div class="form-group">
                <label for="profile_bio"><?php _e('Bio', 'community-x'); ?></label>
                <textarea id="profile_bio" name="bio" rows="4" class="form-control" 
                          placeholder="<?php _e('Tell us about yourself...', 'community-x'); ?>"><?php echo esc_textarea($profile['bio'] ?? ''); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="profile_location"><?php _e('Location', 'community-x'); ?></label>
                    <input type="text" id="profile_location" name="location" class="form-control" 
                           value="<?php echo esc_attr($profile['location'] ?? ''); ?>"
                           placeholder="<?php _e('City, Country', 'community-x'); ?>" />
                </div>

                <div class="form-group">
                    <label for="profile_website"><?php _e('Website', 'community-x'); ?></label>
                    <input type="url" id="profile_website" name="website" class="form-control" 
                           value="<?php echo esc_attr($profile['website'] ?? ''); ?>"
                           placeholder="<?php _e('https://yourwebsite.com', 'community-x'); ?>" />
                </div>
            </div>

            <div class="form-group">
                <label><?php _e('Social Links', 'community-x'); ?></label>
                <div class="social-inputs">
                    <?php 
                    $social_platforms = array(
                        'twitter' => 'Twitter',
                        'linkedin' => 'LinkedIn',
                        'github' => 'GitHub',
                        'instagram' => 'Instagram',
                        'facebook' => 'Facebook'
                    );
                    
                    foreach ($social_platforms as $platform => $label): ?>
                        <div class="social-input">
                            <label for="social_<?php echo $platform; ?>">
                                <i class="fab fa-<?php echo $platform; ?>"></i> <?php echo $label; ?>
                            </label>
                            <input type="url" id="social_<?php echo $platform; ?>" 
                                   name="social_links[<?php echo $platform; ?>]" class="form-control" 
                                   value="<?php echo esc_attr($profile['social_links'][$platform] ?? ''); ?>" />
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="profile_skills"><?php _e('Skills', 'community-x'); ?></label>
                <input type="text" id="profile_skills" name="skills" class="form-control" 
                       value="<?php echo esc_attr(implode(', ', $profile['skills'] ?? array())); ?>"
                       placeholder="<?php _e('JavaScript, PHP, Design, etc. (comma separated)', 'community-x'); ?>" />
                <small class="form-text"><?php _e('Separate skills with commas', 'community-x'); ?></small>
            </div>

            <div class="form-group">
                <label for="profile_interests"><?php _e('Interests', 'community-x'); ?></label>
                <input type="text" id="profile_interests" name="interests" class="form-control" 
                       value="<?php echo esc_attr(implode(', ', $profile['interests'] ?? array())); ?>"
                       placeholder="<?php _e('Photography, Travel, Music, etc. (comma separated)', 'community-x'); ?>" />
                <small class="form-text"><?php _e('Separate interests with commas', 'community-x'); ?></small>
            </div>

            <div class="form-group">
                <label><?php _e('Privacy Settings', 'community-x'); ?></label>
                <div class="privacy-settings">
                    <div class="privacy-option">
                        <label>
                            <input type="checkbox" name="is_public" value="1" 
                                   <?php checked($profile['is_public'] ?? 1, 1); ?> />
                            <?php _e('Make my profile public', 'community-x'); ?>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php _e('Save Changes', 'community-x'); ?>
                </button>
                <button type="button" class="btn btn-secondary modal-close">
                    <?php _e('Cancel', 'community-x'); ?>
                </button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Get user's recent activity
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @param    int    $limit      Number of activities to fetch
     * @return   string             Activity HTML
     */
    public static function get_user_recent_activity($user_id, $limit = 5) {
        global $wpdb;

        $activity_table = $wpdb->prefix . 'community_x_activity';
        
        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $activity_table 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $user_id, $limit
        ));

        if (empty($activities)) {
            return '<p class="no-activity">' . __('No recent activity to display.', 'community-x') . '</p>';
        }

        $output = '<div class="activity-list">';
        
        foreach ($activities as $activity) {
            $output .= '<div class="activity-item">';
            $output .= '<div class="activity-icon">';
            
            switch ($activity->action) {
                case 'user_registered':
                    $output .= '<i class="fas fa-user-plus"></i>';
                    $message = __('Joined the community', 'community-x');
                    break;
                case 'profile_updated':
                    $output .= '<i class="fas fa-user-edit"></i>';
                    $message = __('Updated profile', 'community-x');
                    break;
                case 'post_created':
                    $output .= '<i class="fas fa-file-plus"></i>';
                    $message = __('Created a new post', 'community-x');
                    break;
                case 'comment_added':
                    $output .= '<i class="fas fa-comment"></i>';
                    $message = __('Added a comment', 'community-x');
                    break;
                default:
                    $output .= '<i class="fas fa-circle"></i>';
                    $message = esc_html($activity->action);
            }
            
            $output .= '</div>';
            $output .= '<div class="activity-content">';
            $output .= '<p>' . $message . '</p>';
            $output .= '<span class="activity-time">' . 
                       human_time_diff(strtotime($activity->created_at), current_time('timestamp')) . ' ' . 
                       __('ago', 'community-x') . '</span>';
            $output .= '</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>';

        return $output;
    }

    /**
     * Check if current user can view profile
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID
     * @return   bool               Can view status
     */
    public static function can_view_profile($user_id) {
        $profile = Community_X_User::get_user_profile($user_id);
        
        // Public profiles can be viewed by everyone
        if (empty($profile) || $profile['is_public'] == 1) {
            return true;
        }

        // Users can always view their own profile
        if (get_current_user_id() == $user_id) {
            return true;
        }

        // Moderators and admins can view all profiles
        if (current_user_can('community_moderate_all')) {
            return true;
        }

        // Check if following (future implementation)
        // if (self::is_following($user_id)) {
        //     return true;
        // }

        return false;
    }

    /**
     * Check if current user is following another user
     *
     * @since    1.0.0
     * @param    int    $user_id    User ID to check
     * @return   bool               Following status
     */
    public static function is_following($user_id) {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $interactions_table = $wpdb->prefix . 'community_x_interactions';
        
        $following = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $interactions_table 
             WHERE user_id = %d AND object_id = %d 
             AND object_type = 'user' AND interaction_type = 'follow'",
            get_current_user_id(), $user_id
        ));

        return $following > 0;
    }

    /**
     * AJAX handler for following a user
     *
     * @since    1.0.0
     */
    public static function ajax_follow_user() {
        check_ajax_referer('community_x_public_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to follow users.', 'community-x'));
        }

        $user_id = intval($_POST['user_id']);
        $current_user_id = get_current_user_id();

        if ($user_id == $current_user_id) {
            wp_send_json_error(__('You cannot follow yourself.', 'community-x'));
        }

        if (self::follow_user($current_user_id, $user_id)) {
            wp_send_json_success(__('Now following this user!', 'community-x'));
        } else {
            wp_send_json_error(__('Failed to follow user. Please try again.', 'community-x'));
        }
    }

    /**
     * AJAX handler for unfollowing a user
     *
     * @since    1.0.0
     */
    public static function ajax_unfollow_user() {
        check_ajax_referer('community_x_public_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to unfollow users.', 'community-x'));
        }

        $user_id = intval($_POST['user_id']);
        $current_user_id = get_current_user_id();

        if (self::unfollow_user($current_user_id, $user_id)) {
            wp_send_json_success(__('Unfollowed user successfully!', 'community-x'));
        } else {
            wp_send_json_error(__('Failed to unfollow user. Please try again.', 'community-x'));
        }
    }

    /**
     * Follow a user
     *
     * @since    1.0.0
     * @param    int    $follower_id    Follower user ID
     * @param    int    $user_id        User to follow
     * @return   bool                   Success status
     */
    private static function follow_user($follower_id, $user_id) {
        global $wpdb;
        
        $interactions_table = $wpdb->prefix . 'community_x_interactions';

        $result = $wpdb->insert(
            $interactions_table,
            array(
                'user_id' => $follower_id,
                'object_id' => $user_id,
                'object_type' => 'user',
                'interaction_type' => 'follow'
            ),
            array('%d', '%d', '%s', '%s')
        );

        if ($result) {
            // Log activity
            Community_X_User::log_user_activity($follower_id, 'user_followed', $user_id, 'user');
            return true;
        }

        return false;
    }

    /**
     * Unfollow a user
     *
     * @since    1.0.0
     * @param    int    $follower_id    Follower user ID
     * @param    int    $user_id        User to unfollow
     * @return   bool                   Success status
     */
    private static function unfollow_user($follower_id, $user_id) {
        global $wpdb;
        
        $interactions_table = $wpdb->prefix . 'community_x_interactions';

        $result = $wpdb->delete(
            $interactions_table,
            array(
                'user_id' => $follower_id,
                'object_id' => $user_id,
                'object_type' => 'user',
                'interaction_type' => 'follow'
            ),
            array('%d', '%d', '%s', '%s')
        );

        if ($result) {
            // Log activity
            Community_X_User::log_user_activity($follower_id, 'user_unfollowed', $user_id, 'user');
            return true;
        }

        return false;
    }

    /**
     * Profile card shortcode
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes
     * @return   string            Shortcode output
     */
    public static function profile_card_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'show_stats' => 'yes',
            'show_bio' => 'yes'
        ), $atts);

        $user_id = intval($atts['user_id']);
        if (!$user_id) return '';

        return self::get_profile_card($user_id, $atts);
    }

    /**
     * Get profile card HTML
     *
     * @since    1.0.0
     * @param    int      $user_id    User ID
     * @param    array    $options    Display options
     * @return   string               Card HTML
     */
    public static function get_profile_card($user_id, $options = array()) {
        $user = get_user_by('id', $user_id);
        if (!$user) return '';

        $profile = Community_X_User::get_user_profile($user_id);
        $stats = Community_X_User::get_user_stats($user_id);

        ob_start();
        ?>
        <div class="community-profile-card" data-user-id="<?php echo $user_id; ?>">
            <div class="profile-card-header">
                <img src="<?php echo esc_url(Community_X_User::get_user_avatar($user_id)); ?>" 
                     alt="<?php echo esc_attr($user->display_name); ?>" 
                     class="profile-avatar" />
                <h3 class="profile-name">
                    <a href="<?php echo esc_url(home_url("/community/member/{$user->user_login}/")); ?>">
                        <?php echo esc_html($user->display_name); ?>
                    </a>
                </h3>
                <?php if (!empty($profile['location'])): ?>
                    <p class="profile-location">
                        <i class="fas fa-map-marker-alt"></i> <?php echo esc_html($profile['location']); ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($options['show_bio'] === 'yes' && !empty($profile['bio'])): ?>
                <div class="profile-card-bio">
                    <p><?php echo wp_kses_post(wp_trim_words($profile['bio'], 20)); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($options['show_stats'] === 'yes'): ?>
                <div class="profile-card-stats">
                    <div class="stat">
                        <span class="stat-number"><?php echo number_format($stats['posts']); ?></span>
                        <span class="stat-label"><?php _e('Posts', 'community-x'); ?></span>
                    </div>
                    <div class="stat">
                        <span class="stat-number"><?php echo number_format($stats['followers']); ?></span>
                        <span class="stat-label"><?php _e('Followers', 'community-x'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize profile functionality
Community_X_Profile::init();