function displayForm(num) {
  overlay = document.getElementById("overlay");
  overlay.style.display = "block";
  
  switch(num) {
    case 0: elt = document.getElementById("folder_form"); break;
    case 1: elt = document.getElementById("file_form"); break;
  }
 
  elt.style.visibility = "visible";
  elt.style.display = "inline-block";
}

function closeOverlay(x) {    
  overlay = document.getElementById("overlay");
  var obj = x.parentElement;
  obj.style.visibility = "hidden";
  obj.style.display = "none";
  overlay.style.display = "none";
}

function validateFolderForm(obj) {
  folder_name = document.getElementById("folder_name").value;
  error = document.getElementById("folder_form_error");
  if (dirList.includes(folder_name)) {
    error.innerHTML = "A folder with the specified name already exists!";
    return false;
  }
  else
    return true;
}

function validateFileForm(v) {
  // A static variable to see if there was an attempt to upload a file previously
  if ( typeof validateFileForm.prev == 'undefined' ) 
    validateFileForm.prev = "NONE";
  if (v == 0) {
    validateFileForm.prev = "NONE";
    return ;
  }
  var f = document.getElementById("file");
  var file_name = f.files[0].name;
  var error = document.getElementById("file_form_error");
  var pos = file_name.lastIndexOf(".");
  var ext = file_name.substring(pos);
  
  if (validateFileForm.prev == "CLASH") {
    var altname = document.getElementById("file_name").value;
    pos = altname.lastIndexOf(".");
    
    if (pos == -1) {
      document.getElementById("file_name").value = altname + ext;
      return true;
    }
    
    else {
      error.innerHTML = "Don't use special character like . in the name";
      return false;
    }
  }
  
  if (fileList.includes(file_name)) {
    validateFileForm.prev = "CLASH";
    error.innerHTML = "A file with the specified name already exists!";
    document.getElementById("file_name").style.display = "inline-block";
    document.getElementById("hidden_label").style.display = "inline-block";
    return false;
  }  
    return true;
}