<?php
/*
Plugin Name: Sorenson 360 Video Plugin
Plugin URI: http://sorensonmedia.com
Description: Integration of your Sorenson File library into Wordpress.
Version: 0.0.2
Author: Sorenson Media
Author URI: http://sorensonmedia.com
*/

// Copyright (c) 2009 Sorenson Media, Inc. All rights reserved.
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
	function s360_video_embed ($asset_id,$asset_key) {
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
			tb_show('Sorenson 360 Embed','<?php bloginfo('wpurl'); ?>/index.php?sor360_action=sor360_embed_dialog&width=740&height=600');
			
			
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
			case 'sor360_css':
				header("Content-Type: text/css");
				// css
				die();
				break;
			case 'sor360_placeholder_swap':
				header("Content-Type: text/javascript");
				?>
        jQuery(document).ready( function () {  
         
         jQuery('img.sor360placeholder').each( function(i) {
            
            var asset_key = jQuery(this).attr('alt');
         	var asset_id = jQuery(this).attr('id');
           	
           	if (!asset_id) {
             asset_id = jQuery(this).attr('longdesc');
             jQuery(this).attr('id', asset_id);
           	}
           
         	jQuery(this).wrap("<div style='position:relative' class=''></div>").before("<img src='<?php print(plugins_url(dirname(plugin_basename(__FILE__))."/resources/sor360-play-trigger.png")); ?>' style='position:absolute;left:10px;top:10px;' onClick='s360play(\""+asset_id+"\",\""+asset_key+"\")'/>");
         });
           
        });
        
        function s360play(asset_id,asset_key) {
           	
           	jQuery.post(
             "<?php bloginfo('wpurl'); ?>/index.php"
               , {
               sor360_action: "sor360_swap_shortcode"
               , s360assetid: asset_id
               , s360assetkey: asset_key
             }
             , function(data) {
               jQuery('img#'+asset_id).parent().replaceWith(data);
               //alert (data);
             	return false;
             
             }
            );
            
          }
				<?
				die();
				break;
			case 'sor360_js_admin':
				header("Content-Type: text/javascript");
				?>
jQuery(document).ready( function () {	
				

	jQuery(window).resize(function(){
  		do_resize();
	});
});

function do_resize() {
	jQuery("#TB_ajaxContent").removeAttr("style");
	var bw = jQuery('#TB_window').height();
	jQuery('#TB_ajaxContent').css({'height' : bw-60});
				
}

function sor360TestLogin() {
	var result = jQuery('#sor360_login_test_result');
	result.show().addClass('sor360_login_result_wait').html('<?php _e('Contacting Server...', 'sorenson-360'); ?>');
	jQuery.post(
		"<?php bloginfo('wpurl'); ?>/index.php"
		, {
			sor360_action: "sor360_login_test"
			, sor360_username: jQuery('#sor360_username').val()
			, sor360_password: jQuery('#sor360_password').val()
		}
		, function(data) {
			result.html(data).removeClass('sor360_login_result_wait');
			jQuery('#sor360_login_test_result').animate({opacity: 1},5000,function() {jQuery(this).fadeOut('slow')});
		}
	);
	
};

function sor360DoEmbed(asset_id) {

	var s360assetid = asset_id;
	var s360assetkey = jQuery('select[rel="'+asset_id+'"]').val();
	var s360embedcode = jQuery('div#asset-'+asset_id+' textarea[rel="'+s360assetkey+'"]').val();
	var s360placeholder = jQuery('img#'+asset_id).attr('src');
	jQuery.post(
		"<?php bloginfo('wpurl'); ?>/index.php"
		, {
			sor360_action: "sor360_insert_shortcode"
			, s360assetid: asset_id
			, s360assetkey: s360assetkey
			, s360embedcode: s360embedcode
		}
		, function(data) {
		
			
			send_to_editor('<img src="'+s360placeholder+'" longdesc="'+s360assetid+'" id="'+s360assetid+'" alt="'+s360assetkey+'" class="sor360placeholder" width="240"/>');
			
			//send_to_editor('<img src="<?php print(plugins_url(dirname(plugin_basename(__FILE__)).'/resources/s360-editor-placeholder.png'))?>" id="'+s360assetid+'" rel="'+s360assetkey+'" class="sor360placeholder" />');
			
			//send_to_editor('<br/><br/>[sorenson-360 asset_id="'+s360assetid+'" asset_key="'+s360assetkey+'" ]<br/>');
			return false;
		
		}
	);

};

function sor360page(page) {
	var placeimg = '<p align="center"><img src="<?php print(plugins_url(dirname(plugin_basename(__FILE__))."/resources/ajax-loader-circle.gif")); ?>" id="ajax_wait" /></p>';
	jQuery('div#file_wrapper').html(placeimg);
	jQuery('span.pagenum').html(page);
	
	jQuery('div.sor-pagination-links a').removeClass('on');
	jQuery('div.sor-pagination-links a.l'+page).addClass('on');

	jQuery.post(
		"<?php bloginfo('wpurl'); ?>/index.php"
		, {
			sor360_action: "sor360_pagination"
			, s360page: page
		}
		, function(data) {
		
				jQuery('div#file_wrapper').html(data);
				jQuery('div#file_wrapper').fadeIn(200);
			
			return false;
		
		}
	);

}



<?php
				die();
				break;
			case 'sor360_css_admin':
				header("Content-Type: text/css");
				?>
#sor360_options_page {
	margin: 0px;
	padding: 10px 0;
	
	
}

