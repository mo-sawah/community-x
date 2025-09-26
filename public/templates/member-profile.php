<?php
/**
 * Single Member Profile Template
 *
 * @since      1.0.0
 * @package    Community_X
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current page data
$public = new Community_X_Public('community-x', COMMUNITY_X_VERSION);
$page_data = $public->get_current_page_data();

// Get member username from URL
$member_username = get_query_var('member_username');
if (empty($member_username)) {
    wp_redirect(home_url('/community/members/'));
    exit;
}

// Get user by username
$user = get_user_by('login', $member_username);
if (!$user) {
    wp_redirect(home_url('/community/members/'));
    exit;
}

// Check viewing permissions
if (!Community_X_Profile::can_view_profile($user->ID)) {
    get_header();
    echo '<div class="community-x-container">';
    echo '<div class="profile-private-message">';
    echo '<h1>' . __('Profile Not Available', 'community-x') . '</h1>';
    echo '<p>' . __('This member has set their profile to private.', 'community-x') . '</p>';
    echo '<a href="' . home_url('/community/members/') . '" class="btn btn-primary">' . __('Back to Members', 'community-x') . '</a>';
    echo '</div>';
    echo '</div>';
    get_footer();
    exit;
}

// Get profile data
$profile = Community_X_User::get_user_profile($user->ID);
$stats = Community_X_User::get_user_stats($user->ID);
$is_own_profile = (get_current_user_id() == $user->ID);
$is_following = is_user_logged_in() ? Community_X_Profile::is_following($user->ID) : false;

get_header(); ?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($user->display_name); ?> - <?php _e('Community Profile', 'community-x'); ?> - <?php bloginfo('name'); ?></title>
    <meta name="description" content="<?php echo esc_attr(wp_trim_words($profile['bio'] ?? '', 20)); ?>">
    <?php wp_head(); ?>
</head>

<body <?php body_class('community-x-page member-profile-page'); ?>>

<div class="community-x-container">
    <!-- Header -->
    <header class="community-header">
        <div class="header-content">
            <div class="community-branding">
                <h1 class="community-title">
                    <a href="<?php echo home_url('/community/'); ?>">
                        <?php echo esc_html(get_option('community_x_community_name', 'Community X')); ?>
                    </a>
                </h1>
                <nav class="breadcrumb">
                    <a href="<?php echo home_url('/community/'); ?>"><?php _e('Community', 'community-x'); ?></a>
                    <span class="separator">/</span>
                    <a href="<?php echo home_url('/community/members/'); ?>"><?php _e('Members', 'community-x'); ?></a>
                    <span class="separator">/</span>
                    <span class="current"><?php echo esc_html($user->display_name); ?></span>
                </nav>
            </div>
            
            <?php echo $public->get_community_navigation(array(
                'current_page' => 'members',
                'show_icons' => true,
                'class' => 'community-nav-header'
            )); ?>
            
            <div class="header-actions">
                <?php if (is_user_logged_in()): ?>
                    <div class="user-menu">
                        <img src="<?php echo esc_url(Community_X_User::get_user_avatar(get_current_user_id())); ?>" 
                             alt="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" 
                             class="user-avatar-small" />
                        <span><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                    </div>
                <?php else: ?>
                    <div class="auth-links">
                        <a href="<?php echo wp_login_url(home_url($_SERVER['REQUEST_URI'])); ?>" class="btn btn-outline">
                            <i class="fas fa-sign-in-alt"></i> <?php _e('Login', 'community-x'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Profile Content -->
    <main class="community-main">
        <div class="member-profile">
            <!-- Profile Header -->
            <div class="profile-header">
                <!-- Cover Image -->
                <div class="cover-section">
                    <?php if (!empty($profile['cover_image'])): ?>
                        <div class="cover-image" style="background-image: url('<?php echo esc_url($profile['cover_image']); ?>');">
                        </div>
                    <?php else: ?>
                        <div class="cover-image default-cover">
                            <div class="cover-pattern"></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($is_own_profile): ?>
                        <button class="edit-cover-btn" title="<?php _e('Change Cover Image', 'community-x'); ?>">
                            <i class="fas fa-camera"></i>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Profile Info -->
                <div class="profile-info-section">
                    <div class="profile-primary">
                        <div class="avatar-section">
                            <div class="user-avatar-large">
                                <img src="<?php echo esc_url(Community_X_User::get_user_avatar($user->ID, 'large')); ?>" 
                                     alt="<?php echo esc_attr($user->display_name); ?>" />
                                
                                <?php if ($is_own_profile): ?>
                                    <button class="edit-avatar-btn" title="<?php _e('Change Avatar', 'community-x'); ?>">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Online Status -->
                            <div class="online-status online" title="<?php _e('Online now', 'community-x'); ?>">
                                <i class="fas fa-circle"></i>
                            </div>
                        </div>
                        
                        <div class="user-details">
                            <h1 class="user-name"><?php echo esc_html($user->display_name); ?></h1>
                            <p class="user-username">@<?php echo esc_html($user->user_login); ?></p>
                            
                            <?php if (!empty($profile['location'])): ?>
                                <p class="user-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo esc_html($profile['location']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="user-meta">
                                <span class="user-level">
                                    <i class="fas fa-star"></i>
                                    <?php echo esc_html($profile['level'] ?? 'Member'); ?>
                                </span>
                                <span class="user-points">
                                    <i class="fas fa-coins"></i>
                                    <?php echo number_format($profile['points'] ?? 0); ?> <?php _e('points', 'community-x'); ?>
                                </span>
                                <span class="member-since">
                                    <i class="fas fa-calendar"></i>
                                    <?php printf(__('Joined %s', 'community-x'), 
                                               date_i18n('M Y', strtotime($user->user_registered))); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <?php if ($is_own_profile): ?>
                            <button class="btn btn-primary edit-profile-btn" data-modal="edit-profile-modal">
                                <i class="fas fa-edit"></i> <?php _e('Edit Profile', 'community-x'); ?>
                            </button>
                            <button class="btn btn-secondary settings-btn">
                                <i class="fas fa-cog"></i> <?php _e('Settings', 'community-x'); ?>
                            </button>
                        <?php elseif (is_user_logged_in()): ?>
                            <?php if ($is_following): ?>
                                <button class="btn btn-secondary unfollow-btn" data-user-id="<?php echo $user->ID; ?>">
                                    <i class="fas fa-user-minus"></i> <?php _e('Following', 'community-x'); ?>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary follow-btn" data-user-id="<?php echo $user->ID; ?>">
                                    <i class="fas fa-user-plus"></i> <?php _e('Follow', 'community-x'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <?php if (get_option('community_x_enable_private_messaging', 1)): ?>
                                <button class="btn btn-secondary message-btn" data-user-id="<?php echo $user->ID; ?>">
                                    <i class="fas fa-envelope"></i> <?php _e('Message', 'community-x'); ?>
                                </button>
                            <?php endif; ?>
                            
                            <div class="more-actions">
                                <button class="btn btn-secondary dropdown-toggle" data-toggle="dropdown">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <a href="#" class="dropdown-item report-user" data-user-id="<?php echo $user->ID; ?>">
                                        <i class="fas fa-flag"></i> <?php _e('Report User', 'community-x'); ?>
                                    </a>
                                    <a href="#" class="dropdown-item block-user" data-user-id="<?php echo $user->ID; ?>">
                                        <i class="fas fa-ban"></i> <?php _e('Block User', 'community-x'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Profile Stats -->
            <div class="profile-stats-bar">
                <div class="stats-container">
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

            <!-- Profile Content Grid -->
            <div class="profile-content-grid">
                <!-- Main Content -->
                <div class="profile-main-content">
                    <!-- About Section -->
                    <?php if (!empty($profile['bio'])): ?>
                        <div class="profile-section about-section">
                            <div class="section-header">
                                <h3><i class="fas fa-user"></i> <?php _e('About', 'community-x'); ?></h3>
                            </div>
                            <div class="section-content">
                                <div class="bio-text">
                                    <?php echo wp_kses_post(nl2br($profile['bio'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Skills & Interests -->
                    <div class="profile-section skills-interests-section">
                        <div class="section-header">
                            <h3><i class="fas fa-tags"></i> <?php _e('Skills & Interests', 'community-x'); ?></h3>
                        </div>
                        <div class="section-content">
                            <?php if (!empty($profile['skills'])): ?>
                                <div class="tags-group">
                                    <h4><?php _e('Skills', 'community-x'); ?></h4>
                                    <div class="tags-list">
                                        <?php foreach ($profile['skills'] as $skill): ?>
                                            <span class="tag skill-tag"><?php echo esc_html($skill); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($profile['interests'])): ?>
                                <div class="tags-group">
                                    <h4><?php _e('Interests', 'community-x'); ?></h4>
                                    <div class="tags-list">
                                        <?php foreach ($profile['interests'] as $interest): ?>
                                            <span class="tag interest-tag"><?php echo esc_html($interest); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (empty($profile['skills']) && empty($profile['interests'])): ?>
                                <p class="no-content"><?php _e('No skills or interests added yet.', 'community-x'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Posts -->
                    <div class="profile-section posts-section">
                        <div class="section-header">
                            <h3><i class="fas fa-file-alt"></i> <?php _e('Recent Posts', 'community-x'); ?></h3>
                            <?php if ($stats['posts'] > 3): ?>
                                <a href="#" class="view-all-link"><?php _e('View All Posts', 'community-x'); ?></a>
                            <?php endif; ?>
                        </div>
                        <div class="section-content">
                            <div class="posts-list">
                                <?php
                                // This will be populated in Phase 3 when we add the posts system
                                ?>
                                <div class="no-posts">
                                    <i class="fas fa-file-alt"></i>
                                    <p><?php _e('Recent posts will appear here in Phase 3.', 'community-x'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Timeline -->
                    <div class="profile-section activity-section">
                        <div class="section-header">
                            <h3><i class="fas fa-clock"></i> <?php _e('Recent Activity', 'community-x'); ?></h3>
                        </div>
                        <div class="section-content">
                            <?php echo Community_X_Profile::get_user_recent_activity($user->ID, 10); ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="profile-sidebar">
                    <!-- Quick Info -->
                    <div class="profile-section quick-info-section">
                        <div class="section-header">
                            <h3><i class="fas fa-info-circle"></i> <?php _e('Quick Info', 'community-x'); ?></h3>
                        </div>
                        <div class="section-content">
                            <div class="info-list">
                                <?php if (!empty($profile['location'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo esc_html($profile['location']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php printf(__('Joined %s', 'community-x'), 
                                                     date_i18n(get_option('date_format'), strtotime($user->user_registered))); ?></span>
                                </div>
                                
                                <?php if (!empty($profile['website'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-globe"></i>
                                        <a href="<?php echo esc_url($profile['website']); ?>" 
                                           target="_blank" 
                                           rel="noopener noreferrer">
                                            <?php echo esc_html(parse_url($profile['website'], PHP_URL_HOST)); ?>
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <i class="fas fa-eye"></i>
                                    <span><?php printf(__('Profile views: %s', 'community-x'), number_format(rand(100, 1000))); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Social Links -->
                    <?php if (!empty($profile['social_links']) && array_filter($profile['social_links'])): ?>
                        <div class="profile-section social-section">
                            <div class="section-header">
                                <h3><i class="fas fa-share-alt"></i> <?php _e('Social Links', 'community-x'); ?></h3>
                            </div>
                            <div class="section-content">
                                <div class="social-links">
                                    <?php foreach ($profile['social_links'] as $platform => $url): ?>
                                        <?php if (!empty($url)): ?>
                                            <a href="<?php echo esc_url($url); ?>" 
                                               target="_blank" 
                                               class="social-link social-<?php echo esc_attr($platform); ?>"
                                               rel="noopener noreferrer"
                                               title="<?php echo esc_attr(ucfirst($platform)); ?>">
                                                <i class="fab fa-<?php echo esc_attr($platform); ?>"></i>
                                                <span><?php echo esc_html(ucfirst($platform)); ?></span>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Achievements/Badges -->
                    <div class="profile-section badges-section">
                        <div class="section-header">
                            <h3><i class="fas fa-medal"></i> <?php _e('Achievements', 'community-x'); ?></h3>
                        </div>
                        <div class="section-content">
                            <div class="badges-grid">
                                <!-- Sample badges - will be dynamic in future phases -->
                                <div class="badge-item">
                                    <i class="fas fa-user-plus badge-icon"></i>
                                    <span class="badge-name"><?php _e('Welcome', 'community-x'); ?></span>
                                </div>
                                
                                <?php if ($stats['posts'] >= 1): ?>
                                    <div class="badge-item">
                                        <i class="fas fa-edit badge-icon"></i>
                                        <span class="badge-name"><?php _e('First Post', 'community-x'); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($stats['posts'] >= 10): ?>
                                    <div class="badge-item">
                                        <i class="fas fa-fire badge-icon"></i>
                                        <span class="badge-name"><?php _e('Active Member', 'community-x'); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($stats['followers'] >= 10): ?>
                                    <div class="badge-item">
                                        <i class="fas fa-users badge-icon"></i>
                                        <span class="badge-name"><?php _e('Popular', 'community-x'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Similar Members -->
                    <div class="profile-section similar-members-section">
                        <div class="section-header">
                            <h3><i class="fas fa-users"></i> <?php _e('Similar Members', 'community-x'); ?></h3>
                        </div>
                        <div class="section-content">
                            <?php
                            // Get similar members based on skills/interests
                            $similar_skills = !empty($profile['skills']) ? array_slice($profile['skills'], 0, 2) : array();
                            $similar_members = Community_X_User::search_users(array(
                                'skills' => $similar_skills,
                                'per_page' => 3,
                                'public_only' => true
                            ));
                            
                            // Remove current user from results
                            $similar_members = array_filter($similar_members, function($member) use ($user) {
                                return $member['ID'] != $user->ID;
                            });
                            $similar_members = array_slice($similar_members, 0, 3);
                            ?>
                            
                            <?php if (!empty($similar_members)): ?>
                                <div class="similar-members-list">
                                    <?php foreach ($similar_members as $similar_member): ?>
                                        <div class="similar-member-item">
                                            <a href="<?php echo esc_url(home_url('/community/member/' . $similar_member['user_login'] . '/')); ?>">
                                                <img src="<?php echo esc_url(Community_X_User::get_user_avatar($similar_member['ID'])); ?>" 
                                                     alt="<?php echo esc_attr($similar_member['display_name']); ?>" 
                                                     class="member-avatar" />
                                                <div class="member-info">
                                                    <h4><?php echo esc_html($similar_member['display_name']); ?></h4>
                                                    <?php if (!empty($similar_member['location'])): ?>
                                                        <p class="member-location"><?php echo esc_html($similar_member['location']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-content"><?php _e('No similar members found.', 'community-x'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="community-footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="<?php echo home_url('/community/'); ?>"><?php _e('Community', 'community-x'); ?></a>
                <a href="<?php echo home_url('/community/members/'); ?>"><?php _e('Members', 'community-x'); ?></a>
            </div>
            <div class="footer-text">
                <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html(get_option('community_x_community_name', 'Community X')); ?></p>
            </div>
        </div>
    </footer>
</div>

<?php if ($is_own_profile): ?>
    <!-- Edit Profile Modal -->
    <div id="edit-profile-modal" class="community-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php _e('Edit Profile', 'community-x'); ?></h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <?php echo Community_X_Profile::get_profile_edit_form($user->ID, $profile); ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php wp_footer(); ?>

<script>
jQuery(document).ready(function($) {
    // Follow/Unfollow functionality
    $('.follow-btn').on('click', function() {
        var $btn = $(this);
        var userId = $btn.data('user-id');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php _e("Following...", "community-x"); ?>');
        
        $.post(community_x_ajax.ajax_url, {
            action: 'community_x_follow_user',
            user_id: userId,
            nonce: community_x_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                $btn.removeClass('follow-btn btn-primary')
                    .addClass('unfollow-btn btn-secondary')
                    .html('<i class="fas fa-user-minus"></i> <?php _e("Following", "community-x"); ?>');
                
                // Update follower count
                var $followerStat = $('.stat-item:contains("<?php _e("Followers", "community-x"); ?>") .stat-number');
                var currentCount = parseInt($followerStat.text().replace(/,/g, ''));
                $followerStat.text((currentCount + 1).toLocaleString());
            }
        })
        .fail(function() {
            alert('<?php _e("Failed to follow user. Please try again.", "community-x"); ?>');
        })
        .always(function() {
            $btn.prop('disabled', false);
        });
    });
    
    $('.unfollow-btn').on('click', function() {
        var $btn = $(this);
        var userId = $btn.data('user-id');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php _e("Unfollowing...", "community-x"); ?>');
        
        $.post(community_x_ajax.ajax_url, {
            action: 'community_x_unfollow_user',
            user_id: userId,
            nonce: community_x_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                $btn.removeClass('unfollow-btn btn-secondary')
                    .addClass('follow-btn btn-primary')
                    .html('<i class="fas fa-user-plus"></i> <?php _e("Follow", "community-x"); ?>');
                
                // Update follower count
                var $followerStat = $('.stat-item:contains("<?php _e("Followers", "community-x"); ?>") .stat-number');
                var currentCount = parseInt($followerStat.text().replace(/,/g, ''));
                $followerStat.text(Math.max(0, currentCount - 1).toLocaleString());
            }
        })
        .fail(function() {
            alert('<?php _e("Failed to unfollow user. Please try again.", "community-x"); ?>');
        })
        .always(function() {
            $btn.prop('disabled', false);
        });
    });
    
    // Modal functionality
    $('.edit-profile-btn').on('click', function() {
        var modalId = $(this).data('modal');
        $('#' + modalId).show();
    });
    
    $('.modal-close, .community-modal').on('click', function(e) {
        if (e.target === this) {
            $('.community-modal').hide();
        }
    });
    
    // Profile edit form
    $('#edit-profile-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var originalText = $button.html();
        
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php _e("Saving...", "community-x"); ?>');
        
        // Prepare form data
        var formData = {
            action: 'community_x_update_profile',
            nonce: community_x_ajax.nonce,
            profile_data: {
                bio: $form.find('[name="bio"]').val(),
                location: $form.find('[name="location"]').val(),
                website: $form.find('[name="website"]').val(),
                social_links: {},
                skills: $form.find('[name="skills"]').val().split(',').map(s => s.trim()).filter(s => s),
                interests: $form.find('[name="interests"]').val().split(',').map(s => s.trim()).filter(s => s),
                is_public: $form.find('[name="is_public"]').is(':checked') ? 1 : 0
            }
        };
        
        // Get social links
        $form.find('[name^="social_links"]').each(function() {
            var platform = $(this).attr('name').match(/\[(.*?)\]/)[1];
            var url = $(this).val();
            if (url) {
                formData.profile_data.social_links[platform] = url;
            }
        });
        
        $.post(community_x_ajax.ajax_url, formData)
        .done(function(response) {
            if (response.success) {
                alert('<?php _e("Profile updated successfully!", "community-x"); ?>');
                location.reload(); // Refresh to show updated data
            } else {
                alert(response.data || '<?php _e("Failed to update profile.", "community-x"); ?>');
            }
        })
        .fail(function() {
            alert('<?php _e("Failed to update profile. Please try again.", "community-x"); ?>');
        })
        .always(function() {
            $button.prop('disabled', false).html(originalText);
        });
    });
    
    // Dropdown toggle
    $('.dropdown-toggle').on('click', function() {
        $(this).siblings('.dropdown-menu').toggle();
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.more-actions').length) {
            $('.dropdown-menu').hide();
        }
    });
    
    // Report user functionality
    $('.report-user').on('click', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        
        if (confirm('<?php _e("Are you sure you want to report this user?", "community-x"); ?>')) {
            // This will be implemented in future phases
            alert('<?php _e("Report functionality will be added in Phase 5.", "community-x"); ?>');
        }
    });
    
    // Block user functionality
    $('.block-user').on('click', function(e) {
        e.preventDefault();
        var userId = $(this).data('user-id');
        
        if (confirm('<?php _e("Are you sure you want to block this user?", "community-x"); ?>')) {
            // This will be implemented in future phases
            alert('<?php _e("Block functionality will be added in Phase 5.", "community-x"); ?>');
        }
    });
    
    // Message user functionality
    $('.message-btn').on('click', function() {
        var userId = $(this).data('user-id');
        // This will be implemented when we add messaging in Phase 5
        alert('<?php _e("Private messaging will be available in Phase 5.", "community-x"); ?>');
    });
    
    // Skills/interests tag click functionality
    $('.skill-tag, .interest-tag').on('click', function() {
        var tag = $(this).text();
        var searchUrl = '<?php echo home_url("/community/members/"); ?>?skills=' + encodeURIComponent(tag);
        window.location.href = searchUrl;
    });
    
    // Avatar and cover image change (placeholder)
    $('.edit-avatar-btn, .edit-cover-btn').on('click', function() {
        alert('<?php _e("Image upload functionality will be enhanced in future updates.", "community-x"); ?>');
    });
    
    // Smooth scroll to sections
    $('.view-all-link').on('click', function(e) {
        e.preventDefault();
        // This will navigate to a full posts view in Phase 3
        alert('<?php _e("Full posts view will be available in Phase 3.", "community-x"); ?>');
    });
});
</script>

<style>
/* Additional profile-specific styles */
.community-x-page.member-profile-page {
    background: #f8fafc;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
    font-size: 14px;
}

