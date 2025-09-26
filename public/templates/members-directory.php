<?php
/**
 * Members Directory Template
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

// Check if public viewing is allowed
$allow_public_viewing = get_option('community_x_allow_public_viewing', 1);
if (!$allow_public_viewing && !is_user_logged_in()) {
    wp_redirect(wp_login_url(home_url($_SERVER['REQUEST_URI'])));
    exit;
}

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$skills_filter = isset($_GET['skills']) ? array_map('sanitize_text_field', explode(',', $_GET['skills'])) : array();
$location_filter = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'registered';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';
$page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;

// Search members
$members = Community_X_User::search_users(array(
    'search' => $search,
    'skills' => $skills_filter,
    'location' => $location_filter,
    'per_page' => 12,
    'page' => $page,
    'orderby' => $orderby,
    'order' => $order,
    'public_only' => !is_user_logged_in()
));

// Get total count for pagination
$total_args = array(
    'search' => $search,
    'skills' => $skills_filter,
    'location' => $location_filter,
    'public_only' => !is_user_logged_in()
);
$all_members = Community_X_User::search_users(array_merge($total_args, array('per_page' => 999999)));
$total_members = count($all_members);

// Get unique skills for filter dropdown
global $wpdb;
$profiles_table = $wpdb->prefix . 'community_x_user_profiles';
$skills_data = $wpdb->get_results("SELECT DISTINCT skills FROM $profiles_table WHERE skills IS NOT NULL AND skills != '' AND skills != '[]'");
$all_skills = array();
foreach ($skills_data as $skill_row) {
    $skills = json_decode($skill_row->skills, true);
    if (is_array($skills)) {
        $all_skills = array_merge($all_skills, $skills);
    }
}
$all_skills = array_unique(array_filter($all_skills));
sort($all_skills);

get_header(); ?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php _e('Members Directory', 'community-x'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>

<body <?php body_class('community-x-page members-directory-page'); ?>>

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
                <p class="community-description">
                    <?php echo esc_html(get_option('community_x_community_description', '')); ?>
                </p>
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
                        <div class="user-dropdown">
                            <a href="<?php echo home_url('/community-dashboard/'); ?>">
                                <i class="fas fa-tachometer-alt"></i> <?php _e('Dashboard', 'community-x'); ?>
                            </a>
                            <a href="<?php echo home_url('/community/member/' . wp_get_current_user()->user_login . '/'); ?>">
                                <i class="fas fa-user"></i> <?php _e('My Profile', 'community-x'); ?>
                            </a>
                            <a href="<?php echo wp_logout_url(home_url('/community/')); ?>">
                                <i class="fas fa-sign-out-alt"></i> <?php _e('Logout', 'community-x'); ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="auth-links">
                        <a href="<?php echo wp_login_url(home_url($_SERVER['REQUEST_URI'])); ?>" class="btn btn-outline">
                            <i class="fas fa-sign-in-alt"></i> <?php _e('Login', 'community-x'); ?>
                        </a>
                        <?php if (get_option('users_can_register')): ?>
                            <a href="<?php echo wp_registration_url(); ?>" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> <?php _e('Join', 'community-x'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="community-main">
        <div class="members-directory">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title">
                    <h1><i class="fas fa-users"></i> <?php _e('Members Directory', 'community-x'); ?></h1>
                    <p><?php printf(__('Discover and connect with %s community members', 'community-x'), number_format($total_members)); ?></p>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="members-filters">
                <form method="get" class="filters-form" id="members-filter-form">
                    <div class="search-section">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="search" name="search" 
                                   value="<?php echo esc_attr($search); ?>" 
                                   placeholder="<?php _e('Search members by name or email...', 'community-x'); ?>" 
                                   class="search-input" />
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <div class="filter-group">
                            <label for="skills-filter"><?php _e('Skills:', 'community-x'); ?></label>
                            <select id="skills-filter" name="skills" class="filter-select">
                                <option value=""><?php _e('All Skills', 'community-x'); ?></option>
                                <?php foreach ($all_skills as $skill): ?>
                                    <option value="<?php echo esc_attr($skill); ?>" 
                                            <?php selected(in_array($skill, $skills_filter)); ?>>
                                        <?php echo esc_html($skill); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="location-filter"><?php _e('Location:', 'community-x'); ?></label>
                            <input type="text" id="location-filter" name="location" 
                                   value="<?php echo esc_attr($location_filter); ?>" 
                                   placeholder="<?php _e('Filter by location', 'community-x'); ?>" 
                                   class="filter-input" />
                        </div>
                        
                        <div class="filter-group">
                            <label for="orderby-filter"><?php _e('Sort by:', 'community-x'); ?></label>
                            <select id="orderby-filter" name="orderby" class="filter-select">
                                <option value="registered" <?php selected($orderby, 'registered'); ?>>
                                    <?php _e('Newest Members', 'community-x'); ?>
                                </option>
                                <option value="name" <?php selected($orderby, 'name'); ?>>
                                    <?php _e('Name', 'community-x'); ?>
                                </option>
                                <option value="posts" <?php selected($orderby, 'posts'); ?>>
                                    <?php _e('Most Active', 'community-x'); ?>
                                </option>
                                <option value="points" <?php selected($orderby, 'points'); ?>>
                                    <?php _e('Top Contributors', 'community-x'); ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> <?php _e('Filter', 'community-x'); ?>
                            </button>
                            
                            <?php if ($search || !empty($skills_filter) || $location_filter): ?>
                                <a href="<?php echo home_url('/community/members/'); ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> <?php _e('Clear', 'community-x'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Members Grid -->
            <div class="members-grid">
                <?php if (empty($members)): ?>
                    <div class="no-members">
                        <div class="no-members-icon">
                            <i class="fas fa-users-slash"></i>
                        </div>
                        <h3><?php _e('No members found', 'community-x'); ?></h3>
                        <p><?php _e('Try adjusting your search criteria or clear the filters.', 'community-x'); ?></p>
                        
                        <?php if ($search || !empty($skills_filter) || $location_filter): ?>
                            <a href="<?php echo home_url('/community/members/'); ?>" class="btn btn-primary">
                                <i class="fas fa-users"></i> <?php _e('View All Members', 'community-x'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($members as $member): ?>
                        <?php
                        $member_stats = Community_X_User::get_user_stats($member['ID']);
                        $member_skills = !empty($member['skills']) ? $member['skills'] : array();
                        $member_avatar = Community_X_User::get_user_avatar($member['ID']);
                        ?>
                        <div class="member-card" data-member-id="<?php echo $member['ID']; ?>">
                            <div class="member-card-header">
                                <div class="member-avatar">
                                    <img src="<?php echo esc_url($member_avatar); ?>" 
                                         alt="<?php echo esc_attr($member['display_name']); ?>" />
                                </div>
                                
                                <div class="member-info">
                                    <h3 class="member-name">
                                        <a href="<?php echo esc_url(home_url('/community/member/' . $member['user_login'] . '/')); ?>">
                                            <?php echo esc_html($member['display_name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if (!empty($member['location'])): ?>
                                        <p class="member-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo esc_html($member['location']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <p class="member-level">
                                        <i class="fas fa-star"></i>
                                        <?php echo esc_html($member['level'] ?? 'Member'); ?>
                                        <span class="points">(<?php echo number_format($member['points'] ?? 0); ?> pts)</span>
                                    </p>
                                </div>
                                
                                <div class="member-actions">
                                    <?php if (is_user_logged_in() && $member['ID'] != get_current_user_id()): ?>
                                        <?php if (Community_X_Profile::is_following($member['ID'])): ?>
                                            <button class="btn btn-sm btn-secondary unfollow-btn" 
                                                    data-user-id="<?php echo $member['ID']; ?>">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-primary follow-btn" 
                                                    data-user-id="<?php echo $member['ID']; ?>">
                                                <i class="fas fa-user-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($member['bio'])): ?>
                                <div class="member-bio">
                                    <p><?php echo esc_html(wp_trim_words($member['bio'], 15)); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($member_skills)): ?>
                                <div class="member-skills">
                                    <?php foreach (array_slice($member_skills, 0, 3) as $skill): ?>
                                        <span class="skill-tag"><?php echo esc_html($skill); ?></span>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($member_skills) > 3): ?>
                                        <span class="skill-tag more">
                                            +<?php echo count($member_skills) - 3; ?> more
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="member-stats">
                                <div class="stat">
                                    <span class="stat-number"><?php echo number_format($member_stats['posts']); ?></span>
                                    <span class="stat-label"><?php _e('Posts', 'community-x'); ?></span>
                                </div>
                                <div class="stat">
                                    <span class="stat-number"><?php echo number_format($member_stats['followers']); ?></span>
                                    <span class="stat-label"><?php _e('Followers', 'community-x'); ?></span>
                                </div>
                                <div class="stat">
                                    <span class="stat-number"><?php echo number_format($member_stats['likes_received']); ?></span>
                                    <span class="stat-label"><?php _e('Likes', 'community-x'); ?></span>
                                </div>
                            </div>
                            
                            <div class="member-card-footer">
                                <span class="join-date">
                                    <?php printf(__('Joined %s', 'community-x'), 
                                               date_i18n('M Y', strtotime($member['user_registered']))); ?>
                                </span>
                                
                                <a href="<?php echo esc_url(home_url('/community/member/' . $member['user_login'] . '/')); ?>" 
                                   class="view-profile-btn">
                                    <?php _e('View Profile', 'community-x'); ?>
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_members > 12): ?>
                <div class="members-pagination">
                    <?php
                    $total_pages = ceil($total_members / 12);
                    $current_url = remove_query_arg('pg');
                    
                    // Previous page
                    if ($page > 1): ?>
                        <a href="<?php echo add_query_arg('pg', $page - 1, $current_url); ?>" 
                           class="pagination-btn prev-btn">
                            <i class="fas fa-chevron-left"></i> <?php _e('Previous', 'community-x'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Page numbers -->
                    <div class="page-numbers">
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <a href="<?php echo add_query_arg('pg', 1, $current_url); ?>" class="page-number">1</a>
                            <?php if ($start_page > 2): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="page-number current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="<?php echo add_query_arg('pg', $i, $current_url); ?>" class="page-number"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="page-dots">...</span>
                            <?php endif; ?>
                            <a href="<?php echo add_query_arg('pg', $total_pages, $current_url); ?>" class="page-number"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Next page -->
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo add_query_arg('pg', $page + 1, $current_url); ?>" 
                           class="pagination-btn next-btn">
                            <?php _e('Next', 'community-x'); ?> <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="community-footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="<?php echo home_url('/community/'); ?>"><?php _e('Community', 'community-x'); ?></a>
                <a href="<?php echo home_url('/community/members/'); ?>"><?php _e('Members', 'community-x'); ?></a>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo home_url('/community-dashboard/'); ?>"><?php _e('Dashboard', 'community-x'); ?></a>
                <?php endif; ?>
            </div>
            <div class="footer-text">
                <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html(get_option('community_x_community_name', 'Community X')); ?>. 
                   <?php _e('Powered by Community X', 'community-x'); ?></p>
            </div>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>

<script>
jQuery(document).ready(function($) {
    // Auto-submit form on filter change
    $('.filter-select, .filter-input').on('change', function() {
        $('#members-filter-form').submit();
    });
    
    // Follow/Unfollow functionality
    $('.follow-btn').on('click', function() {
        var $btn = $(this);
        var userId = $btn.data('user-id');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.post(community_x_ajax.ajax_url, {
            action: 'community_x_follow_user',
            user_id: userId,
            nonce: community_x_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                $btn.removeClass('follow-btn btn-primary')
                    .addClass('unfollow-btn btn-secondary')
                    .html('<i class="fas fa-user-minus"></i>');
            }
        })
        .always(function() {
            $btn.prop('disabled', false);
        });
    });
    
    $('.unfollow-btn').on('click', function() {
        var $btn = $(this);
        var userId = $btn.data('user-id');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.post(community_x_ajax.ajax_url, {
            action: 'community_x_unfollow_user',
            user_id: userId,
            nonce: community_x_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                $btn.removeClass('unfollow-btn btn-secondary')
                    .addClass('follow-btn btn-primary')
                    .html('<i class="fas fa-user-plus"></i>');
            }
        })
        .always(function() {
            $btn.prop('disabled', false);
        });
    });
    
    // User dropdown toggle
    $('.user-menu').on('click', function() {
        $(this).find('.user-dropdown').toggle();
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.user-menu').length) {
            $('.user-dropdown').hide();
        }
    });
});
</script>

</body>
</html>

<?php get_footer(); ?>