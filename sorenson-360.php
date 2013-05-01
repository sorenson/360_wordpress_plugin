<?php
/*
Plugin Name: Sorenson 360 Video Plugin
Plugin URI: http://www.sorensonmedia.com/wordpress-plugin/
Description: Integration of your Sorenson File library into Wordpress.
Version: 1.3.2
Author: Sorenson Media
Author URI: http://www.sorensonmedia.com/
*/

// Copyright (c) 2013 Sorenson Media, Inc. All rights reserved.
//
// This is an add-on for WordPress
// http://wordpress.org/
//
// Thanks to Alex King for his contributions.
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************
// ini_set('error_reporting', E_ALL);


load_plugin_textdomain('sorenson360');

if (!defined('PLUGINDIR')) {
  define('PLUGINDIR','wp-content/plugins');
}

if (!defined('THISPLUGINDIR')) {
  define('THISPLUGINDIR',PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)));
}


if (!function_exists('is_admin_page')) {
  function is_admin_page() {
    if (function_exists('is_admin')) {
      return is_admin();
    }
    if (function_exists('check_admin_referer')) {
      return true;
    }
    else {
      return false;
    }
  }
}

/**********************
*
* Define Constants
*
***********************/

include ABSPATH.PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/sorenson-php-lib/sorenson_360.php';

define ('SOR360_PLUGIN_HELP_URL', 'http://www.sorensonmedia.com/wordpress-plugins/');

define ('SOR360_PAGINATION_LIMIT', '15');

/**********************
*
* Plugin Install Function
*
***********************/


function sor360_install() {
  global $wpdb;

  $sor360_install = new sorenson_360;
  $wpdb->sor360 = $wpdb->prefix.'sor360';
  
  foreach ($sor360_install->options as $option) {
    add_option('sor360_'.$option, $sor360_install->$option);
  }
  
  //create table to store embed codes
    
  $wpdb->sor360_e = $wpdb->prefix.'sor360_embed';
  $charset_collate = '';
  if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
    if (!empty($wpdb->charset)) {
      $charset_collate .= " DEFAULT CHARACTER SET $wpdb->charset";
    }
    if (!empty($wpdb->collate)) {
      $charset_collate .= " COLLATE $wpdb->collate";
    }
  }
  $result = $wpdb->query("
    CREATE TABLE `$wpdb->sor360_e` (
    `asset_key` varchar(100) NOT NULL,
      `embed_code` text NOT NULL,
      `asset_id` varchar(144) NOT NULL,
      PRIMARY KEY  (`asset_key`,`asset_id`)
    ) $charset_collate 
  ");
}
register_activation_hook(__FILE__, 'sor360_install');



/**********************
*
* Plugin Class
*
***********************/

class sorenson_360 {
  function sorenson_360() {
    global $wp_db_version, $wpmu_version, $shortcode_tags;
  
    $this->options = array(
      'username',
      'password',
      'install_date',
      'author_override',
    );
    $this->sor360_username = '';
    $this->sor360_password = '';
    $this->install_date = '';
    $this->author_override = 'on';

    // not included in options
    $this->version = '0.01';

    // init process for button control
    add_filter( 'mce_external_plugins', array(&$this, 'mce_external_plugins') );
    add_filter( 'mce_buttons', array(&$this, 'mce_buttons') );
    add_action( 'edit_form_advanced', array(&$this, 'SorensonQuicktagsAndFunctions') );
    add_action( 'edit_page_form', array(&$this, 'SorensonQuicktagsAndFunctions') );
  

  }


  // Load the custom TinyMCE plugin
  function mce_external_plugins( $plugins ) {
    $plugins['sor360'] = plugins_url(dirname(plugin_basename(__FILE__)).'/resources/tinymce3/editor_plugin.js');
    return $plugins;
  }


  // Add the custom TinyMCE buttons
  function mce_buttons( $buttons ) {
    array_push( $buttons, 'sorensonembed');
    return $buttons;
  }
   
  
  
   
  function upgrade() {
    global $wpdb;
    // TODO.. put db upgrades functions here and call when needed

  }
  
  // get sor360 settings from db
  function get_settings() {
    foreach ($this->options as $option) {
      $this->$option = get_option('sor360_'.$option);
    }
  }
  
