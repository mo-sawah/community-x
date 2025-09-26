<?php
/**
 * Enhanced Single Post Template
 */

if (!defined('WPINC')) {
    die;
}

$post_id = get_query_var('post_id');
$post = Community_X_Post::get_post($post_id);

if (!$post) {
    wp_redirect(home_url('/community/'));
    exit;
}

// Increment view count
global $wpdb;
$wpdb->query($wpdb->prepare(
    "UPDATE {$wpdb->prefix}community_x_posts SET view_count = view_count + 1 WHERE id = %d",
    $post_id
));

get_header(); ?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($post['title']); ?> - <?php bloginfo('name'); ?></title>
    <meta name="description" content="<?php echo esc_attr(wp_trim_words(strip_tags($post['content']), 30)); ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo esc_attr($post['title']); ?>">
    <meta property="og:description" content="<?php echo esc_attr(wp_trim_words(strip_tags($post['content']), 30)); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo esc_url(home_url('/community/post/' . $post['id'] . '/')); ?>">
    
    <?php wp_head(); ?>
</head>

<body <?php body_class('community-x-page single-post-page'); ?>>

<div class="community-x-container">
    <!-- Header -->
    <header class="community-header community-header-enhanced">
        <div class="header-content">
            <div class="community-branding">
                <h1 class="community-title">
                    <a href="<?php echo home_url('/community/'); ?>">
                        <i class="fas fa-comments"></i>
                        <?php echo esc_html(get_option('community_x_community_name', 'Community X')); ?>
                    </a>
                </h1>
            </div>
            
            <nav class="breadcrumb-nav">
                <a href="<?php echo home_url('/community/'); ?>" class="breadcrumb-link">
                    <i class="fas fa-home"></i> <?php _e('Community', 'community-x'); ?>
                </a>
                <i class="fas fa-chevron-right breadcrumb-separator"></i>
                <?php if ($post['category_name']): ?>
                    <a href="<?php echo home_url('/community/?category=' . $post['category_id']); ?>" class="breadcrumb-link">
                        <?php echo esc_html($post['category_name']); ?>
                    </a>
                    <i class="fas fa-chevron-right breadcrumb-separator"></i>
                <?php endif; ?>
                <span class="breadcrumb-current"><?php echo esc_html(wp_trim_words($post['title'], 8)); ?></span>
            </nav>
            
            <div class="header-actions">
                <button class="btn btn-secondary share-post-btn" data-post-id="<?php echo $post['id']; ?>">
                    <i class="fas fa-share"></i>
                    <span class="btn-text"><?php _e('Share', 'community-x'); ?></span>
                </button>
                
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo home_url('/community/create-post/'); ?>" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        <span class="btn-text"><?php _e('New Post', 'community-x'); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="community-main">
        <div class="single-post-container">
            <article class="post-article">
                <!-- Post Header -->
                <header class="post-article-header">
                    <?php if ($post['category_name']): ?>
                        <div class="post-category-badge">
                            <a href="<?php echo home_url('/community/?category=' . $post['category_id']); ?>" 
                               class="category-link" 
                               style="background-color: <?php echo esc_attr($post['category_color']); ?>">
                                <i class="<?php echo esc_attr($post['category_icon'] ?? 'fas fa-folder'); ?>"></i>
                                <?php echo esc_html($post['category_name']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <h1 class="post-article-title"><?php echo esc_html($post['title']); ?></h1>
                    
                    <div class="post-meta-bar">
                        <div class="author-section">
                            <a href="<?php echo esc_url(home_url('/community/member/' . $post['author_login'] . '/')); ?>" class="author-profile-link">
                                <img src="<?php echo esc_url(Community_X_User::get_user_avatar($post['author_id'], 'large')); ?>" 
                                     alt="<?php echo esc_attr($post['author_name']); ?>" 
                                     class="author-avatar-large" />
                                <div class="author-details">
                                    <span class="author-name"><?php echo esc_html($post['author_name']); ?></span>
                                    <div class="post-metadata">
                                        <span class="post-date">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date_i18n(get_option('date_format'), strtotime($post['created_at'])); ?>
                                        </span>
                                        <span class="post-time">
                                            <i class="fas fa-clock"></i>
                                            <?php echo human_time_diff(strtotime($post['created_at']), current_time('timestamp')) . ' ' . __('ago', 'community-x'); ?>
                                        </span>
                                        <span class="read-time">
                                            <i class="fas fa-book-open"></i>
                                            <?php echo ceil(str_word_count(strip_tags($post['content'])) / 200); ?> <?php _e('min read', 'community-x'); ?>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        </div>
                        
                        <div class="post-actions">
                            <div class="post-stats-summary">
                                <span class="stat-item">
                                    <i class="fas fa-eye"></i>
                                    <?php echo number_format($post['view_count']); ?>
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-heart"></i>
                                    <?php echo number_format($post['like_count']); ?>
                                </span>
                                <span class="stat-item">
                                    <i class="fas fa-comment"></i>
                                    <?php echo number_format($post['comment_count']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Post Content -->
                <div class="post-article-content">
                    <div class="content-wrapper">
                        <?php echo wp_kses_post(wpautop($post['content'])); ?>
                    </div>
                </div>
                
                <!-- Post Tags -->
                <?php if (!empty($post['tags'])): ?>
                    <footer class="post-article-footer">
                        <div class="post-tags-section">
                            <h4><?php _e('Tags', 'community-x'); ?></h4>
                            <div class="tags-list">
                                <?php foreach($post['tags'] as $tag): ?>
                                    <a href="<?php echo add_query_arg('search', $tag, home_url('/community/')); ?>" class="tag-item">
                                        #<?php echo esc_html($tag); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </footer>
                <?php endif; ?>
            </article>
            
            <!-- Post Interaction Bar -->
            <div class="post-interaction-bar">
                <div class="interaction-buttons">
                    <button class="interaction-btn like-btn <?php echo is_user_logged_in() ? '' : 'disabled'; ?>" 
                            data-post-id="<?php echo $post['id']; ?>"
                            <?php echo !is_user_logged_in() ? 'disabled title="' . __('Login to like posts', 'community-x') . '"' : ''; ?>>
                        <i class="fas fa-heart"></i>
                        <span><?php echo number_format($post['like_count']); ?> <?php _e('Likes', 'community-x'); ?></span>
                    </button>
                    
                    <button class="interaction-btn comment-btn" onclick="document.getElementById('comments-section').scrollIntoView()">
                        <i class="fas fa-comment"></i>
                        <span><?php echo number_format($post['comment_count']); ?> <?php _e('Comments', 'community-x'); ?></span>
                    </button>
                    
                    <button class="interaction-btn share-btn" data-post-id="<?php echo $post['id']; ?>">
                        <i class="fas fa-share"></i>
                        <span><?php _e('Share', 'community-x'); ?></span>
                    </button>
                    
                    <?php if (is_user_logged_in()): ?>
                        <button class="interaction-btn bookmark-btn" data-post-id="<?php echo $post['id']; ?>">
                            <i class="fas fa-bookmark"></i>
                            <span><?php _e('Save', 'community-x'); ?></span>
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="author-follow-section">
                    <?php if (is_user_logged_in() && $post['author_id'] != get_current_user_id()): ?>
                        <?php if (Community_X_Profile::is_following($post['author_id'])): ?>
                            <button class="btn btn-secondary unfollow-btn" data-user-id="<?php echo $post['author_id']; ?>">
                                <i class="fas fa-user-check"></i> <?php _e('Following', 'community-x'); ?>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-primary follow-btn" data-user-id="<?php echo $post['author_id']; ?>">
                                <i class="fas fa-user-plus"></i> <?php _e('Follow', 'community-x'); ?>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Comments Section Placeholder -->
            <section class="comments-section" id="comments-section">
                <h3 class="comments-title">
                    <i class="fas fa-comments"></i>
                    <?php printf(__('Comments (%d)', 'community-x'), $post['comment_count']); ?>
                </h3>
                
                <div class="comments-placeholder">
                    <div class="placeholder-content">
                        <i class="fas fa-comments placeholder-icon"></i>
                        <h4><?php _e('Comments Coming Soon', 'community-x'); ?></h4>
                        <p><?php _e('Comment functionality will be added in Phase 4 of development.', 'community-x'); ?></p>
                    </div>
                </div>
            </section>
            
            <!-- Related Posts -->
            <aside class="related-posts-section">
                <h3 class="related-title">
                    <i class="fas fa-lightbulb"></i>
                    <?php _e('You might also like', 'community-x'); ?>
                </h3>
                
                <div class="related-posts-grid">
                    <?php
                    // Get related posts (same category or similar tags)
                    $related_args = [
                        'per_page' => 3,
                        'exclude_id' => $post['id']
                    ];
                    
                    if ($post['category_id']) {
                        $related_args['category_id'] = $post['category_id'];
                    }
                    
                    $related_posts = Community_X_Post::get_posts($related_args);
                    
                    if (!empty($related_posts)):
                        foreach ($related_posts as $related_post):
                    ?>
                            <article class="related-post-card">
                                <a href="<?php echo esc_url(home_url('/community/post/' . $related_post['id'] . '/')); ?>" class="related-post-link">
                                    <h4 class="related-post-title"><?php echo esc_html($related_post['title']); ?></h4>
                                    <p class="related-post-excerpt"><?php echo esc_html(wp_trim_words(strip_tags($related_post['content']), 15)); ?></p>
                                    <div class="related-post-meta">
                                        <span class="related-author"><?php echo esc_html($related_post['author_name']); ?></span>
                                        <span class="related-date"><?php echo human_time_diff(strtotime($related_post['created_at']), current_time('timestamp')) . ' ' . __('ago', 'community-x'); ?></span>
                                    </div>
                                </a>
                            </article>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <div class="no-related-posts">
                            <p><?php _e('No related posts found.', 'community-x'); ?></p>
                            <a href="<?php echo home_url('/community/'); ?>" class="btn btn-primary">
                                <?php _e('Browse All Posts', 'community-x'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
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

<!-- Share Modal -->
<div id="share-modal" class="community-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Share this post', 'community-x'); ?></h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div class="share-options">
                <button class="share-option" data-share="copy">
                    <i class="fas fa-link"></i>
                    <span><?php _e('Copy Link', 'community-x'); ?></span>
                </button>
                <button class="share-option" data-share="twitter">
                    <i class="fab fa-twitter"></i>
                    <span><?php _e('Twitter', 'community-x'); ?></span>
                </button>
                <button class="share-option" data-share="facebook">
                    <i class="fab fa-facebook"></i>
                    <span><?php _e('Facebook', 'community-x'); ?></span>
                </button>
                <button class="share-option" data-share="linkedin">
                    <i class="fab fa-linkedin"></i>
                    <span><?php _e('LinkedIn', 'community-x'); ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<?php wp_footer(); ?>

</body>
</html>

<?php get_footer(); ?>