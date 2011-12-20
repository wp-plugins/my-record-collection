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

	
	$table_name = $wpdb->prefix . "mrc_records";
	mrc_db_truncate();
	$url = "http://api.discogs.com/users/volmar/collection/folders/0/releases?per_page=100&page=1";
	$json = json_decode(get_file_from_url($url));
	$page = $json->pagination->page;
	$pages = $json->pagination->pages;
	$count = 0;
	
	for($i=1; $i < $pages+1; $i++ ){
		if($i != 1){
			$url = "http://api.discogs.com/users/volmar/collection/folders/0/releases?per_page=100&page=".$i;
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
	// returns file content
	return file_get_contents($src);
}

function parse_boolean($obj) {
    return filter_var($obj, FILTER_VALIDATE_BOOLEAN);
}


$fnc = (isset($_POST['fnc']) ? trim($_POST['fnc']) : null);

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
				"removethe" => parse_boolean($_POST['r_the'])
				),
			'', 
			'yes' 
		);
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
?>
		
<div class="wrap mrcAdmin"> 
	<h2><?php _e( 'My Record Collection Options' , 'my-record-collection')?></h2>
	<div class="mrca_wrapper visible"> 
		<h4><?php _e( '1. Enter Username' , 'my-record-collection')?></h4>
		<p><?php _e( 'Username:' , 'my-record-collection')?> <input type="text" id="discogs_username" value="<?=$username?>"> <input type="button" id="submit_username" class="button-primary" value="<?php _e('Import userdata' , 'my-record-collection') ?>" /> </p>
		<input type="button" id="reset_username" class="button-secondary<?php if(empty($username)) echo ' hidden'; ?>" value="<?php _e('Reset userinfo' , 'my-record-collection') ?>" />
	</div>
	<div class="mrca_wrapper <?php if(!empty($username)) echo " visible"; ?>"> 
		<h4><?=__( '2. Recordinfo' , 'my-record-collection')?></h4>
		<p><strong><?php _e('Records in collection' , 'my-record-collection') ?></strong>: <span id="discogs_recordcount"><?=$discogs_num?></span></p> 
		<input type="button" id="import_records" class="button-primary <?php if(isset($db_num) && $db_num != 0) { echo " hidden"; } ?>" value="<?=_e('Import records to database' , 'my-record-collection')?>" />
		<p id="records_in_db" <?php if(!isset($db_num) || $db_num == 0) { echo " class=\"hidden\""; } ?>><strong><?=_e('Records in database' , 'my-record-collection')?></strong>: <span id="db_recordcount"><?=$db_num?></span></p>
		<p id="update_msg"<?php if($db_num == 0 || abs($db_num - $discogs_num) < 3 ) { echo ' class="hidden"'; } ?>><?php _e( 'Missmatch between rocords in local DB and Discogs DB.<br> If you have added records on discogs, you\'ll need to:<br>' , 'my-record-collection')?><input type="button" id="update_records" class="button-primary" value="<?=_e('Update records in database' , 'my-record-collection')?>" /></p>
	</div>
	<div class="mrca_wrapper <?php if($db_num != 0) echo " visible"; ?>" id="mrc_displaysettings"> 
		<h4><?=__( '3. Display settings' , 'my-record-collection')?></h4>
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
		<input type="button" id="save_settings" class="button-primary" value="<?php _e('Save Settings' , 'my-record-collection') ?>" /
	</div>
</div>
<?php 
}
?>

