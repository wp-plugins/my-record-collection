<?php
	/*
	Plugin Name: My Record Collection
	Plugin URI: http://myrecordcollection.arvid.nu/
	Description: Plugin for displaying your recordcollection on Discogs.com in your blog
	Author: Arvid Sollenby
	Version: 2.0
	Author URI: http://www.arvid.nu
	*/

require_once('mrc.class.php');

$mrc = new MyRecordCollection();


// ADD ACTIONS
add_action('admin_menu', array( $mrc, 'mrc_admin_actions') ); 	
register_activation_hook(__FILE__, array( $mrc, 'createDatabase') );
add_action('init', array( $mrc, 'mrc_init' ) );
add_filter('the_content', array( $mrc, 'display_collection' ), 2);

?>