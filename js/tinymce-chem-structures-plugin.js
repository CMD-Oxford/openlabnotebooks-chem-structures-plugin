(function() {
	tinymce.PluginManager.add('sgc_chem_structures_plugin', function( editor, url ) {
		var pluginFolder = url.replace('/js','');
		// Add Button to Visual Editor Toolbar
		editor.addButton('sgc_chem_structures_text_button', {
			text: 'Chem',
			title: 'Insert Chemical Structures',
			icon: false,
			type: 'menubutton',		
			
			menu: [
			{
			text: 'Draw Chemical Structures',
			onclick: function() {
				
				// Opens Ketcher HTML page
				editor.windowManager.open({
					title: 'Draw Chemical Structure',
					file: pluginFolder + '/ketcher/ketcher.html',
					width: 840,
					height: 510,
					buttons: [{
						text: 'Done',
						onclick: function(e) {
							
							var ketcherParentArray = document.getElementsByClassName('mce-window-body');
							var ketcherParent = ketcherParentArray[0];
							var ketcherFrame = ketcherParent.firstChild;
							var ketcher = null;
							
							if ('contentDocument' in ketcherFrame) {
									ketcher = ketcherFrame.contentWindow.ketcher;
									var molBlock = ketcher.getMolfile();
									//Request
									var url = 'https://openlabnotebooks.org/wp-content/plugins/sgc-chem-structures/sgc-chem-retrieve-images.php';
									var params = 'wait=yes&molblock=' + molBlock;

									var http = new XMLHttpRequest();
									http.open('POST', url, true);
									http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
									http.onreadystatechange = function() {
										if(this.readyState == 4 && this.status == 200) {
											var molBlockReplaced = molBlock.replace( /\n/g, "|8888|");
											molBlockReplaced = molBlockReplaced.replace(/ /g, "|7777|");
											
											var imageHtml = '<img src="data\:image\/png;base64,' + http.responseText + '" data-molblock="' + molBlockReplaced + '" />';
											editor.insertContent(imageHtml, {format: 'raw'});
										}
									}
									http.send(params);

							}
							else {// IE7
									ketcher = document.frames['ifKetcher'].window.ketcher;
							}
											
							top.tinymce.activeEditor.windowManager.close();
												
						}
					}]
				
				});
			}
			},
			
			{
			text: 'Upload Chemical Structures',
				onclick: function(e) {
					editor.windowManager.open({
						title: 'Upload Chemical Structures',
						file: pluginFolder + '/sgc-chem-sd-upload.html',
						width: 840,
						height: 300,
						buttons: [{
						text: 'Upload',
						onclick: function(e) {
							
							var uploadWindowParentArray = document.getElementsByClassName('mce-window-body');
							var uploadWindowParent = uploadWindowParentArray[0];
							var uploadWindowFrame = uploadWindowParent.firstChild;
							const sdf_file = uploadWindowFrame.contentWindow.document.getElementById('real-input').files[0];
							var structures_limit_options = uploadWindowFrame.contentWindow.document.getElementsByName('number');
							for(var i = 0; i < structures_limit_options.length; i++){
								if (structures_limit_options[i].checked == true) {
									structures_limit = structures_limit_options[i].value;
								}								
							}

							var textType = 'sdf';
							var sdf_content;
							
							if (sdf_file == null) {
								alert('Please select an SDF file');
								return;
							}
							
							if (sdf_file.name.match(textType)) {
								var reader = new FileReader();

								reader.onload = function(e) {
									var sdf_link = '';
									
									//Generate table and register request
									//Upload SDF request
									var url = 'https://openlabnotebooks.org/wp-content/plugins/sgc-chem-structures/sgc-chem-upload.php';
									var params = 'sdf_content=' + reader.result + '&limit=' + structures_limit;

									var http = new XMLHttpRequest();
									http.open('POST', url, true);
									http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
									
									http.onreadystatechange = function() {
										if(this.readyState == 4 && this.status == 200) {
											var results_obj = JSON.parse(http.responseText);
											
											upload_id_length = results_obj.length;
											var upload_id = results_obj[upload_id_length - 1];
											
											var outputHtml = '';
											outputHtml = '<table data-upload-id="' + upload_id.upload_id + '"><thead><tr><th>Structure Image</th><th>Smiles</th><th>Mol. Weight</th></tr></thead><tbody>';
											
											for (var key in results_obj) {
												if (key < upload_id_length-1) { // Exclude last iteration
													outputHtml += '<tr>';
													var single_compound  = results_obj[key];
										
													var mol_weight = single_compound.mw;
													mol_weight = +(Math.round(mol_weight + "e+1")  + "e-1")
													var smiles = single_compound.smiles;
													var image = single_compound.mol_image;
													
													outputHtml += '<td><img src="data\:image\/png;base64,' + image + '"/></td>';
													outputHtml += '<td>' + smiles + '</td>';
													outputHtml += '<td>' + mol_weight + '</td>';
													
													outputHtml += '</tr>';
												}
											}
											
											outputHtml += '</tbody></table>';
											outputHtml +=  sdf_link;
											editor.insertContent(outputHtml, {format: 'raw'});
											
										}
									}
									http.send(params);
									
									var postID = document.getElementById('post_ID').value;
									var url = 'https://openlabnotebooks.org/wp-content/plugins/sgc-chem-structures/sgc-chem-wp-file-upload.php';
									var uuid = create_UUID();
									var params = 'file_content=' + reader.result + '&post_id=' + postID + '&file_name=' + postID + '_' + uuid + '.sdf';
									var attFileReq = new XMLHttpRequest();
									attFileReq.open('POST', url, true);
									attFileReq.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
									
									attFileReq.onreadystatechange = function() {
										if(this.readyState == 4 && this.status == 200) {
											var file_url = attFileReq.responseText;
											var pathname = new URL(file_url).pathname;
											sdf_link = '<a href="' + pathname + '">Download SDF file</a>';											
										}
									}
									attFileReq.send(params);
									
									
									top.tinymce.activeEditor.windowManager.close();			
								}  //<-- reader.onload
							

								reader.readAsText(sdf_file);
							}
							else {
								alert('Please select an SDF file');
								return;
							}
						}
							
					}]
					});
					
				 }
			}
			]

		});
	});

})();

function create_UUID(){
	var dt = new Date().getTime();
	var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
			var r = (dt + Math.random()*16)%16 | 0;
			dt = Math.floor(dt/16);
			return (c=='x' ? r :(r&0x3|0x8)).toString(16);
	});
	return uuid;
}
