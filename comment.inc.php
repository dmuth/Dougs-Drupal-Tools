<?php
/**
* Our functions to hook comments.
*
* @author Douglas Muth <http://www.dmuth.org/>
*/


/**
* Hook activity on comments.
*/
function ddt_comment($comment, $op) {

	//
	// We only care about changes to the comment
	//
	if ($op != "delete" && $op != "insert" && $op != "update"
		&& $op != "publish" && $op != "unpublish"
		) {
		return(null);
	}

	$nid = "";
	$message = t("Op: %op ");
	$var = array();
	$var["%op"] = $op;

	//
	// $comment can be an array or an object based on what our operation is.
	//
	if ($op == "insert") {
		$message .= t("on CID %cid");
		$var["%cid"] = $comment["cid"];
		$nid = $comment["nid"];

	} else if ($op == "update") {
		$message .= t("on CID %cid");
		$var["%cid"] = $comment["cid"];
		$nid = $comment["nid"];

		if ($comment["status"] == 1) {
			$message .= t(" (Probably unpublishing.");
		}

	} else if ($op == "delete") {
		$message .= t("on CID %cid");
		$var["%cid"] = $comment->cid;
		$nid = $comment->nid;

	} else if ($op == "publish") {
		$message .= t("on CID %cid");
		$var["%cid"] = $comment["cid"];
		$nid = $comment["nid"];

	} else {
		//
		// catch-all in case something unexpected comes along.
		//
		$message .= t("UNKNOWN OP");

	}

	//
	// If we have a CID, set a link.
	//
	$link = "";
	if (!empty($var["%cid"])) {
		$options = array();
		$options["fragment"] = "comment-" . $var["%cid"];
		$link = l("view", "node/" . $nid, $options);
	}

	ddt_log($message, $var, "", $link);

} // End of ddt_comment()

