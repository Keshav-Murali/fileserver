<div class="wrapper">
  <?php
    echo '<div class="func"><button type="button" onclick="displayForm(0)">Create folder</button></div>';
    echo '<div class="func"><button type="button" onclick="displayForm(1)">Upload file</button></div>';

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

  <button type="submit" name="folder_form" action="">Check and Submit</input>
  <button type="button" onclick="closeOverlay(this)">Close</button>
  </form>

  <form id="file_form" onsubmit="return validateFileForm()" class="function_form" method="POST" enctype="multipart/form-data" action="">
  <fieldset>
    <legend>Upload file</legend><br>
    <table>
      <tr>
       <input onchange="validateFileForm(0)" id="file" type="file" name="file" required></input>
      </tr>
      <tr>
        <td><label id="hidden_label" hidden for="filename">File name - no extension!</label></td>
        <input hidden type="text" id="file_name" name="file_name"></input>
    </table>
  </fieldset>
  <label class="error" id="file_form_error"></label><br>

  <button type="submit" name="file_form" action="">Check and Submit</input>
  <button type="button" onclick="closeOverlay(this)">Close</button>  </form>
</div>