<?php
	$int = $_GET['id']; // RELEASE ID #
	$apikey = "b508dbe1a6"; // DISCOGS API KEY
	
	require( '../../../wp-load.php' );
	$addr = "http://www.discogs.com/release/".$int. "?f=xml&api_key=".$apikey;
	$result = wp_remote_retrieve_body( wp_remote_get($addr) );

	$xml = new SimpleXMLElement($result);
	
	//GET DATA
	$artist		  = stripName($xml->release->artists->artist->name);
	$title		  = $xml->release->title;
	$label		  = $xml->release->labels->label->attributes()->name;
	$catalog	  = $xml->release->labels->label->attributes()->catno;
	$qty		  = $xml->release->formats->format->attributes()->qty;
	$f_name		  = $xml->release->formats->format->attributes()->name;
	$f_desc		  = $xml->release->formats->format->descriptions->description;
	$country	  = $xml->release->country;
	$released	  = $xml->release->released;
	$genres		  = $xml->release->genres->genre;
	$tracks		  = $xml->release->tracklist->track;
	$credits	  = $xml->release->extraartists->artist;
	$notes	  	  = $xml->release->notes;
	
	
	function stripName($name){
		return preg_replace("/\([\d]{1,2}\)/", "", $name);
	}
	
	function getTracklist($tracks){

		$tl = '<table class="tracks"><tr><th colspan="3"> '.__( 'Tracklisting', 'my-record-collection') .'</th></tr>';
		foreach($tracks as $t){
			$tl .= '<tr><td>'.$t->position.'</td><td>'.$t->title;
			if($t->extraartists->artist){
				$lrole=NULL;
				foreach($t->extraartists->artist as $ea){
					if(!strcmp($lrole, $ea->role)){
						$tl .= ', '.stripName($ea->name);
					}else{
						$tl .= '<br>'.$ea->role.': '.stripName($ea->name);
					}
					$lrole = $ea->role;
					
				}
			}
			
			$tl .= '</td><td>'.$t->duration.'</td></tr>';
		}
		$tl .= '</table>';
		return $tl;
	}
	
	function getNotes($notes){
		if($notes){
			$n = "<p class=\"credits\"><b>".__( 'Notes', 'my-record-collection') ."</b>$notes</p>";
		}
		return $n;
	}
	
	function getCredits($credits){
		if($credits){
			$cred = '<p class="credits"><b>'.__( 'Credits', 'my-record-collection') .'</b>';
			$lrole=NULL;
			foreach ($credits as $c){
				if(!strcmp($lrole, $c->role)){
					$cred .= ', '.stripName($c->name);
					if($c->tracks){
						$cred .= ' ('.__( 'tracks', 'my-record-collection') .': '.$c->tracks.') ';
					}
				}else{
					if(!is_null($lrole)){
						$cred .= '<br>';
					}
					$cred .= $c->role.' - '.stripName($c->name);
					if($c->tracks){
						$cred .= ' ('.__( 'tracks', 'my-record-collection') .': '.$c->tracks.') ';
					}
				}
				$lrole = $c->role;
			}
			$cred .= "</p>";
		}
		return $cred;
	}
	if($qty > 1){
		$format = $qty." x ";
	}
	$format .= $f_name;
	
	foreach($f_desc as $d){
		$format .= ", ".$d;
	}
	
	$img = substr($_SERVER["PHP_SELF"],0,-8)."img/".$int.".jpg";
	$fe = $_SERVER['DOCUMENT_ROOT']  . $img;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
	<title></title>
	<style type="text/css">
	* { margin:0; padding:0px; }
	body{
		width: 450px;
		/*border: 1px solid silver;*/
		padding: 5px;
		font-family: Arial, Verdana, Helvetica, sans-serif;
	}
	
	img{
		float: left;
		border: 1px solid #333;
	}
	
	h2{
		display: inline;
		padding-left: 5px;
		font-size: 14px;
	}
	
	pre{
		clear: both;
	}
	
	table{
		padding-left: 5px;
		font-size: 14px;
	}
	
	.credits {
		width: 450px;
		font-size: 12px;
		margin-top: 10px;
	}
	
	.tracks{
		clear: both;
		width:450px;
		font-size: 12px;
		border-spacing: 0;
		padding-left: 0;
		padding-top: 5px;
	}
	
	.tracks th{
		padding: 2px;
		color: #444;
		text-align: left;
		background-color: #eee;
		border-bottom: 1px solid #555;
	}
	
	.tracks td{
		padding: 2px;
		color: #000;
		vertical-align: top;
		text-align: left;
		border-bottom: 1px solid #ddd;
	}
	
	p.credits b{
		display: block;
		padding: 2px;
		color: #444;
		text-align: left;
		background-color: #eee;
		border-bottom: 1px solid #555;
	}
	
	</style>
</head>
<body>
<?php if(file_exists($fe)) { echo '<img src="'.$img.'" class="disp_img">'; } ?><h2><?=$artist?> - <?=$title?></h2>
<table>
	<tr>
		<td><?php _e('Label', 'my-record-collection'); ?>:</td>
		<td><?=$label?></td>
	</tr>
	<tr>
		<td><?php _e('Catalog#', 'my-record-collection'); ?>:</td>
		<td><?=$catalog?></td>
	</tr>
	<tr>
		<td><?php _e('Format', 'my-record-collection'); ?>:</td>
		<td><?=$format?></td>
	</tr>
	<tr>
		<td><?php _e('Country', 'my-record-collection'); ?>:</td>
		<td><?=$country?></td>
	</tr>
	<tr>
		<td><?php _e('Released', 'my-record-collection'); ?>:</td>
		<td><?=$released?></td>
	</tr>
	<tr>
		<td><?php _e('Genre', 'my-record-collection'); ?>:</td>
		<td><?=$genre?></td>
	</tr>
</table>
<?=getTracklist($tracks)?>
<?=getCredits($credits)?>
<?=getNotes($notes)?>
</body>
</html>
	