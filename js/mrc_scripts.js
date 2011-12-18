function showLoader(){
	$(document.body).append('<div id="loader_overlay"></div>');
}
function hideLoader(){
	$('#loader_overlay').remove();
}

jQuery(document).ready(function($) {

	$('#submit_username').bind('click',
		function(){
			showLoader();
			var un = $('#discogs_username').val();
			$.post('/wp-content/plugins/my-record-collection/mrc_import_admin.php',{fnc:'getuser',username:un},function(data){
				var user = jQuery.parseJSON(data);
				$('#discogs_recordcount').text(user.num_collection);
				$('.mrca_wrapper').eq(1).slideDown();
				$('#reset_username').removeClass('hidden');
				hideLoader();
			});
		}
	);
	
	$('#reset_username').bind('click',
		function(){
			$.post('/wp-content/plugins/my-record-collection/mrc_import_admin.php',{fnc:'resetuser'},function(){
				window.location.href = window.location.href;
			});
		}
	);
	
	$('#import_records').bind('click',
		function(){
			showLoader();
			$.post('/wp-content/plugins/my-record-collection/mrc_import_admin.php',{fnc:'add2db'},function(data){
				hideLoader();
				$('#db_recordcount').text(data);
				$('#import_records').remove();
				$('#records_in_db').show();
				$('.mrca_wrapper').eq(2).slideDown();
			});
		}
	);
	
	$('#update_records').bind('click',
		function(){
			showLoader();
			$.post('/wp-content/plugins/my-record-collection/mrc_import_admin.php',{fnc:'add2db'},function(data){
				hideLoader();
				$('#db_recordcount').text(data);
				$('#update_msg').hide();
			});
		}
	);
	
	$('#save_settings').bind('click',
		function(){
			var SettingsContainer = $('#mrc_displaysettings'),
				mode = SettingsContainer.find('input[name=display]:checked').val(),
				sort = SettingsContainer.find('input[name=sort]:checked').val(),
				way  = SettingsContainer.find('input[name=sortway]:checked').val();
			if(mode && sort && way){
				$.post('/wp-content/plugins/my-record-collection/mrc_import_admin.php',{fnc:'savesettings',display:mode,sort:sort,sortway:way},function(){
					alert('käse');
				});
			}
		}
	);
	
});



