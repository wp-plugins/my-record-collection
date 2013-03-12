<?php
/**
 * My Record Collection PHP Class
 *
 * @package MyRecordCollection
 * @author Arvid Sollenby <arvid.sollenby@gmail.com>
 * @copyright Copyright (c) 2013
 * @version 1.1
 **/

Class MyRecordCollection {

	public $table_name;
	public $settings;
	public $fieldnames;
	public $plugin_dir;

	// Constructur sets up
	public function __construct() {
        global $wpdb;
		$this->table_name = $wpdb->prefix . "mrc_records";
		$this->settings = unserialize(get_option('mrc_settings'));
		$this->fieldnames = array(
			'did' => 'Discogs ID',
			'artist' => 'Artist',
			'title' => 'Release title',
			'label' => 'Label',
			'catno' => 'Catalog no',
			'format' => 'Format',
			'r_date' => 'Release date'
		);
		$this->plugin_dir = basename(dirname(__FILE__));
    }

	// Creates the initial table when plugin is ativated
	public function createDatabase () {
		global $wpdb;
		
		if($wpdb->get_var("show tables like '".$this->table_name."'") != $this->table_name) {
			// CREATE THE TABLE
			$sql = "CREATE TABLE IF NOT EXISTS `" . $this->table_name . "` (
					  `mrc_id` mediumint(8) NOT NULL AUTO_INCREMENT,
					  `did` mediumint(8) NOT NULL,
					  `artist` varchar(50) CHARACTER SET utf8 NOT NULL,
					  `title` varchar(150) CHARACTER SET utf8 NOT NULL,
					  `label` varchar(100) CHARACTER SET utf8 NOT NULL,
					  `catno` varchar(30) CHARACTER SET utf8 NOT NULL,
					  `f_name` varchar(20) CHARACTER SET utf8 NOT NULL,
					  `f_qty` int(2) NOT NULL,
					  `f_desc` varchar(100) CHARACTER SET utf8 NOT NULL,
					  `r_date` varchar(20) CHARACTER SET utf8 NOT NULL,
					  `thumb` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
					  PRIMARY KEY (`mrc_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;";

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		}

		add_option("mrc_settings", serialize(
				array(
					'type'		=>	'table',
					'sort'		=>	'artist',
					'order'		=>	'asc',
					'fields'	=>	array(
										'artist'=> 	true,
										'title'	=> 	true,
										'r_date'=> 	true,
										'catno'	=> 	true,
										'did'	=>	false,
										'label'	=> 	false,
										'label'	=> 	false,
										'format'=>	false,
									),
					'link'		=>	'external',
					'dupes'		=>	false,
					'removenum' =>	true,
					'removethe'	=>	false,
					'gridtype'	=> 'w_covers',
					'liststring'=> '[artist] - [title] ([format],[r_date])'
				)
			)
		);
	}

	// INCLUDE THE ADMIN PAGE	
	public function mrc_admin() {  
		$this->createDatabase();
	    include('mrc_admin_page.php');  
	} 

	// ADD SCRIPTS TO ADMIN PAGE
	public function mrc_js(){
		wp_enqueue_script('mrcScript', WP_PLUGIN_URL . '/my-record-collection/js/mrc_scripts.js', array('jquery','jquery-ui-core','jquery-ui-sortable','jquery-ui-tabs'));
		//wp_localize_script( 'mrcScript', 'mrc_loc', mrc_localize_vars());
	}

	// ADD STYLES TO ADMIN PAGE
	public function mrc_css(){
		//
		wp_enqueue_style('mrcStyles', WP_PLUGIN_URL . '/my-record-collection/css/mrc_style.css');
		wp_enqueue_style('jquery-ui','http://code.jquery.com/ui/1.10.1/themes/base/jquery-ui.css');
	}

	// ADMIN-MENU ADD ACTIONS
	public function mrc_admin_actions(){	
		$page = add_menu_page( "My Record Collection Settings", "My Record Collection", "manage_options", "my-record-collection", array( $this, 'mrc_admin') );
		add_action('admin_print_scripts-' . $page, array( $this, 'mrc_js' ), 9);	
		add_action('admin_print_styles-' . $page, array( $this, 'mrc_css' ), 9);
	}

	public function conditionally_add_scripts_and_styles($posts){
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
			// wp_enqueue_script('jquery');
			// wp_enqueue_script('mrcScript', WP_PLUGIN_URL . '/my-record-collection/js/mrc_scripts.js');
			// wp_enqueue_script('fancybox', WP_PLUGIN_URL . '/my-record-collection/js/plugins/fancybox/jquery.fancybox.pack.js');
			//wp_localize_script( 'mrcScript', 'mrc_loc', mrc_localize_vars());
			wp_enqueue_style('mrcStyle', WP_PLUGIN_URL . '/my-record-collection/css/mrc_style.css');
			//wp_enqueue_style('fancybox', WP_PLUGIN_URL . '/my-record-collection/js/plugins/fancybox/jquery.fancybox.css');
		}
	 
		return $posts;
	}

	// Inits the plugin sets up
	public function mrc_init() {
		$plugin_dir = basename(dirname(__FILE__));
		load_plugin_textdomain( 'my-record-collection', 'wp-content/plugins/' . $plugin_dir.'/i18n/', $plugin_dir.'/i18n/' );
	}

	public function get_file_from_url($src){
		$curl = function_exists('curl_version') ? true : false ;
		$fgc =  file_get_contents(__FILE__) ? true : false;
		
		if($curl){
			// Download file
			  $curl = curl_init($src);
			  curl_setopt($curl, CURLOPT_HEADER, 0);  // ignore any headers
			  ob_start();  // use output buffering so the contents don't get sent directly to the browser
			  curl_exec($curl);  // get the file
			  curl_close($curl);
			  $file = ob_get_contents();  // save the contents of the file into $file
			  ob_end_clean();  // turn output buffering back off
			return $file;		
		}else if($fgc){
			// returns file content
			return file_get_contents($src);	
		}
	}

	public function mrc_num_db_rows(){
		global $wpdb;
		return $wpdb->get_var("SELECT COUNT(*) FROM ".$this->table_name);
	}

	public function db_truncate(){
		global $wpdb;
		$wpdb->query('TRUNCATE TABLE '.$this->table_name); 
	}

	public function parse_boolean($obj) {
		return filter_var($obj, FILTER_VALIDATE_BOOLEAN);
	}

	//FUNCTIONS
	public function add2db(){ //ADDS THE XML TO THE DB
		global $wpdb;
		$wpdb->hide_errors();

		$uname = $this->settings['discogs_info']['username'];
		$this->db_truncate();
		$url = "http://api.discogs.com/users/".$uname."/collection/folders/0/releases?per_page=100&page=1";
		$json = json_decode($this->get_file_from_url($url));
		$page = $json->pagination->page;
		$pages = $json->pagination->pages;
		$count = 0;
		
		for($i=1; $i < $pages+1; $i++ ){
			if($i != 1){
				$url = "http://api.discogs.com/users/".$uname."/collection/folders/0/releases?per_page=100&page=".$i;
				$json = json_decode($this->get_file_from_url($url));
			}
			foreach($json->releases as $r){
				$data = array(
				   'mrc_id' => null,
				   'did' 	=> $r->id, 
				   'artist' => html_entity_decode($r->basic_information->artists[0]->name), 
				   'title'	=> html_entity_decode($r->basic_information->title), 
				   'label' 	=> html_entity_decode($r->basic_information->labels[0]->name), 
				   'catno' 	=> $r->basic_information->labels[0]->catno, 
				   'f_name' => $r->basic_information->formats[0]->name, 
				   'f_qty' 	=> $r->basic_information->formats[0]->qty, 
				   'f_desc' => (isset($r->basic_information->formats[0]->descriptions[0]) ? $r->basic_information->formats[0]->descriptions[0] : null), 
				   'r_date' => $r->basic_information->year,
				   'thumb'	=> $r->basic_information->thumb
				 );
				$wpdb->insert( $this->table_name, $data );
				$count++;
			}
		}
		echo $count;
	}

	public function format_data($type,$r){
		$s = $this->settings;
		switch($type){
			case 'artist':
				$artist = $s['removenum'] ? trim(preg_replace("/\([\d]{1,2}\)/", "", $r)) : trim($r);
				return $s['removethe'] ? trim(preg_replace("/,\sThe/", "", $artist)) : trim($artist);
			case 'title':
				return '<a href="http://www.discogs.com/release/'.$r->did.'" target="_blank">'.$r->title.'</a>';
			case 'format':
				if($r->f_qty > 1){
					$qt = $r->f_qty."x";
				}else{
					$qt = "";
				}
				if($r->f_name == "CD" || $r->f_name == "CDr" ){
					$fc = "jewel";
					$f = $qt.$r->f_name;
					if(!empty($r->f_desc)){
						$f .= " (".$r->f_desc.")";
					}
				}else if($r->f_name == "Vinyl"){
					$fc = "vinyl";
					$f = $qt.$r->f_desc;
				}else if($r->f_name == "Cassette"){
					$fc = "other";
					$f = $qt.$r->f_name;
				}else if($r->f_name == "Box Set"){
					$fc = "other";
					$f = $qt.$r->f_name;
				}else{
					$f = "Other";
					$fc = "other";
				}
				return array($f,$fc);
			case 'grid':
				$format = $this->format_data('format',$r);
				$artist = $this->format_data('artist',$r->artist);
				$imgurl = '<img src="' .str_replace("http://api.discogs.com/image/","http://s.pixogs.com/image/",$r->thumb).'">';
				return "<li data-record=\"".$r->did."\" class=\"".$format[1]."\"><a target=\"_blank\" href=\"http://www.discogs.com/release/$r->did\"><span class=\"mrc_artist\">$artist</span> <span class=\"mrc_dash\">-</span> <span class=\"mrc_title\">$r->title</span><span class=\"mrc_comma\">,</span> <span class=\"mrc_format\">".$format[0]."</span><span class=\"mrc_comma\">,</span> <span class=\"mrc_label\">$r->label</span></a>$imgurl</li>";
		}	
	}

	// Displays collection
	public function display_collection($text) {
		global $wpdb;
		$settings = $this->settings;

		$sql = "SELECT * FROM ".$this->table_name;

		if( ! $settings['dupes'] ){
			$sql .= " GROUP BY did";
		}

		switch($settings['sort']){
			case 'year':
				$sql .= ' ORDER BY r_date, artist, title';
				break;
			case 'artist':
				$sql .= ' ORDER BY artist, r_date';
				break;
			case 'title':
				$sql .= ' ORDER BY title, artist';
				break;
			case 'label':
				$sql .= ' ORDER BY label, artist';
				break;
			default:
				$sql .= ' ORDER BY artist, r_date';
				break;
		}

		$sql .= $settings['order'] == 'asc' ? ' ASC' : ' DESC';
		//$sql .= " LIMIT 4";
		$record_rows = $wpdb->get_results($sql);

		$return = '<div id="MyRecordCollection">';
		$enabled_fields = array();

		if($settings['type'] == 'table'){
			$return .= "<table><tr>";
			foreach($settings['fields'] as $k=>$v){
				if($v){
					$return .= '<th>'.$this->fieldnames[$k].'</th>';
					$enabled_fields[] = $k;
				}
			}
			$return .= "</tr>";

			foreach($record_rows as $r){
				$return .= '<tr>';
				foreach($enabled_fields as $f){
					if($f == 'artist'){
						$return .= '<td>'.$this->format_data('artist',$r->artist).'</td>';
					}else if($f == 'format'){
						$format = $this->format_data('format',$r);
						$return .= '<td>'.$format[0].'</td>';
					}else if($f == 'title'){
						$return .= '<td>'.$this->format_data('title',$r).'</td>';
					}else if($f == 'r_date'){
						$year = $r->{$f} == 0 ? '-' : $r->{$f};
						$return .= '<td>'.$year.'</td>';
					}else{
						$return .= '<td>'.$r->{$f}.'</td>';
					}
					
				}
				$return .= '</tr>';
			}		
			$return .= '</table>';
		}else if($settings['type'] == 'grid'){
			$return = '<ul class="'.($settings['gridtype'] == 'w_covers' ? 'music' : 'simplemusic').'">';

			foreach ($record_rows as $rec) {

				$return .= $this->format_data('grid',$rec);
			}
			$return .= '</ul>';
		}else if($settings['type'] == 'list'){

			$return .= '<ul class="list">';

			$search = array('[did]','[artist]','[title]','[label]','[catno]','[format]','[year]');

			foreach ($record_rows as $r) {
				$format = $this->format_data('format',$r);
				$replace = array($r->did,$this->format_data('artist',$r->artist),$this->format_data('title',$r),$r->label,$r->catno,$format[0],$r->r_date);
				$return .= str_replace($search, $replace, $settings['liststring'])."<br>";
			}

			$return .= '</ul>';
		}
		$return .= '</div>';


		return $return;
	}

}

?>