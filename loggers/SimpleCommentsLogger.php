<?php

/**
 * Logs things related to comments
 */
class SimpleCommentsLogger extends SimpleLogger
{

	public $slug = __CLASS__;

	function __construct($sh) {

		parent::__construct($sh);

	}

	/**
	 * Get array with information about this logger
	 *
	 * @return array
	 */
	function getInfo() {

		$arr_info = array(
			"name" => "Comments Logger",
			"description" => "Logs comments, and modifications to them",
			"capability" => "moderate_comments",
			"messages" => array(

				// Comments
				'anon_comment_added' => _x(
					'Added a comment to {comment_post_type} "{comment_post_title}"',
					'A comment was added to the database by a non-logged in internet user',
					'simple-history'
				),

				'user_comment_added' => _x(
					'Added a comment to {comment_post_type} "{comment_post_title}"',
					'A comment was added to the database by a logged in user',
					'simple-history'
				),

				'comment_status_approve' => _x(
					'Approved a comment to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A comment was approved',
					'simple-history'
				),

				'comment_status_hold' => _x(
					'Unapproved a comment to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A comment was was unapproved',
					'simple-history'
				),

				'comment_status_spam' => _x(
					'Marked a comment to post "{comment_post_title}" as spam',
					'A comment was marked as spam',
					'simple-history'
				),

				'comment_status_trash' => _x(
					'Trashed a comment to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A comment was marked moved to the trash',
					'simple-history'
				),

				'comment_untrashed' => _x(
					'Restored a comment to "{comment_post_title}" by {comment_author} ({comment_author_email}) from the trash',
					'A comment was restored from the trash',
					'simple-history'
				),

				'comment_deleted' => _x(
					'Deleted a comment to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A comment was deleted',
					'simple-history'
				),

				'comment_edited' => _x(
					'Edited a comment to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A comment was edited',
					'simple-history'
				),

				// Trackbacks
				'anon_trackback_added' => _x(
					'Added a trackback to {comment_post_type} "{comment_post_title}"',
					'A trackback was added to the database by a non-logged in internet user',
					'simple-history'
				),

				'user_trackback_added' => _x(
					'Added a trackback to {comment_post_type} "{comment_post_title}"',
					'A trackback was added to the database by a logged in user',
					'simple-history'
				),

				'trackback_status_approve' => _x(
					'Approved a trackback to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A trackback was approved',
					'simple-history'
				),

				'trackback_status_hold' => _x(
					'Unapproved a trackback to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A trackback was was unapproved',
					'simple-history'
				),

				'trackback_status_spam' => _x(
					'Marked a trackback to post "{comment_post_title}" as spam',
					'A trackback was marked as spam',
					'simple-history'
				),

				'trackback_status_trash' => _x(
					'Trashed a trackback to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A trackback was marked moved to the trash',
					'simple-history'
				),

				'trackback_untrashed' => _x(
					'Restored a trackback to "{comment_post_title}" by {comment_author} ({comment_author_email}) from the trash',
					'A trackback was restored from the trash',
					'simple-history'
				),

				'trackback_deleted' => _x(
					'Deleted a trackback to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A trackback was deleted',
					'simple-history'
				),

				'trackback_edited' => _x(
					'Edited a trackback to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A trackback was edited',
					'simple-history'
				),

				// Pingbacks
				'anon_pingback_added' => _x(
					'Added a pingback to {comment_post_type} "{comment_post_title}"',
					'A trackback was added to the database by a non-logged in internet user',
					'simple-history'
				),

				'user_pingback_added' => _x(
					'Added a pingback to {comment_post_type} "{comment_post_title}"',
					'A pingback was added to the database by a logged in user',
					'simple-history'
				),

				'pingback_status_approve' => _x(
					'Approved a pingback to "{comment_post_title}" by "{comment_author}"" ({comment_author_email})',
					'A pingback was approved',
					'simple-history'
				),

				'pingback_status_hold' => _x(
					'Unapproved a pingback to "{comment_post_title}" by "{comment_author}" ({comment_author_email})',
					'A pingback was was unapproved',
					'simple-history'
				),

				'pingback_status_spam' => _x(
					'Marked a pingback to post "{comment_post_title}" as spam',
					'A pingback was marked as spam',
					'simple-history'
				),

				'pingback_status_trash' => _x(
					'Trashed a pingback to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A pingback was marked moved to the trash',
					'simple-history'
				),

				'pingback_untrashed' => _x(
					'Restored a pingback to "{comment_post_title}" by {comment_author} ({comment_author_email}) from the trash',
					'A pingback was restored from the trash',
					'simple-history'
				),

				'pingback_deleted' => _x(
					'Deleted a pingback to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A pingback was deleted',
					'simple-history'
				),

				'pingback_edited' => _x(
					'Edited a pingback to "{comment_post_title}" by {comment_author} ({comment_author_email})',
					'A pingback was edited',
					'simple-history'
				),

			), // end messages

			"labels" => array(

				"search" => array(
					"label" => _x("Comments", "Comments logger: search", "simple-history"),
					"label_all" => _x("All comments activity", "Comments logger: search", "simple-history"),
					"options" => array(
						_x("Added comments", "Comments logger: search", "simple-history") => array(
							"anon_comment_added",
							"user_comment_added",
							"anon_trackback_added",
							"user_trackback_added",
							"anon_pingback_added",
							"user_pingback_added"
						),
						_x("Edited comments", "Comments logger: search", "simple-history") => array(
							"comment_edited",
							"trackback_edited",
							"pingback_edited"
						),
						_x("Approved  comments", "Comments logger: search", "simple-history") => array(
							"comment_status_approve",
							"trackback_status_approve",
							"pingback_status_approve"
						),
						_x("Held comments", "Comments logger: search", "simple-history") => array(
							"comment_status_hold",
							"trackback_status_hold",
							"pingback_status_hold"
						),
						_x("Comments status changed to spam", "Comments logger: search", "simple-history") => array(
							"comment_status_spam",
							"trackback_status_spam",
							"pingback_status_spam"
						),
						_x("Trashed comments", "Comments logger: search", "simple-history") => array(
							"comment_status_trash",
							"trackback_status_trash",
							"pingback_status_trash"
						),
						_x("Untrashed comments", "Comments logger: search", "simple-history") => array(
							"comment_untrashed",
							"trackback_untrashed",
							"pingback_untrashed"
						),
						_x("Deleted comments", "Comments logger: search", "simple-history") => array(
							"comment_deleted",
							"trackback_deleted",
							"pingback_deleted"
						),
					)
				) // end search

			) // labels

		);

		return $arr_info;

	}

