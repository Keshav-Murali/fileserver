<!DOCTYPE HTML>

<?php
  // config.php has most of the constants that are required
  // Examples: Database, relation details, image links
  require 'config.php';
  
  // The DB may be needed to process form inputs, so we connect here
  // Apache will disconnect automatically once the script is processed
  
  $mysqli = new mysqli("localhost", $DB_USER, $DB_PASS, $DB_NAME);
  
  // $path - the path of the directory in which index.php resides
  // $fs_root - document root but will use the correct slash for the Operating System
  // Required for deletion/renaming
  // $dir_name - the name of the above mentioned directory
  // $parent_dir - the directory which contains the above mentioned directory
  // This is because a directory is just a special file and it is present in a directory
  // $pos - the position of the last slash
   
  // escaping is required for Windows since it uses '\' in directory paths
  // for Linux, we can simply leave it alone
  if (PHP_OS_FAMILY == 'Windows')
    $WINDOWS = true;
  
  $path = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
  $fs_root = substr(__DIR__, 0, strlen($_SERVER['DOCUMENT_ROOT']));

  if ($WINDOWS) 
    $pos = strrpos($path, '\\');
  else 
    $pos = strrpos($path, '/');
  
  $dir_name = substr($path, $pos+1);
  $parent_dir = substr($path, 0, $pos);
  
  if ($WINDOWS) {
    $escaped_path = addcslashes($path, '\\');
    $escaped_parent_dir = addcslashes($parent_dir, '\\');
  }
  else {
    $escaped_path = $path;
    $escaped_parent_dir = $parent_dir;
  }
  
  // Form processing
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
      
      // Yes, yes code duplication, I'll solve it some day, I promise
      if ( preg_match('/.jpeg/i', $file_name) ||
           preg_match('/.jpg/i', $file_name) ||
           preg_match('/.png/i', $file_name) ||
           preg_match('/.webp/i', $file_name) ||
           preg_match('/.gif/i', $file_name) ||
           preg_match('/.bmp/i', $file_name)
          ) {
        $type = "IMG";
      }

      else if ( preg_match('/.pdf/i', $file_name) || 
                preg_match('/.doc/i', $file_name) ||
                preg_match('/.docx/i', $file_name)
              ) {
        $type = "DOC";
      }

      else {
        $type = "FIL";
      }
    
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
        /*
        Idea:
        let current_dir = path[SLASH]dir_name obtained from the form
        del_recursive(current_dir):
        1) Get a list of files and a list of directories whose path = current_dir
        2) Unlink the files, remove from DB
        3) for each dir in list del_recursive(current_dir[slash]dir)
        4) delete current_dir using rmdir as well as in DB       
        */
                
        if(!function_exists('del_recursive')) {
          function del_recursive($original_dir, $current_name, $fs_root, $mysqli) {
            require 'config.php';
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
        }
        
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
    /* 
      Idea:
       old_path = path + old_dir_name, new_path = path + new_dir_name;
       ren_recursive(old_path, new_path):
       1) get list of dirs in the dir
       2) update the file and folder paths to new_path
       3) recurse on every dir in the list of dirs, old_path[slash]dir_name, new_path[slash]dir_name
       
       update the folder name to new_name
    */    
        $old_path = $escaped_path.(str_replace("\\", "\\\\", DIRECTORY_SEPARATOR)).$ren_old_name;
        $new_path = $escaped_path.(str_replace("\\", "\\\\", DIRECTORY_SEPARATOR)).$ren_new_name;
        
        if(!function_exists('ren_recursive')) {
          function ren_recursive($old_path, $new_path, $mysqli) {
            require 'config.php';
            
            $dir_query = $mysqli->query("SELECT filename from $FILE_TABLE WHERE dir=1 and path='$old_path'");
            $directories = $dir_query->fetch_all(MYSQLI_ASSOC);
            
            // Adjust files and folders all at once
            $mysqli->query("UPDATE $FILE_TABLE set path='$new_path' WHERE path='$old_path'");	

            // Recursively adjust subdirectories - remember that these don't require renaming
            foreach($directories as $value) {
              $old_path = $old_path.(str_replace("\\", "\\\\", DIRECTORY_SEPARATOR));
              $new_path = $new_path.(str_replace("\\", "\\\\", DIRECTORY_SEPARATOR));
              ren_recursive($old_path.$value['filename'],$new_path.$value['filename'] , $mysqli);
            }
          }
        }
        
        ren_recursive($old_path, $new_path, $mysqli); 
        
        //Finally, rename the directory in both the file system and the database
        $mysqli->query("UPDATE $FILE_TABLE set filename='$ren_new_name' where path='$escaped_path' AND filename='$ren_old_name' AND dir = 1");
        rename($fs_root.$path.DIRECTORY_SEPARATOR.$ren_old_name, $fs_root.$path.DIRECTORY_SEPARATOR.$ren_new_name);
        
        /* If there are arise any issues, it'll certainly be related to whitespace. Will check soon */
      }
    }
  }
          
?>

