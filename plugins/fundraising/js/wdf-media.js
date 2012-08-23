function wdf_inject_shortcode () {
	
	
	var iFrame = jQuery('#TB_iframeContent').contents();
	var cont = iFrame.find('.wdf_media_cont:first');
	var type = cont.attr('id');
	var form = cont.serializeArray();

	switch(type) {
		case 'media_fundraising' :
			var funder_select = iFrame.find('#wdf_funder_select');
			
			var shortcode = '[fundraiser';
		
			jQuery.each(form, function(i,e) { 
				shortcode = shortcode + ' '+e.name+'="'+e.value+'"';
			});
			
			shortcode = shortcode + ']';
			
			window.parent.tinyMCE.execCommand("mceInsertContent", true, shortcode);
			window.parent.tb_remove();
		break;
		
		case 'media_donate_button' :
			var shortcode = '[donate_button';
			jQuery.each(form, function(i,e) { 
				shortcode = shortcode + ' '+e.name+'="'+e.value+'"';
			});
			shortcode = shortcode + ']';
			window.parent.tinyMCE.execCommand("mceInsertContent", true, shortcode);
			window.parent.tb_remove();
		break;		
		
	}
}
function input_switch() {
	console.log(this,event);
}