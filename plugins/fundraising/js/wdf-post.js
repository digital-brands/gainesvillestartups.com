jQuery(document).ready( function($) {
	
	$('td.wdf_goal_start_date input[type="text"]').datepicker();
	$('td.wdf_goal_end_date input[type="text"]').datepicker();
	
	$('.wdf_level .wdf_check_switch').live('change', function(e) {
		if($(this).is(':checked')) {
			$(this).parents('.wdf_level').next('tr').find('div.wdf_reward_toggle').slideDown(400);
		} else {
			$(this).parents('.wdf_level').next('tr').find('div.wdf_reward_toggle').slideUp(400);
		}
	});

	//Delete Level Line Item
	$('.wdf_level.last .delete').live('click', function(e) {
		e.preventDefault();
		var reward = $(this).parents('tr.wdf_level.last').next('tr.wdf_reward_options');
		$(this).parents('tr.wdf_level.last').add(reward).remove();
		fixDelete();
		return false;
	});
	
	$('#tooltip_submit').live('click', function() {
		$('#publish').trigger('click');
		return false;
	});
	
	function fixDelete() {
		if($('#wdf_levels_table tbody .wdf_level').length < 2){
			return false;
		}
		$('#wdf_levels_table tbody .wdf_level').removeClass('last');
		$('#wdf_levels_table tbody .wdf_level:last').addClass('last');
		fixInputs();
	}
	
	//Fire fixDelete() on load
	fixDelete();
	
	$('#wdf_add_level').bind('click', function(e) {
		e.preventDefault();
		console.log($('#wdf_levels_table tr.wdf_level.last').find('input:first').attr('name'));
		var current = $('#wdf_levels_table tr.wdf_level.last').find('input:first').attr('name').substr(12,1);
		var newi = parseInt(current) + 1;
		$('#wdf_level_index').val(parseInt($('#wdf_level_index').val()) + 1);
		var template = $('tr[rel="wdf_level_template"]').clone().removeAttr('rel').show();
		var level = template.filter('tr:first').addClass('wdf_level');
		//Replace the name for all inputs with the appropriate index
		console.log($(template).find(':input'));
		$.each($(template).find(':input'), function(i,e) {
			console.log($(e));
			var rel = $(e).attr('rel');
			console.log(rel);
			$(e).attr('name',rel.replace('wdf[levels][','wdf[levels][' + String(newi)))
			$(e).removeAttr('rel');
		});
		$('tr[rel="wdf_level_template"]:first').before(template);
		fixDelete();
		return false;
	});
	
	$('.wdf_actvity_level').hover( function() {
		$(this).find('td:last a').show();
	}, function() {
		$(this).find('td:last a').hide();
	});
	$('.wdf_goal_progress').progressbar({
		value: 0,
		create: function() {
			$(this).progressbar( "option", "value", Math.round( parseInt( $(this).attr('total') * 100) ) / parseInt( $(this).attr('goal') ) );
		}
	});
	
	function fixInputs() {
		var input_switches = $('.wdf_input_switch');
		
		$.each(input_switches, function(i,elm) {
			if(elm.localName == 'textarea') {
				var current = $(elm).html();
			} else if(elm.localName == 'input') {
				var current = $(elm).val();
			}
			$(elm).bind('focusin focusout', function(e) {
				
				if(e.type == 'focusout') {
					$(elm).prev('.wdf_bignum').addClass('wdf_disabled');
				} else {
					$(elm).prev('.wdf_bignum').removeClass('wdf_disabled');
				}
				
				/*if($(elm).hasClass('active')) {
					if(elm.localName == 'input') {
						$(elm).parents('form').bind('submit', function() { $(elm).val(''); });
					} else if (elm.localName == 'textarea') {
						$(elm).parents('form').bind('submit', function() { $(elm).html(''); });
					}
				}
				
				if(elm.localName == 'textarea') {
					var now = $(elm).html();
					if(current == now && e.type == 'focusin') {
						if(!$(elm).hasClass('active'))
							$(elm).html('');
					} else if(now == '' && e.type == 'focusout') {
						if(!$(elm).hasClass('active'))
							$(elm).html(current);
					}
				} else if(elm.localName == 'input') {
					var now = $(this).val();
					if(current == now && e.type == 'focusin') {
						if(!$(elm).hasClass('active'))
							$(elm).val('');
					} else if(e.type == 'focusout' && now == '') {
						if(!$(elm).hasClass('active'))
							$(elm).val(current);
					}
					
				}*/
			});
		});
	}
	//run fix_inputs() on load
	fixInputs();
	
	$('select.wdf_toggle').bind('change', function(e) {
		var rel = $(this).attr('rel');
		var val = $(this).val();
		
		if(rel == 'wdf_has_goal' && val == '1') {
			var elm = $('*[rel="'+rel+'"]').not(this);
			elm.show();
		} else if(rel == 'wdf_thanks_type') {
			$('*[rel="'+rel+'"]').not(this).hide(1, function() {
				$('.wdf_thanks_'+val+'[rel="'+rel+'"]').show();
			});
		} else if(rel == 'wdf_has_goal' && val == '0') {
			var elm = $('*[rel="'+rel+'"]').not(this);
			elm.hide();
		} else if(rel == 'wdf_send_email')  {
			if(val == '1')
				$('*[rel="'+rel+'"]').not(this).show();
			else
				$('*[rel="'+rel+'"]').not(this).hide();
		} else if(rel == 'wdf_recurring')  {
			if(val == 'yes')
				$('*[rel="'+rel+'"]').not(this).show();
			else
				$('*[rel="'+rel+'"]').not(this).hide();
		}
	});
	
});