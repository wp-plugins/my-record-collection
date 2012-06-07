<?php
	/*
	Plugin Name: My Record Collection
	Plugin URI: http://myrecordcollection.arvid.nu/
	Description: Plugin for displaying your recordcollection on Discogs.com in your blog
	Author: Arvid Sollenby
	Version: 1.0.2
	Author URI: http://www.arvid.nu
	*/


// INCLUDE THE ADMIN PAGE	
function mrc_admin() {  
    include('mrc_import_admin.php');  
} 
// ADD SCRIPTS TO ADMIN PAGE
function mrc_js(){
	wp_deregister_script( 'jquery' );
	wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js');
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script('mrcScript', WP_PLUGIN_URL . '/my-record-collection/js/mrc_scripts.js');
	wp_localize_script( 'mrcScript', 'mrc_loc', mrc_localize_vars());
	//wp_enqueue_script('colorbox-JS', WP_PLUGIN_URL . '/my-record-collection/js/jquery.colorbox-min.js');
}

// ADD STYLES TO ADMIN PAGE
function mrc_css(){
	//wp_enqueue_style('mrcStyle', WP_PLUGIN_URL . '/my-record-collection/css/colorbox.css');
	wp_enqueue_style('colorbox-CSS', WP_PLUGIN_URL . '/my-record-collection/css/mrc_style.css');
}

// DO ADMIN ACTIONS
function mrc_admin_actions(){
	//$page = add_options_page("My Record Collection", "My Record Collection", 1, "my-record-collection.php", "mrc_admin");
	
	$page = add_menu_page( "My Record Collection Settings", "My Record Collection", "manage_options", "my-record-collection", "mrc_admin" );
	add_action('admin_print_scripts-' . $page, 'mrc_js', 9);	
	add_action('admin_print_styles-' . $page, 'mrc_css', 9);
}

// INSTALL FUNCTIONS
function mrc_db_install () {
   global $wpdb;

   $table_name = $wpdb->prefix . "mrc_records";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
	// CREATE THE TABLE
	$sql = "CREATE TABLE IF NOT EXISTS `" . $table_name . "` (
			  `id` mediumint(8) NOT NULL,
			  `artist` varchar(50) CHARACTER SET utf8 NOT NULL,
			  `title` varchar(150) CHARACTER SET utf8 NOT NULL,
			  `label` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `catno` varchar(30) CHARACTER SET utf8 NOT NULL,
			  `f_name` varchar(20) CHARACTER SET utf8 NOT NULL,
			  `f_qty` int(2) NOT NULL,
			  `f_desc` varchar(100) CHARACTER SET utf8 NOT NULL,
			  `r_date` varchar(20) CHARACTER SET utf8 NOT NULL,
			  `thumb` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
   }
   chmod(get_option('mrc_upload_dir'), 0776);
   $up_dir = wp_upload_dir();
   update_option("mrc_upload_dir", $up_dir['basedir'] . "/my-record-collection/");
}	

