<?php


namespace CSIG;

if (!defined('ABSPATH')) {
    exit;
}

class Database {
    
    public static function init() {
        add_action( 'init', [ __CLASS__, 'create_tables' ] );
    }
    
    public static function create_tables() {
        global $wpdb;
        
        $table_name = self::get_jobs_table();
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url varchar(500) NOT NULL,
            selector varchar(255) DEFAULT '.csig-card',
            output_format varchar(20) DEFAULT 'raster',
            image_quality varchar(20) DEFAULT 'high',
            save_folder varchar(255) DEFAULT '',
            retina_support tinyint(1) DEFAULT 1,
            iframe_width int(11) DEFAULT 1250,
            iframe_height int(11) DEFAULT 650,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
    
    public static function get_jobs_table() {
        global $wpdb;
        return $wpdb->prefix . 'csig_jobs';
    }
    
    public static function get_job( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 
            "SELECT * FROM " . self::get_jobs_table() . " WHERE id = %d", 
            intval( $id ) 
        ) );
    }
    
    public static function get_all_jobs() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM " . self::get_jobs_table() . " ORDER BY updated_at DESC" );
    }
    
    public static function create_job( $data ) {
        global $wpdb;
        return $wpdb->insert( self::get_jobs_table(), $data );
    }
    
    public static function update_job( $id, $data ) {
        global $wpdb;
        return $wpdb->update( self::get_jobs_table(), $data, [ 'id' => intval( $id ) ] );
    }
    
    public static function delete_job( $id ) {
        global $wpdb;
        return $wpdb->delete( self::get_jobs_table(), [ 'id' => intval( $id ) ] );
    }
    
    public static function update_job_timestamp( $id ) {
        return self::update_job( $id, [ 'updated_at' => current_time( 'mysql' ) ] );
    }
}