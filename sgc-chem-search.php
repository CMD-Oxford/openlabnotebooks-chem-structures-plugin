<?php

include( '../../../wp-load.php');

if (isset($_POST['molblock'])) {
	$molblock = $_POST['molblock'];
}

$molblock = str_replace('|8888|', PHP_EOL , $molblock);
$molblock = str_replace('|7777|', ' ', $molblock);

if (isset($_POST['threshold'])) {
	if (!empty($_POST['threshold'])) {
		$threshold = $_POST['threshold'];
	}
	else {
		$threshold = null;
	}
}

sgc_chem_search($molblock, $threshold);


function sgc_chem_search($molblock) {
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
	$api_url = $config['api_url_compounds'];
	$search_terms = array();
	
  $data = array(
    'wait' => 'yes',
		'token' => $token,
		'project_name' => 'WordPress',
    'search_terms' => $search_terms,
		'from_row' => '0',
		'to_row' => '10',
		'sim_threshold' => $threshold,
    'mol_block' => $molblock
  );

  $field_string = http_build_query($data);
	
	curl_setopt($curl, CURLOPT_URL, $api_url . '?' . $field_string);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

  $result = curl_exec($curl);
	$json_obj = json_decode($result,true );

	for ($i=0;$i<count($json_obj['result-set']);$i++){
		$post_id = $json_obj['result-set'][$i]['elnid'];
		$post = get_post($post_id);

		$post_title = $post->post_title;
		$post_author = get_user_by('id',$post->post_author)->display_name;

		$post_cats = wp_get_post_categories($post_id);
		$cats = array();

		foreach($post_cats as $c){
			$cat = get_category($c);
			$cats[] = $cat->name;
		}

		$json_obj['result-set'][$i]['categories'] = join(', ', $cats);
		$json_obj['result-set'][$i]['title'] = $post_title;
		$json_obj['result-set'][$i]['author'] = $post_author;
	}
	
	print json_encode($json_obj['result-set']);
	
  curl_close($curl);
}

?>