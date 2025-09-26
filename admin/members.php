<?php
/**
 * Admin Members Management Page for Community X
 *
 * @since      1.0.0
 * @package    Community_X
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check user permissions
if (!current_user_can('community_manage_users')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'community-x'));
}

// Handle bulk actions
if (isset($_POST['action']) && isset($_POST['users']) && !empty($_POST['users'])) {
    check_admin_referer('bulk_members_action');
    
    $action = sanitize_text_field($_POST['action']);
    $user_ids = array_map('intval', $_POST['users']);
    
    switch ($action) {
        case 'delete':
            foreach ($user_ids as $user_id) {
                if (current_user_can('delete_users') && $user_id != get_current_user_id()) {
                    wp_delete_user($user_id);
                }
            }
            $message = sprintf(__('%d members deleted successfully.', 'community-x'), count($user_ids));
            break;
            
        case 'promote_moderator':
            foreach ($user_ids as $user_id) {
                $user = new WP_User($user_id);
                $user->set_role('community_moderator');
            }
            $message = sprintf(__('%d members promoted to moderator.', 'community-x'), count($user_ids));
            break;
            
        case 'demote_member':
            foreach ($user_ids as $user_id) {
                $user = new WP_User($user_id);
                $user->set_role('community_member');
            }
            $message = sprintf(__('%d moderators demoted to member.', 'community-x'), count($user_ids));
            break;
    }
    
    if (isset($message)) {
        echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
    }
}

// Get search and filter parameters
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$role_filter = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'registered';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;

// Build user query arguments
$args = array(
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key'     => 'wp_capabilities',
            'value'   => 'community_member',
            'compare' => 'LIKE'
        ),
        array(
            'key'     => 'wp_capabilities',
            'value'   => 'community_moderator', 
            'compare' => 'LIKE'
        )
    ),
    'number' => $per_page,
    'offset' => ($paged - 1) * $per_page,
    'orderby' => $orderby,
    'order' => strtoupper($order)
);

// Add search if provided
if (!empty($search)) {
    $args['search'] = '*' . $search . '*';
    $args['search_columns'] = array('user_login', 'user_email', 'display_name');
}

// Add role filter if provided
if (!empty($role_filter)) {
    $args['meta_query'] = array(
        array(
            'key'     => 'wp_capabilities',
            'value'   => $role_filter,
            'compare' => 'LIKE'
        )
    );
}

// Get users
$user_query = new WP_User_Query($args);
$users = $user_query->get_results();
$total_users = $user_query->get_total();

// Get database for stats
require_once COMMUNITY_X_PLUGIN_PATH . 'includes/class-community-x-database.php';
$database = new Community_X_Database();
$stats = $database->get_stats();
?>

<div class="wrap community-x-members">
    <h1>
        <i class="fas fa-users"></i>
        <?php echo esc_html(get_admin_page_title()); ?>
        <a href="#add-member" class="page-title-action add-member-btn">
            <i class="fas fa-user-plus"></i> <?php _e('Add Member', 'community-x'); ?>
        </a>
    </h1>

    <!-- Statistics Overview -->
    <div class="members-stats">
        <div class="stat-box">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_users'] ?? 0); ?></h3>
                <p><?php _e('Total Members', 'community-x'); ?></p>
            </div>
        </div>
        
        <div class="stat-box">
            <div class="stat-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="stat-content">
                <?php
                $moderators = get_users(array(
                    'meta_key' => 'wp_capabilities',
                    'meta_value' => 'community_moderator',
                    'meta_compare' => 'LIKE',
                    'count_total' => true
                ));
                ?>
                <h3><?php echo number_format($moderators->get_total()); ?></h3>
                <p><?php _e('Moderators', 'community-x'); ?></p>
            </div>
        </div>
        
        <div class="stat-box">
            <div class="stat-icon">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <div class="stat-content">
                <?php
                $new_members = get_users(array(
                    'meta_query' => array(
                        'relation' => 'OR',
                        array(
                            'key'     => 'wp_capabilities',
                            'value'   => 'community_member',
                            'compare' => 'LIKE'
                        ),
                        array(
                            'key'     => 'wp_capabilities',
                            'value'   => 'community_moderator',
                            'compare' => 'LIKE'
                        )
                    ),
                    'date_query' => array(
                        array(
                            'after' => '1 week ago'
                        )
                    ),
                    'count_total' => true
                ));
                ?>
                <h3><?php echo number_format($new_members->get_total()); ?></h3>
                <p><?php _e('New This Week', 'community-x'); ?></p>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="members-filters">
        <form method="get" class="filters-form">
            <input type="hidden" name="page" value="community-x-members" />
            
            <div class="filter-group">
                <label for="members-search"><?php _e('Search:', 'community-x'); ?></label>
                <input type="search" id="members-search" name="s" 
                       value="<?php echo esc_attr($search); ?>" 
                       placeholder="<?php _e('Search members...', 'community-x'); ?>" />
            </div>
            
            <div class="filter-group">
                <label for="role-filter"><?php _e('Role:', 'community-x'); ?></label>
                <select id="role-filter" name="role">
                    <option value=""><?php _e('All Roles', 'community-x'); ?></option>
                    <option value="community_member" <?php selected($role_filter, 'community_member'); ?>>
                        <?php _e('Community Member', 'community-x'); ?>
                    </option>
                    <option value="community_moderator" <?php selected($role_filter, 'community_moderator'); ?>>
                        <?php _e('Community Moderator', 'community-x'); ?>
                    </option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="orderby-filter"><?php _e('Order by:', 'community-x'); ?></label>
                <select id="orderby-filter" name="orderby">
                    <option value="registered" <?php selected($orderby, 'registered'); ?>>
                        <?php _e('Registration Date', 'community-x'); ?>
                    </option>
                    <option value="display_name" <?php selected($orderby, 'display_name'); ?>>
                        <?php _e('Name', 'community-x'); ?>
                    </option>
                    <option value="user_email" <?php selected($orderby, 'user_email'); ?>>
                        <?php _e('Email', 'community-x'); ?>
                    </option>
                </select>
            </div>
            
            <div class="filter-group">
                <select name="order">
                    <option value="desc" <?php selected($order, 'desc'); ?>><?php _e('Descending', 'community-x'); ?></option>
                    <option value="asc" <?php selected($order, 'asc'); ?>><?php _e('Ascending', 'community-x'); ?></option>
                </select>
            </div>
            
            <button type="submit" class="button">
                <i class="fas fa-search"></i> <?php _e('Filter', 'community-x'); ?>
            </button>
            
            <?php if ($search || $role_filter): ?>
                <a href="<?php echo admin_url('admin.php?page=community-x-members'); ?>" class="button">
                    <i class="fas fa-times"></i> <?php _e('Clear', 'community-x'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Members Table -->
    <form method="post" id="members-form">
        <?php wp_nonce_field('bulk_members_action'); ?>
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <select name="action" id="bulk-action-selector-top">
                    <option value="-1"><?php _e('Bulk Actions', 'community-x'); ?></option>
                    <option value="promote_moderator"><?php _e('Promote to Moderator', 'community-x'); ?></option>
                    <option value="demote_member"><?php _e('Demote to Member', 'community-x'); ?></option>
                    <?php if (current_user_can('delete_users')): ?>
                        <option value="delete"><?php _e('Delete', 'community-x'); ?></option>
                    <?php endif; ?>
                </select>
                <button type="submit" class="button action" onclick="return confirm('<?php _e('Are you sure you want to perform this action?', 'community-x'); ?>')">
                    <?php _e('Apply', 'community-x'); ?>
                </button>
            </div>
            
            <!-- Pagination -->
            <?php
            $page_links = paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'current' => $paged,
                'total' => ceil($total_users / $per_page)
            ));
            
            if ($page_links) {
                echo '<div class="tablenav-pages">' . $page_links . '</div>';
            }
            ?>
        </div>

        <table class="wp-list-table widefat fixed striped members">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1" />
                        <label for="cb-select-all-1">
                            <span class="screen-reader-text"><?php _e('Select All', 'community-x'); ?></span>
                        </label>
                    </td>
                    <th class="manage-column column-avatar"><?php _e('Avatar', 'community-x'); ?></th>
                    <th class="manage-column column-name"><?php _e('Name', 'community-x'); ?></th>
                    <th class="manage-column column-email"><?php _e('Email', 'community-x'); ?></th>
                    <th class="manage-column column-role"><?php _e('Role', 'community-x'); ?></th>
                    <th class="manage-column column-posts"><?php _e('Posts', 'community-x'); ?></th>
                    <th class="manage-column column-registered"><?php _e('Registered', 'community-x'); ?></th>
                    <th class="manage-column column-actions"><?php _e('Actions', 'community-x'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr class="no-items">
                        <td colspan="8" class="colspanchange">
                            <?php _e('No members found.', 'community-x'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $user_profile = Community_X_User::get_user_profile($user->ID);
                        $user_stats = Community_X_User::get_user_stats($user->ID);
                        $user_roles = $user->roles;
                        $primary_role = !empty($user_roles) ? $user_roles[0] : 'subscriber';
                        ?>
                        <tr id="user-<?php echo $user->ID; ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="users[]" 
                                       id="user_<?php echo $user->ID; ?>" 
                                       value="<?php echo $user->ID; ?>"
                                       <?php disabled($user->ID, get_current_user_id()); ?> />
                                <label for="user_<?php echo $user->ID; ?>">
                                    <span class="screen-reader-text">
                                        <?php printf(__('Select %s', 'community-x'), $user->display_name); ?>
                                    </span>
                                </label>
                            </th>
                            
                            <td class="column-avatar">
                                <img src="<?php echo esc_url(Community_X_User::get_user_avatar($user->ID)); ?>" 
                                     alt="<?php echo esc_attr($user->display_name); ?>" 
                                     class="member-avatar" />
                            </td>
                            
                            <td class="column-name">
                                <strong>
                                    <a href="<?php echo esc_url(home_url("/community/member/{$user->user_login}/")); ?>" 
                                       target="_blank">
                                        <?php echo esc_html($user->display_name); ?>
                                    </a>
                                </strong>
                                <br>
                                <span class="username">@<?php echo esc_html($user->user_login); ?></span>
                                
                                <?php if (!empty($user_profile['location'])): ?>
                                    <br>
                                    <span class="location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo esc_html($user_profile['location']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="column-email">
                                <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                    <?php echo esc_html($user->user_email); ?>
                                </a>
                            </td>
                            
                            <td class="column-role">
                                <span class="role-badge role-<?php echo esc_attr($primary_role); ?>">
                                    <?php
                                    switch ($primary_role) {
                                        case 'community_moderator':
                                            echo '<i class="fas fa-shield-alt"></i> ' . __('Moderator', 'community-x');
                                            break;
                                        case 'community_member':
                                            echo '<i class="fas fa-user"></i> ' . __('Member', 'community-x');
                                            break;
                                        default:
                                            echo '<i class="fas fa-user-circle"></i> ' . ucfirst($primary_role);
                                    }
                                    ?>
                                </span>
                            </td>
                            
                            <td class="column-posts">
                                <span class="posts-count">
                                    <?php echo number_format($user_stats['posts']); ?>
                                </span>
                            </td>
                            
                            <td class="column-registered">
                                <?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?>
                                <br>
                                <span class="time-ago">
                                    <?php echo human_time_diff(strtotime($user->user_registered), current_time('timestamp')) . ' ' . __('ago', 'community-x'); ?>
                                </span>
                            </td>
                            
                            <td class="column-actions">
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo esc_url(home_url("/community/member/{$user->user_login}/")); ?>" 
                                           target="_blank" title="<?php _e('View Profile', 'community-x'); ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </span>
                                    
                                    <span class="edit">
                                        <a href="<?php echo esc_url(admin_url("user-edit.php?user_id={$user->ID}")); ?>" 
                                           title="<?php _e('Edit User', 'community-x'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </span>
                                    
                                    <?php if ($user->ID != get_current_user_id() && current_user_can('delete_users')): ?>
                                        <span class="delete">
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url("users.php?action=delete&user={$user->ID}"), 'bulk-users')); ?>" 
                                               class="delete-link" 
                                               title="<?php _e('Delete User', 'community-x'); ?>"
                                               onclick="return confirm('<?php _e('Are you sure you want to delete this user?', 'community-x'); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="action2" id="bulk-action-selector-bottom">
                    <option value="-1"><?php _e('Bulk Actions', 'community-x'); ?></option>
                    <option value="promote_moderator"><?php _e('Promote to Moderator', 'community-x'); ?></option>
                    <option value="demote_member"><?php _e('Demote to Member', 'community-x'); ?></option>
                    <?php if (current_user_can('delete_users')): ?>
                        <option value="delete"><?php _e('Delete', 'community-x'); ?></option>
                    <?php endif; ?>
                </select>
                <button type="submit" class="button action" onclick="return confirm('<?php _e('Are you sure you want to perform this action?', 'community-x'); ?>')">
                    <?php _e('Apply', 'community-x'); ?>
                </button>
            </div>
            
            <?php if ($page_links): ?>
                <div class="tablenav-pages"><?php echo $page_links; ?></div>
            <?php endif; ?>
        </div>
    </form>

    <!-- Member Details Modal -->
    <div id="member-details-modal" class="community-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php _e('Member Details', 'community-x'); ?></h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<style>
.community-x-members .members-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0 30px 0;
}

.community-x-members .stat-box {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #6366f1;
    display: flex;
    align-items: center;
    gap: 15px;
}

.community-x-members .stat-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #6366f1, #06b6d4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.community-x-members .stat-content h3 {
    font-size: 24px;
    font-weight: bold;
    margin: 0;
    color: #1e293b;
}

.community-x-members .stat-content p {
    margin: 5px 0 0 0;
    color: #64748b;
    font-weight: 500;
}

.community-x-members .members-filters {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.community-x-members .filters-form {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.community-x-members .filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.community-x-members .filter-group label {
    font-weight: 500;
    color: #374151;
    font-size: 13px;
}

.community-x-members .filter-group input,
.community-x-members .filter-group select {
    padding: 8px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
}

.community-x-members .filter-group input:focus,
.community-x-members .filter-group select:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    outline: none;
}

.community-x-members .member-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.community-x-members .username {
    color: #6b7280;
    font-size: 13px;
}

.community-x-members .location {
    color: #6b7280;
    font-size: 12px;
}

.community-x-members .time-ago {
    color: #9ca3af;
    font-size: 12px;
}

.community-x-members .role-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.community-x-members .role-community_moderator {
    background: #dbeafe;
    color: #1d4ed8;
}

.community-x-members .role-community_member {
    background: #dcfce7;
    color: #15803d;
}

.community-x-members .posts-count {
    font-weight: 600;
    color: #374151;
}

.community-x-members .row-actions {
    display: flex;
    gap: 10px;
}

.community-x-members .row-actions a {
    color: #6b7280;
    text-decoration: none;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s;
}

.community-x-members .row-actions a:hover {
    background: #f3f4f6;
    color: #374151;
}

.community-x-members .row-actions .delete a:hover {
    background: #fee2e2;
    color: #dc2626;
}

@media (max-width: 768px) {
    .community-x-members .members-stats {
        grid-template-columns: 1fr;
    }
    
    .community-x-members .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle bulk action checkboxes
    $('#cb-select-all-1').on('change', function() {
        $('input[name="users[]"]').prop('checked', this.checked);
    });
    
    // Handle individual checkboxes
    $('input[name="users[]"]').on('change', function() {
        var total = $('input[name="users[]"]').length;
        var checked = $('input[name="users[]"]:checked').length;
        $('#cb-select-all-1').prop('checked', total === checked);
    });
});
</script>