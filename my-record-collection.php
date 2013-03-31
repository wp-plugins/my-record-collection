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
add_filter('the_posts', array( $mrc, 'conditionally_add_scripts_and_styles' ) ); 
add_filter('the_content', array( $mrc, 'display_collection' ), 2);

//wp_unschedule_event( wp_next_scheduled( 'bl_cron_hook' ), 'bl_cron_hook' );

/*add_filter( 'cron_schedules', 'bl_add_cron_intervals' );
 
function bl_add_cron_intervals( $schedules ) {
 
   $schedules['5seconds'] = array( // Provide the programmatic name to be used in code
      'interval' => 5, // Intervals are listed in seconds
      'display' => __('Every 5 Seconds') // Easy to read display name
   );
   return $schedules; // Do not forget to give back the list of schedules!
}
*/
add_action( 'bl_cron_hook', 'bl_cron_exec' );
 
if( !wp_next_scheduled( 'bl_cron_hook' ) ) {
   wp_schedule_event( time(), 'hourly', 'bl_cron_hook' );
}
 
function bl_cron_exec() {
	global $mrc;

	$db_num = $mrc->mrc_num_db_rows();

	if($db_num != 0 && isset($mrc->settings['discogs_info']['username'])){

		$url = "http://api.discogs.com/users/".$mrc->settings['discogs_info']['username'];
		$data = $mrc->get_file_from_url($url);
		$js = json_decode($data);

		$discogs_count = $js->num_collection;
		if($db_num < $discogs_count){
			$mrc->add2db($db_num);
		}

	}

}

?>