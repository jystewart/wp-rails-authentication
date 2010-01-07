<div class="wrap">
  <h2>WP Rails Authentication</h2>
  
  <p>Make sure you check the apply_encryption function in the plugin and ensure that it matches the encryption method you use in your rails application.</p>
  
  <div style="float: left; width: 660px; margin: 5px;"> 
    <p>Set the path to your rails app's database.yml file (eg. <em>/var/www/mysite.com/current/config/database.yml</em>).</p>

    <form method="post" action="">
      <p>
        <label style="font-weight: bold" for="yaml_file"><?php __('Path') ?></label>
        <input id="yaml_file" name="yaml_file" type="text" value="<?php echo htmlentities($options['yaml_file']) ?>">
      </p>

      <p><input type="submit" name="update_options" value="Update"  style="font-weight:bold;" /></p>
    </form>
  </div>
</div>

