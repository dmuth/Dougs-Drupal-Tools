<?php
/**
* Our main settings page.
*
* @author Douglas Muth <http://www.dmuth.org/>
*
*/


/**
* Our settings form.
*
* @return array An array of form data
*/
function ddt_settings_form($form_state) {

	$retval = array();
	$ddt_num_messages = $GLOBALS["ddt_num_messages"];

	$retval["settings"] = array(
		"#type" => "fieldset",
		"#title" => t("DDT Settings"),
		);

	$retval["settings"]["search_google"] = array(
		"#type" => "checkbox",
		"#title" => t("Redirect to Google Search"),
		"#description" => t("If the Google Search module is installed, check this to redirect <b>/search</b> to <b>/search/google</b>"),
		"#default_value" => variable_get("ddt_search_google", false),
		);

	if (module_exists("chatroom")) {

		$retval["settings"]["chat_delete"] = array(
			"#type" => "checkbox",
			"#title" => t("Delete all but last $ddt_num_messages chat messages"),
			"#description" => t("If checked, all but the last $ddt_num_messages chat messages will be deleted during cron runs."),
			"#default_value" => variable_get("ddt_chat_delete", false),
			);

	}

	$retval["settings"]["spam_bio"] = array(
		"#type" => "checkbox",
		"#title" => t("Prevent spam in the user's bio"),
		"#description" => t("If checked, we don't allow uers to have excessive URLs in their bio"),
		"#default_value" => variable_get("ddt_spam_bio", false),
		);

	$retval["settings"]["variable_trim"] = array(
		"#type" => "checkbox",
		"#title" => t("Trim captcha_placement_map_cache variable"),
		"#description" => t("The captcha_placement_map_cache variable "
			. "grows without bound. Delete it when it grows past 10 K."),
		"#default_value" => variable_get("variable_trim", false),
		);

	$retval["settings"]["submit"] = array(
		"#type" => "submit",
		"#value" => "Save",
		);

	return($retval);

} // End of ddt_settings_form()


function ddt_settings_form_validate($form, $form_state) {
}


/**
* Our form submission handler.
*/
function ddt_settings_form_submit($form, $form_state) {

	$values = $form_state["values"];

	$search_google = $values["search_google"];
	$chat_delete = $values["chat_delete"];
	$spam_bio = $values["spam_bio"];
	$variable_trim = $values["variable_trim"];
	variable_set("ddt_search_google", $search_google);
	variable_set("ddt_chat_delete", $chat_delete);
	variable_set("ddt_spam_bio", $spam_bio);
	variable_set("variable_trim", $variable_trim);

	drupal_set_message("Settings updated!");

} // End of ddt_settings_form_submit()




