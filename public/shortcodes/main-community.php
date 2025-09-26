<?php
/**
 * Enhanced Main Community Page Template
 */

if (!defined('WPINC')) {
    die;
}

// Get current page data
$public = new Community_X_Public('community-x', COMMUNITY_X_VERSION);
$page_data = $public->get_current_page_data();

// Get search and filter parameters
$page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'latest';

$post_args = [
    'page' => $page,
    'search' => $search,
    'category_id' => $category_id,
    'orderby' => $sort === 'popular' ? 'like_count' : 'created_at',
    'order' => 'DESC',
    'per_page' => 10
];

$posts = Community_X_Post::get_posts($post_args);
$total_posts = Community_X_Post::count_posts(['category_id' => $category_id, 'search' => $search]);
$categories = Community_X_Category::get_all();

get_header(); ?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php _e('Community Feed', 'community-x'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>

<body <?php body_class('community-x-page community-feed-page'); ?>>

<div class="community-x-container">
    <!-- Enhanced Header -->
    <header class="community-header community-header-enhanced">
        <div class="header-content">
            <div class="community-branding">
                <h1 class="community-title">
                    <a href="<?php echo home_url('/community/'); ?>">
                        <i class="fas fa-comments"></i>
                        <?php echo esc_html(get_option('community_x_community_name', 'Community X')); ?>
                    </a>
                </h1>
                <p class="community-tagline">
                    <?php echo esc_html(get_option('community_x_community_description', 'Share, Connect, Grow Together')); ?>
                </p>
            </div>
            
            <?php echo $public->get_community_navigation(array(
                'current_page' => 'main',
                'show_icons' => true,
                'class' => 'community-nav-header'
            )); ?>
            
            <div class="header-actions">
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo home_url('/community/create-post/'); ?>" class="btn btn-primary create-post-btn">
                        <i class="fas fa-plus"></i> 
                        <span class="btn-text"><?php _e('New Post', 'community-x'); ?></span>
                    </a>
                    <div class="user-menu">
                        <img src="<?php echo esc_url(Community_X_User::get_user_avatar(get_current_user_id())); ?>" 
                             alt="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" 
                             class="user-avatar-small" />
                        <span class="user-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                        <i class="fas fa-chevron-down"></i>
                        <div class="user-dropdown">
                            <a href="<?php echo home_url('/community-dashboard/'); ?>">
                                <i class="fas fa-tachometer-alt"></i> <?php _e('Dashboard', 'community-x'); ?>
                            </a>
                            <a href="<?php echo home_url('/community/member/' . wp_get_current_user()->user_login . '/'); ?>">
                                <i class="fas fa-user"></i> <?php _e('My Profile', 'community-x'); ?>
                            </a>
                            <div class="dropdown-divider"></div>
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

    <!-- Main Content Area -->
    <main class="community-main">
        <div class="community-feed-container">
            <!-- Feed Header with Stats -->
            <div class="feed-header">
                <div class="feed-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo number_format($total_posts); ?></span>
                        <span class="stat-label"><?php _e('Posts', 'community-x'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo count($categories); ?></span>
                        <span class="stat-label"><?php _e('Categories', 'community-x'); ?></span>
                    </div>
                    <div class="stat-item">
                        <?php
                        $total_users = wp_count_users();
                        $community_users = get_users(['meta_key' => 'wp_capabilities', 'meta_value' => 'community_member', 'meta_compare' => 'LIKE', 'count_total' => true]);
                        ?>
                        <span class="stat-number"><?php echo number_format($community_users->get_total()); ?></span>
                        <span class="stat-label"><?php _e('Members', 'community-x'); ?></span>
                    </div>
                </div>
                
                <!-- Search and Filters -->
                <div class="feed-controls">
                    <form method="get" class="feed-search-form">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="search" name="search" 
                                   value="<?php echo esc_attr($search); ?>" 
                                   placeholder="<?php _e('Search posts...', 'community-x'); ?>" 
                                   class="search-input" />
                        </div>
                        
                        <div class="filter-controls">
                            <select name="category" class="filter-select" onchange="this.form.submit()">
                                <option value=""><?php _e('All Categories', 'community-x'); ?></option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php selected($category_id, $cat['id']); ?>>
                                        <?php echo esc_html($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="sort" class="filter-select" onchange="this.form.submit()">
                                <option value="latest" <?php selected($sort, 'latest'); ?>><?php _e('Latest', 'community-x'); ?></option>
                                <option value="popular" <?php selected($sort, 'popular'); ?>><?php _e('Popular', 'community-x'); ?></option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-filter"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Feed Layout -->
            <div class="feed-layout">
                <!-- Sidebar -->
                <aside class="feed-sidebar">
                    <!-- Categories Widget -->
                    <div class="sidebar-widget categories-widget">
                        <h3 class="widget-title">
                            <i class="fas fa-folder"></i> <?php _e('Categories', 'community-x'); ?>
                        </h3>
                        <ul class="category-list">
                            <li class="<?php echo is_null($category_id) ? 'active' : ''; ?>">
                                <a href="<?php echo home_url('/community/'); ?>" class="category-link">
                                    <i class="fas fa-globe"></i>
                                    <span><?php _e('All Posts', 'community-x'); ?></span>
                                    <span class="post-count"><?php echo $total_posts; ?></span>
                                </a>
                            </li>
                            <?php foreach ($categories as $cat): ?>
                                <li class="<?php echo ($category_id === $cat['id']) ? 'active' : ''; ?>">
                                    <a href="<?php echo esc_url(add_query_arg('category', $cat['id'], home_url('/community/'))); ?>" class="category-link">
                                        <i class="<?php echo esc_attr($cat['icon']); ?>" 
                                           style="color: <?php echo esc_attr($cat['color']); ?>"></i>
                                        <span><?php echo esc_html($cat['name']); ?></span>
                                        <span class="post-count"><?php echo $cat['post_count']; ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Popular Tags Widget -->
                    <div class="sidebar-widget tags-widget">
                        <h3 class="widget-title">
                            <i class="fas fa-tags"></i> <?php _e('Popular Tags', 'community-x'); ?>
                        </h3>
                        <div class="tags-cloud">
                            <?php
                            // Get popular tags (simplified for now)
                            $popular_tags = ['javascript', 'php', 'wordpress', 'css', 'html', 'react', 'python', 'tutorial'];
                            foreach ($popular_tags as $tag):
                            ?>
                                <a href="<?php echo add_query_arg('search', $tag, home_url('/community/')); ?>" class="tag-link">
                                    #<?php echo $tag; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Community Stats Widget -->
                    <div class="sidebar-widget stats-widget">
                        <h3 class="widget-title">
                            <i class="fas fa-chart-line"></i> <?php _e('Community Stats', 'community-x'); ?>
                        </h3>
                        <div class="stats-list">
                            <div class="stat-row">
                                <span class="stat-label"><?php _e('Posts Today', 'community-x'); ?></span>
                                <span class="stat-value">
                                    <?php 
                                    global $wpdb;
                                    $today_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}community_x_posts WHERE DATE(created_at) = CURDATE()");
                                    echo number_format($today_posts);
                                    ?>
                                </span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label"><?php _e('Active Members', 'community-x'); ?></span>
                                <span class="stat-value">
                                    <?php 
                                    $active_members = $wpdb->get_var("SELECT COUNT(DISTINCT author_id) FROM {$wpdb->prefix}community_x_posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                                    echo number_format($active_members);
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Main Feed Content -->
                <div class="feed-content">
                    <?php if (empty($posts)): ?>
                        <div class="no-posts-found">
                            <div class="no-posts-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h3><?php _e('No posts found', 'community-x'); ?></h3>
                            <p><?php _e('Be the first to share something with the community!', 'community-x'); ?></p>
                            <?php if (is_user_logged_in() && current_user_can('community_create_post')): ?>
                                <a href="<?php echo home_url('/community/create-post/'); ?>" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> <?php _e('Create First Post', 'community-x'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="posts-feed">
                            <?php foreach ($posts as $post): ?>
                                <article class="post-card" data-post-id="<?php echo $post['id']; ?>">
                                    <div class="post-header">
                                        <div class="post-author">
                                            <a href="<?php echo esc_url(home_url('/community/member/' . $post['author_login'] . '/')); ?>" class="author-link">
                                                <img src="<?php echo esc_url(Community_X_User::get_user_avatar($post['author_id'])); ?>" 
                                                     alt="<?php echo esc_attr($post['author_name']); ?>" 
                                                     class="author-avatar" />
                                                <div class="author-info">
                                                    <span class="author-name"><?php echo esc_html($post['author_name']); ?></span>
                                                    <span class="post-time">
                                                        <i class="fas fa-clock"></i>
                                                        <?php echo human_time_diff(strtotime($post['created_at']), current_time('timestamp')) . ' ' . __('ago', 'community-x'); ?>
                                                    </span>
                                                </div>
                                            </a>
                                        </div>
                                        
                                        <?php if ($post['category_name']): ?>
                                            <div class="post-category">
                                                <a href="<?php echo esc_url(add_query_arg('category', $post['category_id'], home_url('/community/'))); ?>" 
                                                   class="category-badge" 
                                                   style="background-color: <?php echo esc_attr($post['category_color']); ?>">
                                                    <i class="<?php echo esc_attr($post['category_icon'] ?? 'fas fa-folder'); ?>"></i>
                                                    <?php echo esc_html($post['category_name']); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="post-content">
                                        <h2 class="post-title">
                                            <a href="<?php echo esc_url(home_url('/community/post/' . $post['id'] . '/')); ?>">
                                                <?php echo esc_html($post['title']); ?>
                                            </a>
                                        </h2>
                                        
                                        <div class="post-excerpt">
                                            <?php echo wp_kses_post(wp_trim_words($post['content'], 30, '...')); ?>
                                        </div>
                                        
                                        <?php if (!empty($post['tags'])): ?>
                                            <div class="post-tags">
                                                <?php foreach (array_slice($post['tags'], 0, 3) as $tag): ?>
                                                    <a href="<?php echo add_query_arg('search', $tag, home_url('/community/')); ?>" class="post-tag">
                                                        #<?php echo esc_html($tag); ?>
                                                    </a>
                                                <?php endforeach; ?>
                                                <?php if (count($post['tags']) > 3): ?>
                                                    <span class="more-tags">+<?php echo count($post['tags']) - 3; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="post-footer">
                                        <div class="post-stats">
                                            <button class="stat-btn like-btn" data-post-id="<?php echo $post['id']; ?>">
                                                <i class="fas fa-heart"></i>
                                                <span><?php echo number_format($post['like_count']); ?></span>
                                            </button>
                                            <a href="<?php echo esc_url(home_url('/community/post/' . $post['id'] . '/#comments')); ?>" class="stat-btn comment-btn">
                                                <i class="fas fa-comment"></i>
                                                <span><?php echo number_format($post['comment_count']); ?></span>
                                            </a>
                                            <button class="stat-btn share-btn" data-post-id="<?php echo $post['id']; ?>">
                                                <i class="fas fa-share"></i>
                                                <span><?php _e('Share', 'community-x'); ?></span>
                                            </button>
                                        </div>
                                        
                                        <a href="<?php echo esc_url(home_url('/community/post/' . $post['id'] . '/')); ?>" class="read-more-btn">
                                            <?php _e('Read More', 'community-x'); ?>
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_posts > 10): ?>
                            <div class="feed-pagination">
                                <?php
                                $total_pages = ceil($total_posts / 10);
                                $current_url = remove_query_arg('pg');
                                
                                if ($page > 1): ?>
                                    <a href="<?php echo add_query_arg('pg', $page - 1, $current_url); ?>" 
                                       class="pagination-btn prev-btn">
                                        <i class="fas fa-chevron-left"></i> <?php _e('Previous', 'community-x'); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <div class="page-numbers">
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <?php if ($i == $page): ?>
                                            <span class="page-number current"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="<?php echo add_query_arg('pg', $i, $current_url); ?>" class="page-number"><?php echo $i; ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="<?php echo add_query_arg('pg', $page + 1, $current_url); ?>" 
                                       class="pagination-btn next-btn">
                                        <?php _e('Next', 'community-x'); ?> <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
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

</body>
</html>

<?php get_footer(); ?>