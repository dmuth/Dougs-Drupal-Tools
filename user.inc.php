<?php
/**
* Code to hook the user event.
*
* @author Douglas Muth <http://www.dmuth.org/>
*/


/**
* Our user hook.
*/
function ddt_user($op, $edit, $user) {

	//
	// We only care about updates.
	//
	if ($op != "update") {
		return(null);
	}

	$uid = $user->uid;
	$var = array();
	$var["!uid"] = $uid;

	//
	// No status variable?  The user is probably editing themselves.  Stop.
	//
	if (!array_key_exists("status", $edit)) {
		$message = t("Updating user ID !uid. ");

	} else {
		//
		// If we're updating an account, note if the account is disabled or not.
		//
		$message = t("Updating user ID !uid. ");

		$status = $edit["status"];
		if ($status == 0) {
			$message .= t("Current status: blocked");

		} else {
			$message .= t("Current status: active.");

		}

	}

	$link = l("view", "user/" . $uid);

	ddt_log($message, $var, "", $link);

} // End of ddt_user()