<?/* 


switch ($disp) {
    case 0:?>
		<input type="hidden" name="mrc_hidden" value="0">  
    <?php
			echo "<h4>" . __( 'Page 1/4: Enter Username' , 'my-record-collection') . "</h4>";?>
				<p><?php echo __( 'Uploaded XML file:' , 'my-record-collection'); ?> <?=get_option('mrc_xml_file')?> </p>



<?php
/*
switch ($disp) {
    case 0:?>
    	<input type="hidden" name="mrc_hidden" value="0">  
      <?php
			echo "<h4>" . __( 'Page 1/4: Enter Username' , 'my-record-collection') . "</h4>";
				<p><?php echo __( 'Uploaded XML file:' , 'my-record-collection'); ?> <?=get_option('mrc_xml_file')?> </p>

		    <? else: ?>
		    	<?php $d_next = ' disabled="disabled"'; ?>
		    	<p><?php _e("Username: " , 'my-record-collection'); ?><input type="text" name="mrc_discogs_username" value="<?php echo $mrc_discogs_username; ?>"></p> 
			<? endif; ?>
			<p class="submit"> 
				<input type="submit" name="submit" class="button-primary" value="<?php _e('Update Options' , 'my-record-collection') ?>" /> 
				<input type="submit" name="submit_next"<?=$d_next?> value="<?php _e('Next page &raquo;' , 'my-record-collection') ?>" />
			</p>
       <? break;
     case 1:?>
	     <input type="hidden" name="mrc_hidden" value="1">  
	        <?php    echo "<h4>" . __( 'Page 2/4: Import XML into database' , 'my-record-collection') . "</h4>"; ?>  
	     <p><?php _e('Uploaded XML-file is', 'my-record-collection');?>: <b><?php echo get_option('mrc_xml_file'); ?></b></p>
		 <?php
		 	if(mrc_num_db_rows() != 0){
		 		printf(__("<p>The database conatains %d rows</p>", 'my-record-collection'), mrc_num_db_rows()); 
				echo '<p><input type="checkbox" name="mrc_empty_db" value="yes" /> '.__('Empty Database', 'my-record-collection').'.</p>';
			}else{
				echo '<p><input type="checkbox" name="import_xml" value="yes" /> '.__('Import XML into DB', 'my-record-collection').'</p>';
				$d_next = ' disabled="disabled"';
			}
		 ?>
			<p class="submit"> 
				<input type="submit" name="submit_prev" value="<?php _e('&laquo; Previous page' , 'my-record-collection') ?>" /> 
				<input type="submit" name="submit" class="button-primary" value="<?php _e('Update Options' , 'my-record-collection') ?>" /> 
				<input type="submit" name="submit_next"<?=$d_next?> value="<?php _e('Next page &raquo;' , 'my-record-collection') ?>" />
			</p>
     
<? break;
     case 2:?>
	 	     <input type="hidden" name="mrc_hidden" value="2">  
	        <?php    echo "<h4>" . __( 'Page 3/4: Import Images' , 'my-record-collection') . "</h4>"; ?>  
			
		 <?php
			
			$directory = MRC_URL_BASE_DIR."/my-record-collection/img/";
			if(!file_exists($directory)){
				mkdir($directory, 0777, true);
			}
			$filecount = countFiles($directory);
			$db_img_count = mrc_num_db_imgs();
			if($filecount == $db_img_count){
				echo '<p class="mrc_imgimport_sucess">';
				printf(__("All %d images imported, go to the next step to set up display-mode. Or check delete images to delet them if something went wrong.", 'my-record-collection'), $filecount); 
				echo '</p><p class="mrc_imgimport_sucess"><input type="checkbox" name="mrc_del_imgs" value="yes" /> '.__('Delete images', 'my-record-collection').'</p>';
				
			}else{
				echo '<p id="mrc_imgimport">';
				printf(__('Importing image <span class="fc">%1d</span> of <span class="tc">%2d</span></p>', 'my-record-collection'), $filecount, $db_img_count);
				
				echo '<input type="button" name="mrc_imp_img" id="mrc_imp_img" value="'.__('Import Images', 'my-record-collection').'" class="button-primary">';
				echo '<p class="mrc_imgimport_sucess" style="display:none;">'.__('All images imported, go to the next step to set up display-mode. Or check delete images to deleta them if something went wrong.', 'my-record-collection').'</p>';
				echo '<p class="mrc_imgimport_sucess" style="display:none;"><input type="checkbox" name="mrc_del_imgs" value="yes" /> '.__('Delete images', 'my-record-collection').'.</p>';
				$d_next = ' disabled="disabled"';
				echo import_images_lis();
			}
			
		 ?>
			<p class="submit"> 
				<input type="submit" name="submit_prev" value="<?php _e('&laquo; Previous page', 'my-record-collection') ?>" /> 
				<input type="submit" name="submit" class="button-primary" value="<?php _e('Update Options', 'my-record-collection') ?>" /> 
				<input type="submit" id="mrc_3_next" name="submit_next"<?=$d_next?> value="<?php _e('Next page &raquo;' , 'my-record-collection') ?>" />
			</p>

	 
	 <?php break;
	      case 3:?>
	 	     <input type="hidden" name="mrc_hidden" value="2">  
	        <?php    echo "<h4>" . __( 'Page 4/4: Finshed' , 'my-record-collection') . "</h4>"; ?>  
			
		 <?php
			$directory = MRC_URL_BASE_DIR."/my-record-collection/img/";

			$filecount = countFiles($directory);
			$db_img_count = mrc_num_db_imgs();
			if($filecount == $db_img_count){
				_e( "<p>Congratulations, My Record Collections is now fully installed. Here's how you do to disply it.</p><ol><li>Create a new WordPress Page, name it whatever you like</li><li>Include the following code <b>in HTML mode</b> <span style=\"background-color: #ddd;\">&lt;!--MyRecordCollection--&gt;</span></li><li>You're Done!</li></ol>", 'my-record-collection');
			}else{
				echo '<p id="mrc_imgimport">';
				printf(__('Importing image <span class="fc">%1d</span> of <span class="tc">%2d</span></p>', 'my-record-collection'), $filecount, $db_img_count);
				echo '<input type="button" name="mrc_imp_img" id="mrc_imp_img" value="'.__('Import Images', 'my-record-collection').'" class="button-primary">';
				echo import_images_lis();
			}
			
		 ?>
			<p class="submit" style="display: none;"> 
				<input type="submit" name="submit_prev" value="<?php _e('&laquo; Previous page' , 'my-record-collection') ?>" /> 
				<input type="submit" name="submit" class="button-primary" value="<?php _e('Update Options' , 'my-record-collection') ?>" /> 
				<input type="submit" name="submit_next"<?=$d_next?> value="<?php _e('Next page &raquo;' , 'my-record-collection') ?>" />
			</p>

	 
	 <?php break;
	 }
*/
?> 