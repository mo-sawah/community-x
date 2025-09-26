<?php
/**
 * Admin Dashboard for Community X
 *
 * @since      1.0.0
 * @package    Community_X
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get community statistics
require_once COMMUNITY_X_PLUGIN_PATH . 'includes/class-community-x-database.php';
$database = new Community_X_Database();
$stats = $database->get_stats();

// Get recent activity (placeholder for now)
$recent_activity = array(
    array(
        'type' => 'user_joined',
        'message' => 'New member John Doe joined the community',
        'time' => '2 minutes ago'
    ),
    array(
        'type' => 'post_created',
        'message' => 'Sarah Smith created a new post in Web Development',
        'time' => '5 minutes ago'
    ),
    array(
        'type' => 'comment_added',
        'message' => 'Mike Johnson commented on "Best React Practices"',
        'time' => '8 minutes ago'
    )
);
?>

<div class="wrap community-x-admin">
    <h1>
        <i class="fas fa-tachometer-alt"></i>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>

    <?php settings_errors(); ?>

    <!-- Statistics Cards -->
    <div class="community-x-stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_users'] ?? 0); ?></h3>
                <p><?php _e('Community Members', 'community-x'); ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_posts'] ?? 0); ?></h3>
                <p><?php _e('Total Posts', 'community-x'); ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['total_likes'] ?? 0); ?></h3>
                <p><?php _e('Total Likes', 'community-x'); ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-bell"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($stats['unread_notifications'] ?? 0); ?></h3>
                <p><?php _e('Unread Notifications', 'community-x'); ?></p>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="community-x-dashboard-content">
        <div class="dashboard-left">
            <!-- Recent Activity -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h2><i class="fas fa-clock"></i> <?php _e('Recent Activity', 'community-x'); ?></h2>
                    <a href="<?php echo admin_url('admin.php?page=community-x-activity'); ?>" class="view-all">
                        <?php _e('View All', 'community-x'); ?>
                    </a>
                </div>
                <div class="widget-content">
                    <?php if (!empty($recent_activity)) : ?>
                        <ul class="activity-list">
                            <?php foreach ($recent_activity as $activity) : ?>
                                <li class="activity-item activity-<?php echo esc_attr($activity['type']); ?>">
                                    <div class="activity-icon">
                                        <?php
                                        $icon = 'fas fa-circle';
                                        switch ($activity['type']) {
                                            case 'user_joined':
                                                $icon = 'fas fa-user-plus';
                                                break;
                                            case 'post_created':
                                                $icon = 'fas fa-file-plus';
                                                break;
                                            case 'comment_added':
                                                $icon = 'fas fa-comment';
                                                break;
                                        }
                                        ?>
                                        <i class="<?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p><?php echo esc_html($activity['message']); ?></p>
                                        <span class="activity-time"><?php echo esc_html($activity['time']); ?></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="no-activity"><?php _e('No recent activity to display.', 'community-x'); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Posts -->
            <?php if ($stats['pending_posts'] > 0) : ?>
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h2><i class="fas fa-hourglass-half"></i> <?php _e('Posts Pending Review', 'community-x'); ?></h2>
                    <a href="<?php echo admin_url('admin.php?page=community-x-posts&status=pending'); ?>" class="view-all">
                        <?php _e('Review All', 'community-x'); ?>
                    </a>
                </div>
                <div class="widget-content">
                    <p class="pending-notice">
                        <strong><?php echo number_format($stats['pending_posts']); ?></strong>
                        <?php _e('posts are waiting for your review.', 'community-x'); ?>
                    </p>
                    <a href="<?php echo admin_url('admin.php?page=community-x-posts&status=pending'); ?>" class="button button-primary">
                        <i class="fas fa-eye"></i> <?php _e('Review Posts', 'community-x'); ?>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-right">
            <!-- Quick Actions -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h2><i class="fas fa-bolt"></i> <?php _e('Quick Actions', 'community-x'); ?></h2>
                </div>
                <div class="widget-content">
                    <div class="quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=community-x-settings'); ?>" class="quick-action-btn">
                            <i class="fas fa-cog"></i>
                            <span><?php _e('Settings', 'community-x'); ?></span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=community-x-categories'); ?>" class="quick-action-btn">
                            <i class="fas fa-folder"></i>
                            <span><?php _e('Categories', 'community-x'); ?></span>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=community-x-members'); ?>" class="quick-action-btn">
                            <i class="fas fa-users"></i>
                            <span><?php _e('Members', 'community-x'); ?></span>
                        </a>
                        <a href="<?php echo home_url('/community/'); ?>" class="quick-action-btn" target="_blank">
                            <i class="fas fa-external-link-alt"></i>
                            <span><?php _e('View Community', 'community-x'); ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Community Info -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h2><i class="fas fa-info-circle"></i> <?php _e('Community Info', 'community-x'); ?></h2>
                </div>
                <div class="widget-content">
                    <div class="community-info">
                        <p><strong><?php _e('Community Name:', 'community-x'); ?></strong><br>
                        <?php echo esc_html(get_option('community_x_community_name', 'Community X')); ?></p>
                        
                        <p><strong><?php _e('Total Categories:', 'community-x'); ?></strong><br>
                        <?php echo number_format($stats['total_categories'] ?? 0); ?></p>
                        
                        <p><strong><?php _e('Public Viewing:', 'community-x'); ?></strong><br>
                        <?php echo get_option('community_x_allow_public_viewing', 1) ? 
                            '<span class="status-enabled">' . __('Enabled', 'community-x') . '</span>' : 
                            '<span class="status-disabled">' . __('Disabled', 'community-x') . '</span>'; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Plugin Info -->
            <div class="dashboard-widget">
                <div class="widget-header">
                    <h2><i class="fas fa-puzzle-piece"></i> <?php _e('Plugin Info', 'community-x'); ?></h2>
                </div>
                <div class="widget-content">
                    <div class="plugin-info">
                        <p><strong><?php _e('Version:', 'community-x'); ?></strong> <?php echo COMMUNITY_X_VERSION; ?></p>
                        <p><strong><?php _e('Author:', 'community-x'); ?></strong> Mohamed Sawah</p>
                        <p><strong><?php _e('Website:', 'community-x'); ?></strong> 
                        <a href="https://sawahsolutions.com" target="_blank">sawahsolutions.com</a></p>
                    </div>
                    
                    <div class="plugin-actions">
                        <a href="<?php echo admin_url('admin.php?page=community-x-settings'); ?>" class="button">
                            <i class="fas fa-cog"></i> <?php _e('Configure', 'community-x'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.community-x-admin {
    margin-top: 20px;
}

.community-x-admin h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 30px;
}

.community-x-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #6366f1;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #6366f1, #06b6d4);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.stat-content h3 {
    font-size: 28px;
    font-weight: bold;
    margin: 0;
    color: #1e293b;
}

.stat-content p {
    margin: 5px 0 0 0;
    color: #64748b;
    font-weight: 500;
}

.community-x-dashboard-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.dashboard-widget {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.widget-header {
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.widget-header h2 {
    margin: 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.view-all {
    color: #6366f1;
    text-decoration: none;
    font-weight: 500;
}

.view-all:hover {
    text-decoration: underline;
}

.widget-content {
    padding: 20px;
}

.activity-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.activity-item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 32px;
    height: 32px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6366f1;
    flex-shrink: 0;
}

.activity-content p {
    margin: 0 0 4px 0;
    font-weight: 500;
}

.activity-time {
    color: #94a3b8;
    font-size: 12px;
}

.no-activity {
    text-align: center;
    color: #94a3b8;
    font-style: italic;
    padding: 40px 0;
}

.pending-notice {
    background: #fef3c7;
    padding: 15px;
    border-radius: 6px;
    border-left: 4px solid #f59e0b;
    margin-bottom: 15px;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px;
    background: #f8fafc;
    border-radius: 8px;
    text-decoration: none;
    color: #64748b;
    transition: all 0.2s;
}

.quick-action-btn:hover {
    background: #6366f1;
    color: white;
    text-decoration: none;
}

.quick-action-btn i {
    font-size: 24px;
}

.community-info p,
.plugin-info p {
    margin-bottom: 12px;
}

.status-enabled {
    color: #10b981;
    font-weight: 500;
}

.status-disabled {
    color: #ef4444;
    font-weight: 500;
}

.plugin-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f1f5f9;
}

@media (max-width: 1200px) {
    .community-x-dashboard-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .community-x-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
}
</style>