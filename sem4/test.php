<html>
<body>
  <?php
    $mysqli = new mysqli("localhost", "root", "", "project");

    $qres = $mysqli->query("SELECT * FROM file where path='\sem4'");
    $newlist = $qres->fetch_all(MYSQLI_NUM);
  
    Print_r($newlist);
  
          
  ?>
</body>
</html>