	public function loaded() {

		/**
		 * Fires immediately after a comment is inserted into the database.
		 */
		add_action( 'comment_post', array( $this, 'on_comment_post'), 10, 2 );

		/**
		 * Fires after a comment status has been updated in the database.
		 * The hook also fires immediately before comment status transition hooks are fired.
		 */
		add_action( "wp_set_comment_status", array( $this, 'on_wp_set_comment_status'), 10, 2 );

		/**
		 *Fires immediately after a comment is restored from the Trash.
		 */
		add_action( "untrashed_comment", array( $this, 'on_untrashed_comment'), 10, 1 );

 		/**
 		 * Fires immediately before a comment is deleted from the database.
 		 */
		add_action( "delete_comment", array( $this, 'on_delete_comment'), 10, 1 );

		/**
		 * Fires immediately after a comment is updated in the database.
	 	 * The hook also fires immediately before comment status transition hooks are fired.
	 	 */
		add_action( "edit_comment", array( $this, 'on_edit_comment'), 10, 1 );


	}

	/**
	 * Get comments context
	 *
	 * @param int $comment_ID
	 * @return mixed array with context if comment found, false if comment not found
	 */
	public function get_context_for_comment($comment_ID) {

		// get_comment passes comment_ID by reference, so it can be unset by that function
		$comment_ID_original = $comment_ID;
		$comment_data = get_comment( $comment_ID );

		if ( is_null( $comment_data ) ) {
			return false;
		}

		$comment_parent_post = get_post( $comment_data->comment_post_ID );

		$context = array(
			"comment_ID" => $comment_ID_original,
			"comment_author" => $comment_data->comment_author,
			"comment_author_email" => $comment_data->comment_author_email,
			"comment_author_url" => $comment_data->comment_author_url,
			"comment_author_IP" => $comment_data->comment_author_IP,
			"comment_content" => $comment_data->comment_content,
			"comment_approved" => $comment_data->comment_approved,
			"comment_agent" => $comment_data->comment_agent,
			"comment_type" => $comment_data->comment_type,
			"comment_parent" => $comment_data->comment_parent,
			"comment_post_ID" => $comment_data->comment_post_ID,
			"comment_post_title" => $comment_parent_post->post_title,
			"comment_post_type" => $comment_parent_post->post_type,
		);

		// Note: comment type is empty for normal comments
		if (empty( $context["comment_type"] ) ) {
			$context["comment_type"] = "comment";
		}

		return $context;

	}

