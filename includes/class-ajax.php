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
    }
    
    public function save_image() {
        check_ajax_referer( 'csig_save_image', 'nonce' );

        $image_data = $_POST['image_data'] ?? '';
        $element_index = intval( $_POST['element_index'] ?? 0 );
        $job_id = intval( $_POST['job_id'] ?? 0 );
        
        if ( ! $image_data ) {
            wp_send_json_error( __( 'Missing image data', 'csig' ) );
        }

        $folder_name = $this->get_job_folder( $job_id );
        $save_dir = $this->settings->get_save_directory( $folder_name );
        $filename = 'csig-' . time() . '-' . ( $element_index + 1 ) . '.png';
        $file_path = $save_dir['path'] . '/' . $filename;

        $image_parts = explode( ',', $image_data );
        $image_base64 = base64_decode( end( $image_parts ) );
        
        if ( file_put_contents( $file_path, $image_base64 ) ) {
            wp_send_json_success( [ 'url' => $save_dir['url'] . '/' . $filename ] );
        } else {
            wp_send_json_error( __( 'Failed to save image file', 'csig' ) );
        }
    }
    
    public function save_pdf() {
        check_ajax_referer( 'csig_save_image', 'nonce' );

        $pdf_data = $_POST['pdf_data'] ?? '';
        $element_index = intval( $_POST['element_index'] ?? 0 );
        $job_id = intval( $_POST['job_id'] ?? 0 );
        
        if ( ! $pdf_data ) {
            wp_send_json_error( __( 'Missing PDF data', 'csig' ) );
        }

        $folder_name = $this->get_job_folder( $job_id );
        $save_dir = $this->settings->get_save_directory( $folder_name );
        $filename = 'csig-' . time() . '-' . ( $element_index + 1 ) . '.pdf';
        $file_path = $save_dir['path'] . '/' . $filename;

        $pdf_parts = explode( ',', $pdf_data );
        $pdf_base64 = base64_decode( end( $pdf_parts ) );
        
        if ( file_put_contents( $file_path, $pdf_base64 ) ) {
            wp_send_json_success( [ 'url' => $save_dir['url'] . '/' . $filename ] );
        } else {
            wp_send_json_error( __( 'Failed to save PDF file', 'csig' ) );
        }
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
    
    private function get_job_folder( $job_id ) {
        if ( ! $job_id ) {
            return null;
        }
        
        // Updated to use post meta instead of database table
        $save_folder = get_post_meta( $job_id, '_csig_save_folder', true );
        return $save_folder ?: 'csig-images';
    }
}