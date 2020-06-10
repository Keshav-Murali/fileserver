<!DOCTYPE HTML>

<html>
  <head>
    <link rel="stylesheet" href="/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
      <?php
        $dir = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
		echo $dir;
      ?>
    </title>
  </head>

  <body>
    <h1>
      <?php
	    echo $dir;
      ?>
    </h1>
     
    <div class="wrapper">
      <?php
        $DB_NAME = "project";
        $USER_TABLE = "user";
        $FILE_TABLE = "file";
        $doclink = "/document.png";
		$folderlink = "/folder.png";
        $filelink = "/file.png";

        $pos = strrpos($dir, '\\');
        $file_name = substr($dir, $pos+1);
        $directory = substr($dir, 0, $pos);

        $mysqli = new mysqli("localhost", "root", "", "project");
        $res = $mysqli->query("SELECT * from file where fordir=1 and filename='".$file_name."' and path='".$directory."'");
  
        if ($res->num_rows == 0) {
          $mysqli->query("INSERT INTO file values('$file_name', '$directory', 1, 'DIR', CURRENT_TIMESTAMP(), 'Pub')");

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
            $mysqli->query("INSERT INTO file values('$list[$i]', '$dir', 0, '$ftype', CURRENT_TIMESTAMP(), 'Pub')");	    
            }

            else if (is_dir($list[$i])) {
              if (! (($list[$i] == ".") || ($list[$i] == "..") || (preg_match('/_files/i', $list[$i])))) {	  
                $newlist[$nlindex] = array($list[$i], "dir", $folderlink);
	            $nlindex++;
	            $mysqli->query("INSERT INTO file values('$list[$i]', '$dir', 1, 'DIR', CURRENT_TIMESTAMP(), 'Pub')");	    
              }
            }
          }

          $newlist = array_merge($newlist, $filelist);
          $nlindex = count($newlist);
        }

        else {
          $qres = $mysqli->query("SELECT * from file where path='$dir'");
          $templist = $qres->fetch_all(MYSQLI_NUM);
          $nlindex = $qres->num_rows;
          $newlist = array();

          for($i = 0; $i < $nlindex; $i++) {
            if($templist[$i][3] == "IMG")
              $templist[$i][3] = $templist[$i][0];
            else if ($templist[$i][3] == "DOC")
              $templist[$i][3] = $doclink;
            else if ($templist[$i][3] == "DIR")
              $templist[$i][3] = $folderlink;
            else
              $templist[$i][3] = $filelink;

            $newlist[$i] = array($templist[$i][0], $templist[$i][3], $templist[$i][3]);
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

