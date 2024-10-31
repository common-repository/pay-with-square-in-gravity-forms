var totalAmount = 0;
var wcSquarePaymentForm = {};

jQuery('form#gform_' + gfsqs.form_id).append('<input type="hidden" class="application_id" value="' + gfsqs.application_id + '" />');
jQuery('form#gform_' + gfsqs.form_id).append('<input type="hidden" class="location_id" value="' + gfsqs.location_id + '" />');
jQuery('form#gform_' + gfsqs.form_id).append('<input type="hidden" class="currency_charge" value="' +
    gfsqs.currency_charge + '" />');
jQuery('form#gform_' + gfsqs.form_id).append('<input type="hidden" class="form_id" value="' + gfsqs.form_id + '" />');

function run_gravity_square(id) {
    let me;
    var application_id = jQuery('form#gform_' + id + ' .application_id').val();
    var location_id = jQuery('form#gform_' + id + ' .location_id').val();
    var currency_charge = jQuery('form#gform_' + id + ' .currency_charge').val();
    var currency_symbol;
    switch (currency_charge) {
        case 'USD':
            currency_symbol = 'US';
            break;
        case 'CAD':
            currency_symbol = 'CA';
            break;
        case 'GBP':
            currency_symbol = 'GB';
            break;
        case 'AUD':
            currency_symbol = 'AU';
            break;
          case 'EUR':
            currency_symbol = 'IE';
            break;    
            
        case 'JPY':
            currency_symbol = 'JP';
            break;
    }

    if (gfsqs.fname != '') {
        var billing_fname = gfsqs.fname;
    } else {
        var billing_fname = "Jane";
    }

    if (gfsqs.lname != '') {
        var billing_lname = gfsqs.lname;
    } else {
        var billing_lname = "Doe";
    }

    if (gfsqs.email != '') {
        var billing_email = gfsqs.email;
    } else {
        var billing_email = "john.doe@gmail.com";
    }

    wcSquarePaymentForm[id] = new SqPaymentForm({
        applicationId: application_id,
        locationId: location_id,
        inputClass: 'gfsq-input',
        inputStyles: jQuery.parseJSON(gfsqs.payment_form_input_styles),
        cardNumber: {
            elementId: 'gfsq-card-number-' + id,
            placeholder: gfsqs.placeholder_card_number
        },
        cvv: {
            elementId: 'gfsq-cvv-' + id,
            placeholder: gfsqs.placeholder_card_cvv
        },
        expirationDate: {
            elementId: 'gfsq-expiration-date-' + id,
            placeholder: gfsqs.placeholder_card_expiration
        },
        postalCode: {
            elementId: 'gfsq-postal-code-' + id,
            placeholder: gfsqs.placeholder_card_postal_code
        },
        callbacks: {
            cardNonceResponseReceived: function(errors, nonce, cardData) {
                gfsqs_pay_from_nonce(errors, nonce, id, currency_charge, billing_fname, billing_lname);
            },
            createPaymentRequest: function() {
                var totalAmount = 0;
                totalAmount = jQuery('.ginput_container_total').find('input.gform_hidden').val();
                totalAmount = parseFloat(totalAmount).toFixed(2);
                if (totalAmount <= 0 || isNaN(totalAmount)) {
                    alert('total amount must be greater than 0');
                    return false;
                } else {

                    var paymentRequestJson = {
                        requestShippingAddress: true,
                        requestBillingInfo: true,
                        shippingContact: {
                            familyName: billing_lname,
                            givenName: billing_fname,
                            email: billing_email,
                        },
                        countryCode: currency_symbol,
                        currencyCode: currency_charge,
                        total: {
                            label: billing_fname + " " + billing_lname,
                            amount: totalAmount.toString(),
                            pending: false
                        },
                    };

                    return paymentRequestJson;
                }
            },
            inputEventReceived: function(inputEvent) {
                switch (inputEvent.eventType) {
                    case 'focusClassAdded':
                        /* HANDLE AS DESIRED */
                        if (inputEvent.field == "cvv") {
                            jQuery('#' + inputEvent.elementId).parent('.element-toRight').siblings('.element-toLeft').find('.gfsq-ccard-container .gfsq-card').addClass('rotate');
                        } else {
                            jQuery('#' + inputEvent.elementId).parent('.element-toRight').siblings('.element-toLeft').find('.gfsq-ccard-container .gfsq-card').removeClass('rotate');
                            jQuery('#' + inputEvent.elementId).parent('.element-toLeft').find('.gfsq-ccard-container .gfsq-card').removeClass('rotate');
                        }
                        //console.log(inputEvent);
                        jQuery('#' + inputEvent.elementId).siblings('.gfsq-ccard-container').find('.gfsq-front').attr('class', 'gfsq-front');
                        jQuery('#' + inputEvent.elementId).siblings('.gfsq-ccard-container').find('.gfsq-back').attr('class', 'gfsq-back');

                    case 'cardBrandChanged':
                        /* HANDLE AS DESIRED */
                        jQuery('#' + inputEvent.elementId).siblings('.gfsq-ccard-container').find('.gfsq-front').attr('class', 'gfsq-front');
                        jQuery('#' + inputEvent.elementId).siblings('.gfsq-ccard-container').find('.gfsq-back').attr('class', 'gfsq-back');
                        //console.log(inputEvent);
                        var cardType = inputEvent.cardBrand
                        jQuery('#' + inputEvent.elementId).siblings('.gfsq-ccard-container').find('.gfsq-front').addClass(cardType);
                        jQuery('#' + inputEvent.elementId).siblings('.gfsq-ccard-container').find('.gfsq-back').addClass(cardType);
                }
            }
        }
    });
    if (jQuery('#gfsq-card-number-' + id).length > 0) {
        wcSquarePaymentForm[id].build();
    }

    jQuery('form#gform_' + id + ' .sqgf_square_nonce').remove();

} // end of run_gravity_square function

