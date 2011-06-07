<?php
/**
* Our code to hook flag operations.
*
* @author Douglas Muth <http://www.dmuth.org/>
*/


/**
* This function is fired whenever a node or comment is flagged.
*
* @param string $action This is "flag" or "unflag"
*
* @param object $flag The flag object.  We check this to determine if a 
*	node or comment was flagged.
*
* @param integer $id The NID or CID that was flagged.
*/
function ddt_flag($action, $flag, $id) {

	$name = get_class($flag);

	if ($name == "flag_node") {

		$nid = $id;
		$var = array();
		$var["%nid"] = $nid;
		$link = l("view", "node/" . $nid);

		if ($action == "flag") {
			$message = t("Node ID %nid flagged.");
		} else {
			$message = t("Node ID %nid unflagged.");
		}

	} else if ($name == "flag_comment") {

		$cid = $id;
		$var = array();
		$var["%cid"] = $cid;

		//
		// Find the NID based on the comment
		//
		$query = "SELECT nid FROM {comments} WHERE cid=%d";
		$cursor = db_query($query, $cid);
		$row = db_fetch_array($cursor);
		$nid = $row["nid"];

		//
		// Create a link to that specific comment.
		//
		$options = array();
		$options["fragment"] = "comment-" . $cid;
		$link = l("view", "node/" . $nid, $options);

		if ($action == "flag") {
			$message = t("Comment ID %cid flagged.");
		} else {
			$message = t("Comment ID %cid unflagged.");
		}

	} else {
		//
		// If we get something unexpected, log this and stop.
		//
		$message = "Unknown classname: %name (ID: %id)";
		$var = array();
		$var["%name"] = $name;
		$var["%id"] = $id;
		ddt_log($message, $var);
		return(null);
	}

	ddt_log($message, $var, "", $link);

} // End of ddt_flag()


