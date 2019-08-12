<?php

include( '../../../wp-load.php');  

global $current_user;
 
if (isset($_POST['sdf_content']) && isset($_POST['limit'])) {
  $sdf_content = $_POST['sdf_content'];
	$structures_limit = $_POST['limit'];
}

if (isset($current_user->user_login)) {
	sgc_chem_upload_sdf_to_chemireg($sdf_content, $structures_limit);	
}

function sgc_chem_upload_sdf_to_chemireg($sdf_content, $structures_limit) {
	$config = parse_ini_file('../../../../../.config.ini');
	
	$username = $config['chem_username'];
	$password = $config['chem_password'];
	$api_url = $config['api_url_login'];

	$curl = curl_init();
  $data = array(
    'username' => $username,
    'password' => $password
  );

  $field_string = http_build_query($data);

  curl_setopt($curl, CURLOPT_URL, $api_url);

  curl_setopt($curl, CURLOPT_POST, count($data));
  curl_setopt($curl,CURLOPT_POSTFIELDS, $field_string);

  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

  $result = curl_exec($curl);
  $result_obj = json_decode($result);
  $token = $result_obj->token;
	
	$curl = curl_init();
	$api_url = $config['api_url_upload'];
	
	$sdf_content_b64 = base64_encode($sdf_content);
	
  $data = array(
    'token' => $token,
    'wait' => 'yes',
    'upload_id' => null,
    'contents' => $sdf_content_b64
  );

  $field_string = http_build_query($data);

  curl_setopt($curl, CURLOPT_URL, $api_url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_POST, count($data));
  curl_setopt($curl,CURLOPT_POSTFIELDS, $field_string);

  $result = curl_exec($curl);
	$json_obj = json_decode($result, JSON_PRETTY_PRINT);
	
	$upload_id = $json_obj['upload_id'];
	
	// Request the structure images from uploaded filesize
	$output = request_uploaded_sdf_structures($token, $upload_id, $structures_limit);
	
	// Register the uploaded compounds
	//register_uploaded_sdf($token, $upload_id);

  curl_close($curl);
	return $output;
}


function request_uploaded_sdf_structures($token, $upload_id, $structures_limit) {
  $config = parse_ini_file('../../../../../.config.ini');
	
	$curl = curl_init();
	$api_url = $config['api_url_upload'];
	
  $data = array(
    'wait' => 'yes',
		'project_name' => 'WordPress',
		'token' => $token,
		'action' => 'preview_sdf_upload',
    'upload_id' => $upload_id,
    'limit' => $structures_limit
  );

  $field_string = http_build_query($data);
	
	curl_setopt($curl, CURLOPT_URL, $api_url . '?' . $field_string);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

  $result = curl_exec($curl);
	$json_obj = json_decode($result, JSON_PRETTY_PRINT); 
	
	foreach ($json_obj['result-set'] as $single_sdf_compound) {
		$mol_weight = $single_sdf_compound['mw'];
		$smiles = $single_sdf_compound['smiles'];
		$image = $single_sdf_compound['mol_image'];		
	}

  curl_close($curl);
	
	$json_obj['result-set'][] = ['upload_id' => $upload_id];
	print json_encode($json_obj['result-set']);
}

?>
