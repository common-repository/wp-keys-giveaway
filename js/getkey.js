jQuery(document).ready( function() {
    console.log("getkey.js prezent..");
   jQuery("#sh9img").click( function() {
      jQuery("#sh9img").hide();
      jQuery('.sh9key').append('getting your key...');
      console.log("getkey clicked");
      post_id = jQuery(this).attr("data-post_id")
      nonce =  jQuery(this).attr("data-nonce")
      jQuery.ajax({
         type : "post",
         dataType : "json",
         url : myAjax.ajaxurl,
         data : {action: "sh9_getkey", post_id : post_id, nonce: nonce},
         success: function(response) {
              console.log(response);
              jQuery('.sh9key').html(response);
              //jQuery('.sh9key').append(response);
         }
      })   
   })

}) 