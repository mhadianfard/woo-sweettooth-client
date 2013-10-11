function submitRedemption()
{
	/**
	 * Wordpress has generated an object for us with some php generated values in it:
	 * st_redemption_ajax_object
	 */
	var redemptionForm = jQuery('form#' + st_redemption_ajax_object.form_id);
	var selectedOption = jQuery('input[name=' + st_redemption_ajax_object.options_name + ']:checked').val();		
	
	jQuery.post(st_redemption_ajax_object.ajax_url, {
		action: st_redemption_ajax_object.ajax_action,
		selected: selectedOption
	}, function(response){
		console.log(response);
	});	
}