	public function on_edit_comment($comment_ID) {

		$context = $this->get_context_for_comment($comment_ID);
		if ( ! $context ) {
			return;
		}

		$this->infoMessage(
			"{$context["comment_type"]}_edited",
			$context
		);

	}

	public function on_delete_comment($comment_ID) {

		$context = $this->get_context_for_comment($comment_ID);
		if ( ! $context ) {
			return;
		}


		$comment_data = get_comment( $comment_ID );

		// add occasions if comment was considered spam
		// if not added, spam comments can easily flood the log
		// Deletions of spam easiy flood log
		if ( isset( $comment_data->comment_approved ) && "spam" === $comment_data->comment_approved ) {
			$context["_occasionsID"] = __CLASS__  . '/' . __FUNCTION__ . "/anon_{$context["comment_type"]}_deleted/type:spam";
		}

		$this->infoMessage(
			"{$context["comment_type"]}_deleted",
			$context
		);

	}

	public function on_untrashed_comment($comment_ID) {

		$context = $this->get_context_for_comment($comment_ID);
		if ( ! $context ) {
			return;
		}

		$this->infoMessage(
			"{$context["comment_type"]}_untrashed",
			$context
		);

	}

	/**
	 * Fires after a comment status has been updated in the database.
	 * The hook also fires immediately before comment status transition hooks are fired.
	 * @param int         $comment_id     The comment ID.
	 * @param string|bool $comment_status The comment status. Possible values include 'hold',
	 *                                    'approve', 'spam', 'trash', or false.
	 * do_action( 'wp_set_comment_status', $comment_id, $comment_status );
	 */
	public function on_wp_set_comment_status($comment_ID, $comment_status) {

		$context = $this->get_context_for_comment($comment_ID);
		if ( ! $context ) {
			return;
		}

		/*
		$comment_status:
			approve
				comment was approved
			spam
				comment was marked as spam
			trash
				comment was trashed
			hold
				comment was un-approved
		*/
		// sf_d($comment_status);exit;
		$message = "{$context["comment_type"]}_status_{$comment_status}";

		$this->infoMessage(
			$message,
			$context
		);

	}

	/**
	 * Fires immediately after a comment is inserted into the database.
	 */
	public function on_comment_post($comment_ID, $comment_approved) {

		$context = $this->get_context_for_comment($comment_ID);

		if ( ! $context ) {
			return;
		}

		$comment_data = get_comment( $comment_ID );

		$message = "";

		if ( $comment_data->user_id ) {

			// comment was from a logged in user
			$message = "user_{$context["comment_type"]}_added";

		} else {

			// comment was from a non-logged in user
			$message = "anon_{$context["comment_type"]}_added";
			$context["_initiator"] = SimpleLoggerLogInitiators::WEB_USER;

			// add occasions if comment is considered spam
			// if not added, spam comments can easily flood the log
			if ( isset( $comment_data->comment_approved ) && "spam" === $comment_data->comment_approved ) {
				$context["_occasionsID"] = __CLASS__  . '/' . __FUNCTION__ . "/anon_{$context["comment_type"]}_added/type:spam";
			}

		}

		$this->infoMessage(
			$message,
			$context
		);

	}

}
