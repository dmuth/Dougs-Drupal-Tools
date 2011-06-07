<?php
/**
* Our cron hooks.
*
* @author Douglas Muth <http://www.dmuth.org/>
*/


/**
* Our crontab, which is run periodically.
* Right now, it just compares variables, so we can see if any of our admins
*	have changed things since the last run.
*/
function ddt_cron() {

	$old_vars = ddt_get_vars();
	$vars = variable_init();

	if (empty($old_vars)) {
		$message = t("No stored variables found.");
		ddt_log($message);

	} else {
		$fields = ddt_get_changed_vars($old_vars, $vars);
		ddt_log_changed_vars($fields);

	}

	//
	// Store our variables for the next pass.
	//
	ddt_put_vars($vars);

} // End of ddt_cron()


