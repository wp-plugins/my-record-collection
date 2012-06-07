<?php
error_reporting(E_ALL);
ini_set('display_errors','On');


//include wp-config or wp-load.php

$root = dirname(dirname(dirname(dirname(__FILE__))));
if (file_exists($root.'/wp-load.php')) {
	// WP 2.6
	require_once($root.'/wp-load.php');
} else {
	// Before 2.6
	require_once($root.'/wp-config.php');
}


//FUNCTIONS
function mrc_add2db(){ //ADDS THE XML TO THE DB
	global $wpdb;
	$wpdb->hide_errors();

	$uname = get_option('mrc_username');
	$table_name = $wpdb->prefix . "mrc_records";
	mrc_db_truncate();
	$url = "http://api.discogs.com/users/".$uname."/collection/folders/0/releases?per_page=100&page=1";
	$json = json_decode(get_file_from_url($url));
	$page = $json->pagination->page;
	$pages = $json->pagination->pages;
	$count = 0;
	
	for($i=1; $i < $pages+1; $i++ ){
		if($i != 1){
			$url = "http://api.discogs.com/users/".$uname."/collection/folders/0/releases?per_page=100&page=".$i;
			$json = json_decode(get_file_from_url($url));
		}
		foreach($json->releases as $r){
			$data = array(
			   'id' 		=> $r->id, 
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
			$wpdb->insert( $table_name, $data );
			$count++;
		}
	}
	echo $count;
}

function mrc_db_truncate(){
	global $wpdb;
	$table_name = $wpdb->prefix . "mrc_records";
	$wpdb->query('TRUNCATE TABLE '.$table_name); 
}

function mrc_num_db_rows(){
	global $wpdb;
	$table_name = $wpdb->prefix . "mrc_records";
	return $wpdb->get_var("SELECT COUNT(*) FROM ".$table_name);
}

function mrc_num_db_imgs(){
	global $wpdb;
	$table_name = $wpdb->prefix . "mrc_records";
	return $wpdb->get_var("SELECT COUNT(*) FROM ".$table_name." WHERE NOT i150 = ''");
}

function import_images_lis(){
	global $wpdb;
	$table_name = $wpdb->prefix . "mrc_records";
	$dir = MRC_URL_BASE_DIR."/my-record-collection/img/";
	$addimg = $wpdb->get_results("SELECT id,i150 FROM ".$table_name." WHERE NOT i150 = ''");
	$dSum = '<ul id="mrc_dst">';
	$sSum = '<ul id="mrc_src">';
	foreach($addimg as $ai){
		$dst = $dir.$ai->id.".jpg";
		$src = $ai->i150;
		$dSum .= "<li>$dst</li>";
		$sSum .= "<li>$src</li>";
	}
	return $dSum."</ul>".$sSum."</ul>";
}

function countFiles($strDirName){
	if ($hndDir = opendir($strDirName))
	{
		$intCount = 0;
		while (false !== ($strFilename = readdir($hndDir))){
			if ($strFilename != "." && $strFilename != ".."){
				$intCount++;
			}
		}
		closedir($hndDir);
	}
	else{
		$intCount = -1;
	}
	return $intCount;
}

function mrc_destroy() {
	$dir = MRC_URL_BASE_DIR."/my-record-collection/img/";
    $mydir = opendir($dir);
    while(false !== ($file = readdir($mydir))) {
        if($file != "." && $file != "..") {
            chmod($dir.$file, 0777);
            if(is_dir($dir.$file)) {
                chdir('.');
                mrc_destroy($dir.$file.'/');
                rmdir($dir.$file) or DIE("couldn't delete $dir$file<br />");
            }
            else
                unlink($dir.$file) or DIE("couldn't delete $dir$file<br />");
        }
    }
    closedir($mydir);
}

function get_file_from_url($src){
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


function parse_boolean($obj) {
    return filter_var($obj, FILTER_VALIDATE_BOOLEAN);
}

function PP($tjo){
	highlight_string(print_r($tjo,1));
}

function showRecordInfo($rID) {
	$data = json_decode(get_file_from_url('http://api.discogs.com/releases/'.$rID));
	
	$artist		= $data->artists[0]->name;
	$title		= $data->title;
	$label		= $data->labels[0]->name;
	$catalog	= $data->labels[0]->catno;
	$qty		= $data->formats[0]->qty;
	$format		= $data->formats[0];
	$country	= (isset($data->country) ? $data->country : null);
	$released	= (isset($data->released) ? $data->released : null);
	$genres		= (isset($data->genres) ? $data->genres : null);
	$tracks		= $data->tracklist;
	$credits	= $data->extraartists;
	$notes	  	= (isset($data->notes) ? $data->notes : null);
	$thumb	  	= (isset($data->thumb) ? $data->thumb : null);
	
	$f = "";
	if($qty > 1){
		$f .= $qty." x ";
	}
	$f .= $format->name;
	
	$f .= ', '.implode(', ',(isset($format->descriptions) ? $format->descriptions : array(null)));
	
	if(!is_null($thumb)) { echo '<div id="MRC_INFO"><img src="'.$thumb.'" class="disp_img">'; } 
	echo "<h2>$artist - $title</h2>";
	echo '<div><span class="label">'.__('Label', 'my-record-collection').':</span>'.$label.'<br>';
	echo '<span class="label">'.__('Catalog#', 'my-record-collection').':</span>'.$catalog.'<br>';
	echo '<span class="label">'.__('Format', 'my-record-collection').':</span>'.$f.'<br>';
	echo '<span class="label">'.__('Country', 'my-record-collection').':</span>'.$country.'<br>';
	echo '<span class="label">'.__('Released', 'my-record-collection').':</span>'.$released.'<br>';
	echo '<span class="label">'.__('Genre', 'my-record-collection').':</span>'.implode(", ",$genres).'</div>';
	$tl = '<table class="tracks"><tr><th colspan="3"> '.__( 'Tracklisting', 'my-record-collection') .'</th></tr>';
	foreach($tracks as $t){
		$tl .= '<tr><td>'.$t->position.'</td><td>'.$t->title;
		if(isset($t->extraartists)){
			$lrole=NULL;
			foreach($t->extraartists as $ea){
				if(!strcmp($lrole, $ea->role)){
					$tl .= ', '.$ea->name;
				}else{
					$tl .= '<br>'.$ea->role.': '.$ea->name;
				}
				$lrole = $ea->role;
				
			}
		}
		
		$tl .= '</td><td>'.$t->duration.'</td></tr>';
	}
	$tl .= '</table>';
	echo $tl;
	if($credits){
		$cred = '<p class="credits"><b>'.__( 'Credits', 'my-record-collection') .'</b>';
		$lrole=NULL;
		foreach ($credits as $c){
			if(!strcmp($lrole, $c->role)){
				$cred .= ', '.$c->name;
				if($c->tracks){
					$cred .= ' ('.__( 'tracks', 'my-record-collection') .': '.$c->tracks.') ';
				}
			}else{
				if(!is_null($lrole)){
					$cred .= '<br>';
				}
				$cred .= $c->role.' - '.$c->name;
				if($c->tracks){
					$cred .= ' ('.__( 'tracks', 'my-record-collection') .': '.$c->tracks.') ';
				}
			}
			$lrole = $c->role;
		}
		$cred .= "</p>";
		echo $cred;
	}
	
	echo '</div>';
	
		
}

$fnc = (isset($_POST['fnc']) ? trim($_POST['fnc']) : null);
if(isset($_GET['recordID']) && !is_null($_GET['recordID'])){
	$fnc = 'showInfo';
}


$uinfo = get_option('mrc_userinfo');
$settings = get_option('mrc_settings');

switch($fnc){
	case 'getuser':
		$url = "http://api.discogs.com/users/".$_POST['username'];
		$data = get_file_from_url($url);	
		$js = json_decode($data,true);
		update_option( 'mrc_username',$_POST['username'],'', 'yes' );	
		echo $data;
		break;
	case 'resetuser':
		mrc_db_truncate();
		delete_option( 'mrc_settings' );
		delete_option( 'mrc_username' );	
		break;
	case 'add2db':
		mrc_add2db();
		break;
	case 'savesettings':
		update_option( 
			'mrc_settings',
			array(
				"display" => $_POST['display'],
				"sort" => $_POST['sort'],
				"sortway" => $_POST['sortway'],
				"removenum" => parse_boolean($_POST['r_num']),
				"removethe" => parse_boolean($_POST['r_the']),
				"colormode" => $_POST['col']
				),
			'', 
			'yes' 
		);
		break;
	case 'showInfo':
		showRecordInfo($_GET['recordID']);
		break;
	default: ?>
	
<?php
$username = get_option('mrc_username');
$settings = get_option('mrc_settings');
if(!empty($username)){
	$url = "http://api.discogs.com/users/".$username;
	$data = get_file_from_url($url);
	$js = json_decode($data);
	$discogs_num = $js->num_collection;
}
$db_num = mrc_num_db_rows();
$discogs_num = (isset($discogs_num) ? $discogs_num : 0);
$removenum = (isset($settings['removenum']) ? $settings['removenum'] : false);
$removethe = (isset($settings['removethe']) ? $settings['removethe'] : false);
$colormode = (isset($settings['colormode']) ? $settings['colormode'] : 'dark');
?>
		
<div class="wrap mrcAdmin"> 
	<h2><?php _e( 'My Record Collection Options' , 'my-record-collection')?></h2>
	<div class="mrca_wrapper visible"> 
		<h4><?php _e( '1. Enter Username' , 'my-record-collection')?></h4>
		<p><?php _e( 'Username:' , 'my-record-collection')?> <input type="text" id="discogs_username" value="<?=$username?>"> <input type="button" id="submit_username" class="button-primary" value="<?php _e('Import userdata' , 'my-record-collection') ?>" /> </p>
		<input type="button" id="reset_username" class="button-secondary<?php if(empty($username)) echo ' hidden'; ?>" value="<?php _e('Reset userinfo' , 'my-record-collection') ?>" />
	</div>
	<div class="mrca_wrapper <?php if(!empty($username)) echo " visible"; ?>"> 
		<h4><?php _e( '2. Recordinfo' , 'my-record-collection')?></h4>
		<p><strong><?php _e('Records in collection' , 'my-record-collection') ?></strong>: <span id="discogs_recordcount"><?=$discogs_num?></span></p> 
		<input type="button" id="import_records" class="button-primary <?php if(isset($db_num) && $db_num != 0) { echo " hidden"; } ?>" value="<?=_e('Import records to database' , 'my-record-collection')?>" />
		<p id="records_in_db" <?php if(!isset($db_num) || $db_num == 0) { echo " class=\"hidden\""; } ?>><strong><?=_e('Records in database' , 'my-record-collection')?></strong>: <span id="db_recordcount"><?=$db_num?></span></p>
		<p id="update_msg"<?php if($db_num == 0 || abs($db_num - $discogs_num) < 3 ) { echo ' class="hidden"'; } ?>><?php _e( 'Missmatch between rocords in local DB and Discogs DB.<br> If you have added records on discogs, you\'ll need to:<br>' , 'my-record-collection')?><input type="button" id="update_records" class="button-primary" value="<?=_e('Update records in database' , 'my-record-collection')?>" /></p>
	</div>
	<div class="mrca_wrapper <?php if($db_num != 0) echo " visible"; ?>" id="mrc_displaysettings"> 
		<h4><?php _e( '3. Display settings' , 'my-record-collection')?></h4>
		<p>
			<strong><?php _e('Select way to display your collection' , 'my-record-collection') ?></strong>: <br>
			<label><input type="radio" <?php if($settings['display'] == 'list') echo "checked "; ?>value="list" name="display"> <?php _e('List mode' , 'my-record-collection') ?><br></label>
			<label><input type="radio" <?php if($settings['display'] == 'covers') echo "checked "; ?>value="covers" name="display"> <?php _e('Recordcovers mode' , 'my-record-collection') ?><br></label>
			<label><input type="radio" <?php if($settings['display'] == 'covers_wo') echo "checked "; ?>value="covers_wo" name="display"> <?php _e('Recordcovers with overlays mode' , 'my-record-collection') ?></label>
		</p> 
		<p>
			<strong><?php _e('Select sort order' , 'my-record-collection') ?></strong>: <br>
			<label><input type="radio" <?php if($settings['sort'] == 'alphaartist') echo "checked "; ?>value="alphaartist" name="sort"> <?php _e('Alphabetical (artist)' , 'my-record-collection') ?><br>
			<label><input type="radio" <?php if($settings['sort'] == 'alfatitle') echo "checked "; ?>value="alphatitle" name="sort"> <?php _e('Alphabetical (title)' , 'my-record-collection') ?></label><br>
			<label><input type="radio" <?php if($settings['sort'] == 'year') echo "checked "; ?>value="year" name="sort"> <?php _e('Year' , 'my-record-collection') ?></label><br>
			<label><input type="radio" <?php if($settings['sort'] == 'format') echo "checked "; ?>value="format" name="sort"> <?php _e('Format' , 'my-record-collection') ?></label><br><br>
			<label><input type="radio" <?php if($settings['sortway'] == 'asc') echo "checked "; ?>value="asc" name="sortway"> <?php _e('Ascending' , 'my-record-collection') ?></label> 
			<label><input type="radio" <?php if($settings['sortway'] == 'desc') echo "checked "; ?>value="desc" name="sortway"> <?php _e('Descending' , 'my-record-collection') ?></label>
		</p>
		<p>
			<strong><?php _e('Extra settings' , 'my-record-collection') ?></strong>: <br>
			<label><input type="checkbox" value="removenum" id="removenum" <?php if($removenum) echo "checked "; ?>> <?php _e('Remove extra numbers in artist names, (eg. change "Creative (2)" to "Creative"' , 'my-record-collection') ?></label><br>
			<label><input type="checkbox" value="removethe" id="removethe" <?php if($removethe) echo "checked "; ?>> <?php _e('Remove ", The" in artist names, (eg. change "Beatles, The" to "Beatles"' , 'my-record-collection') ?></label>
		</p>
		<p>
			<strong><?php _e('Color mode' , 'my-record-collection') ?></strong>: <br>
			<label><input type="radio" <?php if($colormode == 'dark') echo "checked "; ?>value="dark" name="colormode"> <?php _e('Dark on Light BG' , 'my-record-collection') ?></label> 
			<label><input type="radio" <?php if($colormode == 'light') echo "checked "; ?>value="light" name="colormode"> <?php _e('Light on Dark BG' , 'my-record-collection') ?></label>
		</p>
		<input type="button" id="save_settings" class="button-primary" value="<?php _e('Save Settings' , 'my-record-collection') ?>" />
	</div>
	<div class="mrca_wrapper <?php if($db_num != 0) echo " visible"; ?>" id="mrc_displaysettings"> 
		<h4><?php _e( '4. How to display your collection' , 'my-record-collection')?></h4>
		<p><?php _e( 'To display your collection, create a new page and add the following text <strong>in HTML-mode</strong>: <code>&lt;!--MyRecordCollection--&gt;</code>' , 'my-record-collection')?></p> 
	</div>
</div>
<?php 
}
?>