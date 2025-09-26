<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Community_X
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for admin area.
 *
 * @package    Community_X
 * @since      1.0.0
 */
class Community_X_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            COMMUNITY_X_PLUGIN_URL . 'assets/admin/css/community-x-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            COMMUNITY_X_PLUGIN_URL . 'assets/admin/js/community-x-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localize script for AJAX
        wp_localize_script(
            $this->plugin_name,
            'community_x_admin_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('community_x_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'community-x'),
                    'save_success' => __('Settings saved successfully!', 'community-x'),
                    'save_error' => __('Error saving settings. Please try again.', 'community-x'),
                    'loading' => __('Loading...', 'community-x')
                )
            )
        );
    }

    /**
     * Add the plugin admin menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Community X', 'community-x'),
            __('Community X', 'community-x'),
            'community_manage_settings',
            'community-x',
            array($this, 'display_plugin_admin_dashboard'),
            'dashicons-groups',
            30
        );

        // Dashboard submenu (same as main menu)
        add_submenu_page(
            'community-x',
            __('Dashboard', 'community-x'),
            __('Dashboard', 'community-x'),
            'community_manage_settings',
            'community-x',
            array($this, 'display_plugin_admin_dashboard')
        );

        // Settings submenu
        add_submenu_page(
            'community-x',
            __('Settings', 'community-x'),
            __('Settings', 'community-x'),
            'community_manage_settings',
            'community-x-settings',
            array($this, 'display_plugin_settings_page')
        );

        // Members submenu
        add_submenu_page(
            'community-x',
            __('Members', 'community-x'),
            __('Members', 'community-x'),
            'community_manage_users',
            'community-x-members',
            array($this, 'display_plugin_members_page')
        );

        // Posts submenu
        add_submenu_page(
            'community-x',
            __('Posts', 'community-x'),
            __('Posts', 'community-x'),
            'community_moderate_posts',
            'community-x-posts',
            array($this, 'display_plugin_posts_page')
        );

        // Categories submenu
        add_submenu_page(
            'community-x',
            __('Categories', 'community-x'),
            __('Categories', 'community-x'),
            'community_manage_settings',
            'community-x-categories',
            array($this, 'display_plugin_categories_page')
        );
    }

    /**
     * Display the plugin dashboard page.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_dashboard() {
        include_once COMMUNITY_X_PLUGIN_PATH . 'admin/dashboard.php';
    }

    /**
     * Display the plugin settings page.
     *
     * @since    1.0.0
     */
    public function display_plugin_settings_page() {
        include_once COMMUNITY_X_PLUGIN_PATH . 'admin/settings.php';
    }

    /**
     * Display the members management page.
     *
     * @since    1.0.0
     */
    public function display_plugin_members_page() {
        include_once COMMUNITY_X_PLUGIN_PATH . 'admin/members.php';
    }

    /**
     * Display the posts management page.
     *
     * @since    1.0.0
     */
    public function display_plugin_posts_page() {
        include_once COMMUNITY_X_PLUGIN_PATH . 'admin/posts.php';
    }

    /**
     * Display the categories management page.
     *
     * @since    1.0.0
     */
    public function display_plugin_categories_page() {
        include_once COMMUNITY_X_PLUGIN_PATH . 'admin/categories.php';
    }

    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Settings will be expanded in future phases
        register_setting(
            'community_x_settings',
            'community_x_settings',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );
    }

    /**
     * Sanitize settings input.
     *
     * @since    1.0.0
     * @param    array    $input    Settings input array.
     * @return   array              Sanitized settings.
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        if (isset($input['community_name'])) {
            $sanitized['community_name'] = sanitize_text_field($input['community_name']);
        }

        if (isset($input['community_description'])) {
            $sanitized['community_description'] = sanitize_textarea_field($input['community_description']);
        }

        if (isset($input['allow_public_viewing'])) {
            $sanitized['allow_public_viewing'] = (int) $input['allow_public_viewing'];
        }

        return $sanitized;
    }
}