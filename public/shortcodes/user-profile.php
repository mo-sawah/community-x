<?php
/**
 * User Profile Shortcode
 *
 * @since      1.0.0
 * @package    Community_X
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get user ID from attributes or current user
$user_id = isset($atts['user_id']) ? intval($atts['user_id']) : get_current_user_id();
$show_posts = isset($atts['show_posts']) && $atts['show_posts'] === 'no' ? false : true;
$show_stats = isset($atts['show_stats']) && $atts['show_stats'] === 'no' ? false : true;
$editable = isset($atts['editable']) && $atts['editable'] === 'yes' && $user_id == get_current_user_id();

if (!$user_id) {
    return '<p class="community-x-error">' . __('Please log in to view your profile.', 'community-x') . '</p>';
}

$user = get_user_by('id', $user_id);
if (!$user) {
    return '<p class="community-x-error">' . __('User not found.', 'community-x') . '</p>';
}

// Check viewing permissions
if (!Community_X_Profile::can_view_profile($user_id)) {
    return '<p class="community-x-error">' . __('This profile is private.', 'community-x') . '</p>';
}

$profile = Community_X_User::get_user_profile($user_id);
$stats = Community_X_User::get_user_stats($user_id);
$is_own_profile = (get_current_user_id() == $user_id);
?>

<div class="community-x-profile-shortcode">
    <div class="profile-card-widget">
        <div class="profile-header-section">
            <div class="profile-avatar-section">
                <img src="<?php echo esc_url(Community_X_User::get_user_avatar($user_id, 'large')); ?>" 
                     alt="<?php echo esc_attr($user->display_name); ?>" 
                     class="profile-avatar-large" />
                     
                <?php if ($editable): ?>
                    <button class="edit-avatar-btn" title="<?php _e('Change Avatar', 'community-x'); ?>">
                        <i class="fas fa-camera"></i>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="profile-info-section">
                <h2 class="profile-name"><?php echo esc_html($user->display_name); ?></h2>
                <p class="profile-username">@<?php echo esc_html($user->user_login); ?></p>
                
                <?php if (!empty($profile['location'])): ?>
                    <p class="profile-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo esc_html($profile['location']); ?>
                    </p>
                <?php endif; ?>
                
                <div class="profile-meta">
                    <span class="profile-level">
                        <i class="fas fa-star"></i>
                        <?php echo esc_html($profile['level'] ?? 'Member'); ?>
                    </span>
                    <span class="profile-points">
                        <?php echo number_format($profile['points'] ?? 0); ?> <?php _e('points', 'community-x'); ?>
                    </span>
                </div>
                
                <?php if ($editable): ?>
                    <div class="profile-actions">
                        <button class="btn btn-primary btn-sm edit-profile-btn">
                            <i class="fas fa-edit"></i> <?php _e('Edit Profile', 'community-x'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($show_stats): ?>
            <div class="profile-stats-section">
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
        <?php endif; ?>

        <?php if (!empty($profile['bio'])): ?>
            <div class="profile-bio-section">
                <h4><?php _e('About', 'community-x'); ?></h4>
                <p><?php echo wp_kses_post(nl2br($profile['bio'])); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($profile['skills'])): ?>
            <div class="profile-skills-section">
                <h4><?php _e('Skills', 'community-x'); ?></h4>
                <div class="skills-tags">
                    <?php foreach (array_slice($profile['skills'], 0, 5) as $skill): ?>
                        <span class="skill-tag"><?php echo esc_html($skill); ?></span>
                    <?php endforeach; ?>
                    
                    <?php if (count($profile['skills']) > 5): ?>
                        <span class="skill-tag more">
                            +<?php echo count($profile['skills']) - 5; ?> <?php _e('more', 'community-x'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($profile['social_links']) && array_filter($profile['social_links'])): ?>
            <div class="profile-social-section">
                <h4><?php _e('Social Links', 'community-x'); ?></h4>
                <div class="social-links-list">
                    <?php foreach ($profile['social_links'] as $platform => $url): ?>
                        <?php if (!empty($url)): ?>
                            <a href="<?php echo esc_url($url); ?>" 
                               target="_blank" 
                               class="social-link social-<?php echo esc_attr($platform); ?>"
                               rel="noopener noreferrer"
                               title="<?php echo esc_attr(ucfirst($platform)); ?>">
                                <i class="fab fa-<?php echo esc_attr($platform); ?>"></i>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="profile-footer-section">
            <p class="join-date">
                <?php printf(__('Member since %s', 'community-x'), 
                           date_i18n('M Y', strtotime($user->user_registered))); ?>
            </p>
            
            <a href="<?php echo esc_url(home_url('/community/member/' . $user->user_login . '/')); ?>" 
               class="btn btn-outline btn-sm">
                <?php _e('View Full Profile', 'community-x'); ?>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>