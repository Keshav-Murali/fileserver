<?php

  if (isset($_POST)) {
    if (isset($_POST['folder_form'])) {
      $folder_name = $_POST['folder_name'];
      mkdir($folder_name, 0754);
      // Replicate index.php in newly created sub-directory
      if ($WINDOWS)
        copy("index.php", $folder_name.'\\'."index.php");	
      else
        copy("index.php", $folder_name.'/'."index.php");
      // Insert into the database
      $mysqli->query("INSERT INTO $FILE_TABLE values('$folder_name','$escaped_path', 1, 0, 'DIR', CURRENT_TIMESTAMP(), 'Pub')");	
    }
    
    else if (isset($_POST['file_form'])) {
      // If the file did not require renaming
      if ($_POST['file_name'] != '') 
        $file_name = $_POST['file_name'];
      else 
        $file_name = basename($_FILES['file']['name']);
      
      $upload_file_path = getcwd().DIRECTORY_SEPARATOR.$file_name;
      move_uploaded_file($_FILES['file']['tmp_name'], $upload_file_path);
      
      $type = return_file_type($file_name);
    
      $mysqli->query("INSERT INTO $FILE_TABLE values('$file_name','$escaped_path', 0, 1, '$type', CURRENT_TIMESTAMP(), 'Pub')");	

    }
      
    else if (isset($_POST['delete_form'])) {
      $del_file_name = $_POST['del_file_name'];
      $del_file_type = $_POST['del_file_type'];
      
      if ($del_file_type == "file") {
        unlink($fs_root.$path.DIRECTORY_SEPARATOR.$del_file_name);
        $mysqli->query("DELETE FROM $FILE_TABLE WHERE filename='$del_file_name' AND path='$escaped_path' and dir=0");	
      }
      
      else {
        del_recursive($path, $del_file_name, $fs_root, $mysqli);
        /* If there are arise any issues, it'll certainly be related to whitespace. Will check soon */
      }   
    }
    
    else if (isset($_POST['rename_form'])) {
      $ren_old_name = $_POST['ren_old_file_name'];
      $ren_new_name = $_POST['ren_file_name'];
      $ren_file_type = $_POST['ren_file_type'];
      
      if ($ren_file_type == "file") {
        rename($fs_root.$path.DIRECTORY_SEPARATOR.$ren_old_name, $fs_root.$path.DIRECTORY_SEPARATOR.$ren_new_name);
        $mysqli->query("UPDATE $FILE_TABLE SET filename='$ren_new_name' WHERE filename='$ren_old_name' AND path='$escaped_path' and dir=0");	
      }
      
      else {    
        $old_path = $escaped_path.(str_replace("\\", "\\\\", DIRECTORY_SEPARATOR)).$ren_old_name;
        $new_path = $escaped_path.(str_replace("\\", "\\\\", DIRECTORY_SEPARATOR)).$ren_new_name;
        
        // ren_recursive will change the path for all contents inside the directory
        ren_recursive($old_path, $new_path, $mysqli); 
        
        // Then we rename the actual folder
        $mysqli->query("UPDATE $FILE_TABLE set filename='$ren_new_name' where path='$escaped_path' AND filename='$ren_old_name' AND dir = 1");
        rename($fs_root.$path.DIRECTORY_SEPARATOR.$ren_old_name, $fs_root.$path.DIRECTORY_SEPARATOR.$ren_new_name);
        
        // If there are arise any issues, it'll certainly be related to whitespace. Will check soon 
      }
    }
  }
?>