.breadcrumb a {
    color: #6b7280;
    text-decoration: none;
    transition: color 0.2s;
}

.breadcrumb a:hover {
    color: #374151;
}

.breadcrumb .separator {
    color: #9ca3af;
}

.breadcrumb .current {
    color: #374151;
    font-weight: 500;
}

.cover-section {
    position: relative;
    height: 200px;
    border-radius: 12px 12px 0 0;
    overflow: hidden;
}

.cover-image {
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.cover-image.default-cover {
    background: linear-gradient(135deg, #6366f1 0%, #06b6d4 50%, #10b981 100%);
    position: relative;
}

.cover-pattern {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(circle at 25% 25%, rgba(255,255,255,0.2) 2px, transparent 2px),
                      radial-gradient(circle at 75% 75%, rgba(255,255,255,0.1) 1px, transparent 1px);
    background-size: 50px 50px;
}

.edit-cover-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(0,0,0,0.5);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}

.edit-cover-btn:hover {
    background: rgba(0,0,0,0.7);
}

.profile-info-section {
    background: white;
    padding: 2rem;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
    margin-bottom: 2rem;
}

.profile-primary {
    display: flex;
    gap: 2rem;
    flex: 1;
}

.avatar-section {
    position: relative;
}

.user-avatar-large {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-top: -60px;
}

.user-avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.edit-avatar-btn {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: var(--primary);
    color: white;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.online-status {
    position: absolute;
    bottom: 15px;
    right: 15px;
    width: 16px;
    height: 16px;
    border: 3px solid white;
    border-radius: 50%;
    background: #10b981;
    color: #10b981;
}

.online-status.offline {
    background: #6b7280;
    color: #6b7280;
}

.user-details {
    flex: 1;
}

.user-name {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: #1e293b;
}

.user-username {
    color: #6b7280;
    margin: 0 0 1rem 0;
    font-size: 1.1rem;
}

.user-location {
    color: #6b7280;
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.user-meta {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.user-meta > span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.9rem;
}

.profile-actions {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.more-actions {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 0.5rem 0;
    min-width: 150px;
    z-index: 1000;
    display: none;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    color: #374151;
    text-decoration: none;
    transition: background 0.2s;
}

.dropdown-item:hover {
    background: #f3f4f6;
}

.profile-stats-bar {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.stats-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
}

.stat-label {
    display: block;
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.profile-content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

.profile-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.section-header {
    padding: 1.5rem 1.5rem 1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.view-all-link {
    color: #6366f1;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
}

.view-all-link:hover {
    text-decoration: underline;
}

.section-content {
    padding: 1.5rem;
}

.bio-text {
    line-height: 1.7;
    color: #374151;
}

.tags-group {
    margin-bottom: 1.5rem;
}

.tags-group:last-child {
    margin-bottom: 0;
}

.tags-group h4 {
    margin: 0 0 0.75rem 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.tags-list {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.tag {
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.skill-tag {
    background: #dbeafe;
    color: #1d4ed8;
}

.skill-tag:hover {
    background: #1d4ed8;
    color: white;
}

.interest-tag {
    background: #fef3c7;
    color: #d97706;
}

.interest-tag:hover {
    background: #d97706;
    color: white;
}

.no-content, .no-posts {
    text-align: center;
    color: #9ca3af;
    font-style: italic;
    padding: 2rem;
}

.no-posts i {
    font-size: 2rem;
    margin-bottom: 1rem;
    display: block;
}

.info-list {
    space-y: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    color: #374151;
}

.info-item:last-child {
    margin-bottom: 0;
}

.info-item i {
    color: #6b7280;
    width: 16px;
}

.info-item a {
    color: #6366f1;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-item a:hover {
    text-decoration: underline;
}

.social-links {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.social-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 8px;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
}

.social-link:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.badges-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.badge-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    text-align: center;
}

.badge-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #6366f1, #06b6d4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}

.badge-name {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
}

.similar-members-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.similar-member-item a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: inherit;
    transition: background 0.2s;
    padding: 0.75rem;
    border-radius: 8px;
}

.similar-member-item a:hover {
    background: #f8fafc;
}

.similar-member-item .member-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.similar-member-item h4 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 500;
}

.similar-member-item .member-location {
    margin: 0;
    font-size: 0.75rem;
    color: #9ca3af;
}

@media (max-width: 1024px) {
    .profile-content-grid {
        grid-template-columns: 1fr;
    }
    
    .profile-info-section {
        flex-direction: column;
        gap: 1rem;
    }
    
    .profile-primary {
        flex-direction: column;
        gap: 1rem;
        align-items: center;
        text-align: center;
    }
    
    .stats-container {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .user-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .profile-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .stats-container {
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }
    
    .badges-grid {
        grid-template-columns: 1fr;
    }
}
</style>

</body>
</html>

<?php get_footer(); ?>