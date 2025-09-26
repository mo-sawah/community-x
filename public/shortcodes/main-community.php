<?php
/**
 * Main Community Page Template (Shortcode)
 *
 * @since      1.0.0
 * @package    Community_X
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : null;

$post_args = [
    'page' => $page,
    'search' => $search,
    'category_id' => $category_id
];

$posts = Community_X_Post::get_posts($post_args);
$total_posts = Community_X_Post::count_posts(['category_id' => $category_id]);
$categories = Community_X_Category::get_all();
?>
<div class="community-x-main">
    <div class="community-feed-header">
        <h1><?php _e('Community Feed', 'community-x'); ?></h1>
        <p><?php printf(__('Browse the latest %d posts from our members.', 'community-x'), $total_posts); ?></p>
        <?php if (is_user_logged_in() && current_user_can('community_create_post')): ?>
            <a href="<?php echo home_url('/community/create-post/'); // We will create this page/rule later ?>" class="btn btn-primary create-post-btn">
                <i class="fas fa-plus"></i> <?php _e('Create a New Post', 'community-x'); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <div class="feed-layout">
        <aside class="feed-sidebar">
            <div class="sidebar-widget">
                <h4><?php _e('Categories', 'community-x'); ?></h4>
                <ul class="category-list">
                    <li class="<?php echo is_null($category_id) ? 'active' : ''; ?>">
                        <a href="<?php echo home_url('/community/'); ?>">
                            <i class="fas fa-globe"></i> <?php _e('All Posts', 'community-x'); ?>
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                        <li class="<?php echo ($category_id === $cat['id']) ? 'active' : ''; ?>">
                            <a href="<?php echo esc_url(add_query_arg('category', $cat['id'], home_url('/community/'))); ?>">
                                <i class="<?php echo esc_attr($cat['icon']); ?>" style="color: <?php echo esc_attr($cat['color']); ?>"></i>
                                <?php echo esc_html($cat['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <main class="feed-content">
            <div class="posts-list">
                <?php if (empty($posts)): ?>
                    <div class="no-posts-found">
                        <h3><?php _e('No posts found', 'community-x'); ?></h3>
                        <p><?php _e('There are no posts matching your criteria. Why not create the first one?', 'community-x'); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="post-item">
                            <div class="post-author-avatar">
                                <a href="<?php echo esc_url(home_url('/community/member/' . $post['author_login'] . '/')); ?>">
                                    <img src="<?php echo esc_url(Community_X_User::get_user_avatar($post['author_id'])); ?>" alt="<?php echo esc_attr($post['author_name']); ?>">
                                </a>
                            </div>
                            <div class="post-content">
                                <header class="post-header">
                                    <h2 class="post-title">
                                        <a href="<?php echo esc_url(home_url('/community/post/' . $post['id'] . '/')); ?>">
                                            <?php echo esc_html($post['title']); ?>
                                        </a>
                                    </h2>
                                    <div class="post-meta">
                                        <span class="author-name">
                                            <a href="<?php echo esc_url(home_url('/community/member/' . $post['author_login'] . '/')); ?>">
                                                <?php echo esc_html($post['author_name']); ?>
                                            </a>
                                        </span>
                                        <span class="post-date">
                                            <?php echo human_time_diff(strtotime($post['created_at']), current_time('timestamp')) . ' ' . __('ago', 'community-x'); ?>
                                        </span>
                                        <?php if ($post['category_name']): ?>
                                            <span class="post-category">
                                                <a href="<?php echo esc_url(add_query_arg('category', $post['category_id'], home_url('/community/'))); ?>" style="background-color: <?php echo esc_attr($post['category_color']); ?>">
                                                    <?php echo esc_html($post['category_name']); ?>
                                                </a>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </header>
                                <div class="post-excerpt">
                                    <p><?php echo wp_kses_post(wp_trim_words($post['content'], 30, '...')); ?></p>
                                </div>
                                <footer class="post-footer">
                                    <div class="post-stats">
                                        <span><i class="fas fa-heart"></i> <?php echo number_format($post['like_count']); ?></span>
                                        <span><i class="fas fa-comment"></i> <?php echo number_format($post['comment_count']); ?></span>
                                    </div>
                                    <a href="<?php echo esc_url(home_url('/community/post/' . $post['id'] . '/')); ?>" class="btn btn-sm btn-outline read-more">
                                        <?php _e('Read More', 'community-x'); ?>
                                    </a>
                                </footer>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            </main>
    </div>
</div>