<div class="wrapper">
  <?php
    echo '<div class="func"><button type="button" onclick="displayForm(0)">Create folder</button></div>';
    echo '<div class="func"><button type="button" onclick="displayForm(1)">Upload file</button></div>';
    echo '<div class="func"><button type="button" onclick="displayChoice(0)">Delete</button></div>';
    echo '<div class="func"><button type="button" onclick="displayChoice(1)">Rename - not done</button></div>';
  ?>
</div>

<div class="overlay" id="overlay">
  <form id="folder_form" method="POST" enctype="application/x-www-form-urlencoded" class="function_form" onsubmit="return validateFolderForm(this)">
  <fieldset>
    <legend>Create folder</legend>
    <table>
      <tr>
        <td><label for="folder_name">Folder name</label></td>
        <td><input type="text" id="folder_name" name="folder_name" required></td>
      </tr>
    </table>
  </fieldset>
  <label class="error" id="folder_form_error"></label><br>

  <button type="submit" name="folder_form" action="">Check and Submit</button>
  <button type="button" onclick="closeOverlay(this)">Close</button>
  </form>

  <form id="file_form" onsubmit="return validateFileForm()" class="function_form" method="POST" enctype="multipart/form-data" action="">
  <fieldset>
    <legend>Upload file</legend><br>
    <table>
      <tr>
       <td>
         <input onchange="validateFileForm(0)" id="file" type="file" name="file" required>
        </td>
      </tr>
      <tr>
        <td><label id="hidden_label" hidden for="filename">File name - no extension!</label></td>
        <td><input hidden type="text" id="file_name" name="file_name"></td>
    </table>
  </fieldset>
  <label class="error" id="file_form_error"></label><br>
  <button type="submit" name="file_form" action="">Check and Submit</button>
  <button type="button" onclick="closeOverlay(this)">Close</button>  </form>
  
  <form id="delete_form" class="function_form" action="" method="POST">
    <fieldset>
      <legend>Delete</legend>
      <table>
        <tr>
          <td><label>Name</label></td>
          <td><input type="text" id="del_file_name" name="del_file_name" readonly></td>
        </tr>
        <tr>
          <td><label>Type</label></td>
          <td><input type="text" id="del_file_type" name="del_file_type" readonly></td>
        </tr>
      </table>
    </fieldset>
    <label class="error" id="del_form_desc">Are you sure you want to delete?</label><br>
    <button type="submit" name="delete_form" action="">Confirm</button>
    <button type="button" onclick="closeOverlay(this)">Close</button>
  </form>

  <form id="rename_form" class="function_form" action="" method="POST">
  <input type="text" name="file_name"></input>
  <input type="checkbox" name="folder"></input>
  </form>
</div>



