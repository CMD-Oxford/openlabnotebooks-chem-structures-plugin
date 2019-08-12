<?php
/*
  Plugin Name: SGC Chemical Structures
  Description: Chemical Structures addon for TinyMCE.
  Author: SGC RI
  Version: 1.0
  Author URI: http://www.thesgc.org
  License: GPL2
*/

function sgc_chem_structures_main() {
  global $typenow;
  // check the current user's permissions
  if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) {
    return;
  }
	
  if ( !in_array( $typenow, array( 'post', 'page' ) ) ) {
    return;
  }
	
  if ( get_user_option('rich_editing') == 'true' ) {
    add_filter( 'mce_external_plugins', 'sgc_chem_structures_add_tinymce_plugin' );
    add_filter( 'mce_buttons', 'sgc_chem_structures_register_buttons' );
  }
}
add_action('admin_head', 'sgc_chem_structures_main');

function sgc_chem_structures_add_tinymce_plugin( $plugin_array ) {
  $plugin_array['sgc_chem_structures_plugin'] = plugins_url( '/js/tinymce-chem-structures-plugin.js', __FILE__ );
  return $plugin_array;
}

function sgc_chem_structures_register_buttons( $buttons ) {
  array_push( $buttons, 'sgc_chem_structures_text_button' );
  return $buttons;
}

// Load custom scripts
function sgc_chem_structures_custom_scripts() {
    wp_register_style('custom-css', plugin_dir_url( __FILE__ ) . 'css/sgc-chem-style.css' );
    wp_enqueue_style('custom-css');
    wp_register_script( 'custom-js', plugin_dir_url( __FILE__ ) . 'js/sgc-chem-scripts.js' );
    wp_enqueue_script('custom-js');
}
add_action( 'wp_enqueue_scripts', 'sgc_chem_structures_custom_scripts');


function sgc_chem_structures_override_tinymce_options( $initArray ) {
    // Command separated string of extended elements
    $ext = 'svg[*],defs,linearGradient[*],g[*],path[*],rect[*],img[*],table[*],thead[*],tr[*],th[*],tbody[*],td[*]';

    if ( isset( $initArray['extended_valid_elements'] ) ) {
        $initArray['extended_valid_elements'] .= ',' . $ext;
    } else {
        $initArray['extended_valid_elements'] = $ext;
    }
    
		$initArray['paste_data_images'] = true;

    return $initArray;
}
add_filter( 'tiny_mce_before_init', 'sgc_chem_structures_override_tinymce_options' );


/** Hook header */

function sgc_chem_header_add_button() {
	?>
	<a class="sub-search">STRUCTURE SEARCH</a>	
	<?php
}
add_action( 'clean_journal_header', 'sgc_chem_header_add_button', '80');


function sgc_chem_header_add_modal() {
	?>
	<div id="myModal" class="modal">

		<div class="modal-content">
			<span class="title"><h2>Structure search</h2></span>
			<span class="close">&times;</span>
			<iframe src="" id="ketcher-frame"></iframe>
			<div class="search-options">
				<a id="search-ok">SEARCH</a>
				<a id="search-error">SEARCH</a>
				
				<div class="search-option">
					<label for="threshold">Similarity threshold:</label>
					<input type="number" id="threshold" step="0.1" min="0.1" max="1" disabled>
				</div>
				<div class="search-option">
					<label for="select-type">Search type:</label>
						<select id="select-type">
						<option value="substructure">Substructure</option>
						<option value="similarity">Similarity</option>
					</select>
				</div>
			
			</div>
			<div id="search-results">
				<span id="loading"></span>
			</div>
		</div>

	</div>
	<?php
}
add_action( 'clean_journal_header', 'sgc_chem_header_add_modal', '1');


/** Hook save */

function sgc_chem_structures_post_save($post_id) {
	$message = 'Start';
	
	if ( wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) { 
		return; //Autosave or post revision
	}
		
	//We pass $post_id and get the Molblock from the post
	$content_post = get_post($post_id);	
	$content = $content_post->post_content;

	$doc = new DOMDocument();
	$doc->loadHTML($content);
	//Check if tag img exists
	$chemiregTags = $doc->getElementsByTagName('img');
	$molblock_array = array();
	
	foreach($chemiregTags as $i => $tag) {
		$molblock = $tag->getAttribute('data-molblock');
		$message .= "\n";
		if (!empty($molblock)) {
			$molblock_array[] = $molblock;
		}
	}
	
	$upload_id_array = array();
	$tableTags = $doc->getElementsByTagName('table');
	
	foreach($tableTags as $i => $tag) {
		$upload_id = $tag->getAttribute('data-upload-id');
		if (!empty($upload_id)) {
			$upload_id_array[] = $upload_id;
		}
		$message .= "\n";
		$message .= 'Upload ID: ' . $upload_id;
		$message .= $upload_id;
	}
	
	$pluginlog = plugin_dir_path(__FILE__).'debug.log';

	if ( FALSE === get_post_status( $post_id) ) { //The post exists
			return;
	}
	
	else {
		global $wpdb;
		$table_name = $wpdb->prefix . "sgc_chemicalstructures";
		$has_structure = 0;
		
		// Insert structures that have been drawn
		if (!empty($molblock_array)) {
			$molblock_string = json_encode($molblock_array);
			$has_structure = 1;
			$message .= 'S1: ' . $molblock_string;
			
			//Prepare and bind
			$wpdb->insert($table_name, array(
				'post_id' => $post_id,
				'molblock' => $molblock_string,
				'has_structure' => $has_structure,
				'checked' => 0,
				'date' => current_time('mysql', 1)
			));			
		}
		
		// Insert structures from sdf table
		if (!empty($upload_id_array)) {
			$upload_id_string = json_encode($upload_id_array);
			$message .= 'S2: ' . $upload_id_string;
			$has_structure = 1;
			
			//Prepare and bind
			$wpdb->insert($table_name, array(
				'post_id' => $post_id,
				'upload_id' => $upload_id_string,
				'has_structure' => $has_structure,
				'checked' => 0,
				'date' => current_time('mysql', 1)
			));						
		}

		if($has_structure === 0){
			$wpdb->insert($table_name, array(
				'post_id' => $post_id,
				'upload_id' => null,
				'has_structure' => null,
				'checked' => 0,
				'date' => current_time('mysql', 1)
			));						
		}
		
	}
	$message .= "\n"; 
	$message .= '------------------------';
	error_log($message, 3, $pluginlog);
}
add_action( 'publish_post', 'sgc_chem_structures_post_save' );


/** Create database table */

function sgc_chem_structures_database_table() {
	global $wpdb;
  
	$tblname = $wpdb->prefix . "sgc_chemicalstructures";

	#Check to see if the table exists already, if not, then create it

	if($wpdb->get_var( "show tables like '$tblname'" ) != $tblname) {
		$sql = "CREATE TABLE " . $tblname . "(
			id INT NOT NULL AUTO_INCREMENT,
				post_id BIGINT(20) UNSIGNED NOT NULL,
				molblock VARCHAR(5000),
				upload_id VARCHAR(1000),
				chemireg_id INT,
				has_structure BOOLEAN,
				checked BOOLEAN, date DATETIME,
				PRIMARY KEY (ID));";
				
		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	}
}
register_activation_hook( __FILE__, 'sgc_chem_structures_database_table' );


/** Allow SDF file upload */

function sgc_chem_custom_mime_types($mimes) { 
	$mimes['sdf'] = 'chemical/x-mdl-sdfile';
	return $mimes;
}
add_filter( 'upload_mimes', 'sgc_chem_custom_mime_types' );
