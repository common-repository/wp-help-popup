( function ( jQuery ) {


 
jQuery(document).ready(function(){
jQuery("#selectedpages").change(function(){
	if(this.checked) {
        jQuery(".pages1").show();
    }else{
        jQuery(".pages1").hide();
    }
    });
jQuery("#selectedpost").change(function(){
	if(this.checked) {
       jQuery(".post1").show();
    }else{
        jQuery(".post1").hide();
    }
      
    });

	jQuery(".wpic_select2").each(function(){
		jQuery(this).select2();
	});


    jQuery("form").on("change", ".file-upload-field", function(){ 
        jQuery(this).parent(".file-upload-wrapper").attr("data-text",  jQuery(this).val().replace(/.*(\/|\\)/, '') );
    });


});

} (jQuery));
