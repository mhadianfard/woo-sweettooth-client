function submitRedemption()
{
	/**
	 * Wordpress has generated an object for us with some php generated values in it:
	 * st_redemption_ajax_object
	 */
	var redemptionForm = jQuery('form#' + st_redemption_ajax_object.form_id);
	var selectedOption = jQuery('input[name=' + st_redemption_ajax_object.options_name + ']:checked', redemptionForm).val();
	var submitButton = redemptionForm.find('input[type=submit]');
	var pointsBalanceFields = jQuery('.' + st_redemption_ajax_object.balance_class + '_amount');
	
	submitButton.attr('disabled', 'disabled');
	submitButton.attr('data-old-value', submitButton.val());
	submitButton.val('Processing...');
	redemptionForm.animate({
		opacity: 0.25
	});
	
	jQuery.post(st_redemption_ajax_object.ajax_url, {
				action: st_redemption_ajax_object.ajax_action,
				selected: selectedOption
			},			
			function(responseString){
				var response = jQuery.parseJSON(responseString);
				if (!response.success) {
					alert(response.message);
					submitButton.removeAttr('disabled');
					submitButton.val(submitButton.attr('data-old-value'));
					submitButton.val('Processing...');
					
				} else {
					// @todo: CSS should go into a stylesheet.
					redemptionForm.html(
											"<div style='text-align: center; padding: 20px; background-color: #F8F8F8; margin: 10px;'>" +
												"Your redemption coupon code is: <br />" +
												"<strong style='font-size: 20px'>" + response.coupon_code + "<strong>" +
											"</div>"											
										);					
					pointsBalanceFields.html(response.new_balance);
				}
				redemptionForm.animate({
					opacity: 1
				});
			}
	);	
}