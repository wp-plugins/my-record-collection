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
				console.log(parseInt(data));
				hideLoader();
				$('#db_recordcount').text(data);
				$('#import_records').remove();
				$('#records_in_db').show();
			});
		}
	);
	
	$('#update_records').bind('click',
		function(){
			showLoader();
			$.post('/wp-content/plugins/my-record-collection/mrc_import_admin.php',{fnc:'add2db'},function(data){
				console.log(parseInt(data));
				hideLoader();
				$('#db_recordcount').text(data);
				$('#update_msg').hide();
			});
		}
	);

	// $("#MyRecordCollection ul li").click(function() {
	// 	var record_id = $(this).attr('record');
	// 	$.fn.colorbox({width:"530px", height:"520px", iframe:true, href:mrc_loc.SiteUrl+"/wp-content/plugins/my-record-collection/disp.php?id="+record_id});
	// });	
	// 
	// 
	// $('ul.music li').each(function()
	//    {
	//       $(this).qtip({
	//        content: $(this).find('a').text(), // Use the tooltip attribute of the element for the content
	//    style: { 
	// 	  'font-size': 12,
	// 	  width: 200,
	// 	  padding: 4,
	// 	  background: '#ffcc00',
	// 	  color: 'black',
	// 	  textAlign: 'left',
	// 	  border: {
	// 		 width: 3,
	// 		 radius: 5,
	// 		 color: '#d9ae00'
	// 	  },
	// 	  tip: 'bottomMiddle',
	// 	  name: 'dark' // Inherit the rest of the attributes from the preset dark style
	//    },
	//    position: {
	//       corner: {
	//          target: 'topMiddle',
	//          tooltip: 'bottomMiddle'
	//       }
	//    }
	// 	 
	//       });
	//    });
	// 
	// 
	// 
	// 
	// 
	// /*$("ul.music li").click(function() {
	// 	var record_id = $(this).attr('record');
	// 	$.fn.colorbox({width:"530px", height:"520px", iframe:true, href:mrc_loc.SiteUrl+"/wp-content/plugins/my-record-collection/disp.php?id="+record_id});
	// });	*/
	// 
	// 	$('#mrc_imp_img').click(function(){
	// 	var dstList = $("#mrc_dst li");
	// 	var srcA = [];
	// 	$("#mrc_src li").each(function() { srcA.push($(this).text()) });
	// 
	// 	dstList.each(function(i) { 
	// 		var dst = $(this).text();
	// 		var src = srcA[i];
	// 		$.post(mrc_loc.SiteUrl+'/wp-content/plugins/my-record-collection/imp.php?num='+i+'&dst='+dst+'&src='+src, function() {
	// 			var num = parseInt($('#mrc_imgimport span.fc').text());
	// 			var nynum = num+1;
	// 			$('#mrc_imgimport span.fc').text(nynum);
	// 			if(nynum == dstList.length){
	// 				$('#mrc_imgimport').hide();
	// 				$('#mrc_imp_img').hide();
	// 				$('.mrc_imgimport_sucess').show();
	// 				$('#mrc_3_next').removeAttr('disabled');
	// 				
	// 			}
	// 		});
	// 	});
	// });
	
});



