<?php
/**
 * Admin Posts Management Page
 *
 * @since 1.0.0
 * @package Community_X
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// TODO: Implement WP_List_Table for a more native feel in the future.
// For now, a simple custom table to match the `members.php` style.

global $wpdb;
$posts_table = $wpdb->prefix . 'community_x_posts';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['post_id'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'cx_delete_post_' . $_GET['post_id'])) {
        $wpdb->delete($posts_table, ['id' => intval($_GET['post_id'])]);
        echo '<div class="notice notice-success"><p>' . __('Post deleted successfully.', 'community-x') . '</p></div>';
    }
}

// Get posts
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($paged - 1) * $per_page;

$posts = $wpdb->get_results( $wpdb->prepare(
    "SELECT p.*, u.display_name FROM $posts_table p LEFT JOIN {$wpdb->users} u ON p.author_id = u.ID ORDER BY p.created_at DESC LIMIT %d OFFSET %d",
    $per_page, $offset
), ARRAY_A);
$total_posts = $wpdb->get_var("SELECT COUNT(id) FROM $posts_table");
?>
<div class="wrap">
    <h1><?php _e('Community Posts', 'community-x'); ?></h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Title', 'community-x'); ?></th>
                <th><?php _e('Author', 'community-x'); ?></th>
                <th><?php _e('Status', 'community-x'); ?></th>
                <th><?php _e('Date', 'community-x'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($posts)): ?>
                <tr><td colspan="4"><?php _e('No posts found.', 'community-x'); ?></td></tr>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <strong><a href="<?php echo esc_url(home_url('/community/post/' . $post['id'] . '/')); ?>" target="_blank"><?php echo esc_html($post['title']); ?></a></strong>
                            <div class="row-actions">
                                <span class="view"><a href="<?php echo esc_url(home_url('/community/post/' . $post['id'] . '/')); ?>" target="_blank">View</a> | </span>
                                <span class="delete"><a href="<?php echo wp_nonce_url(admin_url('admin.php?page=community-x-posts&action=delete&post_id=' . $post['id']), 'cx_delete_post_' . $post['id']); ?>" class="text-danger" onclick="return confirm('Are you sure?')">Delete</a></span>
                            </div>
                        </td>
                        <td><?php echo esc_html($post['display_name']); ?></td>
                        <td><?php echo esc_html(ucfirst($post['status'])); ?></td>
                        <td><?php echo date_i18n('Y/m/d', strtotime($post['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>