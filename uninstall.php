<?php
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();
	
	//DELETING THE WP OIPTIONS CREATED
	delete_option('mrc_username');
	delete_option('mrc_settings');

	//DELETING THE DATABASE TABLE
	global $wpdb;
    $table_name = $wpdb->prefix . "mrc_records";
    $wpdb->query('DROP TABLE '.$table_name); 
?>