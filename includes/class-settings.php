<?php

namespace CSIG;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Settings {
    
    private $option_prefix = 'csig_';
    
    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }
    
    public function register_settings() {
        // Global settings
        register_setting( 'csig_global_settings', 'csig_default_save_folder' );
        register_setting( 'csig_global_settings', 'csig_default_image_quality' );
        register_setting( 'csig_global_settings', 'csig_default_retina_support' );
        register_setting( 'csig_global_settings', 'csig_default_selector' );
        
        add_settings_section(
            'csig_global_settings_section',
            __( 'Default Settings', 'csig' ),
            [ $this, 'global_settings_callback' ],
            'csig_global_settings'
        );
        
        add_settings_field(
            'csig_default_selector',
            __( 'Default CSS Selector', 'csig' ),
            [ $this, 'default_selector_callback' ],
            'csig_global_settings',
            'csig_global_settings_section'
        );
        
        add_settings_field(
            'csig_default_save_folder',
            __( 'Default Save Folder', 'csig' ),
            [ $this, 'default_save_folder_callback' ],
            'csig_global_settings',
            'csig_global_settings_section'
        );
        
        add_settings_field(
            'csig_default_image_quality',
            __( 'Default Image Quality', 'csig' ),
            [ $this, 'default_image_quality_callback' ],
            'csig_global_settings',
            'csig_global_settings_section'
        );
        
        add_settings_field(
            'csig_default_retina_support',
            __( 'Default Retina Support', 'csig' ),
            [ $this, 'default_retina_support_callback' ],
            'csig_global_settings',
            'csig_global_settings_section'
        );
    }
    
    public function global_settings_callback() {
        echo '<p>' . __( 'These are the default settings used when creating new jobs.', 'csig' ) . '</p>';
    }
    
    public function default_selector_callback() {
        $value = get_option( 'csig_default_selector', '.csig-card' );
        echo '<input type="text" name="csig_default_selector" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . __( 'Default CSS selector to capture (e.g., .my-class, #my-id, .card)', 'csig' ) . '</p>';
    }
    
    public function default_save_folder_callback() {
        $value = get_option( 'csig_default_save_folder', 'csig-images' );
        $upload_dir = wp_upload_dir();
        echo '<input type="text" name="csig_default_save_folder" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">' . sprintf( 
            __( 'Default folder within uploads directory. Current path: %s', 'csig' ),
            '<code>' . $upload_dir['basedir'] . '/' . esc_html( $value ) . '</code>'
        ) . '</p>';
    }
    
    public function default_image_quality_callback() {
        $value = get_option( 'csig_default_image_quality', 'high' );
        $options = [
            'low' => __( 'Low (1x)', 'csig' ),
            'high' => __( 'High (2x)', 'csig' ),
            'ultra' => __( 'Ultra (3x)', 'csig' )
        ];
        
        echo '<select name="csig_default_image_quality">';
        foreach ( $options as $key => $label ) {
            echo '<option value="' . esc_attr( $key ) . '"' . selected( $value, $key, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
    }
    
    public function default_retina_support_callback() {
        $value = get_option( 'csig_default_retina_support', '1' );
        echo '<label><input type="checkbox" name="csig_default_retina_support" value="1"' . checked( $value, '1', false ) . ' /> ' . __( 'Enable Retina/High-DPI support by default', 'csig' ) . '</label>';
    }
    
    public function get_global_defaults() {
        $save_folder = defined( 'CSIG_DEFAULT_SAVE_FOLDER' ) ? CSIG_DEFAULT_SAVE_FOLDER : get_option( 'csig_default_save_folder', 'csig-images' );
        $image_quality = defined( 'CSIG_DEFAULT_IMAGE_QUALITY' ) ? CSIG_DEFAULT_IMAGE_QUALITY : get_option( 'csig_default_image_quality', 'high' );
        $retina_support = get_option( 'csig_default_retina_support', '1' ) === '1';
        $selector = defined( 'CSIG_DEFAULT_SELECTOR' ) ? CSIG_DEFAULT_SELECTOR : get_option( 'csig_default_selector', '.csig-card' );
        
        $quality_map = [ 'low' => 1, 'high' => 2, 'ultra' => 3 ];
        $pixel_ratio = $quality_map[ $image_quality ] ?? 2;
        
        return [
            'saveFolder' => apply_filters( 'csig_default_save_folder', $save_folder ),
            'imageQuality' => apply_filters( 'csig_default_image_quality', $image_quality ),
            'pixelRatio' => $pixel_ratio,
            'retinaSupport' => apply_filters( 'csig_default_retina_support', $retina_support ),
            'selector' => apply_filters( 'csig_default_selector', $selector )
        ];
    }
    
    public function get_save_directory( $folder_name = null ) {
        if ( ! $folder_name ) {
            $defaults = $this->get_global_defaults();
            $folder_name = $defaults['saveFolder'];
        }
        
        $upload_dir = wp_upload_dir();
        $save_dir = $upload_dir['basedir'] . '/' . $folder_name;
        
        if ( ! file_exists( $save_dir ) ) {
            wp_mkdir_p( $save_dir );
        }
        
        return [
            'path' => $save_dir,
            'url' => $upload_dir['baseurl'] . '/' . $folder_name
        ];
    }
    
    public function job_to_settings( $job ) {
        $quality_map = [ 'low' => 1, 'high' => 2, 'ultra' => 3 ];
        
        return [
            'saveFolder' => $job->save_folder ?: 'csig-images',
            'imageQuality' => $job->image_quality,
            'pixelRatio' => $quality_map[ $job->image_quality ] ?? 2,
            'retinaSupport' => $job->retina_support == 1,
            'selector' => $job->selector,
            'outputFormat' => 'raster',
            'iframeWidth' => $job->iframe_width,
            'iframeHeight' => $job->iframe_height
        ];
    }
}
