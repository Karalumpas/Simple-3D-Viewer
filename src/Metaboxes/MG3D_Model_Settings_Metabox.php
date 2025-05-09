<?php
namespace MG3D\Metaboxes;

/**
 * Class MG3D_Model_Settings_Metabox
 * Extends the base metabox functionality with specific 3D model settings
 */
class MG3D_Model_Settings_Metabox {
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        wp_enqueue_style(
            'mg3d-admin-styles',
            MG3D_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MG3D_VERSION
        );

        wp_enqueue_script(
            'model-viewer-admin',
            'https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js',
            array(),
            MG3D_VERSION,
            true
        );

        wp_enqueue_script(
            'mg3d-admin-script',
            MG3D_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-media-utils'),
            MG3D_VERSION,
            true
        );

        wp_localize_script('mg3d-admin-script', 'mg3dAdmin', array(
            'title' => __('Select or Upload 3D Model', 'mg-3d-productviewer'),
            'button' => __('Use this model', 'mg-3d-productviewer'),
            'allowedTypes' => array('model/gltf-binary', 'model/gltf+json'),
        ));
    }

    /**
     * Get the shortcode for the current post
     *
     * @param int $post_id Post ID
     * @return string Shortcode
     */
    public static function get_shortcode($post_id) {
        return sprintf('[mg3d_viewer id="%d"]', $post_id);
    }

    /**
     * Validate camera angle format
     *
     * @param string $angle Camera angle string
     * @return bool Whether the angle is valid
     */
    public static function validate_camera_angle($angle) {
        // Format should be: "0deg 75deg 105%"
        $pattern = '/^\d+deg\s+\d+deg\s+\d+%$/';
        return (bool) preg_match($pattern, $angle);
    }

    /**
     * Get default camera angles
     *
     * @return array Array of preset camera angles
     */
    public static function get_default_angles() {
        return array(
            '0deg 75deg 105%' => __('Default View', 'mg-3d-productviewer'),
            '0deg 0deg 105%' => __('Front View', 'mg-3d-productviewer'),
            '90deg 75deg 105%' => __('Side View', 'mg-3d-productviewer'),
            '180deg 75deg 105%' => __('Back View', 'mg-3d-productviewer'),
            '0deg 90deg 105%' => __('Top View', 'mg-3d-productviewer'),
        );
    }
} 