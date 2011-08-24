<?php
/**
* This file holds code that handle spam-related issues.
*
* @author Douglas Muth <http://www.dmuth.org/>
*/


/**
* This function is used to add our custom validation function to the 
* user profile form.
*/
function ddt_form_alter($form, $form_state, $form_id) {

	if (!variable_get("ddt_spam_bio", "")) {
		return(null);
	}

	if ($form_id == "user_profile_form" || $form_id == "user_register") {
		$form["#validate"][] = "ddt_spam_profile_validate";
	}

} // End of ddt_form_alter()


/**
* This form is used to validate our profile for spammy submissions.
*/
function ddt_spam_profile_validate($form, $form_state) {

	$values = $form_state["values"];
	$key = "profile_biography";
	$max_links = 5;

	if ($values[$key]) {

		$bio = $values[$key];
		$regexp = "|http://|";
		$num_results = preg_match_all($regexp, $bio, $results);

		if ($num_results > $max_links) {
			$error = t("Sorry, but your bio seems to have tripped our spam filter. Please check your links and try again!");
			form_set_error($key, $error);

			$message = t("Blocked bio with more than %num URLs. (Error showed to user: %error)");
			$vars = array(
				"%num" => $num_results,
				"%error" => $error,
				);
			ddt_log($message, $vars, WATCHDOG_ERROR);
		}
	
	}

} // Endof ddt_spam_profile_validate()


