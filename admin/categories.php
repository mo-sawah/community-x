<?php
/**
 * Admin Categories Management Page
 *
 * @since 1.0.0
 * @package Community_X
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$action = $_REQUEST['action'] ?? 'list';
$category_id = $_REQUEST['category_id'] ?? 0;
$message = '';

// Handle form submissions
if (isset($_POST['submit'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'cx_save_category')) {
        die('Security check failed!');
    }
    
    $data = [
        'name' => $_POST['name'],
        'slug' => $_POST['slug'],
        'description' => $_POST['description'],
        'icon' => $_POST['icon'],
        'color' => $_POST['color'],
        'sort_order' => $_POST['sort_order'],
    ];

    if ($category_id) {
        Community_X_Category::update($category_id, $data);
        $message = __('Category updated successfully.', 'community-x');
    } else {
        Community_X_Category::create($data);
        $message = __('Category created successfully.', 'community-x');
    }
    $action = 'list';
}

// Handle delete
if ($action === 'delete') {
     if (wp_verify_nonce($_GET['_wpnonce'], 'cx_delete_category_' . $category_id)) {
        Community_X_Category::delete($category_id);
        $message = __('Category deleted.', 'community-x');
    }
    $action = 'list';
}

?>
<div class="wrap">
    <h1><?php _e('Post Categories', 'community-x'); ?></h1>

    <?php if ($message): ?>
        <div class="notice notice-success"><p><?php echo $message; ?></p></div>
    <?php endif; ?>

    <div id="col-container" class="wp-clearfix">
        <div id="col-left">
            <div class="col-wrap">
                <h2><?php echo $action === 'edit' ? __('Edit Category', 'community-x') : __('Add New Category', 'community-x'); ?></h2>
                <?php
                $cat_data = ($action === 'edit' && $category_id) ? Community_X_Category::get($category_id) : [];
                ?>
                <form method="post">
                    <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'edit' : 'add'; ?>">
                    <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                    <?php wp_nonce_field('cx_save_category'); ?>

                    <div class="form-field">
                        <label for="name"><?php _e('Name', 'community-x'); ?></label>
                        <input name="name" id="name" type="text" value="<?php echo esc_attr($cat_data['name'] ?? ''); ?>" required>
                    </div>
                     <div class="form-field">
                        <label for="slug"><?php _e('Slug', 'community-x'); ?></label>
                        <input name="slug" id="slug" type="text" value="<?php echo esc_attr($cat_data['slug'] ?? ''); ?>">
                    </div>
                     <div class="form-field">
                        <label for="description"><?php _e('Description', 'community-x'); ?></label>
                        <textarea name="description" id="description" rows="3"><?php echo esc_textarea($cat_data['description'] ?? ''); ?></textarea>
                    </div>
                     <div class="form-field">
                        <label for="icon"><?php _e('Icon (Font Awesome)', 'community-x'); ?></label>
                        <input name="icon" id="icon" type="text" value="<?php echo esc_attr($cat_data['icon'] ?? 'fas fa-tag'); ?>">
                    </div>
                     <div class="form-field">
                        <label for="color"><?php _e('Color', 'community-x'); ?></label>
                        <input name="color" id="color" type="color" value="<?php echo esc_attr($cat_data['color'] ?? '#cccccc'); ?>">
                    </div>
                     <div class="form-field">
                        <label for="sort_order"><?php _e('Sort Order', 'community-x'); ?></label>
                        <input name="sort_order" id="sort_order" type="number" value="<?php echo esc_attr($cat_data['sort_order'] ?? 0); ?>">
                    </div>
                    <?php submit_button($action === 'edit' ? __('Update Category', 'community-x') : __('Add New Category', 'community-x')); ?>
                </form>
            </div>
        </div>
        <div id="col-right">
            <div class="col-wrap">
                 <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'community-x'); ?></th>
                            <th><?php _e('Slug', 'community-x'); ?></th>
                            <th><?php _e('Count', 'community-x'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach(Community_X_Category::get_all() as $cat): ?>
                        <tr>
                            <td>
                                <strong><a href="<?php echo admin_url('admin.php?page=community-x-categories&action=edit&category_id=' . $cat['id']); ?>"><?php echo esc_html($cat['name']); ?></a></strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?php echo admin_url('admin.php?page=community-x-categories&action=edit&category_id=' . $cat['id']); ?>">Edit</a> | </span>
                                    <span class="delete"><a href="<?php echo wp_nonce_url(admin_url('admin.php?page=community-x-categories&action=delete&category_id=' . $cat['id']), 'cx_delete_category_' . $cat['id']); ?>" onclick="return confirm('Are you sure?')">Delete</a></span>
                                </div>
                            </td>
                            <td><?php echo esc_html($cat['slug']); ?></td>
                            <td><?php echo intval($cat['post_count']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                 </table>
            </div>
        </div>
    </div>
</div>