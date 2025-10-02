<?php

namespace CSIG;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ajax {
    
    private $settings;
    
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
        add_action( 'wp_ajax_csig_save_image', [ $this, 'save_image' ] );
        add_action( 'wp_ajax_csig_save_pdf', [ $this, 'save_pdf' ] );
        add_action( 'wp_ajax_csig_update_job_stats', [ $this, 'update_job_stats' ] );
        add_action( 'wp_ajax_csig_delete_generated_file', [ $this, 'delete_generated_file' ] );
        add_action( 'wp_ajax_csig_delete_all_generated_files', [ $this, 'delete_all_generated_files' ] );
    }



        
    public function save_image() {
        check_ajax_referer('csig_save_image', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'csig'));
        }
        
        $image_data = $_POST['image_data'] ?? '';
        $element_index = intval($_POST['element_index'] ?? 0);
        $job_id = intval($_POST['job_id'] ?? 0);
        $custom_filename = sanitize_file_name($_POST['custom_filename'] ?? '');
        $overwrite_files = ($_POST['overwrite_files'] ?? '0') === '1';
        
        if (empty($image_data)) {
            wp_send_json_error(__('No image data received', 'csig'));
        }
        
        // Get job settings
        $job_settings = Job_Post_Type::get_job_settings($job_id);
        $filesystem = $this->get_filesystem();
        
        // Parse the data URL
        if (strpos($image_data, 'data:image/png;base64,') !== 0) {
            wp_send_json_error(__('Invalid image data format', 'csig'));
        }
        
        $base64_data = substr($image_data, strlen('data:image/png;base64,'));
        $binary_data = base64_decode($base64_data);
        
        if ($binary_data === false) {
            wp_send_json_error(__('Failed to decode image data', 'csig'));
        }
        
        // Generate filename
        if (!empty($custom_filename)) {
            // Use custom filename
            $filename = $custom_filename . '.png';
        } else {
            // Use default naming
            $job_title = sanitize_file_name(get_the_title($job_id));
            $timestamp = current_time('Y-m-d_H-i-s');
            $filename = $job_title . '_' . $timestamp . '_' . ($element_index + 1) . '.png';
        }
        
        // Create the directory if it doesn't exist
        $save_folder = $job_settings['saveFolder'];
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/' . $save_folder;
        
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        
        $target_path = $target_dir . '/' . $filename;
        $target_url = $upload_dir['baseurl'] . '/' . $save_folder . '/' . $filename;
        
        // Handle filename conflicts based on overwrite setting
        if (!$overwrite_files) {
            $counter = 1;
            $original_filename = $filename;
            while ($this->path_exists($filesystem, $target_path)) {
                $path_info = pathinfo($original_filename);
                $filename = $path_info['filename'] . '_' . $counter . '.' . $path_info['extension'];
                $target_path = $target_dir . '/' . $filename;
                $target_url = $upload_dir['baseurl'] . '/' . $save_folder . '/' . $filename;
                $counter++;
            }
        }
        $was_overwrite = $overwrite_files && $this->path_exists($filesystem, $target_path);
        
        // Save the file
        if (!$this->put_contents($filesystem, $target_path, $binary_data)) {
            wp_send_json_error(__('Failed to save image file', 'csig'));
        }

        Job_Post_Type::add_generated_file($job_id, array(
            'url' => $target_url,
            'path' => $target_path,
            'filename' => $filename,
            'format' => 'png',
            'size' => strlen($binary_data),
        ));
        
        $message = $was_overwrite
            ? __('Image saved successfully (overwritten)', 'csig')
            : __('Image saved successfully', 'csig');
        
        wp_send_json_success(array(
            'message' => $message,
            'filename' => $filename,
            'url' => $target_url,
            'overwritten' => $was_overwrite
        ));
    }

    public function save_pdf() {
        check_ajax_referer('csig_save_image', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'csig'));
        }
        
        $pdf_data = $_POST['pdf_data'] ?? '';
        $element_index = intval($_POST['element_index'] ?? 0);
        $job_id = intval($_POST['job_id'] ?? 0);
        $custom_filename = sanitize_file_name($_POST['custom_filename'] ?? '');
        $overwrite_files = ($_POST['overwrite_files'] ?? '0') === '1';
        
        if (empty($pdf_data)) {
            wp_send_json_error(__('No PDF data received', 'csig'));
        }
        
        // Get job settings
        $job_settings = Job_Post_Type::get_job_settings($job_id);
        $filesystem = $this->get_filesystem();
        
        // Parse the data URL
        if (strpos($pdf_data, 'data:application/pdf;base64,') !== 0) {
            wp_send_json_error(__('Invalid PDF data format', 'csig'));
        }
        
        $base64_data = substr($pdf_data, strlen('data:application/pdf;base64,'));
        $binary_data = base64_decode($base64_data);
        
        if ($binary_data === false) {
            wp_send_json_error(__('Failed to decode PDF data', 'csig'));
        }
        
        // Generate filename
        if (!empty($custom_filename)) {
            // Use custom filename
            $filename = $custom_filename . '.pdf';
        } else {
            // Use default naming
            $job_title = sanitize_file_name(get_the_title($job_id));
            $timestamp = current_time('Y-m-d_H-i-s');
            $filename = $job_title . '_' . $timestamp . '_' . ($element_index + 1) . '.pdf';
        }
        
        // Create the directory if it doesn't exist
        $save_folder = $job_settings['saveFolder'];
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/' . $save_folder;
        
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        
        $target_path = $target_dir . '/' . $filename;
        $target_url = $upload_dir['baseurl'] . '/' . $save_folder . '/' . $filename;
        
        // Handle filename conflicts based on overwrite setting
        if (!$overwrite_files) {
            $counter = 1;
            $original_filename = $filename;
            while ($this->path_exists($filesystem, $target_path)) {
                $path_info = pathinfo($original_filename);
                $filename = $path_info['filename'] . '_' . $counter . '.' . $path_info['extension'];
                $target_path = $target_dir . '/' . $filename;
                $target_url = $upload_dir['baseurl'] . '/' . $save_folder . '/' . $filename;
                $counter++;
            }
        }
        $was_overwrite = $overwrite_files && $this->path_exists($filesystem, $target_path);
        
        // Save the file
        if (!$this->put_contents($filesystem, $target_path, $binary_data)) {
            wp_send_json_error(__('Failed to save PDF file', 'csig'));
        }

        Job_Post_Type::add_generated_file($job_id, array(
            'url' => $target_url,
            'path' => $target_path,
            'filename' => $filename,
            'format' => 'pdf',
            'size' => strlen($binary_data),
        ));
        
        $message = $was_overwrite
            ? __('PDF saved successfully (overwritten)', 'csig')
            : __('PDF saved successfully', 'csig');
        
        wp_send_json_success(array(
            'message' => $message,
            'filename' => $filename,
            'url' => $target_url,
            'overwritten' => $was_overwrite
        ));
    }
    
    public function update_job_stats() {
        check_ajax_referer( 'csig_save_image', 'nonce' );
        
        $job_id = intval( $_POST['job_id'] ?? 0 );
        $generated_files = json_decode( $_POST['generated_files'] ?? '[]', true );
        
        if ( ! $job_id ) {
            wp_send_json_error( __( 'Invalid job ID', 'csig' ) );
        }
        
        Job_Post_Type::update_job_stats( $job_id, $generated_files );
        
        wp_send_json_success( __( 'Job stats updated', 'csig' ) );
    }

    public function delete_generated_file() {
        check_ajax_referer( 'csig_manage_files', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'csig' ) );
        }

        $job_id   = intval( $_POST['job_id'] ?? 0 );
        $file_url = esc_url_raw( $_POST['file_url'] ?? '' );

        if ( ! $job_id || empty( $file_url ) ) {
            wp_send_json_error( __( 'Invalid request', 'csig' ) );
        }

        $file_record = Job_Post_Type::get_generated_file( $job_id, $file_url );

        if ( empty( $file_record ) ) {
            wp_send_json_error( __( 'File not found in job records.', 'csig' ) );
        }

        $upload_dir = wp_upload_dir();
        $base_dir   = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) );
        $base_url   = $upload_dir['baseurl'];

        $filesystem = $this->get_filesystem();
        $target_path = $this->resolve_file_path( $file_record, $file_url, $base_dir, $base_url );

        if ( ! $target_path ) {
            wp_send_json_error( __( 'Unable to determine file path for deletion.', 'csig' ) );
        }

        $deleted = $this->delete_path( $filesystem, $target_path );

        if ( ! $deleted && $this->path_exists( $filesystem, $target_path ) ) {
            wp_send_json_error( __( 'Failed to remove the file from storage.', 'csig' ) );
        }

        Job_Post_Type::remove_generated_file( $job_id, $file_url );

        wp_send_json_success( array(
            'message' => __( 'File deleted successfully.', 'csig' ),
            'fileUrl' => $file_url,
        ) );
    }

    public function delete_all_generated_files() {
        check_ajax_referer( 'csig_manage_files', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'csig' ) );
        }

        $job_id = intval( $_POST['job_id'] ?? 0 );

        if ( ! $job_id ) {
            wp_send_json_error( __( 'Invalid request', 'csig' ) );
        }

        $files = Job_Post_Type::get_generated_files( $job_id );

        if ( empty( $files ) ) {
            wp_send_json_success( array(
                'message'      => __( 'No files to delete.', 'csig' ),
                'deletedCount' => 0,
            ) );
        }

        $upload_dir = wp_upload_dir();
        $base_dir   = wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) );
        $base_url   = $upload_dir['baseurl'];
        $filesystem = $this->get_filesystem();

        $deleted_count = 0;
        $failed        = array();

        foreach ( $files as $url => $file_record ) {
            $target_path = $this->resolve_file_path( $file_record, $url, $base_dir, $base_url );

            if ( ! $target_path ) {
                $failed[] = $url;
                continue;
            }

            $deleted = $this->delete_path( $filesystem, $target_path );

            if ( ! $deleted && $this->path_exists( $filesystem, $target_path ) ) {
                $failed[] = $url;
                continue;
            }

            Job_Post_Type::remove_generated_file( $job_id, $url );
            $deleted_count++;
        }

        if ( ! empty( $failed ) ) {
            wp_send_json_error( array(
                'message'      => __( 'Some files could not be deleted.', 'csig' ),
                'deletedCount' => $deleted_count,
                'failed'       => $failed,
            ) );
        }

        wp_send_json_success( array(
            'message'      => __( 'All generated files deleted.', 'csig' ),
            'deletedCount' => $deleted_count,
        ) );
    }
    
    private function get_job_folder( $job_id ) {
        if ( ! $job_id ) {
            return null;
        }

        // Updated to use post meta instead of database table
        $save_folder = get_post_meta( $job_id, '_csig_save_folder', true );
        return $save_folder ?: 'csig-images';
    }

    private function get_filesystem() {
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        global $wp_filesystem;

        if (!is_object($wp_filesystem)) {
            WP_Filesystem();
        }

        return is_object($wp_filesystem) ? $wp_filesystem : null;
    }

    private function path_exists($filesystem, $path) {
        if ($filesystem && method_exists($filesystem, 'exists')) {
            return $filesystem->exists($path);
        }

        return file_exists($path);
    }

    private function put_contents($filesystem, $path, $data) {
        $result = false;

        if ($filesystem && method_exists($filesystem, 'put_contents')) {
            $chmod = defined('FS_CHMOD_FILE') ? FS_CHMOD_FILE : 0644;
            $result = $filesystem->put_contents($path, $data, $chmod);
        }

        if (!$result) {
            $result = file_put_contents($path, $data) !== false;
        }

        return $result;
    }

    private function delete_path($filesystem, $path) {
        $deleted = false;

        if ($filesystem && method_exists($filesystem, 'delete')) {
            $deleted = $filesystem->delete($path, false, 'f');
        }

        if (!$deleted && $this->path_exists($filesystem, $path)) {
            $deleted = @unlink($path);
        }

        // If file already missing, treat as success.
        if (!$deleted && ! $this->path_exists($filesystem, $path)) {
            $deleted = true;
        }

        return (bool) $deleted;
    }

    private function resolve_file_path( $file_record, $file_url, $base_dir, $base_url ) {
        $target_path = isset( $file_record['path'] ) ? wp_normalize_path( $file_record['path'] ) : '';

        if ( empty( $target_path ) && strpos( $file_url, $base_url ) === 0 ) {
            $relative    = ltrim( str_replace( $base_url, '', $file_url ), '/' );
            $target_path = $base_dir . $relative;
        }

        if ( empty( $target_path ) || strpos( $target_path, $base_dir ) !== 0 ) {
            return false;
        }

        return $target_path;
    }
}
