jQuery(document).ready( function() {

   jQuery(".sh9_deletekey").click( function() {
      post_id = jQuery(this).attr("data-post_id")
      nonce = jQuery(this).attr("data-nonce")
      key = jQuery(this).attr("data-key")
      console.log("merge consola");
      jQuery.ajax({
         type : "post",
         dataType : "json",
         url : myAjax.ajaxurl,
         data : {action: "sh9_deletekey", post_id : post_id, nonce: nonce, key: key},
         success: function(response) {            
            if(response === 1) {
              jQuery("#" + key).hide();
            }
            else {
               alert('error deleting the beta key.')
            }
         }
      })   
   })

}) 