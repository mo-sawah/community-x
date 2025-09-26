<?php
/**
 * Registration Form Shortcode
 *
 * @since      1.0.0
 * @package    Community_X
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check if user registration is enabled
if (!get_option('users_can_register')) {
    return '<p class="community-x-error">' . __('User registration is currently disabled.', 'community-x') . '</p>';
}

// If user is already logged in, show message
if (is_user_logged_in()) {
    return '<p class="community-x-info">' . __('You are already registered and logged in.', 'community-x') . '</p>';
}

// Get shortcode attributes
$redirect_after = isset($atts['redirect_after']) ? esc_url($atts['redirect_after']) : home_url('/community-dashboard/');
$show_login_link = isset($atts['show_login_link']) && $atts['show_login_link'] === 'no' ? false : true;
$required_fields = isset($atts['required_fields']) ? explode(',', $atts['required_fields']) : array('email', 'username', 'password');

// Handle form submission
$errors = array();
$success_message = '';

if ($_POST && isset($_POST['community_x_register_nonce'])) {
    if (wp_verify_nonce($_POST['community_x_register_nonce'], 'community_x_register')) {
        
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $bio = sanitize_textarea_field($_POST['bio']);
        $location = sanitize_text_field($_POST['location']);
        
        // Validation
        if (empty($username)) {
            $errors[] = __('Username is required.', 'community-x');
        } elseif (username_exists($username)) {
            $errors[] = __('Username already exists.', 'community-x');
        }
        
        if (empty($email)) {
            $errors[] = __('Email address is required.', 'community-x');
        } elseif (!is_email($email)) {
            $errors[] = __('Please enter a valid email address.', 'community-x');
        } elseif (email_exists($email)) {
            $errors[] = __('Email address already registered.', 'community-x');
        }
        
        if (empty($password)) {
            $errors[] = __('Password is required.', 'community-x');
        } elseif (strlen($password) < 6) {
            $errors[] = __('Password must be at least 6 characters long.', 'community-x');
        }
        
        if ($password !== $confirm_password) {
            $errors[] = __('Passwords do not match.', 'community-x');
        }
        
        if (!isset($_POST['agree_terms'])) {
            $errors[] = __('You must agree to the terms and conditions.', 'community-x');
        }
        
        // If no errors, create user
        if (empty($errors)) {
            $user_data = array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass' => $password,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'display_name' => $first_name ? $first_name . ' ' . $last_name : $username,
                'role' => get_option('community_x_default_user_role', 'community_member')
            );
            
            $user_id = wp_insert_user($user_data);
            
            if (!is_wp_error($user_id)) {
                // Update profile with additional data
                if ($bio || $location) {
                    Community_X_User::update_user_profile($user_id, array(
                        'bio' => $bio,
                        'location' => $location
                    ));
                }
                
                $success_message = __('Registration successful! Please check your email for verification.', 'community-x');
                
                // Auto-login if email verification is not required
                if (!get_option('community_x_require_email_verification', 1)) {
                    wp_set_current_user($user_id);
                    wp_set_auth_cookie($user_id);
                    
                    if ($redirect_after) {
                        wp_redirect($redirect_after);
                        exit;
                    }
                }
            } else {
                $errors[] = $user_id->get_error_message();
            }
        }
    } else {
        $errors[] = __('Security verification failed. Please try again.', 'community-x');
    }
}
?>

<div class="community-x-registration-form">
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
            
            <?php if ($show_login_link): ?>
                <p>
                    <a href="<?php echo wp_login_url(); ?>" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> <?php _e('Login Now', 'community-x'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="registration-header">
            <h3><?php _e('Join Our Community', 'community-x'); ?></h3>
            <p><?php _e('Create your account to start connecting with other members.', 'community-x'); ?></p>
        </div>
        
        <form method="post" class="community-registration-form">
            <?php wp_nonce_field('community_x_register', 'community_x_register_nonce'); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="reg_first_name"><?php _e('First Name', 'community-x'); ?></label>
                    <input type="text" id="reg_first_name" name="first_name" 
                           value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>" 
                           class="form-control" />
                </div>
                
                <div class="form-group">
                    <label for="reg_last_name"><?php _e('Last Name', 'community-x'); ?></label>
                    <input type="text" id="reg_last_name" name="last_name" 
                           value="<?php echo isset($_POST['last_name']) ? esc_attr($_POST['last_name']) : ''; ?>" 
                           class="form-control" />
                </div>
            </div>
            
            <div class="form-group">
                <label for="reg_username"><?php _e('Username', 'community-x'); ?> *</label>
                <input type="text" id="reg_username" name="username" 
                       value="<?php echo isset($_POST['username']) ? esc_attr($_POST['username']) : ''; ?>" 
                       class="form-control" required />
                <small class="form-help"><?php _e('Username cannot be changed later.', 'community-x'); ?></small>
            </div>
            
            <div class="form-group">
                <label for="reg_email"><?php _e('Email Address', 'community-x'); ?> *</label>
                <input type="email" id="reg_email" name="email" 
                       value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>" 
                       class="form-control" required />
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="reg_password"><?php _e('Password', 'community-x'); ?> *</label>
                    <input type="password" id="reg_password" name="password" 
                           class="form-control" required minlength="6" />
                    <small class="form-help"><?php _e('Minimum 6 characters.', 'community-x'); ?></small>
                </div>
                
                <div class="form-group">
                    <label for="reg_confirm_password"><?php _e('Confirm Password', 'community-x'); ?> *</label>
                    <input type="password" id="reg_confirm_password" name="confirm_password" 
                           class="form-control" required />
                </div>
            </div>
            
            <div class="form-group">
                <label for="reg_bio"><?php _e('Bio', 'community-x'); ?></label>
                <textarea id="reg_bio" name="bio" rows="3" class="form-control" 
                          placeholder="<?php _e('Tell us a bit about yourself...', 'community-x'); ?>"><?php echo isset($_POST['bio']) ? esc_textarea($_POST['bio']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="reg_location"><?php _e('Location', 'community-x'); ?></label>
                <input type="text" id="reg_location" name="location" 
                       value="<?php echo isset($_POST['location']) ? esc_attr($_POST['location']) : ''; ?>" 
                       class="form-control" 
                       placeholder="<?php _e('City, Country', 'community-x'); ?>" />
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="agree_terms" value="1" required />
                    <span class="checkmark"></span>
                    <?php printf(__('I agree to the %s and %s', 'community-x'), 
                                '<a href="#" target="_blank">' . __('Terms of Service', 'community-x') . '</a>',
                                '<a href="#" target="_blank">' . __('Privacy Policy', 'community-x') . '</a>'); ?>
                </label>
            </div>
            
            <?php if (get_option('community_x_require_email_verification', 1)): ?>
                <div class="form-info">
                    <p><i class="fas fa-info-circle"></i> <?php _e('You will receive an email to verify your account.', 'community-x'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="fas fa-user-plus"></i> <?php _e('Create Account', 'community-x'); ?>
                </button>
            </div>
            
            <?php if ($show_login_link): ?>
                <div class="form-footer">
                    <p><?php _e('Already have an account?', 'community-x'); ?> 
                       <a href="<?php echo wp_login_url(); ?>"><?php _e('Login here', 'community-x'); ?></a>
                    </p>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Password strength indicator
    $('#reg_password').on('input', function() {
        var password = $(this).val();
        var strength = 0;
        
        if (password.length >= 6) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        var $indicator = $('.password-strength');
        if (!$indicator.length) {
            $indicator = $('<div class="password-strength"></div>');
            $(this).after($indicator);
        }
        
        var strengthText = '';
        var strengthClass = '';
        
        switch (strength) {
            case 0:
            case 1:
                strengthText = '<?php _e("Weak", "community-x"); ?>';
                strengthClass = 'weak';
                break;
            case 2:
            case 3:
                strengthText = '<?php _e("Medium", "community-x"); ?>';
                strengthClass = 'medium';
                break;
            case 4:
            case 5:
                strengthText = '<?php _e("Strong", "community-x"); ?>';
                strengthClass = 'strong';
                break;
        }
        
        $indicator.removeClass('weak medium strong').addClass(strengthClass);
        $indicator.text('<?php _e("Password strength:", "community-x"); ?> ' + strengthText);
    });
    
    // Password confirmation validation
    $('#reg_confirm_password').on('input', function() {
        var password = $('#reg_password').val();
        var confirmPassword = $(this).val();
        
        var $feedback = $('.password-match-feedback');
        if (!$feedback.length) {
            $feedback = $('<div class="password-match-feedback"></div>');
            $(this).after($feedback);
        }
        
        if (confirmPassword && confirmPassword !== password) {
            $feedback.removeClass('match').addClass('no-match');
            $feedback.text('<?php _e("Passwords do not match", "community-x"); ?>');
        } else if (confirmPassword && confirmPassword === password) {
            $feedback.removeClass('no-match').addClass('match');
            $feedback.text('<?php _e("Passwords match", "community-x"); ?>');
        } else {
            $feedback.removeClass('match no-match').text('');
        }
    });
    
    // Username availability check
    var usernameTimeout;
    $('#reg_username').on('input', function() {
        var username = $(this).val();
        var $feedback = $('.username-feedback');
        
        if (!$feedback.length) {
            $feedback = $('<div class="username-feedback"></div>');
            $(this).after($feedback);
        }
        
        clearTimeout(usernameTimeout);
        
        if (username.length >= 3) {
            $feedback.removeClass('available unavailable').addClass('checking');
            $feedback.text('<?php _e("Checking availability...", "community-x"); ?>');
            
            usernameTimeout = setTimeout(function() {
                $.post(community_x_ajax.ajax_url, {
                    action: 'community_x_check_username',
                    username: username,
                    nonce: community_x_ajax.nonce
                })
                .done(function(response) {
                    if (response.success) {
                        $feedback.removeClass('checking unavailable').addClass('available');
                        $feedback.text('<?php _e("Username is available", "community-x"); ?>');
                    } else {
                        $feedback.removeClass('checking available').addClass('unavailable');
                        $feedback.text(response.data || '<?php _e("Username is not available", "community-x"); ?>');
                    }
                })
                .fail(function() {
                    $feedback.removeClass('checking available unavailable').text('');
                });
            }, 500);
        } else {
            $feedback.removeClass('checking available unavailable').text('');
        }
    });
});
</script>