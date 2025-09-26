<?php
/**
 * Admin Settings Page for Community X
 *
 * @since      1.0.0
 * @package    Community_X
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['community_x_settings_nonce'], 'community_x_save_settings')) {
    // Process and save settings
    $settings = array();
    
    // Sanitize and save each setting
    if (isset($_POST['community_x_settings'])) {
        $input = $_POST['community_x_settings'];
        
        $settings['community_name'] = sanitize_text_field($input['community_name'] ?? 'Community X');
        $settings['community_description'] = sanitize_textarea_field($input['community_description'] ?? '');
        $settings['allow_public_viewing'] = isset($input['allow_public_viewing']) ? 1 : 0;
        $settings['allow_user_registration'] = isset($input['allow_user_registration']) ? 1 : 0;
        $settings['require_email_verification'] = isset($input['require_email_verification']) ? 1 : 0;
        $settings['enable_profile_pictures'] = isset($input['enable_profile_pictures']) ? 1 : 0;
        $settings['enable_private_messaging'] = isset($input['enable_private_messaging']) ? 1 : 0;
        $settings['enable_groups'] = isset($input['enable_groups']) ? 1 : 0;
        $settings['enable_notifications'] = isset($input['enable_notifications']) ? 1 : 0;
        $settings['moderate_all_posts'] = isset($input['moderate_all_posts']) ? 1 : 0;
        $settings['enable_spam_protection'] = isset($input['enable_spam_protection']) ? 1 : 0;
        $settings['posts_per_page'] = absint($input['posts_per_page'] ?? 10);
        $settings['default_user_role'] = sanitize_text_field($input['default_user_role'] ?? 'community_member');
        
        // Save settings
        update_option('community_x_settings', $settings);
        
        // Show success message
        echo '<div class="notice notice-success"><p><i class="fas fa-check-circle"></i> ' . 
             __('Settings saved successfully!', 'community-x') . '</p></div>';
    }
}

// Get current settings
$current_settings = get_option('community_x_settings', array());

// Default values
$defaults = array(
    'community_name' => 'Community X',
    'community_description' => 'A modern, engaging community platform',
    'allow_public_viewing' => 1,
    'allow_user_registration' => 1,
    'require_email_verification' => 1,
    'enable_profile_pictures' => 1,
    'enable_private_messaging' => 1,
    'enable_groups' => 1,
    'enable_notifications' => 1,
    'moderate_all_posts' => 0,
    'enable_spam_protection' => 1,
    'posts_per_page' => 10,
    'default_user_role' => 'community_member'
);

$settings = wp_parse_args($current_settings, $defaults);
?>

<div class="wrap community-x-settings">
    <h1>
        <i class="fas fa-cog"></i>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>

    <form method="post" action="">
        <?php wp_nonce_field('community_x_save_settings', 'community_x_settings_nonce'); ?>
        
        <div class="settings-grid">
            <!-- General Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2><i class="fas fa-home"></i> <?php _e('General Settings', 'community-x'); ?></h2>
                    <p><?php _e('Configure the basic settings for your community.', 'community-x'); ?></p>
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="community_name"><?php _e('Community Name', 'community-x'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="community_name" name="community_x_settings[community_name]" 
                                   value="<?php echo esc_attr($settings['community_name']); ?>" class="regular-text" />
                            <p class="description"><?php _e('The name of your community that will be displayed to users.', 'community-x'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="community_description"><?php _e('Community Description', 'community-x'); ?></label>
                        </th>
                        <td>
                            <textarea id="community_description" name="community_x_settings[community_description]" 
                                      rows="3" cols="50" class="large-text"><?php echo esc_textarea($settings['community_description']); ?></textarea>
                            <p class="description"><?php _e('A brief description of your community.', 'community-x'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Public Viewing', 'community-x'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="community_x_settings[allow_public_viewing]" 
                                       value="1" <?php checked($settings['allow_public_viewing'], 1); ?> />
                                <?php _e('Allow non-members to view community content', 'community-x'); ?>
                            </label>
                            <p class="description"><?php _e('When enabled, visitors can browse posts and profiles without logging in.', 'community-x'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- User Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2><i class="fas fa-users"></i> <?php _e('User Settings', 'community-x'); ?></h2>
                    <p><?php _e('Configure user registration and profile settings.', 'community-x'); ?></p>
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('User Registration', 'community-x'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="community_x_settings[allow_user_registration]" 
                                       value="1" <?php checked($settings['allow_user_registration'], 1); ?> />
                                <?php _e('Allow new users to register', 'community-x'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Email Verification', 'community-x'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="community_x_settings[require_email_verification]" 
                                       value="1" <?php checked($settings['require_email_verification'], 1); ?> />
                                <?php _e('Require email verification for new accounts', 'community-x'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Profile Pictures', 'community-x'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="community_x_settings[enable_profile_pictures]" 
                                       value="1" <?php checked($settings['enable_profile_pictures'], 1); ?> />
                                <?php _e('Enable profile picture uploads', 'community-x'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_user_role"><?php _e('Default User Role', 'community-x'); ?></label>
                        </th>
                        <td>
                            <select id="default_user_role" name="community_x_settings[default_user_role]">
                                <option value="community_member" <?php selected($settings['default_user_role'], 'community_member'); ?>>
                                    <?php _e('Community Member', 'community-x'); ?>
                                </option>
                                <option value="subscriber" <?php selected($settings['default_user_role'], 'subscriber'); ?>>
                                    <?php _e('Subscriber', 'community-x'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Content Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2><i class="fas fa-file-alt"></i> <?php _e('Content Settings', 'community-x'); ?></h2>
                    <p><?php _e('Configure how content is displayed and moderated.', 'community-x'); ?></p>
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="posts_per_page"><?php _e('Posts Per Page', 'community-x'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="posts_per_page" name="community_x_settings[posts_per_page]" 
                                   value="<?php echo esc_attr($settings['posts_per_page']); ?>" min="1" max="50" class="small-text" />
                            <p class="description"><?php _e('Number of posts to display per page.', 'community-x'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Post Moderation', 'community-x'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="community_x_settings[moderate_all_posts]" 
                                       value="1" <?php checked($settings['moderate_all_posts'], 1); ?> />
                                <?php _e('Require approval for all new posts', 'community-x'); ?>
                            </label>
                            <p class="description"><?php _e('When enabled, all posts will require moderator approval before being published.', 'community-x'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Spam Protection', 'community-x'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="community_x_settings[enable_spam_protection]" 
                                       value="1" <?php checked($settings['enable_spam_protection'], 1); ?> />
                                <?php _e('Enable built-in spam protection', 'community-x'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Feature Settings -->
            <div class="settings-section">
                <div class="section-header">
                    <h2><i class="fas fa-star"></i> <?php _e('Features', 'community-x'); ?></h2>
                    <p><?php _e('Enable or disable community features.', 'community-x'); ?></p>
                </div>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Private Messaging', 'community-x'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="community_x_settings[enable_private_messaging]" 
                                       value="1" <?php checked($settings['enable_private_messaging'], 1); ?> />
                                <?php _e('Enable private messaging between members', 'community-x'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Groups', 'community-x'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="community_x_settings[enable_groups]" 
                                       value="1" <?php checked($settings['enable_groups'], 1); ?> />
                                <?php _e('Enable user groups and topics', 'community-x'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Notifications', 'community-x'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="community_x_settings[enable_notifications]" 
                                       value="1" <?php checked($settings['enable_notifications'], 1); ?> />
                                <?php _e('Enable email and in-app notifications', 'community-x'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="settings-footer">
            <?php submit_button(__('Save Settings', 'community-x'), 'primary', 'submit', false, array('class' => 'button-primary button-hero')); ?>
            
            <div class="settings-info">
                <p><i class="fas fa-info-circle"></i> 
                <?php _e('Changes will take effect immediately after saving.', 'community-x'); ?></p>
            </div>
        </div>
    </form>
</div>

<style>
.community-x-settings {
    margin-top: 20px;
}

.community-x-settings h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 30px;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.settings-section {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.section-header {
    background: linear-gradient(135deg, #6366f1, #06b6d4);
    color: white;
    padding: 20px;
}

.section-header h2 {
    margin: 0 0 8px 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 14px;
}

.settings-section .form-table {
    margin: 0;
}

.settings-section .form-table th,
.settings-section .form-table td {
    padding: 20px;
    border-bottom: 1px solid #f1f5f9;
}

.settings-section .form-table tr:last-child th,
.settings-section .form-table tr:last-child td {
    border-bottom: none;
}

.settings-footer {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.settings-info {
    margin: 0;
}

.settings-info p {
    margin: 0;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.button-hero {
    padding: 12px 24px !important;
    font-size: 16px !important;
    height: auto !important;
}

@media (max-width: 1200px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .settings-footer {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
}
</style>