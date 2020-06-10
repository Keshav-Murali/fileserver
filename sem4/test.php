<html>
<body>
  <?php
    $path = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));
	echo $path."\n";
  
    $pos = strrpos($path, '\\');
    $file_name = substr($path, $pos+1);
    $directory = substr($path, 0, $pos);
	
	echo $file_name." ";
	echo $directory." ";
         
	$tmp = addcslashes($directory, '\\');
	echo $tmp;
  ?>
</body>
</html>
