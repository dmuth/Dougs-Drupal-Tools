<?php
/**
* Our main settings page.
*
* @author Douglas Muth <http://www.dmuth.org/>
*
*/


/**
* Our main settings page.
*
* @param string $arg, $arg2, $arg3 Optional page arguments
*
* @return string HTML code of the page
*/
function ddt_settings($arg = "", $arg2 = "", $arg3 = "") {

	$retval = "";

	$retval .= drupal_get_form("ddt_settings_form");

	return($retval);

} // End of ddt_settings()


/**
* Our settings form.
*
* @return array An array of form data
*/
function ddt_settings_form($form_state) {

	$retval = array();

	$retval["settings"] = array(
		"#type" => "fieldset",
		"#title" => t("DDT Settings"),
		);

	$retval["settings"]["search_google"] = array(
		"#type" => "checkbox",
		"#title" => t("Redirect to Google Search"),
		"#description" => t("If the Google Search module is installed, check this to redirect /search to /search/google"),
		"#default_value" => variable_get("ddt_search_google", false),
		);

	$retval["settings"]["chat_delete"] = array(
		"#type" => "checkbox",
		"#title" => t("Delete all but last 1000 chat messages"),
		"#description" => t("If checked, all but the last 1000 chat messages will be deleted during cron runs."),
		"#default_value" => variable_get("ddt_chat_delete", false),
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
	variable_set("ddt_search_google", $search_google);
	variable_set("ddt_chat_delete", $chat_delete);

} // End of ddt_settings_form_submit()




