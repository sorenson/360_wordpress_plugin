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

      //send_to_editor('<img src="<?php print(plugins_url('/sorenson360/resources/s360-editor-placeholder.png'))?>" id="'+s360assetid+'" rel="'+s360assetkey+'" class="sor360placeholder" />');

      //send_to_editor('<br/><br/>[sorenson-360 asset_id="'+s360assetid+'" asset_key="'+s360assetkey+'" ]<br/>');
      return false;

    }
  );

};

function sor360page(page) {
  var placeimg = '<p align="center"><img src="<?php print( bloginfo('wpurl').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__))."/ajax-loader-circle.gif"); ?>" id="ajax_wait" /></p>';
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

function sor360ReloadLibrary() {
  jQuery("#sor360Tabs").tabs('select', 0);
  var placeimg = '<p align="center"><img src="<?php print( bloginfo('wpurl').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__))."/ajax-loader-circle.gif"); ?>" id="ajax_wait" /></p>';
  jQuery('#sor_file_list').html(placeimg);
  
  jQuery.get('<?php bloginfo('wpurl') ?>/index.php?sor360_action=sor360_foo', '', function(data, textStatus){
    jQuery('#sor_file_list').replaceWith(data);
  });
}