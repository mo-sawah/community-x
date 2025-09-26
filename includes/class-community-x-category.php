<?php
/**
 * Category management functionality
 *
 * @since      1.0.0
 * @package    Community_X
 */

class Community_X_Category {

    /**
     * Get a single category by its ID.
     * @param int $id
     * @return array|null
     */
    public static function get($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'community_x_categories';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
    }

    /**
     * Get all categories.
     * @return array
     */
    public static function get_all() {
        global $wpdb;
        $table = $wpdb->prefix . 'community_x_categories';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY sort_order ASC, name ASC", ARRAY_A);
    }

    /**
     * Create a new category.
     * @param array $data
     * @return int|false
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'community_x_categories';

        $d = array(
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['slug'] ?: $data['name']),
            'description' => sanitize_textarea_field($data['description']),
            'icon' => sanitize_text_field($data['icon']),
            'color' => sanitize_hex_color($data['color']),
            'sort_order' => intval($data['sort_order'])
        );

        $wpdb->insert($table, $d);
        return $wpdb->insert_id;
    }

    /**
     * Update an existing category.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'community_x_categories';

        $d = array(
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['slug'] ?: $data['name']),
            'description' => sanitize_textarea_field($data['description']),
            'icon' => sanitize_text_field($data['icon']),
            'color' => sanitize_hex_color($data['color']),
            'sort_order' => intval($data['sort_order'])
        );
        
        return $wpdb->update($table, $d, array('id' => $id));
    }

    /**
     * Delete a category.
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'community_x_categories';
        return $wpdb->delete($table, array('id' => $id));
    }
}