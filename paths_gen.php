<?php
  /* 
    $path - the path of the directory in which index.php resides
    $fs_root - document root but will use the correct slash for the Operating System
    Required for deletion/renaming
    $dir_name - the name of the above mentioned directory
    $parent_dir - the directory which contains the above mentioned directory
    This is because a directory is just a special file and it is present in a directory
    $pos - the position of the last slash

    escaping is required for Windows since it uses '\' in directory paths and '\' is used for
    escape sequences in MySQL
    
    for Linux, we can simply leave it alone
  */
    
  // __DIR__ should not be used since we really want the directory in which the script is executing
  $path = substr(getcwd(), strlen($_SERVER['DOCUMENT_ROOT']));
  $fs_root = substr(getcwd(), 0, strlen($_SERVER['DOCUMENT_ROOT']));

  if (PHP_OS_FAMILY == 'Windows')
    $pos = strrpos($path, '\\');
  else 
    $pos = strrpos($path, '/');
  
  $dir_name = substr($path, $pos+1);
  $parent_dir = substr($path, 0, $pos);
  
  $escaped_path = str_replace("\\", "\\\\", $path);
  $escaped_parent_dir = str_replace("\\", "\\\\", $parent_dir);
?>