jQuery( function() {
	// Add action dinamycally
	jQuery( 'select[name="action"]' ).append( 
		jQuery( '<option/>' ).attr( 'value', 'sis-regenerate' ).text( sis.regenerate )
	);
	
	// Regenerate one element
	jQuery( '.sis-regenerate-one' ).click( function( e ) {
		e.preventDefault();
		new SISAttachRegenerate( this );
	});
	
	// On bulk actions
	jQuery( '#doaction' ).click( function( e ) {
		if( jQuery( this ).parent().find( 'select' ).val() == 'sis-regenerate' ) {
			// Get checked checkbocxes
			var els = jQuery( '.wp-list-table.media #the-list tr input[type="checkbox"]:checked' );
			
			// Check there is any elements selected
			if( els.length > 0 ) {
				
				// Stop default action
				e.preventDefault();
				
				// Make all the selected elements
				els.each( function( i,el ) {
					new SISAttachRegenerate( jQuery( this ) );
				} )
			}
		}
	} );
	
	// Function for regenerating the elements
	var SISAttachRegenerate = function( el ) {
		var regenerate = {
			list : '',
			percent : '' ,
			el : '',
			id : '',
			messageZone : '',
			init: function( el ) {
				this.el = jQuery( el );
				id = this.el.attr( 'id' );
				
				// IF no id found
				if( !id ) {
					id = this.el.closest( '.media-item' ).attr( 'id' );
					this.id = id.replace( 'media-item-', '' );
				} else {
					this.id = id.replace( 'post-', '' );
				}
				
				this.list = { 'id' : this.id, 'title' : 'titre' };
				
				if( this.el.find('.title em').size() == 0 )
					this.el.find('.title strong').after('<em/>');
				
				this.messageZone = this.el.find('.title em');
				
				if( !this.el.hasClass( 'ajaxing' ) )
					this.regenItem();
			},
			setMessage : function( msg ) {
				// Display the message
				this.messageZone.html( ' - '+ msg ).addClass( 'updated' ).addClass( 'fade' ).show();
			},
			regenItem : function( ) {
				var _self = this;
				var wp_nonce = jQuery('input.regen').val();
		
				jQuery.ajax( {
					url: sis.ajaxUrl,
					type: "POST",
					dataType: 'json',
					data: "action=sis_ajax_thumbnail_rebuild&do=regen&id=" + this.list.id+'&nonce='+wp_nonce,
					beforeSend : function() {
						_self.el.fadeTo( 'fast' ,'0.2' ).addClass('ajaxing');
					},
					success: function( r ) {
						var message ='';
						// Check if error or a message in response
						if( ( !r.src || !r.time ) || r.error || typeof r !== 'object' ) {
							if( typeof r !== 'object' )
								message = sis.phpError;
							else 
								message = r.error
						} else {
							message = sis.soloRegenerated.replace( '%s', r.time );
						}
						_self.setMessage( message );
						_self.el.fadeTo( 'fast' ,'1' ).removeClass('ajaxing');
					}
				});
			}
		}
		
		// Launch regeneration
		regenerate.init( jQuery( el ).closest( 'tr' ) );
	}
} );