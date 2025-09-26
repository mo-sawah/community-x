<?php
/**
 * Single Post Template
 *
 * @since      1.0.0
 * @package    Community_X
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$post_id = get_query_var('post_id');
$post = Community_X_Post::get_post($post_id);

if (!$post) {
    // Redirect or show 404
    wp_redirect(home_url('/community/'));
    exit;
}

get_header(); ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($post['title']); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('community-x-page single-post-page'); ?>>
<div class="community-x-container">
    <header class="community-header">
        </header>

    <main class="community-main">
        <article class="single-post-container">
            <header class="post-header">
                 <div class="breadcrumb">
                    <a href="<?php echo home_url('/community/'); ?>"><?php _e('Community', 'community-x'); ?></a>
                    <span class="separator">/</span>
                    <span class="current"><?php echo esc_html($post['title']); ?></span>
                </div>
                <h1 class="post-title"><?php echo esc_html($post['title']); ?></h1>
                <div class="post-meta">
                    <div class="author-info">
                        <img src="<?php echo esc_url(Community_X_User::get_user_avatar($post['author_id'])); ?>" class="author-avatar" />
                        <div class="author-details">
                            <a href="<?php echo esc_url(home_url('/community/member/' . $post['author_login'] . '/')); ?>" class="author-name"><?php echo esc_html($post['author_name']); ?></a>
                            <span class="post-date"><?php echo date_i18n(get_option('date_format'), strtotime($post['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="post-content-full">
                <?php echo wp_kses_post(wpautop($post['content'])); ?>
            </div>
            
            <?php if (!empty($post['tags'])): ?>
            <footer class="post-footer-tags">
                <div class="tags-list">
                    <?php foreach($post['tags'] as $tag): ?>
                        <span class="tag-item"><?php echo esc_html($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            </footer>
            <?php endif; ?>
        </article>
        
        <section class="post-comments-section">
            <h3><?php _e('Comments', 'community-x'); ?></h3>
            <div class="comments-placeholder">
                <p><?php _e('Comment functionality will be added in Phase 4.', 'community-x'); ?></p>
            </div>
        </section>
    </main>
</div>
<?php wp_footer(); ?>
</body>
</html>
<?php get_footer(); ?>