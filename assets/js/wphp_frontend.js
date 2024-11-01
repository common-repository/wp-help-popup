( function ( jQuery ) {

'use strict';


jQuery(function(){

	jQuery(".ajax-search").focus(function(event){
		var val = jQuery(this).val();
		if(!val)
		jQuery(".search_text_desc").fadeIn('slow');

	});

	jQuery(".ajax-search").keyup(function(event){


		var post_title = jQuery(this).val();
		var title_length = post_title.length;
		if(title_length<3){
			jQuery(".search_text_desc").css('display','inline');
			jQuery(".wpic_desc_count").text(3-title_length);
			jQuery('.wp_ajax_result').css("display","none");
		}


		if(title_length>=3){
			jQuery(".search_text_desc").fadeOut('fast');

			jQuery('.wp_result_list').css("display","none");
			jQuery.ajax({
				url: ajax_obj.ajax_url,
				type:'POST',
				data:{
					action : 'wphp_ajax_call',
					ajax_handler:'wphp_ajax_handler',
					nonce:ajax_obj.nonce,
					post_title : post_title
				},
				beforeSend:function(){
				},
				success: function( data ) {
					
					var result_posts= JSON.parse(data);
					var html_markup='';

					if(result_posts.length>0){
						
						jQuery.each(result_posts,function(index,posts){
							html_markup += "<li class=''><a href='"+posts.guid+"' target='_blank'>"+posts.post_title+"</a></li>";
						});
					}else{

						html_markup += "<h2>"+ajax_obj.not_found_text+"</h2>"
					}
					jQuery('.wp_ajax_result').css("display","block");
					jQuery('.wp_ajax_result').html(html_markup);
					
				}
			});
		}else{
			jQuery('.wp_ajax_result').css("display","none");
			jQuery('.wp_result_list').css("display","block");
		}

	});


   jQuery("#formButton").click(function(){
       

		if(jQuery(this).hasClass("close-icon")){
			jQuery(this).removeClass("close-icon");
			jQuery(this).find('img').show();
			jQuery(".wp_hide").hide();
		}else{
			jQuery(this).addClass("close-icon");
			 jQuery(".wp_hide").show();
			 jQuery(this).find('img').hide();

		}
    });


});

} 

(jQuery));