function showLoader(){
	$(document.body).append('<div id="loader_overlay"></div>');
}
function hideLoader(){
	$('#loader_overlay').remove();
}

jQuery(document).ready(function($) {
	
	$('ul.music').MRCinfo();

	var music = $('#MyRecordCollection').find('ul.music').children();

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
				way  = SettingsContainer.find('input[name=sortway]:checked').val(),
				num  = SettingsContainer.find('#removenum').prop('checked'),
				the  = SettingsContainer.find('#removethe').prop('checked');
			if(mode && sort && way){
				var values = {
					fnc 	: 'savesettings',
					display : mode,
					sort 	: sort,
					sortway : way,
					r_num	: num,
					r_the	: the
				};
				$.post('/wp-content/plugins/my-record-collection/mrc_import_admin.php',
					values,function(){
						alert('sparat');
				});
			}
		}
	);

	music.hover(
		function(){
			$('ul.music').MRCinfo('showInfo',$(this));
		},
		function(){
			$('ul.music').MRCinfo('hideInfo',$(this));
		}
	);
	
});

var MRCsettings = {
	onEachRow: null,
	tt: $('#MRC_info_tooltip')
};


(function( $ ){

	var methods = {
		init : function( options ) {

			return this.each(function(){

				var $this = $(this),
				data = $this.data('tooltip');
	
				MRCsettings.onEachRow = Math.floor($this.parent().width() / 110 );

				$(document.body).append('<div id="MRC_info_tooltip"></div>');
			});
		},
		leftOrRight : function ( index, width, p ) {
			var mod = index % MRCsettings.onEachRow,
				 lor = (mod == MRCsettings.onEachRow-1 ? 'left' : 'right'),
				pos;
			if(lor == 'right'){
				pos = { left : (p.left -12), top: p.top - 14 };
				$('#MRC_info_tooltip').removeClass('alternative');
			}else{
				pos = { left : (p.left - width - 33), top: p.top - 14 };
				$('#MRC_info_tooltip').addClass('alternative');
			}
			console.log($('#MRC_info_tooltip'));
			return pos;
		},
		getContent : function( elem ) { 
			var artist = elem.find('.mrc_artist').text(),
				 title = elem.find('.mrc_title').text();
			return artist+'<br>'+title;
		},
		showInfo : function( elem ) {
			var lor = methods.leftOrRight(elem.index(),elem.width(),elem.offset());
			//methods.getContent(elem)
			$('#MRC_info_tooltip').hide().css(lor).show();		
			
		},
		hideInfo : function( ) { 
			$('#MRC_info_tooltip').hide();	
		},
		update : function( content ) { // ...
		}
	};

$.fn.MRCinfo = function( method ) {

	if ( methods[method] ) {
		return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
		return methods.init.apply( this, arguments );
	} else {
		$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
	}    

};

})( jQuery );



