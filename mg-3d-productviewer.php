<?php
/**
 * Plugin Name: MG 3D Product Viewer
 * Plugin URI: https://example.com/mg-3d-productviewer
 * Description: A WordPress plugin that uses Google's model-viewer to display 3D objects on your website.
 * Version: 1.0.1
 * Author: Medegaard Grafisk
 * Author URI: https://mede.dk
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mg-3d-productviewer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('MG3D_VERSION', '1.0.1');
define('MG3D_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MG3D_PLUGIN_URL', plugin_dir_url(__FILE__));

// Register activation hook
register_activation_hook(__FILE__, 'mg3d_activate');

/**
 * Plugin activation function
 */
function mg3d_activate() {
    // Create required directories if they don't exist
    $dirs = array(
        MG3D_PLUGIN_DIR . 'assets',
        MG3D_PLUGIN_DIR . 'assets/css',
        MG3D_PLUGIN_DIR . 'assets/js',
    );
    
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
    
    // Flush rewrite rules after registering custom post type
    flush_rewrite_rules();
}

/**
 * Add GLB and GLTF file types to allowed upload types
 */
function mg3d_add_mime_types($mime_types) {
    // Add GLB and GLTF file types
    $mime_types['glb'] = 'model/gltf-binary';
    $mime_types['gltf'] = 'model/gltf+json';
    $mime_types['bin'] = 'application/octet-stream';  // Add .bin file support
    return $mime_types;
}
add_filter('upload_mimes', 'mg3d_add_mime_types');

/**
 * Fix MIME type detection for 3D model files
 */
function mg3d_fix_mime_type_detection($data, $file, $filename, $mimes) {
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    // Fix for GLB files
    if ($ext === 'glb') {
        $data['ext'] = 'glb';
        $data['type'] = 'model/gltf-binary';
    }
    
    // Fix for GLTF files
    if ($ext === 'gltf') {
        $data['ext'] = 'gltf';
        $data['type'] = 'model/gltf+json';
    }
    
    // Fix for BIN files
    if ($ext === 'bin') {
        $data['ext'] = 'bin';
        $data['type'] = 'application/octet-stream';
    }
    
    return $data;
}
add_filter('wp_check_filetype_and_ext', 'mg3d_fix_mime_type_detection', 10, 4);

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    // Handle namespaced classes
    $prefix = 'MG3D\\';
    $base_dir = MG3D_PLUGIN_DIR . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }

    // Handle non-namespaced classes
    $file = MG3D_PLUGIN_DIR . 'includes/class-' . strtolower(str_replace('_', '-', $class)) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Include required files
require_once MG3D_PLUGIN_DIR . 'includes/class-mg3d-metabox.php';
require_once MG3D_PLUGIN_DIR . 'includes/class-mg3d-shortcode.php';

/**
 * Initialize the plugin
 */
function mg3d_init() {
    // Register custom post type
    register_post_type('mg3d_model', array(
        'labels' => array(
            'name' => __('3D Models', 'mg-3d-productviewer'),
            'singular_name' => __('3D Model', 'mg-3d-productviewer'),
            'add_new' => __('Add New Model', 'mg-3d-productviewer'),
            'add_new_item' => __('Add New 3D Model', 'mg-3d-productviewer'),
            'edit_item' => __('Edit 3D Model', 'mg-3d-productviewer'),
            'new_item' => __('New 3D Model', 'mg-3d-productviewer'),
            'view_item' => __('View 3D Model', 'mg-3d-productviewer'),
            'search_items' => __('Search 3D Models', 'mg-3d-productviewer'),
            'not_found' => __('No 3D models found', 'mg-3d-productviewer'),
            'not_found_in_trash' => __('No 3D models found in trash', 'mg-3d-productviewer'),
            'menu_name' => __('3D Models', 'mg-3d-productviewer'),
        ),
        'public' => true,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-media-interactive',
        'supports' => array('title'),
        'has_archive' => false,
        'rewrite' => array('slug' => '3d-models'),
        'show_in_menu' => true,
    ));

    // Enqueue scripts and styles
    add_action('wp_enqueue_scripts', 'mg3d_enqueue_frontend_scripts');
    add_action('admin_enqueue_scripts', 'mg3d_enqueue_admin_scripts');
    
    // Initialize metabox and shortcode
    new MG3D_Metabox();
    new MG3D_Shortcode();

    // Add shortcode column to admin list
    add_filter('manage_mg3d_model_posts_columns', 'mg3d_add_shortcode_column');
    add_action('manage_mg3d_model_posts_custom_column', 'mg3d_render_shortcode_column', 10, 2);
}
add_action('init', 'mg3d_init');

/**
 * Add shortcode column to admin list
 */