<html>
  <head>
    <link rel="stylesheet" href="/project/style.css">
    <script src="/project/forms.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
      <?php
		    echo $path;
      ?>
    </title>
  </head>

  <body>
    <h1>
      <?php
        if ($path == "/project/sem4" || $path == "\project\sem4")
          echo "Home";
        else {
	        echo $path;
          // Since Linux directory path will not have backslashes, there is no issue
 			    $parent_dir_link = str_replace("\\\\", "/", $escaped_parent_dir);
			    echo "<a class='up' href='$parent_dir_link'>&uarr;</a>";
		    }
      ?>
    </h1>
    
    <?php 
      require 'forms.php';
    ?>
    
    <div class="wrapper">
      <?php
        $result = $mysqli->query("SELECT * from $FILE_TABLE where dir=1 and filename='".$dir_name."' and path='".$escaped_parent_dir."'");
		    
        // We follow the convention: $file_list is retrieved by scandir or from dba_close
        // $new_list is formed and has the required info to display in the page
        
        // We also need 2 arrays: filenames and directory names for JavaScript
        // This is for client-side validation of the file upload form
        // Those two arrays are $js_file_list and $js_dir_list
        $js_file_list = array();
        $js_dir_list = array();
        
		    if ($result->num_rows != 0)
		      $templist = $result->fetch_all(MYSQLI_ASSOC);
		    
        // In case the directory has not been inserted or indexed, we need to do that
        if (($result->num_rows == 0) || ($templist[0]['indexed'] == 0)) {
          $mysqli->query("INSERT INTO $FILE_TABLE values('$dir_name', '$escaped_parent_dir', 1, 1, 'DIR', CURRENT_TIMESTAMP(), 'Pub') ON DUPLICATE KEY UPDATE indexed=1");

          $file_list = scandir(".", 1);
          $file_list_size = count($file_list);

          $new_list = array();
          $new_list_size = 0;

          for ($i = 0; $i < $file_list_size; $i++) {
            if (! ((preg_match('/.php/i', $file_list[$i])) || (is_dir($file_list[$i])))) {
              if ( preg_match('/.jpeg/i', $file_list[$i]) ||
                   preg_match('/.jpg/i', $file_list[$i]) ||
                   preg_match('/.png/i', $file_list[$i]) ||
                   preg_match('/.webp/i', $file_list[$i]) ||
                   preg_match('/.gif/i', $file_list[$i]) ||
                   preg_match('/.bmp/i', $file_list[$i])
                 ) {
                $ftype = "IMG";
                $file_image = $file_list[$i];
              }

              else if ( preg_match('/.pdf/i', $file_list[$i]) || 
       			            preg_match('/.doc/i', $file_list[$i]) ||
	                      preg_match('/.docx/i', $file_list[$i])
                      ) {
                $ftype = "DOC";
                $file_image = $doclink;
              }

              else {
                $ftype = "FIL";
                $file_image = $filelink;
              }
              
              array_push($js_file_list, $file_list[$i]);
              $new_list[$new_list_size] = array("name" => $file_list[$i], "image" => $file_image);
              $new_list_size++;
              $mysqli->query("INSERT INTO $FILE_TABLE values('$file_list[$i]', '$escaped_path', 0, 1, '$ftype', CURRENT_TIMESTAMP(), 'Pub')");	    
            }

            else if (is_dir($file_list[$i])) {
              // We want to prevent . and .. as folder names as well
              array_push($js_dir_list, $file_list[$i]);
              
              if (! (($file_list[$i] == ".") || ($file_list[$i] == "..") || (preg_match('/_files/i', $file_list[$i])))) {	  
                $ftype = "DIR";
                $file_image = $folderlink;

                // Replicated because we do NOT want to add . or .. to the list
                // I found that out the hard way by cleverly putting this only once
                // as the last line of the loop

	              $new_list[$new_list_size] = array("name" => $file_list[$i], "image" => $file_image);
                $new_list_size++;
             
	              $mysqli->query("INSERT INTO $FILE_TABLE values('$file_list[$i]', '$escaped_path', 1, 0, '$ftype', CURRENT_TIMESTAMP(), 'Pub')");	
				        // Replicate index.php in sub-directory
                if ($WINDOWS)
                  copy("index.php", $file_list[$i].'\\'."index.php");	
                else
                  copy("index.php", $file_list[$i].'/'."index.php");
              }
            }
          }
        }

        else {
          $result = $mysqli->query("SELECT * from $FILE_TABLE where path='$escaped_path'");
          $file_list = $result->fetch_all(MYSQLI_ASSOC);
          $file_list_size = $new_list_size = $result->num_rows;
          $new_list = array();

          for($i = 0; $i < $new_list_size; $i++) {
            if($file_list[$i]['type'] == "IMG")
              $new_list[$i] = array("name" => $file_list[$i]['filename'], "image" => $file_list[$i]['filename']);
            else if ($file_list[$i]['type'] == "DOC")
              $new_list[$i] = array("name" => $file_list[$i]['filename'], "image" => $doclink);
            else if ($file_list[$i]['type'] == "DIR")
              $new_list[$i] = array("name" => $file_list[$i]['filename'], "image" => $folderlink);
            else
              $new_list[$i] = array("name" => $file_list[$i]['filename'], "image" => $filelink);
            
            if($file_list[$i]['type'] == "DIR")
              array_push($js_dir_list, $file_list[$i]['filename']);
            else
              array_push($js_file_list, $file_list[$i]['filename']);
          }
          array_push($js_dir_list, ".", "..");
        }
	 
        for ($i = 0; $i < $new_list_size; $i++) {
          echo "<div class='item'>";
          echo '<a href="'.$new_list[$i]['name'].'">';
          echo '<figure>'.'<img src ="'.$new_list[$i]['image'].'"'."id='i$i'".' />';
          echo "<figcaption id='n$i'>".$new_list[$i]['name'].'</figcaption>';
          echo '</figure>'.'</a>';
          echo "<span class='del_pointer' onclick='delForm($i)'>"."&check;"."</span> ";
          echo "<span class='ren_pointer' onclick='renForm($i)'>"."&check;"."</span>";
          echo "</div>";
        }
      ?>
    </div>

    <script type="text/javascript">
    // Needed for client side validation of forms
       var fileList = <?php echo json_encode($js_file_list); ?>;
       var dirList = <?php echo json_encode($js_dir_list); ?>;
    </script>
  </body>
</html>

