<!DOCTYPE HTML>

<html>
  <head>
    <link rel="stylesheet" href="/project/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
      <?php
        // $path - the path of the directory in which index.php resides
        // $dir_name - the name of the above mentioned directory
        // $parent_dir - the directory which contains the above mentioned directory
        // This is because a directory is just a special file and it is present in a directory
        // $pos - the position of the last slash
         
        
        // escaping is required for Windows since it uses '\' in directory paths
        // for Linux, we can simply leave it alone
        if (PHP_OS_FAMILY == 'Windows')
          $WINDOWS = true;
        
        $path = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
        
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
     
    <div class="wrapper">
      <?php
        $DB_NAME = "project";
        $USER_TABLE = "user";
        $FILE_TABLE = "file";
        $doclink = "/project/document.png";
		    $folderlink = "/project/folder.png";
        $filelink = "/project/file.png";

        $mysqli = new mysqli("localhost", "root", "", "project");
        $result = $mysqli->query("SELECT * from file where dir=1 and filename='".$dir_name."' and path='".$escaped_parent_dir."'");
		    
        // We follow the convention: $file_list is retrieved by scandir or from dba_close
        // $new_list is formed and has the required info to display in the page
		    if ($result->num_rows != 0)
		      $templist = $result->fetch_all(MYSQLI_ASSOC);
		    
        // In case the directory has not been inserted or indexed, we need to do that
        if (($result->num_rows == 0) || ($templist[0]['indexed'] == 0)) {
          $mysqli->query("INSERT INTO file values('$dir_name', '$escaped_parent_dir', 1, 1, 'DIR', CURRENT_TIMESTAMP(), 'Pub') ON DUPLICATE KEY UPDATE indexed=1");

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
              
              $new_list[$new_list_size] = array("name" => $file_list[$i], "image" => $file_image);
              $new_list_size++;
              $mysqli->query("INSERT INTO file values('$file_list[$i]', '$escaped_path', 0, 1, '$ftype', CURRENT_TIMESTAMP(), 'Pub')");	    
            }

            else if (is_dir($file_list[$i])) {
              if (! (($file_list[$i] == ".") || ($file_list[$i] == "..") || (preg_match('/_files/i', $file_list[$i])))) {	  
                $ftype = "DIR";
                $file_image = $folderlink;
                
                // Replicated because we do NOT want to add . or .. to the list
                // I found that out the hard way by cleverly putting this only once
                // as the last line of the loop
	              $new_list[$new_list_size] = array("name" => $file_list[$i], "image" => $file_image);
                $new_list_size++;
             
	              $mysqli->query("INSERT INTO file values('$file_list[$i]', '$escaped_path', 1, 0, '$ftype', CURRENT_TIMESTAMP(), 'Pub')");	
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
          $result = $mysqli->query("SELECT * from file where path='$escaped_path'");
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
          }		       
        }
	 
        for ($i = 0; $i < $new_list_size; $i++) {
          echo "<div class='item'>";
          echo '<a href="'.$new_list[$i]['name'].'">';
          echo '<figure>'.'<img src="'.$new_list[$i]['image'].'"'.'>';
          echo '<figcaption>'.$new_list[$i]['name'].'</figcaption>';
          echo '</figure>'.'</a>';
          echo "</div>";
        }
      ?>
    </div>
  </body>
</html>

