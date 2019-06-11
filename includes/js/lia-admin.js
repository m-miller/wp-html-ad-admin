
// listen for check in rotation column
// if checked, ajax LIA_save_postmeta

jQuery( document ).ready(function() {
	
	jQuery("[id^=ad-rotate-]").click(function() {
		// now get post id from end of div ID
		var postid=event.target.id.slice(10);
			//console.log('Post ID: '+ event.target.id.slice(10));
	    var checked = jQuery(this).is(':checked');
		var unchecked = !checked;

	    jQuery.ajax({
	        type: "POST",
	        url: 'admin-ajax.php',
	        data: { checked : checked,
					postid: postid,
				 	action: 'LIA_save_postmeta'},
			success: function() {
				if (checked) {
					jQuery('#ad-rotate-'+postid).after('<label style="color:red; margin-left:5px;" for="ad-rotate-'+postid+'">Added!</label>');
					jQuery('label[for="ad-rotate-'+postid+'"]').fadeOut(700);
				}
				if (unchecked){
					jQuery('#ad-rotate-'+postid).after('<label style="color:red; margin-left:5px;" for="ad-rotate-'+postid+'">Removed!</label>');
					jQuery('label[for="ad-rotate-'+postid+'"]').fadeOut(700);
				}
				},
	        error: function() {
	            console.log('oopsie!');
	        },
	    });
	});
	
	

});