#sor360_options_page fieldset  {
	padding:65px 20px 20px 20px;
	width:80%;
	border:1px solid #ccc;
	background: #fff url(<?php bloginfo('url'); ?>/wp-content/plugins/<?php echo dirname(plugin_basename(__FILE__)) ?>/resources/sorenson-logo.png) 10px 10px no-repeat;
	position:relative;
}
#sor360_options_page fieldset label {
	display:block;
	color:#000;
}
#sor360_options_page fieldset #prompt {
	position:absolute;
	top:0px;
	right:20px;
}
#sor360_options_page #author_override {
	margin:20px;
}
#sor360_login_test_result {
	background: #FFFBCC;
	display: none;
	margin: 10px 0;
	padding: 5px;
	border:1px solid #E6DB55;
	color:#111;
	font-weight:bold;
}
#sor360_options_page div.clear {
	clear: both;
	float: none;
}

.sorviewer {
	background:#f1f1f1;
	border:1px solid #ccc;
	margin-bottom:10px;
	padding:5px;
}
.sorviewer  img{ 
	background:#fff;
	padding:2px;
	border:1px solid #ccc;

}
.sor_w_title small em{
	color:#777;
	font-size:12px;
}
div#sor_file_list div.sor-pagination-links {
	padding:5px 0 2px 0;
	display:block;
	height:30px;
}
div#sor_file_list div.sor-pagination-links a {
		background:#21759B;
		color:#fff;
		padding:4px 8px;
		margin-right:3px;
		display:block;
		float:left;
		cursor:pointer;
		text-decoration:none;
		font-weight:bold;

}
div#sor_file_list div.sor-pagination-links a.on {
	background:#D54E21;
}
div#sor_file_list div.sor-pagination-links a:hover {
	background:#d57b5e;
}
.sor_w_title {
		background: #fff url(<?php bloginfo('url'); ?>/wp-content/plugins/<?php echo dirname(plugin_basename(__FILE__)) ?>/resources/sorenson-logo-small.png) top right no-repeat;
		padding:5px 0;

}
/* Tabs
----------------------------------*/
.ui-tabs { padding: .0em; zoom: 1; }
.ui-tabs .ui-tabs-nav { list-style: none; position: relative; padding: .0em .0em 0 .5em; border-bottom:1px solid #999;height:28px; display:none;}
.ui-tabs .ui-tabs-nav li { position: relative; float: left; border-bottom-width: 0 !important; margin: 0 .2em -1px 0; padding: 0; background:#ccc; border:1px solid #999;}
.ui-tabs .ui-tabs-nav li a { float: left; text-decoration: none; padding: 5px; color:#21759B; }

.ui-tabs .ui-tabs-nav li.ui-tabs-selected { padding-bottom: 1px; border-bottom-width: 0; background:#fff; }
.ui-tabs .ui-tabs-nav li.ui-tabs-selected a, .ui-tabs .ui-tabs-nav li.ui-state-disabled a, .ui-tabs .ui-tabs-nav li.ui-state-processing a { cursor: text; }
.ui-tabs .ui-tabs-nav li a, .ui-tabs.ui-tabs-collapsible .ui-tabs-nav li.ui-tabs-selected a { cursor: pointer; } /* first selector in group seems obsolete, but required to overcome bug in Opera applying cursor: text overall if defined elsewhere... */
.ui-tabs .ui-tabs-panel { padding: 2px; display: block; border-width: 0; background: none; }
.ui-tabs .ui-tabs-hide { display: none !important; }

<?php

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
	// test
	
	// TODO Better error handling
	
	if (!$s_username || !$s_password) {
	  print('<h3>Sorenson 360 username and password not set.</h3><p>Please visit your profile or settings to configure Sorenson 360</p>');
	} elseif (!is_a($account, 'S360_Account')) {
	  print('<h3>Sorenson 360 Error: ' . $account['errorMessage'] . '</h3>');
	} else {
		
		$assets = $account->getAssets(0, SOR360_PAGINATION_LIMIT);
		$pages = round($account->totalAssetCount / SOR360_PAGINATION_LIMIT);
		
		$overlayhead = '
		<script type="text/javascript">
				jQuery("#sor360Tabs").tabs();
	
				  		
		</script>
		<div id="swrap">
		<h2 class="sor_w_title">Your Sorenson 360 library <small><em>Choose video to embed.</em></small></h2>
		
		<div id="sor360Tabs" class="ui-tabs">
			<ul class="ui-tabs-nav">
				<li><a href="#sor_file_list"><span>Library</span></a></li>
				<li><a href="#sor_new_vid"><span>New Video</span></a></li>
			</ul>	
			<div id="sor_file_list">
				<div class="sor-pagination-links">
					<div style="float:right"><strong>Page <span class="pagenum">1</span> of '.$pages.'</strong> ('.$account->totalAssetCount.' total assets)</div>
					
					';
					
					for($i=1;$i <= $pages;$i++) {
						$i == 1 ? $style = "on" : $style="";
						$overlayhead .= '<a href="#" onClick="sor360page('.$i.');"  class="l'.$i.' '.$style.'">'.$i.'</a> ';
					}
				$overlayhead .='		

				<img src="'.plugins_url(dirname(plugin_basename(__FILE__))."/resources/ajax-loader-circle.gif").'" id="ajax_wait" style="display:none;" onload="do_resize();"/>
				</div>
				<div id="file_wrapper">
		';
		print($overlayhead);
		
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
									<li><strong>FileName:</strong> '.$as->name.'</li>
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
					//$renderhtml .= print_r($as);
		} // end foreach
		
		print($renderhtml);	

		print(
				'
				
				</div>
				<div class="sor-pagination-links">
						<div style="float:right"><strong>Page <span class="pagenum">1</span> of '.$pages.'</strong> ('.$account->totalAssetCount.' total assets)</div>
					
					');
					
					for($i=1;$i <= $pages;$i++) {
						$i == 1 ? $style = "on" : $style="";
						print('<a href="#" onClick="sor360page('.$i.');" class="l'.$i.' '.$style.'">'.$i.'</a> ');
					}
				print('		

					</div>

				</div>
				<div id="sor_new_vid">
				</div>
			</div><!-- end tab container -->
			
			</div><!-- end wrap -->
							
		');
		
	}
	
		
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
