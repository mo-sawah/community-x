<?php
/**
 * Main Community Feed Template - Clean Modern Design
 */

if (!defined('WPINC')) {
    die;
}

// Get current page data
$public = new Community_X_Public('community-x', COMMUNITY_X_VERSION);
$page_data = $public->get_current_page_data();

// Get filter parameters
$filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'latest';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$page = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;

// Set sort order based on filter
$orderby = 'created_at';
$order = 'DESC';

switch ($filter) {
    case 'trending':
        $orderby = 'like_count';
        break;
    case 'following':
        // We'll implement this in phase 4
        $orderby = 'created_at';
        break;
    default: // latest
        $orderby = 'created_at';
        break;
}

$post_args = [
    'page' => $page,
    'search' => $search,
    'category_id' => $category_id,
    'orderby' => $orderby,
    'order' => $order,
    'per_page' => 10
];

$posts = Community_X_Post::get_posts($post_args);
$total_posts = Community_X_Post::count_posts(['category_id' => $category_id, 'search' => $search]);
$categories = Community_X_Category::get_all();

// Get community stats
global $wpdb;
$total_members = wp_count_users();
$online_members = 89; // Placeholder - would calculate from last activity
$total_likes_today = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}community_x_interactions WHERE interaction_type = 'like' AND DATE(created_at) = CURDATE()");