function gfsqs_pay_from_nonce(errors, nonce, id, currency_charge, billing_fname, billing_lname) {

    if (errors) {
        var html = '';

        html += '<ul class="sqgf-errors gfield_error">';

        // handle errors
        jQuery(errors).each(function(index, error) {
            html += '<li class="gfield_description validation_message">' + error.message + '</li>';
        });
		jQuery('#gform_submit_button_' + id).prop('disabled', false);
        html += '</ul>';

        // append it to DOM
        jQuery('form#gform_' + id).find('.messages').html(html);

    } else {

        if (jQuery.trim(nonce) && typeof nonce != 'undefined') {

            totalAmount = jQuery('#gform_' + id + ' .ginput_container_total .gform_hidden').val();

            totalAmount = parseFloat(totalAmount).toFixed(2);
            const verificationDetails = {
                intent: 'CHARGE',
                amount: totalAmount.toString(),
                currencyCode: currency_charge,
                billingContact: {
                    familyName: billing_lname,
                    givenName: billing_fname
                }
            };

            wcSquarePaymentForm[id].verifyBuyer(
                nonce,
                verificationDetails,
                function(err, verificationResult) {
                    if (err == null) {
                        jQuery('.gform_wrapper form#gform_' + id).find('.gform_body').append('<input type="hidden" class="sqgf_square_nonce" name="sqgf_square_nonce" value="' + nonce + '" />');
                        jQuery('.gform_wrapper form#gform_' + id).find('.gform_body').append('<input type="hidden" class="sqgf_square_nonce" name="sqgf_square_verify" value="' + verificationResult.token + '" />');
                        
                        jQuery('#gform_submit_button_' + id).prop('disabled', false);
                        jQuery('.gform_wrapper form#gform_' + id).submit();
                        jQuery('#gform_submit_button_' + id).prop('disabled', true);
                    }
                }
            );
        } else {
            jQuery('#gform_submit_button_' + id).prop('disabled', false);
            var html = '';
            html += '<ul class="sqgf-errors gfield_error">';
            // handle errors
            html += '<li class="gfield_description validation_message">Credit card nonce not found contact system admin</li>';
            html += '</ul>';
            // append it to DOM
            jQuery('.gform_wrapper form#gform_' + id).find('.messages').html(html);
            return false;

        }
    }
}

jQuery(document).ready(function($) {
	
/*var totalAmount = 0;

jQuery('form#gform_' + gfsqs.form_id).append('<input type="text" class="application_id" value="' + gfsqs.application_id + '" />');
jQuery('form#gform_' + gfsqs.form_id).append('<input type="text" class="location_id" value="' + gfsqs.location_id + '" />');
jQuery('form#gform_' + gfsqs.form_id).append('<input type="text" class="currency_charge" value="' +
    gfsqs.currency_charge + '" />');
jQuery('form#gform_' + gfsqs.form_id).append('<input type="text" class="form_id" value="' + gfsqs.form_id + '" />');*/

    jQuery('.gform_wrapper .gform_button').on('click', function(event) {
        event.preventDefault();

        me = jQuery(this);
        parent = me.parents('form');
        id = parent.find('.form_id').val();
        if (me.closest('form').find('#gfsq-card-number-' + id).length > 0) {
            if (jQuery(parent).find('.gf_sqquare_container').is(':visible')) {
                // remove any error messages first
                jQuery('form#gform_' + id + ' .messages').find('.sqgf-errors').remove();
                me.prop('disabled', true);
                wcSquarePaymentForm[id].requestCardNonce();
                return false;
            } else {
                me.closest('.gform_wrapper form').submit();
            }
        }			
		
    });

});

jQuery(document).on('gform_post_render', function(event, form_id, current_page) {
	
	if ( jQuery('.application_id').length <= 0 ) {
		var totalAmount = 0;

jQuery('form#gform_' + gfsqs.form_id).append('<input type="hidden" class="application_id" value="' + gfsqs.application_id + '" />');
jQuery('form#gform_' + gfsqs.form_id).append('<input type="hidden" class="location_id" value="' + gfsqs.location_id + '" />');
jQuery('form#gform_' + gfsqs.form_id).append('<input type="hidden" class="currency_charge" value="' +
    gfsqs.currency_charge + '" />');
jQuery('form#gform_' + gfsqs.form_id).append('<input type="hidden" class="form_id" value="' + gfsqs.form_id + '" />');
	}

    var isHidden = document.getElementById("gform_wrapper_" + form_id).style.display == "none";

    if (isHidden) {
        jQuery('#gform_wrapper_' + form_id).show();
    }

    if (jQuery('#gform_wrapper_' + form_id + ' .application_id').length > 0) {
        setTimeout(function() {
            run_gravity_square(form_id);
        }, 1000);
    }

    if (gfsqs.fname != '') {
        jQuery('#gform_fields_' + form_id + ' .name_first').find('input').val(gfsqs.fname);
    }

    if (gfsqs.lname != '') {
        jQuery('#gform_fields_' + form_id + ' .name_last').find('input').val(gfsqs.lname);
    }

    if (gfsqs.email != '') {
        jQuery('#gform_fields_' + form_id + ' .ginput_container_email').find('input').val(gfsqs.email);
    }


    if (jQuery('#gform_wrapper_' + form_id).hasClass('gform_validation_error')) {
        jQuery('html, body').animate({
            scrollTop: jQuery('#gform_wrapper_' + form_id).offset().top
        }, 2000);
		wcSquarePaymentForm[id].destroy();
		run_gravity_square(form_id);
    }

});