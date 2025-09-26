<?php
/**
 * Post Submission Form Shortcode
 *
 * @since      1.0.0
 * @package    Community_X
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

if (!is_user_logged_in() || !current_user_can('community_create_post')) {
    return '<p class="community-x-error">' . __('You do not have permission to create posts.', 'community-x') . '</p>';
}

// Get categories for dropdown
$categories = Community_X_Category::get_all();

// Handle form submission
$errors = array();
$success_message = '';

if ($_POST && isset($_POST['community_x_post_nonce'])) {
    if (wp_verify_nonce($_POST['community_x_post_nonce'], 'community_x_create_post')) {
        
        $title = sanitize_text_field($_POST['post_title']);
        $content = wp_kses_post($_POST['post_content']);
        $category_id = intval($_POST['category_id']);
        $tags = isset($_POST['post_tags']) ? array_map('sanitize_text_field', explode(',', $_POST['post_tags'])) : array();
        
        // Validation
        if (empty($title)) {
            $errors[] = __('Title is required.', 'community-x');
        }
        
        if (empty($content)) {
            $errors[] = __('Content is required.', 'community-x');
        }
        
        if (strlen($title) > 200) {
            $errors[] = __('Title must be less than 200 characters.', 'community-x');
        }
        
        if (strlen($content) < 10) {
            $errors[] = __('Content must be at least 10 characters long.', 'community-x');
        }
        
        // If no errors, create post
        if (empty($errors)) {
            $post_data = array(
                'title' => $title,
                'content' => $content,
                'category_id' => $category_id,
                'tags' => $tags
            );
            
            $result = Community_X_Post::create_post($post_data);
            
            if (!is_wp_error($result)) {
                $success_message = __('Post created successfully! Redirecting...', 'community-x');
                echo '<script>setTimeout(function(){ window.location.href = "' . home_url('/community/post/' . $result . '/') . '"; }, 2000);</script>';
            } else {
                $errors[] = $result->get_error_message();
            }
        }
    } else {
        $errors[] = __('Security verification failed. Please try again.', 'community-x');
    }
}
?>

<div class="community-x-post-form">
    <?php if (!empty($errors)): ?>
        <div class="form-errors">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="form-success">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php else: ?>
        <div class="post-form-header">
            <h2><?php _e('Create New Post', 'community-x'); ?></h2>
            <p><?php _e('Share your thoughts, ideas, or questions with the community.', 'community-x'); ?></p>
        </div>
        
        <form method="post" class="community-post-form" id="community-post-form">
            <?php wp_nonce_field('community_x_create_post', 'community_x_post_nonce'); ?>
            
            <div class="form-group">
                <label for="post_title"><?php _e('Title', 'community-x'); ?> *</label>
                <input type="text" id="post_title" name="post_title" 
                       value="<?php echo isset($_POST['post_title']) ? esc_attr($_POST['post_title']) : ''; ?>" 
                       class="form-control" required maxlength="200" 
                       placeholder="<?php _e('Enter an engaging title for your post...', 'community-x'); ?>" />
                <div class="character-count">
                    <span class="current">0</span> / <span class="max">200</span> <?php _e('characters', 'community-x'); ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="category_id"><?php _e('Category', 'community-x'); ?></label>
                <select id="category_id" name="category_id" class="form-control">
                    <option value="0"><?php _e('Select a category (optional)', 'community-x'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php selected(isset($_POST['category_id']) ? $_POST['category_id'] : 0, $category['id']); ?>>
                            <?php echo esc_html($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="post_content"><?php _e('Content', 'community-x'); ?> *</label>
                <div class="editor-toolbar">
                    <button type="button" class="editor-btn" data-action="bold" title="<?php _e('Bold', 'community-x'); ?>">
                        <i class="fas fa-bold"></i>
                    </button>
                    <button type="button" class="editor-btn" data-action="italic" title="<?php _e('Italic', 'community-x'); ?>">
                        <i class="fas fa-italic"></i>
                    </button>
                    <button type="button" class="editor-btn" data-action="link" title="<?php _e('Add Link', 'community-x'); ?>">
                        <i class="fas fa-link"></i>
                    </button>
                    <button type="button" class="editor-btn" data-action="list" title="<?php _e('Bullet List', 'community-x'); ?>">
                        <i class="fas fa-list-ul"></i>
                    </button>
                </div>
                <textarea id="post_content" name="post_content" rows="8" class="form-control" required 
                          placeholder="<?php _e('Write your post content here. You can use basic formatting and include links...', 'community-x'); ?>"><?php echo isset($_POST['post_content']) ? esc_textarea($_POST['post_content']) : ''; ?></textarea>
                <div class="content-helper">
                    <small><?php _e('Minimum 10 characters. You can use basic HTML tags like &lt;strong&gt;, &lt;em&gt;, &lt;a&gt;, and &lt;ul&gt;.', 'community-x'); ?></small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="post_tags"><?php _e('Tags', 'community-x'); ?></label>
                <input type="text" id="post_tags" name="post_tags" 
                       value="<?php echo isset($_POST['post_tags']) ? esc_attr($_POST['post_tags']) : ''; ?>" 
                       class="form-control" 
                       placeholder="<?php _e('Enter tags separated by commas (e.g., javascript, tutorial, beginner)', 'community-x'); ?>" />
                <small class="form-help"><?php _e('Tags help others find your post. Separate multiple tags with commas.', 'community-x'); ?></small>
            </div>
            
            <div class="form-group">
                <div class="post-options">
                    <?php if (get_option('community_x_moderate_all_posts', 0)): ?>
                        <div class="moderation-notice">
                            <i class="fas fa-info-circle"></i>
                            <?php _e('Your post will be reviewed by moderators before being published.', 'community-x'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="fas fa-paper-plane"></i> <?php _e('Publish Post', 'community-x'); ?>
                </button>
                <a href="<?php echo home_url('/community/'); ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> <?php _e('Cancel', 'community-x'); ?>
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Character counter for title
    $('#post_title').on('input', function() {
        var length = $(this).val().length;
        var max = 200;
        $('.character-count .current').text(length);
        
        if (length > max * 0.9) {
            $('.character-count').addClass('warning');
        } else {
            $('.character-count').removeClass('warning');
        }
        
        if (length >= max) {
            $('.character-count').addClass('error');
        } else {
            $('.character-count').removeClass('error');
        }
    });
    
    // Simple editor toolbar
    $('.editor-btn').on('click', function(e) {
        e.preventDefault();
        var action = $(this).data('action');
        var textarea = $('#post_content')[0];
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var selectedText = textarea.value.substring(start, end);
        var replacement = '';
        
        switch(action) {
            case 'bold':
                replacement = '<strong>' + selectedText + '</strong>';
                break;
            case 'italic':
                replacement = '<em>' + selectedText + '</em>';
                break;
            case 'link':
                var url = prompt('Enter URL:');
                if (url) {
                    replacement = '<a href="' + url + '">' + (selectedText || url) + '</a>';
                }
                break;
            case 'list':
                var lines = selectedText.split('\n');
                replacement = '<ul>\n';
                lines.forEach(function(line) {
                    if (line.trim()) {
                        replacement += '<li>' + line.trim() + '</li>\n';
                    }
                });
                replacement += '</ul>';
                break;
        }
        
        if (replacement) {
            textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
            textarea.focus();
        }
    });
    
    // Auto-save draft (placeholder for future enhancement)
    var autoSaveTimer;
    $('#post_title, #post_content').on('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Could implement auto-save functionality here
        }, 5000);
    });
    
    // Form validation
    $('#community-post-form').on('submit', function(e) {
        var title = $('#post_title').val().trim();
        var content = $('#post_content').val().trim();
        
        if (!title) {
            alert('<?php _e("Please enter a title for your post.", "community-x"); ?>');
            $('#post_title').focus();
            e.preventDefault();
            return false;
        }
        
        if (!content || content.length < 10) {
            alert('<?php _e("Please enter at least 10 characters of content.", "community-x"); ?>');
            $('#post_content').focus();
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        $(this).find('button[type="submit"]').prop('disabled', true)
               .html('<i class="fas fa-spinner fa-spin"></i> <?php _e("Publishing...", "community-x"); ?>');
    });
});
</script>

<style>
.community-x-post-form {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.post-form-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.post-form-header h2 {
    margin: 0 0 0.5rem 0;
    color: #1f2937;
}

.post-form-header p {
    margin: 0;
    color: #6b7280;
}

.character-count {
    text-align: right;
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.character-count.warning .current {
    color: #f59e0b;
}

.character-count.error .current {
    color: #ef4444;
}

.editor-toolbar {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px 6px 0 0;
}

.editor-btn {
    background: none;
    border: 1px solid #d1d5db;
    padding: 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    color: #6b7280;
}

.editor-btn:hover {
    background: #e5e7eb;
    color: #374151;
}

.form-control {
    border-radius: 6px;
}

#post_content {
    border-radius: 0 0 6px 6px;
    border-top: none;
}

.content-helper {
    margin-top: 0.5rem;
}

.content-helper small {
    color: #6b7280;
}

.moderation-notice {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    padding: 1rem;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #92400e;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .community-x-post-form {
        padding: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .editor-toolbar {
        flex-wrap: wrap;
    }
}
</style>