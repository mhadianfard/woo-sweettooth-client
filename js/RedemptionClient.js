function submitRedemption()
{
	/**
	 * Wordpress has generated an object for us with some php generated values in it:
	 * st_redemption_ajax_object
	 */
	var redemptionForm = jQuery('form#' + st_redemption_ajax_object.form_id);
	var selectedOption = jQuery('input[name=' + st_redemption_ajax_object.options_name + ']:checked', redemptionForm).val();
	var submitButton = redemptionForm.find('input[type=submit]');
	
	submitButton.attr('disabled', 'disabled');
	
	jQuery.post(st_redemption_ajax_object.ajax_url, {
		action: st_redemption_ajax_object.ajax_action,
		selected: selectedOption
	}, function(responseString){
		var response = jQuery.parseJSON(responseString);
		if (!response.success) {
			alert(response.message);
		} else {
			redemptionForm.html("Your redemption coupon code is: <br /><strong>" + response.couponCode + "<strong>");
		}
	});	
}