<?php
/**
 * Class MG3D_Shortcode
 * Handles the shortcode functionality for displaying 3D models
 */
class MG3D_Shortcode {
    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('mg3d_viewer', array($this, 'render_shortcode'));
    }

    /**
     * Render the shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered shortcode content
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
            'width' => '100%',
            'height' => '400px',
            'bg_color' => '',
            'bg_transparent' => '',
            'material_color' => '',
            'enable_color_change' => '',
            'camera_angle' => '',
            'auto_rotate' => '',
            'rotation_speed' => '',
            'zoom_level' => '',
            'enable_ar' => '',
            'shadow_intensity' => '',
            'exposure' => '',
            'animation_name' => '',
            'autoplay' => '',
            'saved_camera_position' => '',
            'use_saved_position' => '',
        ), $atts, 'mg3d_viewer');

        // Validate post ID
        $post_id = absint($atts['id']);
        if (!$post_id || get_post_type($post_id) !== 'mg3d_model') {
            return '<p>' . __('Invalid 3D model ID.', 'mg-3d-productviewer') . '</p>';
        }

        $model_url = get_post_meta($post_id, '_mg3d_model_url', true);
        
        if (!$model_url) {
            return '<p>' . __('No 3D model found.', 'mg-3d-productviewer') . '</p>';
        }

        // Get default values
        $default_bg_color = get_post_meta($post_id, '_mg3d_bg_color', true);
        if (!$default_bg_color) $default_bg_color = '#ffffff';
        
        $default_bg_transparent = get_post_meta($post_id, '_mg3d_bg_transparent', true);
        $default_bg_transparent = ($default_bg_transparent === 'yes') ? 'yes' : 'no';
        
        $default_material_color = get_post_meta($post_id, '_mg3d_material_color', true);
        if (!$default_material_color) $default_material_color = '#ffffff';
        
        $default_enable_color_change = get_post_meta($post_id, '_mg3d_enable_color_change', true);
        $default_enable_color_change = ($default_enable_color_change === 'yes') ? 'yes' : 'no';
        
        $default_camera_angle = get_post_meta($post_id, '_mg3d_camera_angle', true);
        if (!$default_camera_angle) $default_camera_angle = '0deg 75deg 105%';
        
        // Fix for auto-rotate not respecting saved settings
        $default_auto_rotate = get_post_meta($post_id, '_mg3d_auto_rotate', true);
        // If empty or not 'yes', set it to 'no' explicitly
        $default_auto_rotate = ($default_auto_rotate === 'yes') ? 'yes' : 'no';
        
        $default_rotation_speed = absint(get_post_meta($post_id, '_mg3d_rotation_speed', true));
        if (!$default_rotation_speed) $default_rotation_speed = 30;
        
        $default_zoom_level = floatval(get_post_meta($post_id, '_mg3d_zoom_level', true));
        if (!$default_zoom_level) $default_zoom_level = 1.5;
        
        $default_enable_ar = get_post_meta($post_id, '_mg3d_enable_ar', true);
        if (!$default_enable_ar) $default_enable_ar = 'yes';
        
        $default_shadow_intensity = floatval(get_post_meta($post_id, '_mg3d_shadow_intensity', true));
        if (!$default_shadow_intensity) $default_shadow_intensity = 1;
        
        $default_exposure = floatval(get_post_meta($post_id, '_mg3d_exposure', true));
        if (!$default_exposure) $default_exposure = 1;
        
        $default_animation_name = get_post_meta($post_id, '_mg3d_animation_name', true);
        
        $default_autoplay = get_post_meta($post_id, '_mg3d_autoplay', true);
        if (!$default_autoplay) $default_autoplay = 'no';

        // Get model settings from post meta or shortcode attributes
        $settings = array(
            'poster_url' => get_post_meta($post_id, '_mg3d_poster_url', true),
            'bg_color' => !empty($atts['bg_color']) ? $atts['bg_color'] : $default_bg_color,
            'bg_transparent' => isset($atts['bg_transparent']) && $atts['bg_transparent'] !== '' ? $atts['bg_transparent'] : $default_bg_transparent,
            'material_color' => !empty($atts['material_color']) ? $atts['material_color'] : $default_material_color,
            'enable_color_change' => isset($atts['enable_color_change']) && $atts['enable_color_change'] !== '' ? $atts['enable_color_change'] : $default_enable_color_change,
            'camera_angle' => !empty($atts['camera_angle']) ? $atts['camera_angle'] : $default_camera_angle,
            'auto_rotate' => isset($atts['auto_rotate']) && $atts['auto_rotate'] !== '' ? $atts['auto_rotate'] : $default_auto_rotate,
            'rotation_speed' => !empty($atts['rotation_speed']) ? absint($atts['rotation_speed']) : $default_rotation_speed,
            'zoom_level' => !empty($atts['zoom_level']) ? floatval($atts['zoom_level']) : $default_zoom_level,
            'enable_ar' => !empty($atts['enable_ar']) ? $atts['enable_ar'] : $default_enable_ar,
            'shadow_intensity' => !empty($atts['shadow_intensity']) ? floatval($atts['shadow_intensity']) : $default_shadow_intensity,
            'exposure' => !empty($atts['exposure']) ? floatval($atts['exposure']) : $default_exposure,
            'animation_name' => !empty($atts['animation_name']) ? $atts['animation_name'] : $default_animation_name,
            'autoplay' => !empty($atts['autoplay']) ? $atts['autoplay'] : $default_autoplay,
            'saved_camera_position' => !empty($atts['saved_camera_position']) ? $atts['saved_camera_position'] : get_post_meta($post_id, '_mg3d_saved_camera_position', true),
            'use_saved_position' => !empty($atts['use_saved_position']) ? $atts['use_saved_position'] : get_post_meta($post_id, '_mg3d_use_saved_position', true),
        );

        // Ensure rotation speed is never zero to avoid division by zero
        if (empty($settings['rotation_speed']) || $settings['rotation_speed'] < 1) {
            $settings['rotation_speed'] = 30;
        }

        // Sanitize boolean values
        $settings['auto_rotate'] = $this->sanitize_yes_no($settings['auto_rotate']);
        $settings['enable_ar'] = $this->sanitize_yes_no($settings['enable_ar']);
        $settings['autoplay'] = $this->sanitize_yes_no($settings['autoplay']);
        $settings['bg_transparent'] = $this->sanitize_yes_no($settings['bg_transparent']);
        $settings['enable_color_change'] = $this->sanitize_yes_no($settings['enable_color_change']);

        $unique_id = 'mg3d-model-' . $post_id . '-' . mt_rand(1000, 9999);
        $output = '<div class="mg3d-viewer-container" id="' . esc_attr($unique_id) . '-container">';
        // Kun AR-knap i frontend-toolbar hvis aktiveret
        $output .= '<div class="mg3d-toolbar">';
        if ($settings['enable_ar'] === 'yes') {
            $output .= '<button class="ar-button" title="' . esc_attr__('View in AR', 'mg-3d-productviewer') . '">';
            $output .= '<svg viewBox="0 0 24 24"><path d="M9.5 6.5v3h-3v-3h3M11 5H5v6h6V5zm-1.5 9.5v3h-3v-3h3M11 13H5v6h6v-6zm6.5-6.5v3h-3v-3h3M19 5h-6v6h6V5zm-6 8h1.5v1.5H13V13zm1.5 1.5H16V16h-1.5v-1.5zM16 13h1.5v1.5H16V13zm-3 3h1.5v1.5H13V16zm1.5 1.5H16V19h-1.5v-1.5zM16 16h1.5v1.5H16V16zm1.5-1.5H19V16h-1.5v-1.5zm0 3H19V19h-1.5v-1.5zM22 7h-2V4h-3V2h5v5zm0 15v-5h-2v3h-3v2h5zM2 22h5v-2H4v-3H2v5zM2 2v5h2V4h3V2H2z"/></svg>';
            $output .= __('View in AR', 'mg-3d-productviewer');
            $output .= '</button>';
        }
        $output .= '</div>';
        $output .= '<model-viewer';
        $output .= ' id="' . esc_attr($unique_id) . '"';
        $output .= ' src="' . esc_url($model_url) . '"';
        $output .= ' data-initial-camera-orbit="' . esc_attr($initial_camera_orbit) . '"';
        
        // Add poster if available
        if (!empty($settings['poster_url'])) {
            $output .= ' poster="' . esc_url($settings['poster_url']) . '"';
        }
        
        // Add style attributes
        $output .= ' style="width: ' . esc_attr($atts['width']) . '; height: ' . esc_attr($atts['height']) . '; background-color: ' . ($settings['bg_transparent'] === 'yes' ? 'transparent' : esc_attr($settings['bg_color'])) . ';';
        
        // Add material color if enabled
        if ($settings['enable_color_change'] === 'yes') {
            $output .= ' --poster-color: ' . esc_attr($settings['material_color']) . ';';
        }
        
        $output .= '"';
        
        $lock_camera_position = get_post_meta($post_id, '_mg3d_lock_camera_position', true);
        $saved_camera_position = get_post_meta($post_id, '_mg3d_saved_camera_position', true);
        
        // Add camera settings
        if ($lock_camera_position === 'yes' && !empty($saved_camera_position)) {
            $output .= ' camera-orbit="' . esc_attr($saved_camera_position) . '"';
            // Ikke camera-controls hvis låst
        } else {
            $output .= ' camera-orbit="' . esc_attr($settings['camera_angle']) . '"';
            $output .= ' camera-controls';
        }
        
        $output .= ' min-camera-orbit="auto auto 50%"';
        $output .= ' max-camera-orbit="auto auto 200%"';
        
        // Add auto-rotation if enabled
        if ($settings['auto_rotate'] === 'yes') {
            $rotation_per_second = 1 / $settings['rotation_speed'];
            $output .= ' auto-rotate';
            $output .= ' rotation-per-second="' . esc_attr($rotation_per_second) . '"';
        }
        
        // Add AR if enabled
        if ($settings['enable_ar'] === 'yes') {
            $output .= ' ar ar-modes="webxr scene-viewer quick-look"';
        }
        
        // Add other visual settings
        $output .= ' shadow-intensity="' . esc_attr($settings['shadow_intensity']) . '"';
        $output .= ' exposure="' . esc_attr($settings['exposure']) . '"';
        
        // Add animation settings if available
        if (!empty($settings['animation_name'])) {
            $output .= ' animation-name="' . esc_attr($settings['animation_name']) . '"';
            if ($settings['autoplay'] === 'yes') {
                $output .= ' autoplay';
            }
        }
        
        // Add performance/UI settings
        $output .= ' loading="lazy"';
        $output .= ' reveal="auto"';
        $output .= ' touch-action="pan-y"';
        
        // Close opening tag
        $output .= '>';
        
        // Add progress bar
        $output .= '<div class="progress-bar hide" slot="progress-bar">';
        $output .= '<div class="update-bar"></div>';
        $output .= '</div>';
        
        // Add AR button if enabled
        if ($settings['enable_ar'] === 'yes') {
            $output .= '<button slot="ar-button" class="ar-button">';
            $output .= esc_html__('View in AR', 'mg-3d-productviewer');
            $output .= '</button>';
        }
        
        // Add interaction prompt
        $output .= '<div class="controls-info" slot="interaction-prompt">';
        $output .= esc_html__('Click and drag to rotate', 'mg-3d-productviewer');
        $output .= '</div>';
        
        // Add poster
        $output .= '<div slot="poster" class="poster">';
        if (empty($settings['poster_url'])) {
            $output .= '<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNTAwIiBoZWlnaHQ9IjUwMCIgdmlld0JveD0iMCAwIDUwMCA1MDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjUwMCIgaGVpZ2h0PSI1MDAiIGZpbGw9IiNFNUU1RTUiLz48cGF0aCBkPSJNMTUwIDI0MCBMMjUwIDMxMCBMMzUwIDI0MCBMMjUwIDE3MCBaIiBmaWxsPSIjQUFBIi8+PHBhdGggZD0iTTI0OCAxMjAgTDI0OCAzODAiIHN0cm9rZT0iI0FBQSIgc3Ryb2tlLXdpZHRoPSI0Ii8+PC9zdmc+" alt="loading">';
        }
        $output .= '<div class="poster-info">';
        $output .= '<p>' . esc_html__('Loading 3D Model...', 'mg-3d-productviewer') . '</p>';
        $output .= '<p>' . esc_html__('Click to load', 'mg-3d-productviewer') . '</p>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Add camera controls
        $output .= '<div class="camera-controls">';
        $output .= '<button class="camera-button reset-camera" title="' . esc_attr__('Reset View', 'mg-3d-productviewer') . '">';
        $output .= '<svg viewBox="0 0 24 24"><path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/></svg>';
        $output .= '</button>';
        $output .= '</div>';

        // Close model-viewer tag
        $output .= '</model-viewer>';
        $output .= '</div>';

        // Add inline script for this instance
        $output .= '<script type="module">';
        $output .= 'document.getElementById("' . esc_attr($unique_id) . '").addEventListener("load", function() {';
        $output .= '  const viewer = this;';
        $output .= '  const resetBtn = viewer.parentElement.querySelector(".reset-camera");';
        $output .= '  if (resetBtn) {';
        $output .= '    resetBtn.addEventListener("click", () => {';
        $output .= '      viewer.setAttribute("camera-orbit", viewer.dataset.initialCameraOrbit);';
        $output .= '    });';
        $output .= '  }';
        $output .= '});';
        $output .= '</script>';

        return $output;
    }

    /**
     * Sanitize yes/no value
     * 
     * @param string $value The value to sanitize
     * @return string Sanitized value (yes/no)
     */
    private function sanitize_yes_no($value) {
        return ($value === 'yes' || $value === '1' || $value === 'true') ? 'yes' : 'no';
    }
}