<?php
/**
 * Plugin Name: Community X
 * Plugin URI: https://sawahsolutions.com
 * Description: Modern WordPress Community Plugin with user profiles, frontend submission, search, and AI integration.
 * Version: 1.0.3
 * Author: Mohamed Sawah
 * Author URI: https://sawahsolutions.com
 * Text Domain: community-x
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin version.
 */
define('COMMUNITY_X_VERSION', '1.0.3');

/**
 * Plugin directory path.
 */
define('COMMUNITY_X_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('COMMUNITY_X_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename.
 */
define('COMMUNITY_X_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin text domain.
 */
define('COMMUNITY_X_TEXT_DOMAIN', 'community-x');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-community-x-activator.php
 */
function activate_community_x() {
    require_once COMMUNITY_X_PLUGIN_PATH . 'includes/class-community-x-activator.php';
    Community_X_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-community-x-deactivator.php
 */
function deactivate_community_x() {
    require_once COMMUNITY_X_PLUGIN_PATH . 'includes/class-community-x-deactivator.php';
    Community_X_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_community_x');
register_deactivation_hook(__FILE__, 'deactivate_community_x');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require COMMUNITY_X_PLUGIN_PATH . 'includes/class-community-x.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function run_community_x() {
    $plugin = new Community_X();
    $plugin->run();
}
run_community_x();