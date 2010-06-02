<?php
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();
	
	function remove_dir($current_dir) {
    
        if($dir = @opendir($current_dir)) {
            while (($f = readdir($dir)) !== false) {
                if($f > '0' and filetype($current_dir.$f) == "file") {
                    unlink($current_dir.$f);
                } elseif($f > '0' and filetype($current_dir.$f) == "dir") {
                    remove_dir($current_dir.$f."\\");
                }
            }
            closedir($dir);
            rmdir($current_dir);
        }
    }

	remove_dir(get_option('mrc_upload_dir'));
	//DELETING THE WP OIPTIONS CREATED
	delete_option('mrc_display_mode');
	delete_option('mrc_upload_dir');
	delete_option('mrc_xml_file');

	//DELETING THE DATABASE TABLE
	global $wpdb;
    $table_name = $wpdb->prefix . "mrc_records";
    $wpdb->query('DROP TABLE '.$table_name); 
?>