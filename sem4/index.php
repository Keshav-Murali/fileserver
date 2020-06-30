<!DOCTYPE HTML>

<?php
  /* config.php has most of the constants that are required
     Examples: Database, relation details, image links
     functions.php has helper functions 
  */
  require_once 'config.php';
  require_once 'functions.php';
  
  // The DB may be needed to process form inputs, so we connect here
  // Apache will disconnect automatically once the script is processed
  $mysqli = new mysqli("localhost", $DB_USER, $DB_PASS, $DB_NAME);
  
  // See the quoted file to see the conventions used for the various paths
  require_once 'paths_gen.php';
  
  // Form processing
  require_once 'form_processing.php';
          
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
      require_once 'forms.php';
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
              $ftype = return_file_type($file_list[i]);
              $file_image = return_img_link($file_list[i], $ftype);
              
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

          // See functions.php for return_img_link funtion 
          for($i = 0; $i < $new_list_size; $i++) {
            $new_list[$i] = array("name" => $file_list[$i]['filename'],
                                  "image" => return_img_link($file_list[$i]['filename'],
                                                             $file_list[$i]['type']));
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
          echo '<figure>'.'<img src ="'.$new_list[$i]['image'].'"'." id='i$i'".' />';
          echo "<figcaption id='n$i'>".$new_list[$i]['name'].'</figcaption>';
          echo '</figure>'.'</a>';
          echo "<span class='del_pointer' onclick='delForm($i)'>"."&check;"."</span> ";
          echo "<span class='ren_pointer' onclick='renForm($i)'>"."&check;"."</span>";
          echo "</div>".PHP_EOL;
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