get_header(); ?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php _e('Community', 'community-x'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>

<body <?php body_class('community-x-page community-main-page'); ?>>

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <h1 class="text-xl font-bold text-gray-900">
                        <a href="<?php echo home_url('/community/'); ?>" class="text-gray-900 no-underline">
                            <?php echo esc_html(get_option('community_x_community_name', 'Community')); ?>
                        </a>
                    </h1>
                    
                    <!-- Search -->
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <form method="get" class="inline">
                            <input type="hidden" name="filter" value="<?php echo esc_attr($filter); ?>">
                            <input type="hidden" name="category" value="<?php echo esc_attr($category_id); ?>">
                            <input
                                type="text"
                                name="search"
                                value="<?php echo esc_attr($search); ?>"
                                placeholder="<?php _e('Search posts, people, topics...', 'community-x'); ?>"
                                class="pl-12 pr-4 py-2.5 w-80 bg-gray-50 border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all"
                            />
                        </form>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <?php if (is_user_logged_in()): ?>
                        <button class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                            <i class="fas fa-bell"></i>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
                        </button>
                        
                        <a href="<?php echo home_url('/community/create-post/'); ?>" class="flex items-center space-x-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl hover:bg-blue-700 transition-colors no-underline">
                            <i class="fas fa-plus"></i>
                            <span class="font-medium"><?php _e('New Post', 'community-x'); ?></span>
                        </a>
                        
                        <div class="relative">
                            <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors user-menu-trigger">
                                <img 
                                    src="<?php echo esc_url(Community_X_User::get_user_avatar(get_current_user_id())); ?>"
                                    alt="<?php echo esc_attr(wp_get_current_user()->display_name); ?>"
                                    class="w-8 h-8 rounded-full object-cover"
                                />
                            </button>
                            <div class="user-dropdown absolute right-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg hidden">
                                <a href="<?php echo home_url('/community/member/' . wp_get_current_user()->user_login . '/'); ?>" class="block px-4 py-2 text-gray-700 hover:bg-gray-50"><?php _e('My Profile', 'community-x'); ?></a>
                                <a href="<?php echo home_url('/community-dashboard/'); ?>" class="block px-4 py-2 text-gray-700 hover:bg-gray-50"><?php _e('Dashboard', 'community-x'); ?></a>
                                <a href="<?php echo wp_logout_url(home_url('/community/')); ?>" class="block px-4 py-2 text-gray-700 hover:bg-gray-50"><?php _e('Logout', 'community-x'); ?></a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo wp_login_url(home_url($_SERVER['REQUEST_URI'])); ?>" class="text-gray-600 hover:text-gray-900 px-4 py-2"><?php _e('Login', 'community-x'); ?></a>
                        <a href="<?php echo wp_registration_url(); ?>" class="bg-blue-600 text-white px-4 py-2.5 rounded-xl hover:bg-blue-700 transition-colors no-underline"><?php _e('Join', 'community-x'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-3">
                <!-- Filter Bar -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-2">
                        <a href="<?php echo add_query_arg(['filter' => 'latest'], remove_query_arg('pg')); ?>"
                           class="flex items-center space-x-2 px-4 py-2 rounded-xl transition-colors <?php echo $filter === 'latest' ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-100'; ?> no-underline">
                            <i class="fas fa-clock"></i>
                            <span><?php _e('Latest', 'community-x'); ?></span>
                        </a>
                        
                        <a href="<?php echo add_query_arg(['filter' => 'trending'], remove_query_arg('pg')); ?>"
                           class="flex items-center space-x-2 px-4 py-2 rounded-xl transition-colors <?php echo $filter === 'trending' ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-100'; ?> no-underline">
                            <i class="fas fa-fire"></i>
                            <span><?php _e('Trending', 'community-x'); ?></span>
                        </a>
                        
                        <?php if (is_user_logged_in()): ?>
                            <a href="<?php echo add_query_arg(['filter' => 'following'], remove_query_arg('pg')); ?>"
                               class="flex items-center space-x-2 px-4 py-2 rounded-xl transition-colors <?php echo $filter === 'following' ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-100'; ?> no-underline">
                                <i class="fas fa-users"></i>
                                <span><?php _e('Following', 'community-x'); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <select name="category_filter" class="text-gray-600 bg-white border border-gray-200 rounded-lg px-3 py-2" onchange="location.href=this.value">
                            <option value="<?php echo remove_query_arg(['category', 'pg']); ?>"><?php _e('All Categories', 'community-x'); ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo esc_url(add_query_arg(['category' => $cat['id']], remove_query_arg('pg'))); ?>" <?php selected($category_id, $cat['id']); ?>>
                                    <?php echo esc_html($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Posts Feed -->
                <?php if (empty($posts)): ?>
                    <div class="bg-white border border-gray-200 rounded-xl p-12 text-center">
                        <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php _e('No posts found', 'community-x'); ?></h3>
                        <p class="text-gray-600 mb-6"><?php _e('Be the first to share something with the community!', 'community-x'); ?></p>
                        <?php if (is_user_logged_in() && current_user_can('community_create_post')): ?>
                            <a href="<?php echo home_url('/community/create-post/'); ?>" class="bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition-colors no-underline">
                                <i class="fas fa-plus mr-2"></i><?php _e('Create First Post', 'community-x'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($posts as $post): ?>
                            <article class="bg-white border border-gray-200 rounded-xl p-6 hover:border-gray-300 transition-all duration-200 post-card" data-post-id="<?php echo $post['id']; ?>">
                                <!-- Author Header -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <img 
                                            src="<?php echo esc_url(Community_X_User::get_user_avatar($post['author_id'])); ?>" 
                                            alt="<?php echo esc_attr($post['author_name']); ?>"
                                            class="w-10 h-10 rounded-full object-cover"
                                        />
                                        <div>
                                            <h4 class="font-semibold text-gray-900">
                                                <a href="<?php echo esc_url(home_url('/community/member/' . $post['author_login'] . '/')); ?>" class="text-gray-900 no-underline hover:text-blue-600">
                                                    <?php echo esc_html($post['author_name']); ?>
                                                </a>
                                            </h4>
                                            <div class="flex items-center space-x-2 text-sm text-gray-500">
                                                <span>@<?php echo esc_html($post['author_login']); ?></span>
                                                <span>•</span>
                                                <span><?php echo human_time_diff(strtotime($post['created_at']), current_time('timestamp')) . ' ' . __('ago', 'community-x'); ?></span>
                                                <span>•</span>
                                                <span><?php echo ceil(str_word_count(strip_tags($post['content'])) / 200); ?> <?php _e('min read', 'community-x'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                                        <i class="fas fa-ellipsis-h text-gray-400"></i>
                                    </button>
                                </div>

                                <!-- Content -->
                                <div class="mb-4">
                                    <h2 class="text-xl font-semibold text-gray-900 mb-3 leading-tight">
                                        <a href="<?php echo esc_url(home_url('/community/post/' . $post['id'] . '/')); ?>" class="text-gray-900 no-underline hover:text-blue-600">
                                            <?php echo esc_html($post['title']); ?>
                                        </a>
                                    </h2>
                                    <p class="text-gray-800 leading-relaxed"><?php echo wp_kses_post(wp_trim_words($post['content'], 40)); ?></p>
                                </div>

                                <!-- Tags -->
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php if (!empty($post['tags'])): ?>
                                        <?php foreach (array_slice($post['tags'], 0, 4) as $tag): ?>
                                            <span class="px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full hover:bg-gray-200 cursor-pointer transition-colors">
                                                #<?php echo esc_html($tag); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if ($post['category_name']): ?>
                                        <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full font-medium">
                                            <?php echo esc_html($post['category_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                    <div class="flex items-center space-x-6">
                                        <button class="flex items-center space-x-2 px-3 py-2 rounded-lg transition-all text-gray-500 hover:bg-gray-50 like-btn <?php echo Community_X_Interactions::has_user_liked_post($post['id']) ? 'text-red-600 bg-red-50' : ''; ?>" data-post-id="<?php echo $post['id']; ?>">
                                            <i class="fas fa-heart"></i>
                                            <span class="text-sm font-medium"><?php echo number_format($post['like_count']); ?></span>
                                        </button>
                                        
                                        <a href="<?php echo esc_url(home_url('/community/post/' . $post['id'] . '/#comments')); ?>" class="flex items-center space-x-2 text-gray-500 hover:bg-gray-50 px-3 py-2 rounded-lg transition-colors no-underline">
                                            <i class="fas fa-comment"></i>
                                            <span class="text-sm font-medium"><?php echo number_format($post['comment_count']); ?></span>
                                        </a>
                                        
                                        <button class="flex items-center space-x-2 text-gray-500 hover:bg-gray-50 px-3 py-2 rounded-lg transition-colors share-btn" data-post-id="<?php echo $post['id']; ?>">
                                            <i class="fas fa-share"></i>
                                            <span class="text-sm font-medium"><?php _e('Share', 'community-x'); ?></span>
                                        </button>
                                    </div>
                                    
                                    <?php if (is_user_logged_in()): ?>
                                        <button class="text-gray-400 hover:text-gray-600 hover:bg-gray-50 p-2 rounded-lg transition-colors bookmark-btn" data-post-id="<?php echo $post['id']; ?>">
                                            <i class="fas fa-bookmark"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Load More -->
                    <?php if ($total_posts > 10): ?>
                        <div class="text-center mt-8">
                            <button class="px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors">
                                <?php _e('Load More Posts', 'community-x'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Community Stats -->
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-4"><?php _e('Community Stats', 'community-x'); ?></h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600"><?php echo number_format($total_members['total_users']); ?></div>
                            <div class="text-sm text-gray-500"><?php _e('Members', 'community-x'); ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600"><?php echo number_format($total_posts); ?></div>
                            <div class="text-sm text-gray-500"><?php _e('Posts', 'community-x'); ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600"><?php echo number_format($online_members); ?></div>
                            <div class="text-sm text-gray-500"><?php _e('Online', 'community-x'); ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600"><?php echo number_format($total_likes_today); ?></div>
                            <div class="text-sm text-gray-500"><?php _e('Likes Today', 'community-x'); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-4"><?php _e('Categories', 'community-x'); ?></h3>
                    <div class="space-y-3">
                        <a href="<?php echo remove_query_arg(['category', 'pg']); ?>"
                           class="w-full flex items-center justify-between p-3 rounded-lg transition-colors hover:bg-gray-50 no-underline text-gray-700">
                            <span class="font-medium"><?php _e('All Posts', 'community-x'); ?></span>
                            <span class="text-sm text-gray-500"><?php echo number_format($total_posts); ?></span>
                        </a>
                        <?php foreach ($categories as $category): ?>
                            <a href="<?php echo esc_url(add_query_arg(['category' => $category['id']], remove_query_arg('pg'))); ?>"
                               class="w-full flex items-center justify-between p-3 rounded-lg transition-colors hover:bg-gray-50 no-underline text-gray-700 <?php echo $category_id == $category['id'] ? 'bg-blue-50 border border-blue-200' : ''; ?>">
                                <span class="font-medium"><?php echo esc_html($category['name']); ?></span>
                                <span class="text-sm text-gray-500"><?php echo number_format($category['post_count']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Trending Tags -->
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-4"><?php _e('Trending Tags', 'community-x'); ?></h3>
                    <div class="flex flex-wrap gap-2">
                        <?php
                        // Get trending tags
                        $trending_tags = ['react', 'javascript', 'css', 'php', 'wordpress', 'design', 'tutorial', 'tips'];
                        foreach ($trending_tags as $tag):
                        ?>
                            <button class="px-3 py-1.5 bg-gray-100 text-gray-600 text-sm rounded-lg hover:bg-gray-200 transition-colors">
                                #<?php echo $tag; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Active Members -->
                <div class="bg-white border border-gray-200 rounded-xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-4"><?php _e('Active Members', 'community-x'); ?></h3>
                    <div class="space-y-3">
                        <?php
                        // Get recent active users
                        $active_users = get_users([
                            'meta_key' => 'wp_capabilities',
                            'meta_value' => 'community_member',
                            'meta_compare' => 'LIKE',
                            'number' => 5,
                            'orderby' => 'user_registered',
                            'order' => 'DESC'
                        ]);
                        
                        foreach ($active_users as $user):
                        ?>
                            <div class="flex items-center space-x-3">
                                <div class="relative">
                                    <img 
                                        src="<?php echo esc_url(Community_X_User::get_user_avatar($user->ID)); ?>" 
                                        alt="<?php echo esc_attr($user->display_name); ?>"
                                        class="w-8 h-8 rounded-full object-cover"
                                    />
                                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></div>
                                </div>
                                <a href="<?php echo esc_url(home_url('/community/member/' . $user->user_login . '/')); ?>" class="text-sm font-medium text-gray-700 hover:text-blue-600 no-underline">
                                    <?php echo esc_html($user->display_name); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php wp_footer(); ?>

<script>
jQuery(document).ready(function($) {
    // User menu toggle
    $('.user-menu-trigger').on('click', function() {
        $('.user-dropdown').toggleClass('hidden');
    });
    
    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.user-menu-trigger, .user-dropdown').length) {
            $('.user-dropdown').addClass('hidden');
        }
    });
    
    // Like button functionality
    $('.like-btn').on('click', function(e) {
        e.preventDefault();
        
        if (!<?php echo is_user_logged_in() ? 'true' : 'false'; ?>) {
            alert('<?php _e("Please log in to like posts", "community-x"); ?>');
            return;
        }
        
        var $btn = $(this);
        var postId = $btn.data('post-id');
        var $count = $btn.find('span');
        var currentCount = parseInt($count.text()) || 0;
        var isLiked = $btn.hasClass('text-red-600');
        
        // Optimistic UI update
        if (isLiked) {
            $btn.removeClass('text-red-600 bg-red-50').addClass('text-gray-500');
            $count.text(Math.max(0, currentCount - 1));
        } else {
            $btn.removeClass('text-gray-500').addClass('text-red-600 bg-red-50');
            $count.text(currentCount + 1);
        }
        
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'community_x_like_post',
            post_id: postId,
            nonce: '<?php echo wp_create_nonce('community_x_public_nonce'); ?>'
        })
        .fail(function() {
            // Revert on error
            if (!isLiked) {
                $btn.removeClass('text-red-600 bg-red-50').addClass('text-gray-500');
                $count.text(currentCount);
            } else {
                $btn.removeClass('text-gray-500').addClass('text-red-600 bg-red-50');
                $count.text(currentCount);
            }
        });
    });
});
</script>

</body>
</html>

<?php get_footer(); ?>