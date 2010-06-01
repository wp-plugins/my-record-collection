<?php

// Define Constants
$upload_dir = wp_upload_dir();
define("MRC_URL_BASE_URL", $upload_dir['baseurl']);
define("MRC_URL_BASE_DIR", $upload_dir['basedir']);

//FUNCTIONS
function mrc_add2db(){ //ADDS THE XML TO THE DB
	global $wpdb;
	$wpdb->hide_errors();
	$filename 	= get_option('mrc_upload_dir').get_option('mrc_xml_file');
	$table_name = $wpdb->prefix . "mrc_records";
	$xmlRAW 	= utf8_encode(file_get_contents($filename));
	$old 		= array(
					"<Collection Sleeve Condition />",
					"Collection Folder",
					"<Collection Notes />",
					"<Collection Media Condition />",
					"Collection Sleeve Condition",
					"Collection Notes",
					"Collection Media Condition",
					"<Collection Media Condition />");
	$new 		= array(
					"<CollectionSleeveCondition/>",
					"CollectionFolder",
					"<CollectionNotes/>",
					"<CollectionMediaCondition/>",
					"CollectionSleeveCondition",
					"CollectionNotes",
					"CollectionMediaCondition",
					"<CollectionMediaCondition/>"
				);
	$xml = str_replace($old,$new,$xmlRAW);
	$MYxml = new SimpleXMLElement($xml);
	
	foreach($MYxml->release as $rec){
		$elon = count($rec->images->image);
	
		$dW="";
		for($i=0; $i < $elon; $i++){
			if($rec->images->image[$i]->attributes()->type == "primary"){
				$dW = $rec->images->image[$i]->attributes()->uri150;
			}else{
				$dW .= "";
			}
			
		}
		if($elon == 0){
			$dW == NULL;
		}else{
			if($dW == ""){
				$dW = $rec->images->image[0]->attributes()->uri150;
			}
		}
			$data = array(
					   'id' 	=> $rec->attributes()->id, 
					   'artist' => utf8_decode($rec->artists->artist->name), 
					   'title'	=> utf8_decode(html_entity_decode($rec->title)), 
					   'label' 	=> utf8_decode(html_entity_decode($rec->labels->label->attributes()->name)), 
					   'catno' 	=> utf8_decode($rec->labels->label->attributes()->catno), 
					   'f_name' => utf8_decode($rec->formats->format->attributes()->name), 
					   'f_qty' 	=> $rec->formats->format->attributes()->qty, 
					   'f_desc' => utf8_decode($rec->formats->format->descriptions->description), 
					   'r_date' => $rec->released,
					   'country'=> utf8_decode($rec->country),
					   'i150'	=> $dW
					 );
		$wpdb->insert( $table_name, $data );
	}
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

?>

<div class="wrap mrcAdmin">  
    <?php    echo "<h2>" . __( 'My Record Collection Options' , 'my-record-collection') . "</h2>"; ?>  
    <form enctype="multipart/form-data" name="mrc_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>"> 
<?php  
$d_next=""; //SET START VALUE

/** CHECK WHAT PAGE TO DISPLAY **/
if		(isset($_POST['submit_next'])){ $disp = $_POST['mrc_hidden']+1;	}
else if	(isset($_POST['submit_prev'])){ $disp = $_POST['mrc_hidden']-1;	}

else{
	if(isset($_POST['submit'])){ $disp = $_POST['mrc_hidden'];	} //Show witch page to display
	if(!isset($_POST['mrc_hidden'])){
        $disp = 0;
     }else if($_POST['mrc_hidden'] == '0') { // PAGE 1 show
    	if(isset($_POST['del_xml']) && $_POST['del_xml'] == "yes"){
			$delfile = get_option('mrc_upload_dir').get_option('mrc_xml_file');
			unlink($delfile);
			delete_option('mrc_xml_file');
			mrc_db_truncate();
			mrc_destroy();
		}else{
			$target_path = get_option('mrc_upload_dir');	
			$target_path = $target_path . basename( $_FILES['mrc_xml_file']['name']); 
			if(!file_exists(get_option('mrc_upload_dir'))){
				mkdir(get_option('mrc_upload_dir'), 0777, true);
			}
			
			
			
			if(move_uploaded_file($_FILES['mrc_xml_file']['tmp_name'], $target_path)) {
			    //echo "The file ".  basename( $_FILES['mrc_xml_file']['name'])." has been uploaded";
			    update_option('mrc_xml_file', $_FILES['mrc_xml_file']['name']); 
			} else{
			    _e( "There was an error uploading the file, please try again!", 'my-record-collection');
			}
        
    	} 

	}else if($_POST['mrc_hidden'] == '1') { // PAGE 2 show
		if(isset($_POST['mrc_empty_db']) && $_POST['mrc_empty_db'] == "yes"){
			mrc_db_truncate();
			mrc_destroy();
		}else if(isset($_POST['import_xml']) && $_POST['import_xml'] == "yes"){
			mrc_add2db();
		}

	}else if($_POST['mrc_hidden'] == '2') { // PAGE 2 show
		if(isset($_POST['import_img']) && $_POST['import_img'] == "yes"){
			//$limit = $_POST['imported_imgs'];
			//import_images($limit);
		}else if(isset($_POST['mrc_del_imgs']) && $_POST['mrc_del_imgs'] == "yes"){
			mrc_destroy();
		}

	}
}
?> 

<?php
switch ($disp) {
    case 0:?>
    	<input type="hidden" name="mrc_hidden" value="0">  
            <?php
        	$filename = get_option('mrc_upload_dir').get_option('mrc_xml_file');
			echo "<h4>" . __( 'Page 1/4: Upload XML File' , 'my-record-collection') . "</h4>";
			if (file_exists($filename) && is_file($filename)) :?>
				<p><?php echo __( 'Uploaded XML file:' , 'my-record-collection'); ?> <?=get_option('mrc_xml_file')?> </p>
				<p><input type="checkbox" name="del_xml" value="yes" /> <?php _e( 'Delete XML' , 'my-record-collection'); ?></p>
		    <? else: ?>
		    	<?php $d_next = ' disabled="disabled"'; ?>
				<p><?php _e('You can export your collection <a href="http://www.discogs.com/users/export" target="_blank">here</a>. Remember to export it in XML-mode.', 'my-record-collection');?></p>
		    	<p><?php _e("XML file: " , 'my-record-collection'); ?><input type="file" name="mrc_xml_file" value="<?php echo $mrc_xml_file; ?>" size="20"></p> 
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

?> 
    </form>  
</div> 