<?php
error_reporting(E_ALL);
ini_set('display_errors','On');

$root = dirname(dirname(dirname(dirname(__FILE__))));
if (file_exists($root.'/wp-load.php')) {
	// WP 2.6
	require_once($root.'/wp-load.php');
} else {
	// Before 2.6
	require_once($root.'/wp-config.php');
}

require_once('mrc.class.php');

$mrc = new MyRecordCollection();


$fnc = (isset($_POST['fnc']) ? trim($_POST['fnc']) : null);
$settings = unserialize(get_option('mrc_settings'));

switch($fnc){
	case 'getuser':
		$url = "http://api.discogs.com/users/".$_POST['username'];
		$data = $mrc->get_file_from_url($url);
		$js = json_decode($data);
		$settings['discogs_info'] = array(
			'username'			=> $js->username,
			'num_collection'	=> $js->num_collection
		);
		update_option('mrc_settings', serialize($settings));
		echo $data;
		break;

	case 'resetuser':
		$mrc->db_truncate();
		unset($settings['discogs_info']);
		update_option('mrc_settings', serialize($settings));	
		break;

	case 'resetdatabase':
		$x = isset($_POST['start']) ? $_POST['start'] : 0;
		if($x == 0){
			$mrc->db_truncate();
		}
		$mrc->add2db($x);	
		break;

	case 'add2db':
		$mrc->add2db();
		break;

	case 'savesettings':
		extract($_POST);
		$settings['type'] = $type;
		$s = array();
		//print_r($fields);
		foreach($fields['enable'] as $f){
			$s[$f] = true;
		}
		foreach($fields['disable'] as $f){
			$s[$f] = false;
		}
		//print_r($s);
		$settings['fields'] = $s;
		$settings['sort'] = $sort;
		$settings['order'] = $way;
		$settings['gridtype'] = $gridtype;
		$settings['removenum'] = $mrc->parse_boolean($num);
		$settings['removethe'] = $mrc->parse_boolean($the);
		$settings['dupes'] = $mrc->parse_boolean($dupes);
		$settings['add_styles'] = $mrc->parse_boolean($add_styles);
		$settings['liststring'] = $liststring;
		$settings['theme'] = $theme;


		update_option('mrc_settings', serialize($settings));

		break;

	default:
	?>

<div class="wrap mrcAdmin"> 
	<?php

		if(isset($settings['discogs_info']['username'])){
			$url = "http://api.discogs.com/users/".$settings['discogs_info']['username'];
			$data = $mrc->get_file_from_url($url);
			$js = json_decode($data);
			$settings['discogs_info'] = array(
				'username'			=> $js->username,
				'num_collection'	=> $js->num_collection
			);
			update_option('mrc_settings', serialize($settings));
		}

		highlight_string(print_r($settings,1));
		$username 		= isset($settings['discogs_info']['username']) ? $settings['discogs_info']['username'] : null;
		$discogs_num 	= isset($settings['discogs_info']['num_collection']) ? $settings['discogs_info']['num_collection'] : null;
		$db_num 		= $mrc->mrc_num_db_rows();
		$display_type 	= $settings['type'];
		$fields = $settings['fields'];

		$fieldnames = array(
			'did' => 'Discogs ID',
			'artist' => 'Artist',
			'title' => 'Release title',
			'label' => 'Label',
			'catno' => 'Catalog no',
			'format' => 'Format',
			'r_date' => 'Release date'
		);
	?>
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
		<p id="update_msg"<?php if($db_num == 0 || abs($db_num - $discogs_num) < 3 ) { echo ' class="hidden"'; } ?>><?php _e( 'Missmatch between rocords in local DB and Discogs DB.<br> If you have added records on discogs, you\'ll need to:<br>' , 'my-record-collection')?>
			<input type="button" id="update_records" class="button-primary" value="<?=_e('Update records in database' , 'my-record-collection')?>" />
			<input type="button" id="reset_records" class="button-primary" value="<?=_e('Reset database' , 'my-record-collection')?>" />
		</p>
	</div>
	<div class="mrca_wrapper <?php if($db_num != 0) echo " visible"; ?>" id="mrc_displaysettings"> 
		<h4><?php _e( '3. Display settings' , 'my-record-collection')?></h4>
		
		<p><strong><?php _e('Select way to display your collection' , 'my-record-collection') ?></strong>:</p>
		
		<input type="hidden" name="display_type" id="display_type" value="<?=$display_type?>">
		<style>

			.mrca_wrapper { display: none; border: 1px solid silver; background: #eee; padding: 5px 10px; -webkit-border-radius: 7px; -moz-border-radius: 7px; border-radius: 7px; margin-top: 5px; } 

			.mrca_wrapper.visible {display:block; } 

			.hidden {display: none; } 

			#loader_overlay {position: fixed; top: 0px; left: 0px; width: 100%; height: 100%; background: rgba(255,255,255,0.7) url(/wp-content/plugins/my-record-collection/gfx/loader.gif) 50% 50% no-repeat; z-index: 300; }
			.ui-sortable { list-style-type: none; margin: 0; padding: 0; display: inline-block; vertical-align: top; min-height: 150px; width: 150px; padding: 3px; }
			.ui-sortable li { margin: 3px; padding: 0.4em; border: 1px solid #bbb; }

			.ui-sortable li.header { border: none; font-weight: bold;}

			.fields { border-radius: 5px; }
			.fields.enabled { background: #eee; }
			.fields.disabled { background: #ccc; color: #666; }
			/*.ui-sortable li span { position: absolute; margin-left: -1.3em; }*/
		</style>
		<div id="tabs">
			<ul>
				<li<? if($display_type == 'table') echo ' class="ui-tabs-active"'?>><a href="#table"><?php _e( 'Table' , 'my-record-collection')?></a></li>
				<li<? if($display_type == 'grid') echo ' class="ui-tabs-active"'?>><a href="#grid"><?php _e( 'Grid' , 'my-record-collection')?></a></li>
				<li<? if($display_type == 'list') echo ' class="ui-tabs-active"'?>><a href="#list"><?php _e( 'List' , 'my-record-collection')?></a></li>
			</ul>
			<div id="table">
				<p><?php _e('You can select what fields you like to show in your table with the drag and drop interface below. Drag the fields you want visible to the enabled column and the ones you don\'t want to show to the disabled column. You can also adjust the order by moving the enabled fields up and down.' , 'my-record-collection') ?></p>
				<?php
					$enabled = '<li class="header">'.__( 'Enabled fields' , 'my-record-collection').'</li>';
					$disabled = '<li class="header">'.__( 'Disabled fields' , 'my-record-collection').'</li>';
					foreach ($fields as $k=>$v) {
						if($v){
							$enabled .= '<li data-name="'.$k.'">'.$fieldnames[$k].'</li>';
						}else{
							$disabled .= '<li data-name="'.$k.'">'.$fieldnames[$k].'</li>';
						}	
					}
				?>
			
				<ul class="fields enabled">
					<?=$enabled?>
				</ul>
				<ul class="fields disabled">
					<?=$disabled?>
				</ul>
			</div>
			<div id="grid">
				<h4><?php _e('Choose grid-mode' , 'my-record-collection') ?></h4>
				<p><?php _e('The grid-mode comes in two flavours. The simple one and the "overlays"-mode, where i use the beautiful PNG overlays created by <a href="http://www.komodomedia.com/blog/2009/03/sexy-music-album-overlays/">Komodo media</a>. Choose the one you like best.' , 'my-record-collection') ?></p>
				<img src="<?php echo plugins_url('gfx/grid_simple.png',__FILE__); ?>" alt=""><br>
				<label><input type="radio" <?php if($settings['gridtype'] == 'simple') echo "checked "; ?>value="simple" name="gridtype"> <?php _e('Recordcovers mode' , 'my-record-collection') ?></label><br>
				<img src="<?php echo plugins_url('gfx/grid_w_overlays.png',__FILE__); ?>" alt=""><br>
				<label><input type="radio" <?php if($settings['gridtype'] == 'w_covers') echo "checked "; ?>value="w_covers" name="gridtype"> <?php _e('Recordcovers with overlays mode' , 'my-record-collection') ?></label><br>
			</div>
			<div id="list">
				<p><?php _e('This mode will display your collection in a HTML-list (unordered list, UL). To choose wich fields you want to include, compose the string for each row. Just replace the field with the right code from the list below:' , 'my-record-collection') ?></p>
				<ul>
					<li><b>[artist]</b> - <?php _e('the artist name','my-record-collection'); ?></li>
					<li><b>[title]</b> - <?php _e('the release title','my-record-collection'); ?></li>
					<li><b>[format]</b> - <?php _e('the release format','my-record-collection'); ?></li>
					<li><b>[label]</b> - <?php _e('record label','my-record-collection'); ?></li>
					<li><b>[catno]</b> - <?php _e('the cataloge number','my-record-collection'); ?></li>
					<li><b>[year]</b> - <?php _e('release year','my-record-collection'); ?></li>
				</ul>
				<textarea id="liststring" name="liststring" style="width: 400px"><?php echo $settings['liststring']?></textarea>
			</div>
		</div>

		<p>
			<strong><?php _e('Select sort order' , 'my-record-collection') ?></strong>: <br>
			<label><input type="radio" <?php if($settings['sort'] == 'artist') echo "checked "; ?>value="artist" name="sort"> <?php _e('Alphabetical (artist)' , 'my-record-collection') ?><br>
			<label><input type="radio" <?php if($settings['sort'] == 'title') echo "checked "; ?>value="title" name="sort"> <?php _e('Alphabetical (title)' , 'my-record-collection') ?></label><br>
			<label><input type="radio" <?php if($settings['sort'] == 'year') echo "checked "; ?>value="year" name="sort"> <?php _e('Year' , 'my-record-collection') ?></label><br>
			<label><input type="radio" <?php if($settings['sort'] == 'label') echo "checked "; ?>value="label" name="sort"> <?php _e('Record label' , 'my-record-collection') ?></label><br><br>
			<label><input type="radio" <?php if($settings['order'] == 'asc') echo "checked "; ?>value="asc" name="sortway"> <?php _e('Ascending' , 'my-record-collection') ?></label> 
			<label><input type="radio" <?php if($settings['order'] == 'desc') echo "checked "; ?>value="desc" name="sortway"> <?php _e('Descending' , 'my-record-collection') ?></label>
		</p>
		<p>
			<strong><?php _e('Extra settings' , 'my-record-collection') ?></strong>: <br>
			<label><input type="checkbox" value="removenum" id="removenum" <?php if($settings['removenum']) echo "checked "; ?>> <?php _e('Remove extra numbers in artist names, (eg. change "Creative (2)" to "Creative"' , 'my-record-collection') ?></label><br>
			<label><input type="checkbox" value="removethe" id="removethe" <?php if($settings['removethe']) echo "checked "; ?>> <?php _e('Remove ", The" in artist names, (eg. change "Beatles, The" to "Beatles"' , 'my-record-collection') ?></label><br>
			<label><input type="checkbox" value="dupes" id="dupes" <?php if($settings['dupes']) echo "checked "; ?>> <?php _e('Show duplicates in the collection (if you have multiple copies of the same release)' , 'my-record-collection') ?></label><br>
			<label><input type="checkbox" value="add_styles" id="add_styles" <?php if($settings['add_styles']) echo "checked "; ?>> <?php _e('Add default styles. Uncheck this if you want to use your own stylesheet. (not recomended in grid-mode).' , 'my-record-collection') ?></label>
		</p>
		<p>
			<strong><?php _e('Color mode' , 'my-record-collection') ?></strong>: <br>
			<label><input type="radio" <?php if($settings['theme'] == 'darkonlight') echo "checked "; ?>value="darkonlight" name="theme"> <?php _e('Dark on Light BG' , 'my-record-collection') ?></label> 
			<label><input type="radio" <?php if($settings['theme'] == 'lightondark') echo "checked "; ?>value="lightondark" name="theme"> <?php _e('Light on Dark BG' , 'my-record-collection') ?></label>
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