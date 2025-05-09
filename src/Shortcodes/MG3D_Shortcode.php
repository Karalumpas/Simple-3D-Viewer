<?php
namespace MG3D\Shortcodes;

/**
 * Class MG3D_Shortcode
 * Extends the base shortcode functionality with specific 3D model display features
 */
class MG3D_Shortcode {
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'mg3d-frontend-styles',
            MG3D_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            MG3D_VERSION
        );

        wp_enqueue_script(
            'model-viewer',
            'https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js',
            array(),
            MG3D_VERSION,
            true
        );
    }

    /**
     * Get viewer attributes
     *
     * @param array $settings Model viewer settings
     * @return array Model viewer attributes
     */
    public static function get_viewer_attributes($settings) {
        $default_attributes = array(
            'camera-controls' => '',
            'auto-rotate' => '',
            'ar' => '',
            'ar-modes' => 'webxr scene-viewer quick-look',
            'shadow-intensity' => '1',
            'exposure' => '1',
        );

        return array_merge($default_attributes, $settings);
    }

    /**
     * Generate viewer styles
     *
     * @param array $settings Model viewer settings
     * @return string Inline CSS styles
     */
    public static function get_viewer_styles($settings) {
        $styles = array(
            'width' => isset($settings['width']) ? $settings['width'] : '100%',
            'height' => isset($settings['height']) ? $settings['height'] : '400px',
            'background-color' => isset($settings['bg_color']) ? $settings['bg_color'] : '#ffffff',
        );

        $style_string = '';
        foreach ($styles as $property => $value) {
            $style_string .= sprintf('%s: %s; ', $property, $value);
        }

        return trim($style_string);
    }

    /**
     * Generate loading progress bar HTML
     *
     * @return string Progress bar HTML
     */
    public static function get_progress_bar_html() {
        ob_start();
        ?>
        <div class="progress-bar hide" slot="progress-bar">
            <div class="update-bar"></div>
        </div>
        <button slot="ar-button" class="ar-button">
            <?php _e('View in AR', 'mg-3d-productviewer'); ?>
        </button>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if the current browser supports AR features
     *
     * @return bool Whether AR is supported
     */
    public static function is_ar_supported() {
        return wp_is_mobile() && (
            strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false ||
            strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false ||
            strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false
        );
    }
} 