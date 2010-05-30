<?php
//	IMPORTS THE RECORD COVERS
require( '../../../wp-load.php' );
function download($src, $dst) {
	$theBody = wp_remote_retrieve_body( wp_remote_get($src) );
	$fp = fopen($dst, 'w+');
	fwrite($fp, $theBody);
	fclose($fp);

}
	$dst = $_GET['dst'];
	$src = $_GET['src'];
if(!file_exists($dst)){
	download($src,$dst);
}
?>