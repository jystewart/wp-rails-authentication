<?php
/*
Plugin Name: WP Rails Authenticate
Version: 1.0
Description: Provides tools to authenticate wordpress users against a Ruby on Rails application database
Author: James Stewart
Author URI: http://jystewart.net/process/
*/

if (! class_exists('WP_Rails_Authentication')) {
  class WP_Rails_Authentication {
    function __construct() {
      if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
        add_action('init', array(&$this, 'initialize_options'));
      }
      add_action('admin_menu', array(&$this, 'add_options_page'));
      add_action('wp_authenticate', array(&$this, 'authenticate'), 10, 2);
      add_filter('check_password', array(&$this, 'skip_password_check'), 10, 4);
      add_action('lost_password', array(&$this, 'disable_function'));
      add_action('retrieve_password', array(&$this, 'disable_function'));
      add_action('password_reset', array(&$this, 'disable_function'));
      add_action('check_passwords', array(&$this, 'generate_password'), 10, 3);
      add_filter('show_password_fields', array(&$this, 'disable_password_fields'));
    }
    
    /*************************************************************
     * Plugin hooks
     *************************************************************/

    /*
     * Add options for this plugin to the database.
     */
    function initialize_options() {
      if (current_user_can('manage_options')) {
        add_option('yaml_file', false, 'Path to root of rails application');
      }
    }
    
    /*
     * Apply the encryption for comparing passwords.
     *
     * This presumes a simple sha512 hash of salt and password. Change this function to 
     * use a different encryption approach
     *
     * @param stdClass the user object returned from the rails app's database
     * @return string the encrypted password
     */
    function apply_encryption($data) {
      return hash('sha512', "--{$data->salt}--{$password}--");
    }
    
    /*
     * Add an options pane for this plugin.
     */
    function add_options_page() {
      if (function_exists('add_options_page')) {
        add_options_page('WP Rails Authentication', 'WP Rails Authentication', 9, __FILE__, array(&$this, '_display_options_page'));
      }
    }
    
    function _display_options_page() {
      $options = array('yaml_file' => get_option('yaml_file'));
      
      if (isset($_POST['yaml_file'])) {
        $new_path = attribute_escape(stripslashes($_POST['yaml_file']));
        if (file_exists($new_path)) {
          update_option('yaml_file', $new_path);
          echo '<div class="updated"><p>' . __('File location saved') . '</p></div>';          
        } else {
          echo '<div class="error"><p>' . __('Please enter a valid path to a folder') . '</p></div>';
        }
      }
      
      include dirname(__FILE__) . '/templates/options.tpl.php';
    }
    
    /**
     * We offer two ways to define the root path of your rails app. Retrieve the correct one.
     */
    function get_yaml_file() {
      if (defined('RAILS_ROOT')) {
        return RAILS_ROOT . '/config/database.yml';
      } else {
        $opt = get_option('yaml_file');
        if (empty($opt)) {
          wp_die('yaml file location not defined');
        }
        return $opt;
      }
    }
    
    /**
     * Make and return a connection to the rails app's database
     */
    function oc_db() {
      if (empty($this->db)) {
        $config_file = realpath($this->get_yaml_file());
        // load our database credentials from the rails database.yml file
        $yaml = file_get_contents($config_file);
        $data = syck_load($yaml);
        $credentials = $data['staging'];
        extract($credentials);
        $this->db = new mysqli($host, $username, $password, $database);
      }
      return $this->db;
    }
    
    function authenticate_rails($username, $password) {
      $db = $this->oc_db();
      $query = sprintf("SELECT `id`, `first_name`, `last_name`, `email`, `encrypted_password`, `salt` FROM `users` WHERE `email` = '%s'", 
        mysql_real_escape_string($username));
      $login = $db->query($query);
      if (! $login->num_rows || $login->num_rows == 0) {
        return false;
      }
      $data = $login->fetch_object();
      
      $encrypted_password = $this->apply_encryption($data);
      if ($encrypted_password == $data->encrypted_password) {
        return $data;
      }
      return false;
    }
    
    function authenticate($username, $password) {

      $username = sanitize_user($username);

      if (empty($username)) {
        return new WP_Error('empty_username', __('<strong>ERROR</strong>: The username field is empty.'));
      }

      if (empty($password)) {
        return new WP_Error('empty_password', __('<strong>ERROR</strong>: The password field is empty.'));
      }
      
      $oc_user = $this->authenticate_rails($username, $password);
      
      if ($oc_user) {
        // does this user exist already? if not create
        $user = get_userdatabylogin($username);
        if (! $user or $user->user_login != $username) {
          $user = $this->_create_user($oc_user);
        }
      } else {
        // do a standard wordpress authentication thing
        return wp_authenticate($username, $password);
      }

      return $user;
    }
    
    /*
     * Skip the password check, since we've externally authenticated.
     */
    function skip_password_check($check, $password, $hash, $user_id) {
      return true;
    }
    
    /*
     * Used to disable certain display elements, e.g. password
     * fields on profile screen.
     */
    function disable_password_fields($show_password_fields) {
      return false;
    }
    
    /*
     * Used to disable certain login functions, e.g. retrieving a
     * user's password.
     */
    function disable_function() {
      die('Disabled');
    }
    
    /*************************************************************
     * Functions
     *************************************************************/

    /*
     * Generate a random password.
     */
    function _get_password($length = 10) {
      return substr(md5(uniqid(microtime())), 0, $length);
    }

    /*
     * Create a new WordPress account for the specified username.
     *
     * @todo  Store some extra metadata to identify them as an OnlyConnect user and store their ID
     */
    function _create_user($oc_user) {
      $password = $this->_get_password();

      $user_data = array(
        'user_pass' => $password,
        'user_login' => $oc_user->email,
        'first_name' => $oc_user->first_name,
        'last_name' => $oc_user->last_name,
        'display_name' => $oc_user->first_name . ' '. $oc_user->last_name,
        'email' => $oc_user->email
      );
      
      require_once(WPINC . DIRECTORY_SEPARATOR . 'registration.php');
      $user_id = wp_insert_user($user_data);
      update_usermeta($user_id, 'onlyconnect_id', $oc_user->id);
      return get_userdatabylogin($oc_user->email);
    }
  }
}

// Load the plugin hooks, etc.
$oc_authentication_plugin = new WP_Rails_Authentication();