  // grab $_post fields and create options obj
  function populate_settings() {
    foreach ($this->options as $option) {
      if (isset($_POST['sor360_'.$option])) {
        $this->$option = stripslashes($_POST['sor360_'.$option]);
      } 
      if (empty($_POST['sor360_author_override'])) {
        $this->author_override = 'off';
      }
    }
  }
  
  
  // puts/updates options obj into db
  function update_settings($user_id = null) {
    if (current_user_can('manage_options')) {
      foreach ($this->options as $option) {
        update_option('sor360_'.$option, $this->$option);
      }
      if (empty($this->install_date)) {
        update_option('sor360_install_date', current_time('mysql'));
      }     
    } else {
      update_usermeta($user_id,'sor360_username',$_POST['sor360_username']);  
      update_usermeta($user_id,'sor360_password',$_POST['sor360_password']);
    }
  } 
  
  
  // adds the embed code, id, and key to db for retrieveal by shortcode
  function add_embed_database($s360assetid,$s360assetkey,$s360embedcode) {
    global $wpdb;
    $wpdb->sor360_e = $wpdb->prefix.'sor360_embed';
    $query = ("INSERT IGNORE INTO `$wpdb->sor360_e` ( `asset_key` , `embed_code` , `asset_id` )
VALUES ('$s360assetkey','$s360embedcode','$s360assetid');
");

    return $wpdb->query($query); 
  
  
  }
  // grab the embed code and return it.. called by ajax
  function s360_video_embed($asset_id,$asset_key) {
    global $wpdb;
    
    //return print_r($attr['asset_id']);
    $id = $asset_id;
    $key = $asset_key;
    $wpdb->sor360_e = $wpdb->prefix.'sor360_embed';
     $sor_asset = $wpdb->get_var("SELECT embed_code FROM $wpdb->sor360_e WHERE asset_key = '$key' AND asset_id = '$id'");
    
    print($sor_asset);
    exit;
  }

  // Output all of the JS for the button function + dialog box
  function SorensonQuicktagsAndFunctions() {
  
  //the js to handle lightbox overlays
  ?>
  <script type="text/javascript">
  // <![CDATA[
    function s360ButtonClick() {
      tb_show('Sorenson 360 Embed','<?php bloginfo('wpurl'); ?>/index.php?sor360_action=sor360_embed_dialog&width=720&height=600');
      
      
    }
    </script>
  <?php 
  }

} // end class

/**************
* Test the 360 Auth
***************/

function sor360_login_test($username, $password) {

  $account = S360_Account::login($username, $password);

  if (is_a($account, 'S360_Account')) {
    return __("360 Login succeeded, Let's Rock and Roll, Press Update Below to save.", 'sorenson_360');
  } else {
    return __('Sorry, 360 login failed. Error message from Sorenson 360: ' . $account['errorMessage'], 'sorenson_360');
  }
}

/**************
* init
***************/
function sor360_init() {
  global $wpdb, $sor360;
  $sor360 = new sorenson_360;

  $sor360->get_settings();
  
  global $wp_version;
  
  wp_enqueue_script( 'jquery-ui-tabs' );
  
  if (isset($wp_version) && version_compare($wp_version, '2.5', '>=') && empty ($sor360->install_date)) {
    add_action('admin_notices', create_function( '', "echo '<div class=\"error\"><p>Please update your <a href=\"".get_bloginfo('wpurl')."/wp-admin/options-general.php?page=sorenson-360.php\">360 settings</a>.</p></div>';" ) );
  }
}
add_action('init', 'sor360_init');

/**************
* Include JS and CSS in admin screen
***************/

function sor360_head_admin() {
  print('
    <link rel="stylesheet" type="text/css" href="'.get_bloginfo('wpurl').'/index.php?sor360_action=sor360_css_admin" />
    <script type="text/javascript" src="'.get_bloginfo('wpurl').'/index.php?sor360_action=sor360_js_admin"></script>
  ');
}
add_action('admin_head', 'sor360_head_admin');

/**************
* Placeholder swap 
***************/

function sor360_placeholder_swap() {
  print('
  <script type="text/javascript" src="'.get_bloginfo('wpurl').'/wp-includes/js/jquery/jquery.js?ver=1.2.6"></script>
  <script type="text/javascript" src="'.get_bloginfo('wpurl').'/index.php?sor360_action=sor360_placeholder_swap"></script>
  ');
}
add_action('wp_head', 'sor360_placeholder_swap');


/**************
* Handle requests from option settings form
***************/

function sor360_request_handler() {
  global $wpdb, $sor360;
  if (!empty($_GET['sor360_action'])) {
    switch($_GET['sor360_action']) {
      case 'sor360_js':
        header("Content-type: text/javascript");
        //js
        break;
        die();
      case 'sor360_embed_dialog':
        sor360_embed_dialog();
        die();
        break;
      case 'sor360_file_list':
        sor360_build_file_list();
        die();
        break;
      case 'sor360_css':
        header("Content-Type: text/css");
        // css
        die();
        break;
      case 'sor360_js_admin':
        header("Content-Type: text/javascript");
        include ABSPATH.PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/resources/sorenson-360-admin.js';
        die();
        break;
      case 'sor360_placeholder_swap':
        header("Content-Type: text/javascript");
        include ABSPATH.PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/resources/sorenson-360-placeholder.js';
        die();
        break;
      case 'sor360_css_admin':
        header("Content-Type: text/css");
        include ABSPATH.PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/resources/sorenson-360-admin.css';
        die();
        break;
      case 'sor360_foo':
        header('Content-Type: text/javascript');
        print(sor360_build_file_list());
        die();
        break;
    

    }     
  }
  // handle $_POST from admin form config/options form
  if (!empty($_POST['sor360_action'])) {
    switch($_POST['sor360_action']) {
      case 'sor360_update_settings':
        $sor360->populate_settings();
        $sor360->update_settings();
        wp_redirect(get_bloginfo('wpurl').'/wp-admin/options-general.php?page=sorenson-360.php&updated=true');

        die();
        break;
      case 'sor360_login_test':
        $test = @sor360_login_test(
          @stripslashes($_POST['sor360_username'])
          , @stripslashes($_POST['sor360_password'])
        );
        die(__($test, 'sorenson-360'));
        break;
      case 'sor360_swap_shortcode':
        $sor360->s360_video_embed($_POST['s360assetid'],$_POST['s360assetkey']);
        break;
      case 'sor360_pagination':
        sor360_pagination($_POST['s360page']);
        break;
      case 'sor360_update_settings_user':
        $sor360->update_settings($_POST['sor360_user_id']);
        break;
      case 'sor360_insert_shortcode':
        $result = $sor360->add_embed_database($_POST['s360assetid'],$_POST['s360assetkey'],$_POST['s360embedcode']);
        //echo $result;
        die(__($result, 'sorenson-360'));
        break;
    }// end switch
  } // end $_POST action check
} // end request_handler

add_action('init', 'sor360_request_handler', 10);


/**************
* Admin plugin optons config form
***************/
function sor360_options_form() {
  global $wpdb, $sor360;
  
  $sor360->author_override == 'on' ? $override = "checked='checked'" : "";
  print('
      <div class="wrap" id="sor360_options_page">
        <h2>'.__('Sorenson 360 Options', 'sorenson-360').'</h2>
        <form id="sor360_options" name="sor360_options" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post">
          <input type="hidden" name="sor360_action" value="sor360_update_settings" />
          <fieldset class="options">
            <div class="option">
              <table>
                <tr>
                  <td>
                    <label for="sor360_username">'.__('Username', 'sorenson-360').'</label>
                    <input type="text" size="25" name="sor360_username" id="sor360_username" value="'.$sor360->username.'" autocomplete="off" />
                  </td>
                  <td>
                    <label for="sor360_username">'.__('Password', 'sorenson-360').'</label>
                    <input type="password" size="25" name="sor360_password" id="sor360_password" value="'.$sor360->password.'" autocomplete="off" /></td>
                  <td valign="bottom"><input type="button" name="sor360_login_test" id="sor360_login_test" value="'.__('Test Login Info', 'sorensen-360').'" onclick="sor360TestLogin(); return false;" /></td>
                </tr>
              </table>
              <div id="sor360_login_test_result"></div>
            </div>
            
          </fieldset>
          <p id="author_override"><input type="checkbox" name="sor360_author_override" '.$override.'/> Allow individual Authors to supply/use their own 360 library? </p>
          <p class="submit">
            <input type="submit" name="submit" value="'.__('Update Sorenson 360 Options', 'sorenson-360').'" />
          </p>
        </form>
      </div>
  ');
        //<h2>'.__('README', 'sorenson-360').'</h2>
        //<p>'.__('Find answers to common questions here.', 'sorenson-360').'</p>
        //<iframe id="sor360_readme" width="85%" height="700" src="'.SOR360_PLUGIN_HELP_URL.'"></iframe>
}

/**************
* Add actions/menus to admin controls
***************/

function sor360_menu_items() {
  if (current_user_can('manage_options')) {
    add_options_page(
      __('Sorenson 360 Options', 'sorenson-360')
      , __('Sorenson 360', 'sorenson-360')
      , 10
      , basename(__FILE__)
      , 'sor360_options_form'
    );
  }
  
}
add_action('admin_menu', 'sor360_menu_items');

function sor360_plugin_action_links($links, $file) {
  $plugin_file = basename(__FILE__);
  if ($file == $plugin_file) {
    $settings_link = '<a href="options-general.php?page='.$plugin_file.'">'.__('Settings', 'sorenson-360').'</a>';
    array_unshift($links, $settings_link);
  }
  return $links;
}
add_filter('plugin_action_links', 'sor360_plugin_action_links', 10, 2);


/**************
* Add actions/menus to profile page for users.. 
***************/
function sor360_unique_author_auth() {

  global $wpdb, $sor360;
  
  $current_user = wp_get_current_user();
  $user_id = $current_user->ID;
  $role_id = get_usermeta($user_id,'wp_user_level');
  
  if ($sor360->author_override == 'on' && $role_id >= 2 && $user_id > 1) {
  
    $s_username = get_usermeta($user_id,'sor360_username');
    $s_password = get_usermeta($user_id,'sor360_password');
    
    print('
      <div class="wrap" id="sor360_options_page">
        <h3>'.__('Sorenson 360 Options', 'sorenson-360').'</h3>
        <p>If you have a Sorenson 360 Account, enter it below to gain access to your files to embed in posts</p>
          <input type="hidden" name="sor360_action" value="sor360_update_settings_user" />
          <input type="hidden" name="sor360_user_id" value="'.$user_id.'" />

          <fieldset class="options">
            <div class="option">
              <table>
                <tr>
                  <td>
                    <label for="sor360_username">'.__('Username', 'sorenson-360').'</label>
                    <input type="text" size="25" name="sor360_username" id="sor360_username" value="'.$s_username.'" autocomplete="off" />
                  </td>
                  <td>
                    <label for="sor360_username">'.__('Password', 'sorenson-360').'</label>
                    <input type="password" size="25" name="sor360_password" id="sor360_password" value="'.$s_password.'" autocomplete="off" /></td>
                  <td valign="bottom"><input type="button" name="sor360_login_test" id="sor360_login_test" value="'.__('Test Login Info', 'sorensen-360').'" onclick="sor360TestLogin(); return false;" /></td>
                </tr>
              </table>
              <div id="sor360_login_test_result"></div>
            </div>
            
          </fieldset>
        
      </div>
    ');
  }
}
add_filter('profile_personal_options','sor360_unique_author_auth');

/************
* 360 overlay on edit screen
*************/
function sor360_embed_dialog(){
  global $wpdb, $sor360;
  $current_user = wp_get_current_user();
  $user_id = $current_user->ID;  
  $role_id = get_usermeta($user_id,'wp_user_level');
  
  if ($sor360->author_override == 'on' && $role_id >= 2 && $user_id > 1) {
    $s_username = get_usermeta($user_id,'sor360_username');
    $s_password = get_usermeta($user_id,'sor360_password');
  } else {
    $s_username = $sor360->username;
    $s_password = $sor360->password;  
  }
    
  //live  
  $account = S360_Account::login($s_username, $s_password);
  
  // TODO Better error handling
  
  if (!$s_username || !$s_password) {
    print('<h3>Sorenson 360 username and password not set.</h3><p>Please visit your profile or settings to configure Sorenson 360</p>');
  } elseif (!is_a($account, 'S360_Account')) {
    print('<h3>Sorenson 360 Error: ' . $account['errorMessage'] . '</h3>');
  } else {
    
    $doc = '';
    
    $overlayhead = '
    <script type="text/javascript">
      function do_resize() {
        jQuery("#TB_ajaxContent").removeAttr("style");
        var bw = jQuery("#TB_window").height();
        jQuery("#TB_ajaxContent").css({"height" : bw - 60});
        jQuery("#TB_ajaxContent").css({"width" : 720 - 30});
        jQuery("#TB_window").css({"width" : 720});

      }
      
      jQuery(document).ready( function () {
        jQuery("#sor360Tabs").tabs();
      });
      
    </script>
    
    <div id="swrap">
    <h2 class="sor_w_title">Your Sorenson 360 library <small><em>Choose video to embed.</em></small></h2>
    
    <div id="sor360Tabs" class="ui-tabs">
      <ul class="ui-tabs-nav">
        <li><a href="#sor_file_list"><span>Library</span></a></li>
      </ul> 
';

    $file_list_tab = sor360_build_file_list($account);
                
    $overlayfoot = '
      </div><!-- end tab container -->
      
      </div><!-- end wrap -->

    ';

    $doc .= $overlayhead;
    $doc .= $file_list_tab;



    $doc .= $overlayfoot;

    print($doc);
  }
}

function sor360_build_file_list(){
  global $wpdb, $sor360;
  sor360_init();
  $current_user = wp_get_current_user();
  $user_id = $current_user->ID;  
  $role_id = get_usermeta($user_id,'wp_user_level');

  if ($sor360->author_override == 'on' && $role_id >= 2 && $user_id > 1) {
    $s_username = get_usermeta($user_id,'sor360_username');
    $s_password = get_usermeta($user_id,'sor360_password');
  } else {
    $s_username = $sor360->username;
    $s_password = $sor360->password;  
  }

  //live  
  $account = S360_Account::login($s_username, $s_password);
  
  $assets = $account->getAssets(0, SOR360_PAGINATION_LIMIT);
  $pages = round($account->totalAssetCount / SOR360_PAGINATION_LIMIT);
  
  // build file list tab
  $file_list_tab = '
    <div id="sor_file_list">
      <div class="sor-pagination-links">
        <div style="float:right"><strong>Page <span class="pagenum">1</span> of '.$pages.'</strong> ('.$account->totalAssetCount.' total assets)</div>
        
        ';
        
        for($i=1;$i <= $pages;$i++) {
          $i == 1 ? $style = "on" : $style="";
          $file_list_tab .= '<a href="#" onClick="sor360page('.$i.');"  class="l'.$i.' '.$style.'">'.$i.'</a> ';
        }
      $file_list_tab .='    

      <img src="'.plugins_url(dirname(plugin_basename(__FILE__))."/resources/ajax-loader-circle.gif").'" id="ajax_wait" style="display:none;" onload="do_resize();"/>
      </div>
      <div id="file_wrapper">
  ';
  
  
  $renderhtml = '';
  foreach($assets as $as) {
    $aspect = $as->width / $as->height;
    switch (round($aspect,2)) {
      case "1.33":
        $ratio = "4:3";
      break;
      case "1.78":
        $ratio = "16:9";
      break;
      case "1.00":
        $ratio = "Square";
      break;
      case "1.25":
        $ratio = "5:4";
      break;
      default:
        $ratio ="N/A";
      break;
      }
    $renderhtml .= '
        <div class="sorviewer">
          <h3>Title: '.$as->displayName.'</h3>
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td valign="top" width="280px">
                <img src="'.$as->thumbnailImageUrl.'"  id="'.$as->id.'" width="240"/>             
              </td>
              <td valign="top">
              <ul>
                <li><strong>Description:</strong> '.$as->description.'</li>
                <li><strong>Default Aspect Ratio:</strong> '.$ratio.' <strong>Duration:</strong> '.$as->videoDuration.'</li>
                <li><strong>FileName:</strong> '.substr($as->name, 30).'</li>
                <li><strong># Views:</strong> '.$as->numberOfViews.'</li>
                
                <li><small><strong>ID:</strong> '.$as->id.'</small></li>
                <li><strong>Select Size to Embed:</strong><br/>
                  <select id="sor360_embedlist" rel="'.$as->id.'">
            ';
                   foreach ($as->embedList as $k => $v) { 
                    $renderhtml .='<option value="'.$k.'">'.$k.'</option>';
                   }
                $renderhtml .=' 
                  </select>
                </li>
                <li><button id="sor_360_embed" class="button-primary" onclick="sor360DoEmbed(\''.$as->id.'\'); return false;">Add to post</button></li>
              </ul>
              </td>
            </tr>
          </table>  
          <div id= "asset-'.$as->id.'" style="display:none"> 
            ';
             foreach ($as->embedList as $k => $v) { 
              $renderhtml .='<textarea rel="'.$k.'">'.htmlentities($v).'</textarea>';
              }
          $renderhtml .='
          </div>
        
        </div>
        ';
  } // end foreach
  
  $file_list_tab .= $renderhtml;

  $file_list_tab .= '
      
      </div>
      <div class="sor-pagination-links">
          <div style="float:right"><strong>Page <span class="pagenum">1</span> of '.$pages.'</strong> ('.$account->totalAssetCount.' total assets)</div>
        
        ';
        
  for($i=1;$i <= $pages;$i++) {
    $i == 1 ? $style = "on" : $style="";
    $file_list_tab .= '<a href="#" onClick="sor360page('.$i.');" class="l'.$i.' '.$style.'">'.$i.'</a> ';
  }

  $file_list_tab .= '
        </div>

      </div>';
      
  return $file_list_tab;
}

/************
* 360 pagination in JS
*************/
function sor360_pagination($page){
  
  if ($page == 1) {
    $offset = 0;
  } else {
    $offset = (($page-1)*SOR360_PAGINATION_LIMIT) +1;
  }
  
  global $wpdb, $sor360;
  $current_user = wp_get_current_user();
  $user_id = $current_user->ID;  
  $role_id = get_usermeta($user_id,'wp_user_level');
  
  if ($sor360->author_override == 'on' && $role_id >= 2 && $user_id > 1) {
    $s_username = get_usermeta($user_id,'sor360_username');
    $s_password = get_usermeta($user_id,'sor360_password');
  } else {
    $s_username = $sor360->username;
    $s_password = $sor360->password;  
  }

  $account = S360_Account::login($s_username, $s_password);

  // TODO Better error handling
  if (!is_a($account, 'S360_Account')) {
    print('<h3>Sorenson 360 Error: ' . $account['errorMessage'] . '</h3>');
  } else {
    
    $assets = $account->getAssets($offset, SOR360_PAGINATION_LIMIT);
    $innerhtml = "";
    
    foreach($assets as $as) {
      $aspect = $as->width / $as->height;
      switch (round($aspect,2)) {
        case "1.33":
          $ratio = "4:3";
        break;
        case "1.78":
          $ratio = "16:9";
        break;
        case "1.00":
          $ratio = "Square";
        break;
        case "1.25":
          $ratio = "5:4";
        break;
        default:
          $ration ="N/A";
        break;
        }
      $innerhtml .= '
          <div class="sorviewer">
            <h3>Title: '.$as->displayName.'</h3>
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td valign="top" width="280px">
                  <img src="'.$as->thumbnailImageUrl.'"  id="'.$as->id.'" width="240"/>             
                </td>
                <td valign="top">
                <ul>
                  <li><strong>Description:</strong> '.$as->description.'</li>
                  <li><strong>Default Aspect Ratio:</strong> '.$ratio.' <strong>Duration:</strong> '.$as->videoDuration.'</li>
                  <li><strong>FileName:</strong> '.$as->name.'</li>
                  <li><strong># Views:</strong> '.$as->numberOfViews.'</li>
                  
                  <li><small><strong>ID:</strong> '.$as->id.'</small></li>
                  <li><strong>Select Size to Embed:</strong><br/>
                    <select id="sor360_embedlist" rel="'.$as->id.'">
                      ';
                     foreach ($as->embedList as $k => $v) { 
                      $innerhtml .= '<option value="'.$k.'">'.$k.'</option>';
                     }
                  $innerhtml .= ' 
                    </select>
                  </li>
                  <li><button id="sor_360_embed" class="button-primary" onclick="sor360DoEmbed(\''.$as->id.'\'); return false;">Add to post</button></li>
                </ul>
                </td>
              </tr>
            </table>  
            <div id= "asset-'.$as->id.'" style="display:none"> 
            ';
               foreach ($as->embedList as $k => $v) { 
                $innerhtml .= '<textarea rel="'.$k.'">'.htmlentities($v).'</textarea>';
                }
            $innerhtml .= '
            </div>
          </div>
      ';  
      
    } // end foreach
    
    print($innerhtml);
    exit;
  }
  
    
}

  
/************
* 360 shortcode for embeding and parsing post
*************/
// moved up to class
/*function s360_video_embed ($asset_id,$asset_key) {
  global $wpdb;
  
  //return print_r($attr['asset_id']);
  $id = $asset_id;
  $key = $asset_key;
  $wpdb->sor360_e = $wpdb->prefix.'sor360_embed';
   $sor_asset = $wpdb->get_var("SELECT embed_code FROM $wpdb->sor360_e WHERE asset_key = '$key' AND asset_id = '$id'");
  
  print('
    <div class="sorenson360_embedded">
      '.$sor_asset.'
    </div>
  ');
 
}*/
//add_shortcode('sorenson-360', 's360_video_embed');


?>