function mg3d_add_shortcode_column($columns) {
    $new_columns = array();
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['shortcode'] = __('Shortcode', 'mg-3d-productviewer');
        }
    }
    return $new_columns;
}

/**
 * Render shortcode column content
 */
function mg3d_render_shortcode_column($column, $post_id) {
    if ($column === 'shortcode') {
        $shortcode = sprintf('[mg3d_viewer id="%d"]', $post_id);
        echo '<input type="text" class="mg3d-shortcode" value="' . esc_attr($shortcode) . '" readonly onclick="this.select();" style="width: 200px;">';
    }
}

/**
 * Add model-viewer script to head with proper type="module" attribute
 * This is a workaround for wp_enqueue_script not properly handling ES modules
 */
function mg3d_add_model_viewer_script() {
    static $script_added = false;
    if (!$script_added) {
        // Add error handling for script loading
        echo '<script type="module">
            window.addEventListener("error", function(e) {
                if (e.target.tagName === "SCRIPT" && e.target.src.includes("model-viewer")) {
                    console.error("Failed to load model-viewer script:", e);
                    document.dispatchEvent(new CustomEvent("model-viewer-error", { detail: e }));
                }
            }, true);
        </script>';
        echo '<script type="module" src="https://unpkg.com/@google/model-viewer@v3.1.1/dist/model-viewer.min.js" crossorigin="anonymous"></script>';
        $script_added = true;
    }
}
add_action('wp_head', 'mg3d_add_model_viewer_script', 5);
add_action('admin_head', 'mg3d_add_model_viewer_script', 5);

/**
 * Enqueue frontend scripts and styles
 */
function mg3d_enqueue_frontend_scripts() {
    // Enqueue frontend styles
    wp_enqueue_style(
        'mg3d-frontend-styles',
        MG3D_PLUGIN_URL . 'assets/css/frontend.css',
        array(),
        MG3D_VERSION
    );

    // Enqueue frontend script with error handling
    wp_enqueue_script(
        'mg3d-frontend-script',
        MG3D_PLUGIN_URL . 'assets/js/frontend.js',
        array(),
        MG3D_VERSION,
        true
    );

    // Add error handling data
    wp_localize_script('mg3d-frontend-script', 'mg3dData', array(
        'errorMessages' => array(
            'loadError' => __('Failed to load 3D model viewer. Please try refreshing the page.', 'mg-3d-productviewer'),
            'modelError' => __('Failed to load 3D model. Please check the model URL.', 'mg-3d-productviewer'),
            'arError' => __('AR mode is not supported on this device.', 'mg-3d-productviewer'),
        ),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mg3d-nonce'),
    ));
}

/**
 * Enqueue admin scripts and styles
 */
function mg3d_enqueue_admin_scripts($hook) {
    global $post_type;
    
    // Only load on 3D model post type screens
    if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'mg3d_model') {
        wp_enqueue_media();
        
        // Register custom script for the metabox
        wp_enqueue_script(
            'mg3d-admin-script',
            MG3D_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MG3D_VERSION,
            true
        );
        
        // Enqueue admin styles
        wp_enqueue_style(
            'mg3d-admin-styles',
            MG3D_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MG3D_VERSION
        );
    }
    
    // Models list screen
    if ($hook === 'edit.php' && $post_type === 'mg3d_model') {
        wp_enqueue_style(
            'mg3d-admin-styles',
            MG3D_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MG3D_VERSION
        );
    }
}

function mg3d_enqueue_admin_assets() {
    $screen = get_current_screen();
    
    // Only load on our custom post type
    if ($screen->post_type !== 'mg3d_model') {
        return;
    }
    
    // Enqueue WordPress media uploader
    wp_enqueue_media();
    
    // Enqueue admin styles
    wp_enqueue_style(
        'mg3d-admin',
        plugins_url('assets/css/admin.css', __FILE__),
        array(),
        MG3D_VERSION
    );
    
    // Enqueue admin scripts
    wp_enqueue_script(
        'mg3d-admin',
        plugins_url('assets/js/admin.js', __FILE__),
        array('jquery'),
        MG3D_VERSION,
        true
    );
    
    // Localize script
    wp_localize_script('mg3d-admin', 'mg3dAdmin', array(
        'title' => __('Select 3D Model', 'mg-3d-productviewer'),
        'button' => __('Select', 'mg-3d-productviewer'),
        'error' => array(
            'load' => __('Error loading model. Please check the URL and try again.', 'mg-3d-productviewer'),
            'format' => __('Unsupported file format. Please use GLB or GLTF files.', 'mg-3d-productviewer')
        )
    ));
}
add_action('admin_enqueue_scripts', 'mg3d_enqueue_admin_assets');