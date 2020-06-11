<!DOCTYPE HTML>

<html>
  <head>
    <link rel="stylesheet" href="/project/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
      <?php
        $full_path = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
		echo $full_path;
		$pos = strrpos($full_path, '\\');
        $folder_name = substr($full_path, $pos+1);
        $directory = addcslashes(substr($full_path, 0, $pos), '\\');
		$escaped_full_path = addcslashes($full_path, '\\');
      ?>
    </title>
  </head>

  <body>
    <h1>
      <?php
	    echo $full_path;
		if ($full_path != "\project\sem4") {
			$mod_directory = str_replace("\\\\", "/", $directory);
			echo "<a class='up' href='$mod_directory'>&uarr;</a>";
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
        $res = $mysqli->query("SELECT * from file where dir=1 and filename='".$folder_name."' and path='".$directory."'");
		
		$tlimit = $res->num_rows;
		if ($tlimit != 0)
		  $templist = $res->fetch_all(MYSQLI_NUM);
		
        if (($tlimit == 0) || ($templist[0][3] == 0)) {
          $mysqli->query("INSERT INTO file values('$folder_name', '$directory', 1, 1, 'DIR', CURRENT_TIMESTAMP(), 'Pub') ON DUPLICATE KEY UPDATE indexed=1");

          $list = scandir(".", 1);
          $listsize = count($list);

          $newlist = array();
          $nlindex = 0;

          $filelist = array();
          $fileindex = 0;

          for ($i = 0; $i < $listsize; $i++) {
            if (! ((preg_match('/.php/i', $list[$i])) || (is_dir($list[$i])))) {
              if ( preg_match('/.jpeg/i', $list[$i]) ||
                   preg_match('/.jpg/i', $list[$i]) ||
                   preg_match('/.png/i', $list[$i]) ||
                   preg_match('/.webp/i', $list[$i]) ||
                   preg_match('/.gif/i', $list[$i]) ||
                   preg_match('/.bmp/i', $list[$i])
                 ) {
                   $ftype = "IMG";
                   $ffile = $list[$i];
              }

              else if ( preg_match('/.pdf/i', $list[$i]) || 
			            preg_match('/.doc/i', $list[$i]) ||
	                    preg_match('/.docx/i', $list[$i])
                      ) {
                        $ftype = "DOC";
                        $ffile = $doclink;
              }

              else {
                $ftype = "FIL";
                $ffile = $filelink;
              }

            $filelist[$fileindex] = array($list[$i], $ftype, $ffile);
            $fileindex++;
            $mysqli->query("INSERT INTO file values('$list[$i]', '$escaped_full_path', 0, 1, '$ftype', CURRENT_TIMESTAMP(), 'Pub')");	    
            }

            else if (is_dir($list[$i])) {
              if (! (($list[$i] == ".") || ($list[$i] == "..") || (preg_match('/_files/i', $list[$i])))) {	  
                $newlist[$nlindex] = array($list[$i], "dir", $folderlink);
	            $nlindex++;
	            $mysqli->query("INSERT INTO file values('$list[$i]', '$escaped_full_path', 1, 0, 'DIR', CURRENT_TIMESTAMP(), 'Pub')");	
				copy("index.php", $list[$i].'\\'."index.php");	// Replicate index.php in subdirectory
              }
            }
          }

          $newlist = array_merge($newlist, $filelist);
          $nlindex = count($newlist);
        }

        else {
          $qres = $mysqli->query("SELECT * from file where path='$escaped_full_path'");
          $templist = $qres->fetch_all(MYSQLI_NUM);
          $nlindex = $qres->num_rows;
          $newlist = array();

          for($i = 0; $i < $nlindex; $i++) {
            if($templist[$i][4] == "IMG")
              $templist[$i][4] = $templist[$i][0];
            else if ($templist[$i][4] == "DOC")
              $templist[$i][4] = $doclink;
            else if ($templist[$i][4] == "DIR")
              $templist[$i][4] = $folderlink;
            else
              $templist[$i][4] = $filelink;

            $newlist[$i] = array($templist[$i][0], $templist[$i][4], $templist[$i][4]);
          }		       
        }
	 
        for ($i = 0; $i < $nlindex; $i++) {
          echo "<div class='item'>";
          echo '<a href="'.$newlist[$i][0].'">';
          echo '<figure>'.'<img src="'.$newlist[$i][2].'"'.'>';
          echo '<figcaption>'.$newlist[$i][0].'</figcaption>';
          echo '</figure>'.'</a>';
          echo "</div>";
        }
      ?>
    </div>
  </body>
</html>

