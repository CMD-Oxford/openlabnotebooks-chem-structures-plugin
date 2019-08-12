<?php

include( '../../../wp-load.php');  

global $current_user;
 
if (isset($_POST['file_content'])) {
  $file_content = $_POST['file_content'];
}

if (isset($_POST['post_id'])) {
  $post_id = $_POST['post_id'];
}

if (isset($_POST['file_name'])) {
  $filename = $_POST['file_name'];
}

if (isset($current_user->user_login)) {
	sgc_chem_wp_file_upload($file_content, $post_id, $filename);
}

function sgc_chem_wp_file_upload($file_content, $post_id, $filename) {
	$upload_file = wp_upload_bits($filename, null, $file_content);
	$wp_upload_dir = wp_upload_dir();
	
	if (!$upload_file['error']) {
		$wp_filetype = wp_check_filetype($filename, null);
		$attachment = array(
			'guid' => $wp_upload_dir['url'] . '/' . basename( $filename ),
			'post_mime_type' => $wp_filetype['type'],
			'post_parent' => $post_id,
			'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
			'post_content' => '',
			'post_status' => 'inherit'
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $post_id );

		if (!is_wp_error($attachment_id)) {
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
			$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
			wp_update_attachment_metadata( $attachment_id,  $attachment_data );
		}
	}

	print $upload_file['url'];
}

?>