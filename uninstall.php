<?php
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') )
    exit();
	
	function rmdir_recursive($dir) {
		$files = scandir($dir);
		array_shift($files);    // remove '.' from array
		array_shift($files);    // remove '..' from array
	   
		foreach ($files as $file) {
			$file = $dir . '/' . $file;
			if (is_dir($file)) {
				rmdir_recursive($file);
				rmdir($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dir);
	}

	rmdir_recursive(get_option('mrc_upload_dir'));
	//DELETING THE WP OIPTIONS CREATED
	delete_option('mrc_display_mode');
	delete_option('mrc_upload_dir');
	delete_option('mrc_xml_file');

	//DELETING THE DATABASE TABLE
	global $wpdb;
    $table_name = $wpdb->prefix . "mrc_records";
    $wpdb->query('DROP TABLE '.$table_name); 
?>