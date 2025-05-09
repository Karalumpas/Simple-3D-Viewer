<?php
/**
 * Class MG3D_Metabox
 * Handles the creation and management of the 3D model metabox
 */
class MG3D_Metabox {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add the meta box to the custom post type
     */
    public function add_meta_box() {
        add_meta_box(
            'mg3d_model_settings',
            __('3D Model Settings', 'mg-3d-productviewer'),
            array($this, 'render_meta_box'),
            'mg3d_model',
            'normal',
            'high'
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        if ($hook !== 'post.php' && $hook !== 'post-new.php' || $post_type !== 'mg3d_model') {
            return;
        }

        wp_enqueue_media();
        
        // Register custom script for the metabox
        wp_register_script(
            'mg3d-admin-script',
            MG3D_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MG3D_VERSION,
            true
        );
        
        wp_enqueue_script('mg3d-admin-script');
    }

    /**
     * Render the meta box content
     */
    public function render_meta_box($post) {
        wp_nonce_field('mg3d_model_settings', 'mg3d_model_settings_nonce');

        // Get saved values
        $model_url = get_post_meta($post->ID, '_mg3d_model_url', true);
        $poster_url = get_post_meta($post->ID, '_mg3d_poster_url', true);
        $bg_color = get_post_meta($post->ID, '_mg3d_bg_color', true) ?: '#ffffff';
        $bg_transparent = get_post_meta($post->ID, '_mg3d_bg_transparent', true) ?: 'no';
        $material_color = get_post_meta($post->ID, '_mg3d_material_color', true) ?: '#ffffff';
        $enable_color_change = get_post_meta($post->ID, '_mg3d_enable_color_change', true) ?: 'no';
        $camera_angle = get_post_meta($post->ID, '_mg3d_camera_angle', true) ?: '0deg 75deg 105%';
        $auto_rotate = get_post_meta($post->ID, '_mg3d_auto_rotate', true) ?: 'yes';
        $rotation_speed = get_post_meta($post->ID, '_mg3d_rotation_speed', true) ?: '30';
        $zoom_level = get_post_meta($post->ID, '_mg3d_zoom_level', true) ?: '1.5';
        $enable_ar = get_post_meta($post->ID, '_mg3d_enable_ar', true) ?: 'yes';
        $shadow_intensity = get_post_meta($post->ID, '_mg3d_shadow_intensity', true) ?: '1';
        $exposure = get_post_meta($post->ID, '_mg3d_exposure', true) ?: '1';
        $animation_name = get_post_meta($post->ID, '_mg3d_animation_name', true) ?: '';
        $autoplay = get_post_meta($post->ID, '_mg3d_autoplay', true) ?: 'no';
        $shortcode = sprintf('[mg3d_viewer id="%d"]', $post->ID);
        $saved_camera_position = get_post_meta($post->ID, '_mg3d_saved_camera_position', true);
        $use_saved_position = get_post_meta($post->ID, '_mg3d_use_saved_position', true) ?: 'no';
        $lock_camera_position = get_post_meta($post->ID, '_mg3d_lock_camera_position', true) ?: 'no';
        ?>
        <div class="mg3d-metabox-container">
            <?php // Preview First ?>
            <div class="mg3d-preview" id="mg3d-preview-container">
                <?php if ($model_url) : ?>
                <model-viewer
                    id="mg3d-model-preview"
                    src="<?php echo esc_url($model_url); ?>"
                    <?php echo $poster_url ? 'poster="' . esc_url($poster_url) . '"' : ''; ?>
                    style="width: 100%; height: 400px; background-color: <?php echo $bg_transparent === 'yes' ? 'transparent' : esc_attr($bg_color); ?>;"
                    camera-orbit="<?php echo esc_attr($camera_angle); ?>"
                    min-camera-orbit="auto auto 50%"
                    max-camera-orbit="auto auto 200%"
                    camera-controls
                    <?php echo $auto_rotate === 'yes' ? 'auto-rotate rotation-per-second="' . (1 / absint($rotation_speed)) . '"' : ''; ?>
                    <?php echo $enable_ar === 'yes' ? 'ar ar-modes="webxr scene-viewer quick-look"' : ''; ?>
                    shadow-intensity="<?php echo esc_attr($shadow_intensity); ?>"
                    exposure="<?php echo esc_attr($exposure); ?>"
                    <?php echo $animation_name ? 'animation-name="' . esc_attr($animation_name) . '"' : ''; ?>
                    <?php echo $autoplay === 'yes' && $animation_name ? 'autoplay' : ''; ?>
                    <?php echo $enable_color_change === 'yes' ? 'style="--poster-color: ' . esc_attr($material_color) . ';"' : ''; ?>>
                </model-viewer>
                <?php else: ?>
                <div class="mg3d-no-model">
                    <p><?php _e('Upload a 3D model to see a preview', 'mg-3d-productviewer'); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <div class="mg3d-shortcode-info">
                <h3><?php _e('Shortcode', 'mg-3d-productviewer'); ?></h3>
                <p><?php _e('Use this shortcode to display the 3D model in your posts or pages:', 'mg-3d-productviewer'); ?></p>
                <input type="text" value="<?php echo esc_attr($shortcode); ?>" readonly onclick="this.select();" class="widefat">
                <p class="description">
                    <?php _e('Advanced usage with material color: ', 'mg-3d-productviewer'); ?>
                    <code>[mg3d_viewer id="<?php echo $post->ID; ?>" material_color="#ff0000" enable_color_change="yes"]</code>
                </p>
            </div>

            <div class="mg3d-model-settings">
                <h3><?php _e('Model Settings', 'mg-3d-productviewer'); ?></h3>
                
                <!-- Model Upload -->
                <div class="mg3d-setting-group">
                    <label for="mg3d_model_url"><?php _e('3D Model (GLB/GLTF)', 'mg-3d-productviewer'); ?></label>
                    <input type="text" id="mg3d_model_url" name="mg3d_model_url" value="<?php echo esc_attr($model_url); ?>" class="widefat">
                    <button type="button" class="button mg3d-upload-button" data-input="mg3d_model_url"><?php _e('Upload Model', 'mg-3d-productviewer'); ?></button>
                </div>

                <!-- Poster Image -->
                <div class="mg3d-setting-group">
                    <label for="mg3d_poster_url"><?php _e('Poster Image (Loading Preview)', 'mg-3d-productviewer'); ?></label>
                    <input type="text" id="mg3d_poster_url" name="mg3d_poster_url" value="<?php echo esc_attr($poster_url); ?>" class="widefat">
                    <button type="button" class="button mg3d-upload-button" data-input="mg3d_poster_url"><?php _e('Upload Poster', 'mg-3d-productviewer'); ?></button>
                </div>

                <!-- Visual Settings -->
                <div class="mg3d-setting-group">
                    <label for="mg3d_bg_color"><?php _e('Background Color', 'mg-3d-productviewer'); ?></label>
                    <input type="color" id="mg3d_bg_color" name="mg3d_bg_color" value="<?php echo esc_attr($bg_color); ?>">
                </div>

                <div class="mg3d-setting-group">
                    <label>
                        <input type="checkbox" name="mg3d_bg_transparent" value="yes" <?php checked($bg_transparent, 'yes'); ?>>
                        <?php _e('Transparent Background', 'mg-3d-productviewer'); ?>
                    </label>
                </div>

                <!-- Material Color Settings -->
                <div class="mg3d-setting-group">
                    <label>
                        <input type="checkbox" name="mg3d_enable_color_change" value="yes" <?php checked($enable_color_change, 'yes'); ?>>
                        <?php _e('Enable Material Color Override', 'mg-3d-productviewer'); ?>
                    </label>
                    <p class="description"><?php _e('This will override the main material color of the 3D model. Note: Only works with models that support material color changes (like single-material objects such as t-shirts).', 'mg-3d-productviewer'); ?></p>
                </div>

                <div class="mg3d-setting-group" id="material-color-group">
                    <label for="mg3d_material_color"><?php _e('Material Color', 'mg-3d-productviewer'); ?></label>
                    <input type="color" id="mg3d_material_color" name="mg3d_material_color" value="<?php echo esc_attr($material_color); ?>">
                    <p class="description"><?php _e('This color will be applied to the main material of the 3D model.', 'mg-3d-productviewer'); ?></p>
                </div>

                <div class="mg3d-setting-group">
                    <label for="mg3d_shadow_intensity"><?php _e('Shadow Intensity', 'mg-3d-productviewer'); ?></label>
                    <input type="range" id="mg3d_shadow_intensity" name="mg3d_shadow_intensity" min="0" max="1" step="0.1" value="<?php echo esc_attr($shadow_intensity); ?>">
                    <span class="range-value"><?php echo esc_html($shadow_intensity); ?></span>
                </div>

                <div class="mg3d-setting-group">
                    <label for="mg3d_exposure"><?php _e('Exposure', 'mg-3d-productviewer'); ?></label>
                    <input type="range" id="mg3d_exposure" name="mg3d_exposure" min="0" max="2" step="0.1" value="<?php echo esc_attr($exposure); ?>">
                    <span class="range-value"><?php echo esc_html($exposure); ?></span>
                </div>

                <!-- Camera Settings -->
                <div class="mg3d-setting-group">
                    <label for="mg3d_camera_angle"><?php _e('Default Camera Angle', 'mg-3d-productviewer'); ?></label>
                    <select id="mg3d_camera_angle" name="mg3d_camera_angle" class="widefat">
                        <?php foreach ($this->get_default_angles() as $angle => $label) : ?>
                            <option value="<?php echo esc_attr($angle); ?>" <?php selected($camera_angle, $angle); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mg3d-setting-group">
                    <label for="mg3d_zoom_level"><?php _e('Default Zoom Level', 'mg-3d-productviewer'); ?></label>
                    <input type="range" id="mg3d_zoom_level" name="mg3d_zoom_level" min="0.5" max="3" step="0.1" value="<?php echo esc_attr($zoom_level); ?>">
                    <span class="range-value"><?php echo esc_html($zoom_level); ?></span>
                </div>

                <!-- Camera Position Controls -->
                <div class="mg3d-setting-group">
                    <h4><?php _e('Camera Position Controls', 'mg-3d-productviewer'); ?></h4>
                    <div class="camera-controls">
                        <button type="button" class="button" id="mg3d-save-camera"><?php _e('Save Current Camera Position', 'mg-3d-productviewer'); ?></button>
                        <input type="hidden" id="mg3d_saved_camera_position" name="mg3d_saved_camera_position" value="<?php echo esc_attr($saved_camera_position); ?>">
                        <p class="description"><?php _e('Rotate the model to your desired position and click to save it.', 'mg-3d-productviewer'); ?></p>
                    </div>
                    <div class="camera-options">
                        <label>
                            <input type="checkbox" name="mg3d_use_saved_position" value="yes" <?php checked($use_saved_position, 'yes'); ?>>
                            <?php _e('Use saved position as default view', 'mg-3d-productviewer'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="mg3d_lock_camera_position" value="yes" <?php checked($lock_camera_position, 'yes'); ?>>
                            <?php _e('Lock camera position (prevents user rotation)', 'mg-3d-productviewer'); ?>
                        </label>
                    </div>
                    <div id="saved-position-preview" class="<?php echo empty($saved_camera_position) ? 'hidden' : ''; ?>">
                        <p><?php _e('Saved Position:', 'mg-3d-productviewer'); ?> <span id="saved-position-value"><?php echo esc_html($saved_camera_position); ?></span></p>
                    </div>
                </div>

                <!-- Animation Settings -->
                <div class="mg3d-setting-group">
                    <label>
                        <input type="checkbox" name="mg3d_auto_rotate" value="yes" <?php checked($auto_rotate, 'yes'); ?>>
                        <?php _e('Enable Auto-Rotation', 'mg-3d-productviewer'); ?>
                    </label>
                </div>

                <div class="mg3d-setting-group" id="rotation-speed-group">
                    <label for="mg3d_rotation_speed"><?php _e('Rotation Speed (seconds per revolution)', 'mg-3d-productviewer'); ?></label>
                    <input type="number" id="mg3d_rotation_speed" name="mg3d_rotation_speed" min="1" max="60" value="<?php echo esc_attr($rotation_speed); ?>">
                </div>

                <!-- Animation Settings (if model has animations) -->
                <div class="mg3d-setting-group">
                    <label for="mg3d_animation_name"><?php _e('Animation Name', 'mg-3d-productviewer'); ?></label>
                    <input type="text" id="mg3d_animation_name" name="mg3d_animation_name" value="<?php echo esc_attr($animation_name); ?>" class="widefat">
                    <p class="description"><?php _e('Leave empty if model has no animations', 'mg-3d-productviewer'); ?></p>
                </div>

                <div class="mg3d-setting-group">
                    <label>
                        <input type="checkbox" name="mg3d_autoplay" value="yes" <?php checked($autoplay, 'yes'); ?>>
                        <?php _e('Autoplay Animation', 'mg-3d-productviewer'); ?>
                    </label>
                </div>

                <!-- AR Settings -->
                <div class="mg3d-setting-group">
                    <label>
                        <input type="checkbox" name="mg3d_enable_ar" value="yes" <?php checked($enable_ar, 'yes'); ?>>
                        <?php _e('Enable AR View', 'mg-3d-productviewer'); ?>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save the meta box data
     */
    public function save_meta_box($post_id) {
        if (!isset($_POST['mg3d_model_settings_nonce']) ||
            !wp_verify_nonce($_POST['mg3d_model_settings_nonce'], 'mg3d_model_settings')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Regular fields with their sanitization functions
        $text_fields = array(
            'mg3d_model_url' => function($value) {
                $url = esc_url_raw($value);
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    return '';
                }
                return $url;
            },
            'mg3d_poster_url' => function($value) {
                $url = esc_url_raw($value);
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    return '';
                }
                return $url;
            },
            'mg3d_bg_color' => 'sanitize_hex_color',
            'mg3d_material_color' => 'sanitize_hex_color',
            'mg3d_camera_angle' => function($value) {
                // Validate camera angle format
                if (!preg_match('/^\d+deg\s+\d+deg\s+\d+%$/', $value)) {
                    return '0deg 75deg 105%';
                }
                return sanitize_text_field($value);
            },
            'mg3d_saved_camera_position' => function($value) {
                if (!preg_match('/^\d+deg\s+\d+deg\s+\d+%$/', $value)) {
                    return '';
                }
                return sanitize_text_field($value);
            },
            'mg3d_rotation_speed' => function($value) {
                $speed = absint($value);
                return ($speed >= 1 && $speed <= 60) ? $speed : 30;
            },
            'mg3d_zoom_level' => function($value) {
                $zoom = floatval($value);
                return ($zoom >= 0.5 && $zoom <= 3) ? $zoom : 1.5;
            },
            'mg3d_shadow_intensity' => function($value) {
                $intensity = floatval($value);
                return ($intensity >= 0 && $intensity <= 1) ? $intensity : 1;
            },
            'mg3d_exposure' => function($value) {
                $exposure = floatval($value);
                return ($exposure >= 0 && $exposure <= 2) ? $exposure : 1;
            },
            'mg3d_animation_name' => 'sanitize_text_field',
        );

        // Checkbox fields
        $checkbox_fields = array(
            'mg3d_auto_rotate',
            'mg3d_bg_transparent',
            'mg3d_enable_color_change',
            'mg3d_enable_ar',
            'mg3d_autoplay',
            'mg3d_use_saved_position',
            'mg3d_lock_camera_position',
        );

        // Process regular fields
        foreach ($text_fields as $field => $sanitize_callback) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                if (is_callable($sanitize_callback)) {
                    $value = $sanitize_callback($value);
                } else {
                    $value = call_user_func($sanitize_callback, $value);
                }
                update_post_meta($post_id, '_' . $field, $value);
            }
        }

        // Process checkbox fields
        foreach ($checkbox_fields as $field) {
            $value = isset($_POST[$field]) ? 'yes' : 'no';
            update_post_meta($post_id, '_' . $field, $value);
        }
    }

    /**
     * Get default camera angles
     */
    private function get_default_angles() {
        return array(
            '0deg 75deg 105%' => __('Default View', 'mg-3d-productviewer'),
            '0deg 0deg 105%' => __('Front View', 'mg-3d-productviewer'),
            '90deg 75deg 105%' => __('Side View', 'mg-3d-productviewer'),
            '180deg 75deg 105%' => __('Back View', 'mg-3d-productviewer'),
            '0deg 90deg 105%' => __('Top View', 'mg-3d-productviewer'),
        );
    }
}