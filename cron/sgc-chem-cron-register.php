<?php

chemireg_check();

function chemireg_check() {
	$conn = db_connect();

	if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
	}
	  
	$table_name = 'wp_sgc_chemicalstructures';
	$select_query = "SELECT * FROM " . $table_name . " WHERE checked=0";
	$result = $conn->query($select_query);
	
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {			
			$molblocks_raw = $row['molblock'];
			$upload_ids = json_decode($row['upload_id'], true);
			chemireg_delete($row['post_id']);

			$has_structure = 0;

			//Register compounds from SDF file
			if (!empty($upload_ids)) {
				print '<h3>Register compounds from SDF</h3>';
				$ins_query_checked = "UPDATE " .  $table_name . " SET checked=1 WHERE post_id=" . $row['post_id'];

				$has_structure = 1;

				if ($conn->query($ins_query_checked) === TRUE) {
					foreach ($upload_ids as $upload_id) {
						chemireg_register_uploaded_sdf($upload_id, $row['post_id']);
					}
				}
			}
			
			if (empty($molblocks_raw)) {
				//return;
			}
			else {
				$molblocks = str_replace('|8888|', '\\r\\n', $molblocks_raw);
				$molblocks = str_replace('|7777|', ' ', $molblocks);
				$molblocks_array = json_decode($molblocks, true);	
				$ins_query_checked = "UPDATE " .  $table_name . " SET checked=1 WHERE post_id=" . $row['post_id'];
				
				$has_structure = 1;

				$chemireg_id_array[] = $molblock;
				if ($conn->query($ins_query_checked) === TRUE) {
					foreach ($molblocks_array as $molblock) {
						$chemireg_id = chemireg_register($molblock, $row['post_id']);
						
						echo "<br>";
						print 'Chemireg ID: ' . $chemireg_id;
						echo "<br>";
            $chemireg_id_array[] = $chemireg_id;
						$ins_query_chem_id = "UPDATE " . $table_name . " SET chemireg_id=" . $chemireg_id . " WHERE post_id=" . $row['post_id'];
						if ($conn->query($ins_query_chem_id) === TRUE) {
							//successful
						}
					}
				} 
				else {
					print 'error';
				}
			}

			if($has_structure === 0){
				$ins_query_checked = "UPDATE " .  $table_name . " SET checked=1 WHERE post_id=" . $row['post_id'];
				$conn->query($ins_query_checked);
			}
		}
	}
	
	else {
		print '<h3>No results in the database</h3>';
	}
	mysqli_close($conn);
}


function db_connect() {
	static $conn;
	
	if(!isset($conn)) {
		$config = parse_ini_file('../../../../../.config.ini');
		$conn = new mysqli($config['servername'],$config['username'],$config['password'],$config['dbname']);
	}

	if($conn === false) {
		return mysqli_connect_error(); 
	}
	return $conn;
}


function chemireg_register_uploaded_sdf($upload_id, $post_id) {
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
	
	# Upload configuration
	$config = array(
		'batchable' => array( 'default_value'=> true, 'map_column' => null),
		'classification' => array('default_value'=> 'ZZ', 'map_column' => null), 
		'supplier_id' => array('default_value' => 'ANY', 'map_column' => null), 
		'supplier' => array('default_value' => 'wordpress', 'map_column' => null), 
		'elnid' => array('default_value' => $post_id, 'map_column' => null)
	);
	
  $data = array(
    'wait' => 'yes',
		'project_name' => 'WordPress',
		'token' => $token,
		'upload_key' => $upload_id,
		'upload_defaults' => json_encode($config),
		'upload_name' => 'wp_upload.sdf'
  );

  $field_string = http_build_query($data);

  curl_setopt($curl, CURLOPT_URL, $api_url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_POST, count($data));
  curl_setopt($curl,CURLOPT_POSTFIELDS, $field_string);

  $result = curl_exec($curl);
	$json_obj = json_decode($result, JSON_PRETTY_PRINT); 
	
	//Send email if there is an error during SDF registration
	if (!empty($json_obj['result-set'][0]['error'])) {
		$redis_upload_id = '';
		if (!empty($json_obj['result-set'][0]['upload_id'])) {
			$redis_upload_id = $json_obj['result-set'][0]['upload_id'];
		}
		send_email($redis_upload_id, 'from_person@email.com', 'to_person@email.com');
	}
}


function chemireg_register($molblock, $post_id) {
	$config = parse_ini_file('../../../../../.config.ini');
	
  $curl = curl_init();
	
  $username = $config['chem_username'];
  $password = $config['chem_password'];
	$api_url = $config['api_url_login'];
	
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
	
	$compound = array(
								'-1' => array (
									'id' => '-1',
									'classification' =>'ZZ',
									'supplier' => 'wordpress',
									'supplier_id' => 'AA',
									'compound_sdf' => $molblock,
									'elnid' => $post_id
									),
								);
								
	$data = array(
						'wait' => 'yes',
						'project_name' => 'WordPress',
						'token' => $token,
						'compounds' => json_encode($compound)
					);
					
  $field_string = http_build_query($data);
	
	print $field_string;
	
  curl_setopt($curl, CURLOPT_URL, $api_url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_POST, count($data));
  curl_setopt($curl,CURLOPT_POSTFIELDS, $field_string);

  $result = curl_exec($curl);
	$json_obj = json_decode($result, JSON_PRETTY_PRINT); 
	
  curl_close($curl);
}


function chemireg_delete($post_id) {
	$config = parse_ini_file('../../../../../.config.ini');
	
	$curl = curl_init();
	
  $username = $config['chem_username'];
	$password = $config['chem_password'];
	$api_url = $config['api_url_login'];
	
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
	
	$data = array(
						'wait' => 'yes',
						'project_name' => 'WordPress',
						'token' => $token,
						'field_name' => 'elnid',
						'field_value' => $post_id
					);
					
  $field_string = http_build_query($data);
	
  curl_setopt($curl, CURLOPT_URL, $api_url);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
	curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl,CURLOPT_POSTFIELDS, $field_string);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

  $result = curl_exec($curl);
	$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$json_obj = json_decode($result, JSON_PRETTY_PRINT); 
	
	curl_close($curl);
}

function send_email($redis_upload_id, $from, $to) {
	$subject = "SDF Registration Failed";
	$message = "Registration failed for " . $redis_upload_id;
	$headers = "From:" . $from;
	mail($to, $subject, $message, $headers);
	echo "The email message was sent.";
}

?>