<?php

include( '../../../wp-load.php');  

global $current_user;

if (isset($_POST['molblock'])) {
  $molblock = $_POST['molblock'];
}

if (isset($current_user->user_login)) {
	$output = sgc_chem_convert_molblock_to_image($molblock);
	print $output;	
}

function sgc_chem_convert_molblock_to_image($molblock) {
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
	$api_url = $config['api_url_image'];
	
  $data = array(
    'token' => $token,
    'wait' => 'yes',
    'format' => 'png',
    'molblock' => $molblock
  );

  $field_string = http_build_query($data);

  curl_setopt($curl, CURLOPT_URL, $api_url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_POST, count($data));
  curl_setopt($curl,CURLOPT_POSTFIELDS, $field_string);

  $result = curl_exec($curl);
  $json_obj = json_decode($result);
  $png = $json_obj->{'result-set'}[0]->{'png_content'};

  curl_close($curl);
	
  return $png;
}

?>