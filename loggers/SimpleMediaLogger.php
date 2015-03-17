<?php

/**
 * Logs media uploads
 */
class SimpleMediaLogger extends SimpleLogger
{

	public $slug = "SimpleMediaLogger";

	/**
	 * Get array with information about this logger
	 *
	 * @return array
	 */
	function getInfo() {

		$arr_info = array(
			"name" => "Media/Attachments Logger",
			"description" => "Logs media uploads and edits",
			"capability" => "edit_pages",
			"messages" => array(
				'attachment_created' => __('Created {post_type} "{attachment_title}"', 'simple-history'),
				'attachment_updated' => __('Edited {post_type} "{attachment_title}"', 'simple-history'),
				'attachment_deleted' => __('Deleted {post_type} "{attachment_title}" ("{attachment_filename}")', 'simple-history')
			),
			"labels" => array(
				"search" => array(
					"label" => _x("Media", "Media logger: search", "simple-history"),
					"options" => array(
						_x("Added media", "Media logger: search", "simple-history") => array(
							"attachment_created"
						),
						_x("Updated media", "Media logger: search", "simple-history") => array(
							"attachment_updated"
						),
						_x("Deleted media", "Media logger: search", "simple-history") => array(
							"attachment_deleted"
						),
					)
				) // end search array
			) // end labels
		);

		return $arr_info;

	}

	public function loaded() {

		add_action("admin_init", array($this, "on_admin_init"));

		add_action( 'xmlrpc_call_success_mw_newMediaObject', array($this, "on_mw_newMediaObject"), 10, 2 );

	}

	function on_admin_init() {

		add_action("add_attachment", array($this, "on_add_attachment"));
		add_action("edit_attachment", array($this, "on_edit_attachment"));
		add_action("delete_attachment", array($this, "on_delete_attachment"));

	}

	/**
	 * Filter that fires after a new attachment has been added via the XML-RPC MovableType API.
	 *
	 * @since 2.0.21
	 *
	 * @param int   $id   ID of the new attachment.
	 * @param array $args An array of arguments to add the attachment.
	 */
	function on_mw_newMediaObject($attachment_id, $args) {

		$attachment_post = get_post( $attachment_id );
		$filename = esc_html( wp_basename( $attachment_post->guid ) );
		$mime = get_post_mime_type( $attachment_post );
		$file  = get_attached_file( $attachment_id );
		$file_size = false;

		if ( file_exists( $file ) ) {
			$file_size = filesize( $file );
		}

		$this->infoMessage(
			'attachment_created',
			array(
				"post_type" => get_post_type( $attachment_post ),
				"attachment_id" => $attachment_id,
				"attachment_title" => get_the_title( $attachment_post ),
				"attachment_filename" => $filename,
				"attachment_mime" => $mime,
				"attachment_filesize" => $file_size
			)
		);

	}

	/**
	 * Called when an attachment is added
	 */
	function on_add_attachment($attachment_id) {

		$attachment_post = get_post( $attachment_id );
		$filename = esc_html( wp_basename( $attachment_post->guid ) );
		$mime = get_post_mime_type( $attachment_post );
		$file  = get_attached_file( $attachment_id );
		$file_size = false;

		if ( file_exists( $file ) ) {
			$file_size = filesize( $file );
		}

		$this->infoMessage(
			'attachment_created',
			array(
				"post_type" => get_post_type( $attachment_post ),
				"attachment_id" => $attachment_id,
				"attachment_title" => get_the_title( $attachment_post ),
				"attachment_filename" => $filename,
				"attachment_mime" => $mime,
				"attachment_filesize" => $file_size
			)
		);

	}

	/**
	 * An attachmet is changed
	 * is this only being called if the title of the attachment is changed?!
	 *
	 * @param int $attachment_id
	 */
	function on_edit_attachment($attachment_id) {

		$attachment_post = get_post( $attachment_id );
		$filename = esc_html( wp_basename( $attachment_post->guid ) );
		$mime = get_post_mime_type( $attachment_post );
		$file  = get_attached_file( $attachment_id );

		$this->infoMessage(
			"attachment_updated",
			array(
				"post_type" => get_post_type( $attachment_post ),
				"attachment_id" => $attachment_id,
				"attachment_title" => get_the_title( $attachment_post ),
				"attachment_filename" => $filename,
				"attachment_mime" => $mime
			)
		);

	}

	/**
	 * Called when an attachment is deleted
	 */
	function on_delete_attachment($attachment_id) {

		$attachment_post = get_post( $attachment_id );
		$filename = esc_html( wp_basename( $attachment_post->guid ) );
		$mime = get_post_mime_type( $attachment_post );
		$file  = get_attached_file( $attachment_id );

		$this->infoMessage(
			"attachment_deleted",
			array(
				"post_type" => get_post_type( $attachment_post ),
				"attachment_id" => $attachment_id,
				"attachment_title" => get_the_title( $attachment_post ),
				"attachment_filename" => $filename,
				"attachment_mime" => $mime
			)
		);

	}

}
