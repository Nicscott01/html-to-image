<div class="wrap">
    <h1><?php _e( 'Global Settings', 'csig' ); ?></h1>
<?php
/* 
    <form method="post" action="options.php">
        <?php
        settings_fields( 'csig_global_settings' );
        do_settings_sections( 'csig_global_settings' );
        submit_button();
        ?>
    </form>
  */
?>  
    <div class="card" style="margin-top: 20px;">
        <h2><?php _e( 'Developer Options', 'csig' ); ?></h2>
        <p><?php _e( 'You can override these settings programmatically:', 'csig' ); ?></p>
        
        <h3><?php _e( 'wp-config.php Constants', 'csig' ); ?></h3>
        <pre><code><?php echo esc_html( "// Set default save folder
define('CSIG_DEFAULT_SAVE_FOLDER', 'my-custom-images');

// Set default image quality (low, high, ultra)
define('CSIG_DEFAULT_IMAGE_QUALITY', 'ultra');

// Set default selector
define('CSIG_DEFAULT_SELECTOR', '.csig-card');" ); ?></code></pre>
        
        <h3><?php _e( 'Filters', 'csig' ); ?></h3>
        <pre><code><?php echo esc_html( "// Dynamically set save folder
add_filter('csig_default_save_folder', function(\$folder) {
    return 'custom-folder-' . date('Y-m');
});

// Dynamically set image quality
add_filter('csig_default_image_quality', function(\$quality) {
    return 'ultra';
});

// Dynamically set selector
add_filter('csig_default_selector', function(\$selector) {
    return '.csig-card';
});" ); ?></code></pre>
    </div>
</div>