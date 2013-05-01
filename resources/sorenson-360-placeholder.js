jQuery(document).ready( function () {  
 
 jQuery('img.sor360placeholder').each( function(i) {
    
    var asset_key = jQuery(this).attr('alt');
  var asset_id = jQuery(this).attr('id');
    
    if (!asset_id) {
     asset_id = jQuery(this).attr('longdesc');
     jQuery(this).attr('id', asset_id);
    }
   
  jQuery(this).wrap("<div style='position:relative' class=''></div>").before("<img src='<?php print(plugins_url(dirname(plugin_basename(__FILE__))."/sor360-play-trigger.png")); ?>' style='position:absolute;left:10px;top:10px;' onClick='s360play(\""+asset_id+"\",\""+asset_key+"\")'/>");
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