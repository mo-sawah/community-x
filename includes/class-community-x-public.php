<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Community_X
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for public-facing side.
 *
 * @package    Community_X
 * @since      1.0.0
 */
class Community_X_Public {

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
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            COMMUNITY_X_PLUGIN_URL . 'assets/public/css/community-x-public.css',
            array(),
            $this->version,
            'all'
        );

        // Enqueue Font Awesome if not already loaded
        if (!wp_style_is('font-awesome', 'enqueued')) {
            wp_enqueue_style(
                'community-x-fontawesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
                array(),
                '6.4.0'
            );
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            COMMUNITY_X_PLUGIN_URL . 'assets/public/js/community-x-public.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localize script for AJAX
        wp_localize_script(
            $this->plugin_name,
            'community_x_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('community_x_public_nonce'),
                'user_logged_in' => is_user_logged_in(),
                'strings' => array(
                    'login_required' => __('Please log in to perform this action.', 'community-x'),
                    'loading' => __('Loading...', 'community-x'),
                    'error' => __('An error occurred. Please try again.', 'community-x'),
                    'success' => __('Action completed successfully!', 'community-x')
                )
            )
        );
    }

    /**
     * Register community pages and shortcodes.
     *
     * @since    1.0.0
     */
    public function register_community_pages() {
        // Add rewrite rules for community pages
        add_rewrite_rule(
            '^community/?$',
            'index.php?community_page=main',
            'top'
        );

        add_rewrite_rule(
            '^community/dashboard/?$',
            'index.php?community_page=dashboard',
            'top'
        );

        add_rewrite_rule(
            '^community/members/?$',
            'index.php?community_page=members',
            'top'
        );

        add_rewrite_rule(
            '^community/post/([0-9]+)/?$',
            'index.php?community_page=single_post&post_id=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            '^community/member/([^/]+)/?$',
            'index.php?community_page=member_profile&member_username=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            '^community/category/([^/]+)/?$',
            'index.php?community_page=category&category_slug=$matches[1]',
            'top'
        );

        // Register shortcodes
        add_shortcode('community_x_main', array($this, 'shortcode_main_community'));
        add_shortcode('community_x_dashboard', array($this, 'shortcode_dashboard'));
        add_shortcode('community_x_members', array($this, 'shortcode_members'));
        add_shortcode('community_x_post_form', array($this, 'shortcode_post_form'));
        add_shortcode('community_x_user_profile', array($this, 'shortcode_user_profile'));
    }

    /**
     * Add custom query variables.
     *
     * @since    1.0.0
     * @param    array    $vars    Existing query variables.
     * @return   array             Modified query variables.
     */
    public function add_query_vars($vars) {
        $vars[] = 'community_page';
        $vars[] = 'post_id';
        $vars[] = 'member_username';
        $vars[] = 'category_slug';
        return $vars;
    }

    /**
     * Handle template redirects for community pages.
     *
     * @since    1.0.0
     */
    public function template_redirect() {
        $community_page = get_query_var('community_page');

        if (!$community_page) {
            return;
        }

        // Check if public viewing is allowed for non-logged-in users
        if (!is_user_logged_in() && !$this->is_public_access_allowed($community_page)) {
            wp_redirect(wp_login_url(home_url($_SERVER['REQUEST_URI'])));
            exit;
        }

        switch ($community_page) {
            case 'main':
                $this->load_community_template('main');
                break;
            case 'dashboard':
                $this->load_community_template('dashboard');
                break;
            case 'members':
                $this->load_community_template('members');
                break;
            case 'single_post':
                $this->load_community_template('single-post');
                break;
            case 'member_profile':
                $this->load_community_template('member-profile');
                break;
            case 'category':
                $this->load_community_template('category');
                break;
        }
    }

    /**
     * Check if public access is allowed for a specific page.
     *
     * @since    1.0.0
     * @param    string    $page    Page name.
     * @return   bool              True if public access allowed.
     */
    private function is_public_access_allowed($page) {
        $public_pages = array('main', 'single_post', 'member_profile', 'members', 'category');
        $allow_public_viewing = get_option('community_x_allow_public_viewing', 1);

        return $allow_public_viewing && in_array($page, $public_pages);
    }

    /**
     * Load community template file.
     *
     * @since    1.0.0
     * @param    string    $template    Template name.
     */
    private function load_community_template($template) {
        $template_file = COMMUNITY_X_PLUGIN_PATH . 'public/templates/' . $template . '.php';

        if (file_exists($template_file)) {
            include $template_file;
            exit;
        }
    }

    /**
     * Main community shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            Shortcode output.
     */
    public function shortcode_main_community($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => 10,
            'show_categories' => 'yes',
            'show_search' => 'yes'
        ), $atts);

        ob_start();
        include COMMUNITY_X_PLUGIN_PATH . 'public/shortcodes/main-community.php';
        return ob_get_clean();
    }

    /**
     * Dashboard shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            Shortcode output.
     */
    public function shortcode_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access your dashboard.', 'community-x') . '</p>';
        }

        ob_start();
        include COMMUNITY_X_PLUGIN_PATH . 'public/shortcodes/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Members directory shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            Shortcode output.
     */
    public function shortcode_members($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 20,
            'show_search' => 'yes',
            'show_filters' => 'yes'
        ), $atts);

        ob_start();
        include COMMUNITY_X_PLUGIN_PATH . 'public/shortcodes/members.php';
        return ob_get_clean();
    }

    /**
     * Post submission form shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            Shortcode output.
     */
    public function shortcode_post_form($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to create a post.', 'community-x') . '</p>';
        }

        $atts = shortcode_atts(array(
            'redirect_after' => '',
            'show_categories' => 'yes',
            'required_role' => 'community_member'
        ), $atts);

        // Check user permissions
        if (!current_user_can('community_create_post')) {
            return '<p>' . __('You do not have permission to create posts.', 'community-x') . '</p>';
        }

        ob_start();
        include COMMUNITY_X_PLUGIN_PATH . 'public/shortcodes/post-form.php';
        return ob_get_clean();
    }

    /**
     * User profile shortcode.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string            Shortcode output.
     */
    public function shortcode_user_profile($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'show_posts' => 'yes',
            'show_stats' => 'yes',
            'editable' => 'no'
        ), $atts);

        ob_start();
        include COMMUNITY_X_PLUGIN_PATH . 'public/shortcodes/user-profile.php';
        return ob_get_clean();
    }

    /**
     * Get current community page data.
     *
     * @since    1.0.0
     * @return   array    Page data array.
     */
    public function get_current_page_data() {
        $page_data = array(
            'type' => get_query_var('community_page', 'main'),
            'post_id' => get_query_var('post_id', 0),
            'member_username' => get_query_var('member_username', ''),
            'category_slug' => get_query_var('category_slug', ''),
            'is_public' => !is_user_logged_in(),
            'current_user_id' => get_current_user_id()
        );

        return $page_data;
    }

    /**
     * Check if current user can access community feature.
     *
     * @since    1.0.0
     * @param    string    $feature    Feature name.
     * @return   bool                  True if user can access feature.
     */
    public function user_can_access($feature) {
        if (!is_user_logged_in()) {
            // Check which features are available to public
            $public_features = array('view_posts', 'view_profiles', 'view_members');
            $allow_public = get_option('community_x_allow_public_viewing', 1);
            
            return $allow_public && in_array($feature, $public_features);
        }

        $capability_map = array(
            'create_post' => 'community_create_post',
            'edit_post' => 'community_edit_own_post',
            'delete_post' => 'community_delete_own_post',
            'comment' => 'community_comment',
            'like' => 'community_like',
            'follow' => 'community_follow',
            'message' => 'community_message',
            'edit_profile' => 'community_edit_profile',
            'moderate' => 'community_moderate_posts'
        );

        $capability = isset($capability_map[$feature]) ? $capability_map[$feature] : $feature;
        
        return current_user_can($capability);
    }

    /**
     * Generate community navigation menu.
     *
     * @since    1.0.0
     * @param    array    $args    Navigation arguments.
     * @return   string            Navigation HTML.
     */
    public function get_community_navigation($args = array()) {
        $defaults = array(
            'show_icons' => true,
            'current_page' => '',
            'class' => 'community-nav'
        );
        
        $args = wp_parse_args($args, $defaults);

        $nav_items = array(
            'main' => array(
                'title' => __('Home', 'community-x'),
                'icon' => 'fas fa-home',
                'url' => home_url('/community/'),
                'public' => true
            ),
            'members' => array(
                'title' => __('Members', 'community-x'),
                'icon' => 'fas fa-users',
                'url' => home_url('/community/members/'),
                'public' => true
            ),
            'dashboard' => array(
                'title' => __('Dashboard', 'community-x'),
                'icon' => 'fas fa-tachometer-alt',
                'url' => home_url('/community/dashboard/'),
                'public' => false
            )
        );

        $nav_html = '<nav class="' . esc_attr($args['class']) . '">';
        $nav_html .= '<ul>';

        foreach ($nav_items as $key => $item) {
            // Skip private items for public users
            if (!$item['public'] && !is_user_logged_in()) {
                continue;
            }

            $active_class = ($args['current_page'] === $key) ? ' class="active"' : '';
            $icon = $args['show_icons'] ? '<i class="' . $item['icon'] . '"></i> ' : '';
            
            $nav_html .= '<li' . $active_class . '>';
            $nav_html .= '<a href="' . esc_url($item['url']) . '">';
            $nav_html .= $icon . esc_html($item['title']);
            $nav_html .= '</a>';
            $nav_html .= '</li>';
        }

        $nav_html .= '</ul>';
        $nav_html .= '</nav>';

        return $nav_html;
    }

    /**
     * Get community statistics.
     *
     * @since    1.0.0
     * @return   array    Statistics array.
     */
    public function get_community_stats() {
        require_once COMMUNITY_X_PLUGIN_PATH . 'includes/class-community-x-database.php';
        
        $database = new Community_X_Database();
        $stats = $database->get_stats();
        
        // Add more calculated stats
        $stats['engagement_rate'] = $stats['total_posts'] > 0 ? 
            round(($stats['total_likes'] + ($stats['total_posts'] * 2)) / $stats['total_posts'] * 100) : 0;
        
        return $stats;
    }
}