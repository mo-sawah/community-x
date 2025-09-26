<?php
/**
 * Define the internationalization functionality
 *
 * @since      1.0.0
 * @package    Community_X
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Community_X
 */
class Community_X_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'community-x',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}