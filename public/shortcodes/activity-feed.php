<?php
/**
 * Activity Feed Shortcode
 *
 * @since      1.0.0
 * @package    Community_X
 */

if (!defined('WPINC')) {
    die;
}

if (!is_user_logged_in()) {
    return '<p class="community-x-info">' . __('Please log in to view your activity feed.', 'community-x') . '</p>';
}

$user_id = get_current_user_id();
$activities = Community_X_Interactions::get_user_activity_feed($user_id, 20);
?>

<div class="community-x-activity-feed">
    <div class="activity-feed-header">
        <h3>
            <i class="fas fa-stream"></i>
            <?php _e('Activity Feed', 'community-x'); ?>
        </h3>
        <p><?php _e('Latest updates from people you follow', 'community-x'); ?></p>
    </div>
    
    <?php if (empty($activities)): ?>
        <div class="no-activity-feed">
            <div class="no-activity-icon">
                <i class="fas fa-rss"></i>
            </div>
            <h4><?php _e('Your feed is empty', 'community-x'); ?></h4>
            <p><?php _e('Follow other members to see their activities in your feed.', 'community-x'); ?></p>
            <a href="<?php echo home_url('/community/members/'); ?>" class="btn btn-primary">
                <i class="fas fa-users"></i> <?php _e('Discover Members', 'community-x'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="activity-feed-list">
            <?php foreach ($activities as $activity): ?>
                <div class="activity-feed-item" data-activity-id="<?php echo $activity['id']; ?>">
                    <div class="activity-avatar">
                        <img src="<?php echo esc_url(Community_X_User::get_user_avatar($activity['user_id'])); ?>" 
                             alt="<?php echo esc_attr($activity['user_name']); ?>" />
                    </div>
                    
                    <div class="activity-content">
                        <div class="activity-header">
                            <a href="<?php echo home_url('/community/member/' . $activity['user_login'] . '/'); ?>" class="activity-user">
                                <?php echo esc_html($activity['user_name']); ?>
                            </a>
                            <span class="activity-action">
                                <?php
                                switch ($activity['action']) {
                                    case 'post_created':
                                        echo '<i class="fas fa-plus-circle"></i> ' . __('created a new post', 'community-x');
                                        break;
                                    case 'post_liked':
                                        echo '<i class="fas fa-heart"></i> ' . __('liked a post', 'community-x');
                                        break;
                                    case 'user_followed':
                                        echo '<i class="fas fa-user-plus"></i> ' . __('followed someone', 'community-x');
                                        break;
                                    default:
                                        echo esc_html($activity['action']);
                                }
                                ?>
                            </span>
                            <span class="activity-time">
                                <?php echo human_time_diff(strtotime($activity['created_at']), current_time('timestamp')) . ' ' . __('ago', 'community-x'); ?>
                            </span>
                        </div>
                        
                        <?php if ($activity['post_title']): ?>
                            <div class="activity-object">
                                <a href="<?php echo home_url('/community/post/' . $activity['post_id'] . '/'); ?>" class="activity-post-link">
                                    <i class="fas fa-file-alt"></i>
                                    <?php echo esc_html($activity['post_title']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="activity-feed-footer">
            <button class="btn btn-secondary load-more-activity" data-page="2">
                <i class="fas fa-refresh"></i> <?php _e('Load More', 'community-x'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>

<style>
.community-x-activity-feed {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    overflow: hidden;
}

.activity-feed-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #f1f5f9;
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
}

.activity-feed-header h3 {
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #1e293b;
}

.activity-feed-header p {
    margin: 0;
    color: #64748b;
}

.no-activity-feed {
    text-align: center;
    padding: 3rem 2rem;
}

.no-activity-icon {
    font-size: 3rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.activity-feed-list {
    padding: 1rem 0;
}

.activity-feed-item {
    display: flex;
    gap: 1rem;
    padding: 1rem 2rem;
    border-bottom: 1px solid #f8fafc;
    transition: background 0.2s ease;
}

.activity-feed-item:hover {
    background: #f8fafc;
}

.activity-feed-item:last-child {
    border-bottom: none;
}

.activity-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.activity-content {
    flex: 1;
}

.activity-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}

.activity-user {
    font-weight: 600;
    color: #1e293b;
    text-decoration: none;
}

.activity-user:hover {
    color: #6366f1;
}

.activity-action {
    color: #64748b;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.activity-time {
    color: #94a3b8;
    font-size: 0.75rem;
    margin-left: auto;
}

.activity-object {
    margin-top: 0.5rem;
}

.activity-post-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #6366f1;
    text-decoration: none;
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
    background: #f1f5f9;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.activity-post-link:hover {
    background: #e2e8f0;
    color: #4f46e5;
}

.activity-feed-footer {
    padding: 1.5rem 2rem;
    text-align: center;
    border-top: 1px solid #f1f5f9;
}

@media (max-width: 768px) {
    .activity-feed-item {
        padding: 1rem;
    }
    
    .activity-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .activity-time {
        margin-left: 0;
    }
}
</style>