// DISPLAY FUNCTION
function mrc_display($text) {
	global $wpdb, $table_prefix;
	
	$settings = get_option('mrc_settings');
	$mode = $settings['display'];
	$darkOrLight = (isset($settings['colormode']) ? ' class="'.$settings['colormode'].'"' : ' class="dark"');
	//Only perform plugin functionality if post/page text has <!--MyRecordCollection-->
	if (preg_match("|<!--MyRecordCollection-->|", $text)) {
		$wpdb->query("SET NAMES 'utf8'");
		switch($settings['sort']){
			case 'alphaartist':
				$order = "artist ".$settings['sortway'].", r_date";
				break;
			case 'alphatitle':
				$order = "title ".$settings['sortway'].", artist, r_date";
				break;
			case 'year':
				$order = "r_date ".$settings['sortway'].", artist";
				break;
			case 'format':
				$order = "f_name ".$settings['sortway'].", artist, r_date";
				break;
			default:
				$order = "artist ".$settings['sortway'].", r_date";
				break;
		};
	
		$record_rows = $wpdb->get_results("SELECT * FROM  `".$wpdb->prefix."mrc_records` ORDER BY $order");
		if($mode == "list"){
			$posts = '<div id="MyRecordCollection"'.$darkOrLight.'><ul class="simple">';
		}else if($mode == "covers"){
			$posts = '<div id="MyRecordCollection"'.$darkOrLight.'><p>'.__('Click on the cover to see more information about the record', 'my-record-collection').'.</p><ul class="simplemusic">';
		}else{
			$posts = '<div id="MyRecordCollection"'.$darkOrLight.'><p>'.__('Click on the cover to see more information about the record', 'my-record-collection').'.</p><ul class="music">';
		}
		
		foreach ($record_rows as $rec) {

			if($settings['removenum']){
				$artist = trim(preg_replace("/\([\d]{1,2}\)/", "", $rec->artist));
			}else{
				$artist = trim($rec->artist);
			}

			if($settings['removethe']){
				$artist = trim(preg_replace("/,\sThe/", "", $artist));
			}else{
				$artist = trim($artist);
			}

			
			$title = $rec->title;
			if($rec->f_qty > 1){
				$qt = $rec->f_qty."x";
			}else{
				$qt = "";
			}
			if($rec->f_name == "CD"){
				$fc = "jewel";
				$f = $qt."CD";
				if(!empty($rec->f_desc)){
					$f .= " (".$rec->f_desc.")";
				}
			}else if($rec->f_name == "Vinyl"){
				$fc = "vinyl";
				$f = $qt.$rec->f_desc;
			}else{
				$f = "&Ouml;vrigt";
			}
			
			if($rec->thumb == ""){
				$imgurl = '';
			}else{
				$up_dir = wp_upload_dir();
				$imgurl = '<img src="' .str_replace("http://api.discogs.com/image/","http://s.dsimg.com/image/",$rec->thumb).'">';
			}

			if($mode == "list"){
				$imgurl = '';
			}
			$posts .= "<li data-record=\"".$rec->id."\" class=\"$fc\"><a target=\"_blank\" href=\"http://www.discogs.com/release/$rec->id\"><span class=\"mrc_artist\">$artist</span> <span class=\"mrc_dash\">-</span> <span class=\"mrc_title\">$title</span><span class=\"mrc_comma\">,</span> <span class=\"mrc_format\">$f</span><span class=\"mrc_comma\">,</span> <span class=\"mrc_label\">$rec->label</span></a>$imgurl</li>";	

		}
		$posts .= "</ul><p class=\"mrc_d_foot\">".__("This record collection is based on data from discogs.com generated by the <b>My Record Collection</b> WordPress-plugin. Discogs is a trademark of Zink Media, Inc. My Record Collection-plugin is not affiliated with or endorsed by Zink Media, Inc. All images are copyright of their respective owners. Further entry data from the Discogs database is licensed in the public domain", 'my-record-collection').".</p></div>";
		$text = preg_replace("|.*<!--MyRecordCollection-->.*|", $posts, $text);
	}
	return $text;
}

function conditionally_add_scripts_and_styles($posts){
	if (empty($posts)) return $posts;
 
	$shortcode_found = false; // use this flag to see if styles and scripts need to be enqueued
	foreach ($posts as $post) {
		if (preg_match("|<!--MyRecordCollection-->|", $post->post_content)) {
		//if (stripos($post->post_content, '<!--MyRecordCollection-->')) {
			$shortcode_found = true; // bingo!
			break;
		}
	}
 
	if ($shortcode_found) {
		// enqueue here
		wp_enqueue_script('jquery');
		wp_enqueue_script('mrcScript', WP_PLUGIN_URL . '/my-record-collection/js/mrc_scripts.js');
		wp_enqueue_script('fancybox', WP_PLUGIN_URL . '/my-record-collection/js/plugins/fancybox/jquery.fancybox.pack.js');
		wp_localize_script( 'mrcScript', 'mrc_loc', mrc_localize_vars());
		wp_enqueue_style('mrcStyle', WP_PLUGIN_URL . '/my-record-collection/css/mrc_style.css');
		wp_enqueue_style('fancybox', WP_PLUGIN_URL . '/my-record-collection/js/plugins/fancybox/jquery.fancybox.css');
	}
 
	return $posts;
}

function mrc_localize_vars() {
	 $settings = get_option('mrc_settings');
    return array(
        'SiteUrl' => get_bloginfo('url'),
		'lightOrDark' => (isset($settings['colormode']) ? $settings['colormode'] : 'dark'),
		'saveMsg' => __('Your changes are saved!', 'my-record.collection')
    );
} //End localize_vars

function mrc_init() {
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'my-record-collection', 'wp-content/plugins/' . $plugin_dir.'/i18n/', $plugin_dir.'/i18n/' );
}

add_action('admin_menu', 'mrc_admin_actions'); 	
register_activation_hook(__FILE__,'mrc_db_install');
add_filter('the_posts', 'conditionally_add_scripts_and_styles'); // the_posts gets triggered before wp_head
add_filter('the_content', 'mrc_display', 2);
add_action('init', 'mrc_init');

	
?>