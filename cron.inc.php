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

	$chat_delete = variable_get("ddt_chat_delete", false);
	if ($chat_delete) {

		$query = "SELECT "
			. "max(cmid) AS cmid "
			. "FROM {chatroom_msg}"
			;
		$cursor = db_query($query);
		$row = db_fetch_array($cursor);
		$cmid = $row["cmid"];
		$cmid = $cmid - 1000;
		$cmid = 1; // Debugging
		if ($cmid > 0) {
			$message = t("Deleting all chatroom messages with ID <= %id",
				array("%id" => $cmid));
			ddt_log($message);
			$query = "DELETE FROM {chatroom_msg} "
				. "WHERE "
				. "cmid <= '%s' "
				;
			$query_args = array($cmid);
			db_query($query, $query_args);
		}

	}

} // End of ddt_cron()


