<div class="wrap">
    <h1><?php echo sprintf( __( 'Capture Images: %s', 'csig' ), esc_html( $job->name ) ); ?></h1>
    <p><a href="<?php echo admin_url( 'admin.php?page=client-side-image-generator' ); ?>">&larr; <?php _e( 'Back to Jobs', 'csig' ); ?></a></p>
    
    <!-- Job Details -->
    <div class="card" style="margin-bottom: 20px;">
        <h2><?php _e( 'Job Details', 'csig' ); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _e( 'URL:', 'csig' ); ?></th>
                <td><a href="<?php echo esc_url( $job->url ); ?>" target="_blank"><?php echo esc_html( $job->url ); ?></a></td>
            </tr>
            <tr>
                <th><?php _e( 'Selector:', 'csig' ); ?></th>
                <td><code><?php echo esc_html( $job->selector ); ?></code></td>
            </tr>
            <tr>
                <th><?php _e( 'Format:', 'csig' ); ?></th>
                <td><?php echo esc_html( ucfirst( $job->output_format ) ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Quality:', 'csig' ); ?></th>
                <td><?php echo esc_html( ucfirst( $job->image_quality ) ); ?> (<?php echo $job_settings['pixelRatio']; ?>x)</td>
            </tr>
            <tr>
                <th><?php _e( 'Save Folder:', 'csig' ); ?></th>
                <td><?php echo esc_html( $job_settings['saveFolder'] ); ?></td>
            </tr>
            <tr>
                <th><?php _e( 'Iframe Size:', 'csig' ); ?></th>
                <td><?php echo $job->iframe_width; ?>×<?php echo $job->iframe_height; ?>px</td>
            </tr>
        </table>
    </div>
    
    <!-- Performance warning -->
    <?php if ( $job_settings['pixelRatio'] >= 3 ): ?>
    <div class="notice notice-warning">
        <p><strong><?php _e( 'Performance Note:', 'csig' ); ?></strong> <?php _e( 'This job uses Ultra quality (3x) which may take significantly longer to process.', 'csig' ); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Hidden fields for JS -->
    <input type="hidden" id="csig-job-url" value="<?php echo esc_attr( $job->url ); ?>" />
    <input type="hidden" id="csig-job-format" value="<?php echo esc_attr( $job->output_format ); ?>" />
    
    <button class="button button-primary button-hero" id="csig-load"><?php echo sprintf( __( 'Run Job: %s', 'csig' ), esc_html( $job->name ) ); ?></button>
    
    <div id="csig-status" style="margin-top: 10px; display: none;">
        <div class="notice notice-info">
            <p id="csig-status-text"><?php _e( 'Processing...', 'csig' ); ?></p>
            <div style="width: 100%; background-color: #f0f0f0; border-radius: 3px; margin-top: 10px;">
                <div id="csig-progress-bar" style="width: 0%; height: 20px; background-color: #0073aa; border-radius: 3px; transition: width 0.3s;"></div>
            </div>
        </div>
    </div>
    <div id="csig-preview" style="margin-top:20px;"></div>
</div>