
fieldSettings.square = '.conditional_logic_field_setting, .prepopulate_field_setting, .error_message_setting, .label_setting, .label_placement_setting, .admin_label_setting, .size_setting, .visibility_setting, .duplicate_setting, .default_value_setting, .placeholder_setting, .description_setting, .css_class_setting, .cardholder_name_setting';

console.log(fieldSettings);
jQuery(document).bind("gform_load_field_settings", function(event, field, form) {
    //console.log(field);
    //console.log(form);

    if (field.type == 'square') {

        //cardholder field value
        if (typeof field["card_num"] !== "undefined") {
            //alert(field["cardholder_name_label"]);
            jQuery("#card_num").attr("value", field["card_num"]);
        } else {
            jQuery("#card_num").attr("value", '');
        }

        if (typeof field["card_exp"] !== "undefined") {
            //alert(field["cardholder_name_label"]);
            jQuery("#card_exp").attr("value", field["card_exp"]);
        } else {
            jQuery("#card_exp").attr("value", '');
        }

        if (typeof field["card_cvv"] !== "undefined") {
            //alert(field["cardholder_name_label"]);
            jQuery("#card_cvv").attr("value", field["card_cvv"]);
        } else {
            jQuery("#card_cvv").attr("value", '');
        }

        if (typeof field["card_zip"] !== "undefined") {
            //alert(field["cardholder_name_label"]);
            jQuery("#card_zip").attr("value", field["card_zip"]);
        } else {
            jQuery("#card_zip").attr("value", '');
        }

        if (typeof field["card_name"] !== "undefined") {
            //alert(field["cardholder_name_label"]);
            jQuery("#card_name").attr("value", field["card_name"]);
        } else {
            jQuery("#card_name").attr("value", '');
        }
    }
});
