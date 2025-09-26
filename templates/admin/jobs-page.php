
<div class="wrap">
    <h1><?php _e( 'Image Generator Jobs', 'csig' ); ?></h1>
    <p><?php _e( 'Create and manage capture jobs. Each job saves specific settings that can be re-used.', 'csig' ); ?></p>
    
    <!-- Create New Job Form -->
    <div class="card" style="margin-bottom: 20px;">
        <h2><?php _e( 'Create New Job', 'csig' ); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field( 'csig_create_job', 'csig_nonce' ); ?>
            <input type="hidden" name="action" value="create_job" />
            
            <table class="form-table">
                <tr>
                    <th><label for="job_name"><?php _e( 'Job Name', 'csig' ); ?></label></th>
                    <td><input type="text" id="job_name" name="job_name" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th><label for="job_url"><?php _e( 'URL to Capture', 'csig' ); ?></label></th>
                    <td><input type="url" id="job_url" name="job_url" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th><label for="job_selector"><?php _e( 'CSS Selector', 'csig' ); ?></label></th>
                    <td><input type="text" id="job_selector" name="job_selector" value="<?php echo esc_attr( $defaults['selector'] ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="job_format"><?php _e( 'Output Format', 'csig' ); ?></label></th>
                    <td>
                        <select id="job_format" name="job_format">
                            <option value="raster"><?php _e( 'Raster (PNG)', 'csig' ); ?></option>
                            <option value="vector"><?php _e( 'Vector (PDF)', 'csig' ); ?></option>
                            <option value="both"><?php _e( 'Both', 'csig' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="job_quality"><?php _e( 'Image Quality', 'csig' ); ?></label></th>
                    <td>
                        <select id="job_quality" name="job_quality">
                            <option value="low" <?php selected( $defaults['imageQuality'], 'low' ); ?>><?php _e( 'Low (1x)', 'csig' ); ?></option>
                            <option value="high" <?php selected( $defaults['imageQuality'], 'high' ); ?>><?php _e( 'High (2x)', 'csig' ); ?></option>
                            <option value="ultra" <?php selected( $defaults['imageQuality'], 'ultra' ); ?>><?php _e( 'Ultra (3x)', 'csig' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="job_folder"><?php _e( 'Save Folder', 'csig' ); ?></label></th>
                    <td><input type="text" id="job_folder" name="job_folder" value="<?php echo esc_attr( $defaults['saveFolder'] ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="job_retina"><?php _e( 'Retina Support', 'csig' ); ?></label></th>
                    <td><label><input type="checkbox" id="job_retina" name="job_retina" value="1" <?php checked( $defaults['retinaSupport'] ); ?> /> <?php _e( 'Enable Retina/High-DPI support', 'csig' ); ?></label></td>
                </tr>
                <tr>
                    <th><label><?php _e( 'Iframe Size', 'csig' ); ?></label></th>
                    <td>
                        <input type="number" name="job_width" value="1250" style="width: 80px;" /> × 
                        <input type="number" name="job_height" value="650" style="width: 80px;" /> px
                    </td>
                </tr>
            </table>
            
            <?php submit_button( __( 'Create Job', 'csig' ) ); ?>
        </form>
    </div>
    
    <!-- Jobs List -->
    <h2><?php printf( __( 'Existing Jobs (%d)', 'csig' ), count( $jobs ) ); ?></h2>
    <?php if ( empty( $jobs ) ): ?>
        <p><?php _e( 'No jobs created yet. Create your first job above.', 'csig' ); ?></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e( 'Job Name', 'csig' ); ?></th>
                    <th><?php _e( 'URL', 'csig' ); ?></th>
                    <th><?php _e( 'Selector', 'csig' ); ?></th>
                    <th><?php _e( 'Format', 'csig' ); ?></th>
                    <th><?php _e( 'Quality', 'csig' ); ?></th>
                    <th><?php _e( 'Last Updated', 'csig' ); ?></th>
                    <th><?php _e( 'Actions', 'csig' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $jobs as $job ): ?>
                <tr>
                    <td><strong><?php echo esc_html( $job->name ); ?></strong></td>
                    <td><a href="<?php echo esc_url( $job->url ); ?>" target="_blank"><?php echo esc_html( wp_parse_url( $job->url, PHP_URL_HOST ) ); ?></a></td>
                    <td><code><?php echo esc_html( $job->selector ); ?></code></td>
                    <td><?php echo esc_html( ucfirst( $job->output_format ) ); ?></td>
                    <td><?php echo esc_html( ucfirst( $job->image_quality ) ); ?></td>
                    <td><?php echo esc_html( date( 'M j, Y g:i A', strtotime( $job->updated_at ) ) ); ?></td>
                    <td>
                        <a href="<?php echo admin_url( 'admin.php?page=csig-capture&job_id=' . $job->id ); ?>" class="button button-primary"><?php _e( 'Run Job', 'csig' ); ?></a>
                        <form method="post" action="" style="display: inline;">
                            <?php wp_nonce_field( 'csig_delete_job', 'csig_nonce' ); ?>
                            <input type="hidden" name="action" value="delete_job" />
                            <input type="hidden" name="job_id" value="<?php echo $job->id; ?>" />
                            <input type="submit" value="<?php _e( 'Delete', 'csig' ); ?>" class="button button-link-delete" onclick="return confirm('<?php _e( 'Are you sure you want to delete this job?', 'csig' ); ?>')" />
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>