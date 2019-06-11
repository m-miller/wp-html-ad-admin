jQuery(document).ready(function() {
	
	var newFrame,
		metaBox = jQuery( '#link_loginads_section.postbox' ),
		dnlink = metaBox.find( '#img-src' );
		
		jQuery('#liastdate, #liaenddate').datepicker();
			
	  dnlink.on( 'click', function( event ){

	    event.preventDefault();

	    // Create the media frame.
	    newFrame = wp.media({
	      title: 'Add link',
	      button: { text: 'Use this one!' },
	      multiple: false  // Set to true to allow multiple files to be selected
	    });

	    newFrame.on( 'select', function() {
	      attachment = newFrame.state().get( 'selection' ).first().toJSON();
		  dnlink.val( attachment.url ) ;
	    });

	   newFrame.open();
	  });

});