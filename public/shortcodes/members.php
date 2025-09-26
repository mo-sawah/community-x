<?php
/**
 * Members Directory Shortcode
 *
 * @since      1.0.0
 * @package    Community_X
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get shortcode attributes
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$skills_filter = isset($_GET['skills']) ? array_map('sanitize_text_field', explode(',', $_GET['skills'])) : array();
$location_filter = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
$per_page = isset($atts['per_page']) ? intval($atts['per_page']) : 12;
$show_search = isset($atts['show_search']) && $atts['show_search'] === 'no' ? false : true;
$show_filters = isset($atts['show_filters']) && $atts['show_filters'] === 'no' ? false : true;

// Search members
$members = Community_X_User::search_users(array(
    'search' => $search,
    'skills' => $skills_filter,
    'location' => $location_filter,
    'per_page' => $per_page,
    'page' => 1,
    'orderby' => 'registered',
    'order' => 'desc',
    'public_only' => !is_user_logged_in()
));

// Get all skills for filter
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
?>

<div class="community-x-members-shortcode">
    <?php if ($show_search || $show_filters): ?>
        <div class="members-search-filters">
            <form method="get" class="members-filter-form">
                <?php if ($show_search): ?>
                    <div class="search-section">
                        <div class="search-input-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="search" name="search" 
                                   value="<?php echo esc_attr($search); ?>" 
                                   placeholder="<?php _e('Search members...', 'community-x'); ?>" 
                                   class="members-search-input" />
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_filters): ?>
                    <div class="filters-section">
                        <div class="filter-row">
                            <div class="filter-item">
                                <label for="skills-select"><?php _e('Skills:', 'community-x'); ?></label>
                                <select id="skills-select" name="skills" class="filter-select">
                                    <option value=""><?php _e('All Skills', 'community-x'); ?></option>
                                    <?php foreach ($all_skills as $skill): ?>
                                        <option value="<?php echo esc_attr($skill); ?>" 
                                                <?php selected(in_array($skill, $skills_filter)); ?>>
                                            <?php echo esc_html($skill); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-item">
                                <label for="location-input"><?php _e('Location:', 'community-x'); ?></label>
                                <input type="text" id="location-input" name="location" 
                                       value="<?php echo esc_attr($location_filter); ?>" 
                                       placeholder="<?php _e('Any location', 'community-x'); ?>" 
                                       class="filter-input" />
                            </div>
                            
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> <?php _e('Filter', 'community-x'); ?>
                                </button>
                                
                                <?php if ($search || !empty($skills_filter) || $location_filter): ?>
                                    <a href="<?php echo remove_query_arg(array('search', 'skills', 'location')); ?>" 
                                       class="btn btn-secondary">
                                        <i class="fas fa-times"></i> <?php _e('Clear', 'community-x'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>

    <div class="members-results">
        <?php if (empty($members)): ?>
            <div class="no-members-found">
                <div class="no-members-icon">
                    <i class="fas fa-users-slash"></i>
                </div>
                <h3><?php _e('No members found', 'community-x'); ?></h3>
                <p><?php _e('Try adjusting your search criteria.', 'community-x'); ?></p>
            </div>
        <?php else: ?>
            <div class="members-grid">
                <?php foreach ($members as $member): ?>
                    <?php
                    $member_stats = Community_X_User::get_user_stats($member['ID']);
                    $member_avatar = Community_X_User::get_user_avatar($member['ID']);
                    $member_skills = !empty($member['skills']) ? array_slice($member['skills'], 0, 3) : array();
                    ?>
                    <div class="member-card-compact">
                        <div class="member-card-header">
                            <div class="member-avatar-wrapper">
                                <img src="<?php echo esc_url($member_avatar); ?>" 
                                     alt="<?php echo esc_attr($member['display_name']); ?>" 
                                     class="member-avatar" />
                                <div class="online-status"></div>
                            </div>
                            
                            <div class="member-info">
                                <h4 class="member-name">
                                    <a href="<?php echo esc_url(home_url('/community/member/' . $member['user_login'] . '/')); ?>">
                                        <?php echo esc_html($member['display_name']); ?>
                                    </a>
                                </h4>
                                
                                <?php if (!empty($member['location'])): ?>
                                    <p class="member-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo esc_html($member['location']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="member-level">
                                    <span class="level-badge">
                                        <?php echo esc_html($member['level'] ?? 'Member'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($member['bio'])): ?>
                            <div class="member-bio">
                                <p><?php echo esc_html(wp_trim_words($member['bio'], 12)); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($member_skills)): ?>
                            <div class="member-skills">
                                <?php foreach ($member_skills as $skill): ?>
                                    <span class="skill-badge"><?php echo esc_html($skill); ?></span>
                                <?php endforeach; ?>
                                
                                <?php if (count($member['skills'] ?? array()) > 3): ?>
                                    <span class="skill-badge more-skills">
                                        +<?php echo count($member['skills']) - 3; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="member-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo number_format($member_stats['posts']); ?></span>
                                <span class="stat-label"><?php _e('Posts', 'community-x'); ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo number_format($member_stats['followers']); ?></span>
                                <span class="stat-label"><?php _e('Followers', 'community-x'); ?></span>
                            </div>
                        </div>
                        
                        <div class="member-actions">
                            <?php if (is_user_logged_in() && $member['ID'] != get_current_user_id()): ?>
                                <?php if (Community_X_Profile::is_following($member['ID'])): ?>
                                    <button class="btn btn-sm btn-secondary unfollow-btn" 
                                            data-user-id="<?php echo $member['ID']; ?>">
                                        <i class="fas fa-user-check"></i> <?php _e('Following', 'community-x'); ?>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-primary follow-btn" 
                                            data-user-id="<?php echo $member['ID']; ?>">
                                        <i class="fas fa-user-plus"></i> <?php _e('Follow', 'community-x'); ?>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <a href="<?php echo esc_url(home_url('/community/member/' . $member['user_login'] . '/')); ?>" 
                               class="btn btn-sm btn-outline view-profile-btn">
                                <i class="fas fa-eye"></i> <?php _e('View', 'community-x'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($members) >= $per_page): ?>
                <div class="members-load-more">
                    <a href="<?php echo home_url('/community/members/'); ?>" 
                       class="btn btn-primary">
                        <?php _e('View All Members', 'community-x'); ?> 
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>