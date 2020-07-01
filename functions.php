<?php
  function return_img_link($file_name, $file_type) {
    global $doclink, $folderlink, $filelink;
    if ($file_type == "IMG")
      return $file_name;
    else if ($file_type == "DOC")
      return $doclink;
    else if ($file_type == "DIR")
      return $folderlink;
    else
      return $filelink;    
  }
  
  function return_file_type($file_name) {
    if (preg_match('/.jpeg|.jpg|.png|.webp|.gif|.bmp/i', $file_name))
      return "IMG";
    else if (preg_match('/.pdf|.doc|.docx|.epub|.djvu/i', $file_name))
      return "DOC";
    else 
      return "FIL";
  }
  
  // if $is_original, then we need to set the referenced variables - otherwise, we ignore them
  // if $is_rescan, then we are rescanning - so we clear the database of the directory contents first
  function scan_dir_recursive($parent_dir, $dir_name, $mysqli, &$js_file_list, &$js_dir_list, &$new_list, $is_original, $is_rescan) {
    global $FILE_TABLE, $fs_root, $folderlink;
    $path = $parent_dir.DIRECTORY_SEPARATOR.$dir_name;
    $escaped_parent_dir = str_replace("\\", "\\\\", $parent_dir);
    $escaped_path = str_replace("\\", "\\\\", $path);

    if ($is_rescan) {
      $mysqli->query("DELETE FROM $FILE_TABLE where path='$escaped_path'");
    }
    
    $mysqli->query("INSERT INTO $FILE_TABLE values('$dir_name', '$escaped_parent_dir', 1, 1, 'DIR', CURRENT_TIMESTAMP(), 'Pub') ON DUPLICATE KEY UPDATE indexed=1");

    $file_list = scandir($fs_root.$path);
    $file_list_size = count($file_list);
    $new_list_size = 0;

    for ($i = 0; $i < $file_list_size; $i++) {
      if (! ((preg_match('/index.php/i', $file_list[$i])) || (is_dir($file_list[$i])))) {
        $ftype = return_file_type($file_list[$i]);
        $file_image = return_img_link($file_list[$i], $ftype);
        $mysqli->query("INSERT INTO $FILE_TABLE values('$file_list[$i]', '$escaped_path', 0, 1, '$ftype', CURRENT_TIMESTAMP(), 'Pub')");	    

        if ($is_original) {
          array_push($js_file_list, $file_list[$i]);
          $new_list[$new_list_size] = array("name" => $file_list[$i], "image" => $file_image);
          $new_list_size++;
        }
      }

      else if (is_dir($file_list[$i])) {
        // We want to prevent . and .. as folder names as well
        if ($is_original)
          array_push($js_dir_list, $file_list[$i]);
        
        if (! (($file_list[$i] == ".") || ($file_list[$i] == "..") || (preg_match('/_files/i', $file_list[$i])))) {	  
          $ftype = "DIR";
          
          if ($is_original) {
            $file_image = $folderlink;
            // Replicated because we do NOT want to add . or .. to the list
            // I found that out the hard way by cleverly putting this only once
            // as the last line of the loop
            $new_list[$new_list_size] = array("name" => $file_list[$i], "image" => $file_image);
            $new_list_size++;
          }
       
          $mysqli->query("INSERT INTO $FILE_TABLE values('$file_list[$i]', '$escaped_path', 1, 0, '$ftype', CURRENT_TIMESTAMP(), 'Pub')");	
          // Replicate index.php in sub-directory
          copy("index.php", $file_list[$i].DIRECTORY_SEPARATOR."index.php");
          // Recursively index subdirectory
          scan_dir_recursive($path, $file_list[$i], $mysqli, $js_file_list, $js_dir_list, $new_list, FALSE, $is_rescan);          
        }
      }
    }
  }
  
  /*
    Idea:
    let current_dir = path[SLASH]dir_name obtained from the form
    del_recursive(current_dir):
    1) Get a list of files and a list of directories whose path = current_dir
    2) Unlink the files, remove from DB
    3) for each dir in list del_recursive(current_dir[slash]dir)
    4) delete current_dir using rmdir as well as in DB       
  */
  function del_recursive($original_dir, $current_name, $fs_root, $mysqli) {
    global $FILE_TABLE;
    $escaped_original_dir = str_replace("\\", "\\\\", $original_dir);
    $current_dir = $original_dir.DIRECTORY_SEPARATOR.$current_name;
    $escaped_current_dir = str_replace("\\", "\\\\", $current_dir);
    
    $file_query = $mysqli->query("SELECT filename from $FILE_TABLE WHERE dir=0 AND path = '$escaped_current_dir'");
    $files = $file_query->fetch_all(MYSQLI_ASSOC);
    $dir_query = $mysqli->query("SELECT filename from $FILE_TABLE WHERE dir=1 and path = '$escaped_current_dir'");
    $directories = $dir_query->fetch_all(MYSQLI_ASSOC);
    
    foreach($files as $value) {
      unlink($fs_root.$current_dir.DIRECTORY_SEPARATOR.$value['filename']);
    }
    
    // Delete files all at once
    $mysqli->query("DELETE FROM $FILE_TABLE WHERE path='$escaped_current_dir' AND dir=0");	

    foreach($directories as $value) {
      del_recursive($current_dir, $value['filename'], $fs_root, $mysqli);
    }
    // Delete current folder - but not before getting rid of the pesky index.php
    unlink($fs_root.$current_dir.DIRECTORY_SEPARATOR."index.php");
    rmdir($fs_root.$current_dir);
    $mysqli->query("DELETE FROM $FILE_TABLE WHERE filename='".$current_name."' AND path='".$escaped_original_dir."' AND dir=1");
  }
  
/* 
  Idea:
  ren_recursive(old_path, new_path):
  1) get list of dirs in the dir
  2) update the file and folder paths to new_path
  3) recurse on every dir in the list of dirs, old_path[slash]dir_name, new_path[slash]dir_name

  The final step of renaming the specific folder itself is done in index.php
*/

  function ren_recursive($old_path, $new_path, $mysqli) {
    global $FILE_TABLE;

    $dir_query = $mysqli->query("SELECT filename from $FILE_TABLE WHERE dir=1 and path='$old_path'");
    $directories = $dir_query->fetch_all(MYSQLI_ASSOC);
    print_r($directories);
    
    // Adjust files and folders all at once
    $mysqli->query("UPDATE $FILE_TABLE set path='$new_path' WHERE path='$old_path'");	

    $old_slashed_path = $old_path.(str_replace("\\", "\\\\", DIRECTORY_SEPARATOR));
    $new_slashed_path = $new_path.(str_replace("\\", "\\\\", DIRECTORY_SEPARATOR));
    // Recursively adjust subdirectories - remember that these don't require renaming
    foreach($directories as $value) {
      ren_recursive($old_slashed_path.$value['filename'],$new_slashed_path.$value['filename'] , $mysqli);
    }
  }