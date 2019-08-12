var uploadButton = document.querySelector('.browse-btn');
var fileInfo = document.querySelector('.file-info');
var realInput = document.getElementById('real-input');

uploadButton.addEventListener('click', () => {
	realInput.click();
});


realInput.onchange = function() {
	var name = realInput.value.split(/\\|\//).pop();
	var truncated = name.length > 20 
		? name.substr(name.length - 20) 
		: name;

  var CFS = checkFileSize(this);
	var CFE = checkFileExtension(this);
	
	if (CFS && CFE) {
		fileInfo.innerHTML = truncated;
	}
	else {
		
	}
};


function checkFileSize(file) {
	fileSize = file.files[0].size/1024;
	console.log('The file size is: ' + fileSize + ' KB');
	if (fileSize < 2048) {
		return true;
	}
	else {
		 alert('File is too big');
		 return false;
	}
}


function checkFileExtension(file) {
	var fileName = file.value;
	var ext = fileName.substring(fileName.lastIndexOf('.') + 1);
	if(ext == "sdf") {
		return true;
	} 
	else {
		alert('Please upload only SDF files');
		return false;
	}
}