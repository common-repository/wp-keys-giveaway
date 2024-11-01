<?php

/*
Plugin Name: WP Keys Giveaway
Plugin URI: http://shad0w.me/wp-keys-giveaway-plugin/
Description: Do you have some keys for a software or videogame and want to give them to your users? This plugin allows you to do just that.
Version: 1.0.1
Author: Shad9w
Author URI: http://shad0w.me
*/

global $wpdb;
define('SH9BKPLUGINURL', WP_PLUGIN_URL."/".dirname( plugin_basename( __FILE__ ) ) );
define('SH9BKTABLE', $wpdb->prefix . "sh9_betakeys");
register_activation_hook(__FILE__,'sh9_create_table');

function sh9_create_table(){
    global $wpdb;
    if($wpdb->get_var("SHOW TABLES LIKE '" . SH9BKTABLE . "'")!=SH9BKTABLE){
        $create_table_sql = "CREATE TABLE " . SH9BKTABLE . " (
            `id` BIGINT(50) NOT NULL AUTO_INCREMENT, 
            `key` VARCHAR(200) NOT NULL, 
            `post` INT(11) NOT NULL, 
            `ip` varchar(30) NOT NULL DEFAULT 'none', 
            `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, 
            `taken` INT(1) NOT NULL DEFAULT '0', 
            PRIMARY KEY (`id`)
        );";
        $wpdb->query($wpdb->prepare($create_table_sql));
        $wpdb->flush();
    }
}

function sh9_drop_table(){
  global $wpdb;
  $query = $wpdb->prepare("DROP table " . SH9BKTABLE);
  $wpdb->query($query);
  $wpdb->flush();
}

function shad9w_register_post_type_betakeygiveaway(){
register_post_type('sh9_betakey', array(	'label' => 'Beta Key Promotions','description' => 'Beta Key Promotions','public' => true,'show_ui' => true,'show_in_menu' => true,'capability_type' => 'post','hierarchical' => false,'rewrite' => array('slug' => 'shad9wbetakey'),'query_var' => true,'exclude_from_search' => false,'supports' => array('title',),'labels' => array (
  'name' => 'WP Keys Giveaway',
  'singular_name' => 'WP Keys Giveaway',
  'menu_name' => 'WP Keys Giveaway',
  'add_new' => 'Add Key Giveaway',
  'add_new_item' => 'Add Key Giveaway',
  'edit' => 'Edit',
  'edit_item' => 'Edit Key Giveaway',
  'new_item' => 'New Key Giveaway',
  'view' => 'View Key Giveaways',
  'view_item' => '',
  'search_items' => 'Search Key Giveaways',
  'not_found' => 'No Key Giveaways Found',
  'not_found_in_trash' => 'No Key Giveaways Found in Trash',
  'parent' => 'Parent Key Giveaways',
),'menu_icon' =>SH9BKPLUGINURL . '/images/icon.png',
));
}
add_action('init', 'shad9w_register_post_type_betakeygiveaway');

add_action('add_meta_boxes', 'sh9_meta_box_add');  
function sh9_meta_box_add(){
    add_meta_box( 'meta-box-sh9-betakey', 'Add The Keys Here', 'sh9_meta_box_cb', 'sh9_betakey', 'normal', 'high' );  
}  

function sh9_meta_box_cb(){
    global $post, $wpdb;
    $values = get_post_custom( $post->ID );  
    wp_nonce_field( 'sh9_meta_box_nonce', 'meta_box_nonce' );
    $iplock = isset( $values['sh9_meta_box_iplock'] ) ? esc_attr( $values['sh9_meta_box_iplock'][0] ) : ""; 
    $logged = isset( $values['sh9_meta_box_loggedin'] ) ? esc_attr( $values['sh9_meta_box_loggedin'][0] ) : "";  
?>
    
    <input type="checkbox" id="sh9_meta_box_iplock" name="sh9_meta_box_iplock" <?php checked( $iplock, 'on' ); ?> />  
    <label for="sh9_meta_box_iplock">IP Lock (1 key / IP).</label>
    <br/>
    
    <input type="checkbox" id="sh9_meta_box_loggedin" name="sh9_meta_box_loggedin" <?php checked( $logged, 'on' ); ?> />  
    <label for="sh9_meta_box_loggedin">Only logged users can get keys.</label>  
    
    <p>
    <label for="code">Use this code inside your post:</label><br/>
    <input type="text" id="code" name="code" value="[keys id=<?php echo $post->ID; ?>]" />
    </p>
    
    <p>
        <label for="sh9_meta_box_text">Add the keys here. One per line.</label><br/>
        <textarea rows="5" style="width: 90%" name="sh9_meta_box_textarea"></textarea>
    </p>  
    
<?php

  // display all the keys + delete button here.
  $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".SH9BKTABLE." WHERE `post`= %d ", $post->ID));
  if($count>0){
    $nonce = wp_create_nonce("sh9_deletekey_nonce");
    $keys = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".SH9BKTABLE." WHERE `post` = %d", $post->ID ) );
    echo "<h2>There are $count keys in our database!</h2>";
    echo "<ul>";
    foreach($keys as $key){
      if($key->taken>0) $taken = "key taken by " . $key->ip;
      else $taken = "key available";
      echo "<li id=" . $key->id . "> ";
      
      echo '<img class="sh9_deletekey" data-nonce="' . $nonce . '" data-post_id="' . $post->ID . '" data-key="'. $key->id .'" src="'.SH9BKPLUGINURL.'/images/delete.png" /> ';    
      echo $key->key . " - " . $taken;
      echo "</li>";
    }
    echo "</ul>";

  }

}

function sh9_meta_box_save( $post_id )  {
    global $wpdb;
    
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return; 
    if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'sh9_meta_box_nonce' ) ) return; 
    if( !current_user_can( 'edit_post' ) ) return;  

    if( isset( $_POST['sh9_meta_box_textarea'] ) )  
        update_post_meta( $post_id, 'sh9_meta_box_textarea', esc_attr( $_POST['sh9_meta_box_textarea'] ) );
        
        $inserted = 0;
        $_POST['sh9_meta_box_textarea'] = str_replace("\n\r", "\n", $_POST['sh9_meta_box_textarea']);
        $_POST['sh9_meta_box_textarea'] = str_replace("\r\n", "\n", $_POST['sh9_meta_box_textarea']);
        $_POST['sh9_meta_box_textarea'] = str_replace("\r", "\n", $_POST['sh9_meta_box_textarea']);

        $codes = explode("\n", $_POST['sh9_meta_box_textarea']);
        foreach($codes as $code){
            $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".SH9BKTABLE." WHERE `key` = %s AND `post`= %d ", $code, $post_id));
            if($exists>0||trim($code)==""){
            
            }else{
              $wpdb->insert(SH9BKTABLE, array("taken" => 0, "key" => $code, "post" => $post_id), array("%d", "%s", "%d"));
              $inserted++;
            }
        }
        
        if(isset($_POST['sh9_meta_box_iplock'])){
          if($_POST['sh9_meta_box_iplock']) $iplock = 'on';
          else $iplock = 'off';
        }
        
        if(isset($_POST['sh9_meta_box_loggedin'])){
          if($_POST['sh9_meta_box_loggedin']) $loggedin = 'on';
          else $loggedin = 'off';
        }        
        $message = $inserted. " keys added!";
        
        update_post_meta( $post_id, 'sh9_meta_box_message', $message );
        update_post_meta( $post_id, 'sh9_meta_box_iplock', $iplock);
        update_post_meta( $post_id, 'sh9_meta_box_loggedin', $loggedin);
}

add_action( 'save_post', 'sh9_meta_box_save' );

function sh9_updated_messages( $messages ) {
  global $post, $post_ID;
  $values = get_post_custom( $post_ID );  
  $message = isset( $values['sh9_meta_box_message'] ) ? esc_attr( $values['sh9_meta_box_message'][0] ) : "";  
  
  $messages['sh9_betakey'] = array(
    0 => '', 
    1 => sprintf( __('Promotion updated. ' . $message, 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated. ' . $message, 'your_text_domain'),
    3 => __('Custom field deleted. ' . $message, 'your_text_domain'),
    4 => __('Promotion updated. ' . $message, 'your_text_domain'),
    5 => isset($_GET['revision']) ? sprintf( __('Promotion restored to revision from %s', 'your_text_domain'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Promotion published. ' . $message, 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Book saved.', 'your_text_domain'),
    8 => sprintf( __('Promotion submited ' . $message, 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Promotion scheduled for: <strong>%1$s</strong>.', 'your_text_domain'),
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Promotion draft updated. ' . $message, 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );

  return $messages;
}
add_filter( 'post_updated_messages', 'sh9_updated_messages' );


add_action("wp_ajax_sh9_deletekey", "sh9_deletekey");
add_action("wp_ajax_nopriv_sh9_deletekey", "sh9_must_login");

function sh9_deletekey() {
  global $wpdb;
  if ( !wp_verify_nonce( $_REQUEST['nonce'], "sh9_deletekey_nonce")) die();
  $sql = $wpdb->prepare("DELETE FROM ".SH9BKTABLE." WHERE `id` = %d AND `post`=%d", $_REQUEST["key"], $_REQUEST["post_id"]);
  $wpdb->query($sql);
  if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    die('1');
  }
  else {
    header("Location: ".$_SERVER["HTTP_REFERER"]);
  }
}

function sh9_getkey() {
  global $wpdb;
  if ( !wp_verify_nonce( $_REQUEST['nonce'], "sh9_getkey_nonce")) die(json_encode('nonce error'));
  $post = $_REQUEST["post_id"];
  $values = get_post_custom( $post );
  $iplock = isset( $values['sh9_meta_box_iplock'] ) ? esc_attr( $values['sh9_meta_box_iplock'][0] ) : "";
  if($iplock=="on"){
    $key = $wpdb->get_var($wpdb->prepare("SELECT `key` FROM ". SH9BKTABLE . " WHERE `post` = %d AND `ip` = %s", $post, sh9_getip()));
    if($key){
      $result = "<p class='key'>You already got a key.<br/>Your key is: $key!</p>";
      die(json_encode($result));
    }
  }
  $logged = isset( $values['sh9_meta_box_loggedin'] ) ? esc_attr( $values['sh9_meta_box_loggedin'][0] ) : "";  
  if($logged){
    if(!is_user_logged_in()){
      $result = "<p>The keys are available only for logged users.</p>";
      $result .="<p>You need to <a href='". wp_login_url( get_permalink() ) ."'>login</a> or ";
      $result .= "<a href='".site_url('/wp-login.php?action=register&redirect_to=' . get_permalink())."'>register</a> first!<p>";
      die(json_encode($result));
    }
  }
  
	$keys = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ". SH9BKTABLE . " WHERE `post` = %d AND `taken` = %d", $post, 0));
	if($keys==0) die(json_encode("<p class='key'>No keys left. Sorry.</p>"));
	
	$sql = $wpdb->prepare("SELECT `key` FROM " . SH9BKTABLE . " WHERE `post`= %d AND `taken` = %d", $post, 0);
  $key = $wpdb->get_var($sql);
  
  $wpdb->update(SH9BKTABLE, array("ip" => sh9_getip(), "taken"=>'1'), array("key" => $key, "post" => $post), array("%s", "%d"), array("%s", "%d") );
  
  if($key){
    $result = "<p class='key'>Your key is: $key</p>";
  }else{
    $result = "error getting your key, please refresh the page and try again!";
  }
  
  if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    die(json_encode($result));
  }
  else {
    die(json_encode('error getting your key, please refresh the page and try again. or try using a different browser.'));
  }
}

add_action("wp_ajax_sh9_getkey", "sh9_getkey");
add_action("wp_ajax_nopriv_sh9_getkey", "sh9_getkey");

function sh9_must_login() {
   die("You're not allowed to do that.");
}

add_action( 'admin_enqueue_scripts', 'sh9_admin_scripts' );

function sh9_admin_scripts() {
   wp_register_script( "key_giveaway", SH9BKPLUGINURL.'/js/admin.js', array('jquery') );
   wp_localize_script( 'key_giveaway', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
   wp_enqueue_script( 'key_giveaway' );
}

function sh9_shortcode($atts){
  global $wpdb;
  extract( shortcode_atts( array(
		'id' => 0,
	), $atts ) );
	if(get_post_type( $id )!="sh9_betakey") return "Invalid shortcode!";
	$keys = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ". SH9BKTABLE . " WHERE `post` = %d AND `taken` = %d", $id, 0));
	if($keys>0){
    $nonce = wp_create_nonce("sh9_getkey_nonce");
    $return = "<div class='keys'>";
    $return.= "<div class='sh9key'><a href='#keys'><img id=\"sh9img\" data-post_id='".$id."' data-nonce='".$nonce."' src=".SH9BKPLUGINURL."/images/get-your-key.png alt=\"get your key\" /></a></div>";
    $return.= "<p class='big'>$keys keys left!</p>";
    $return.= "</div>";
    return $return;
	}else{
    return "<div class='keys'><p class='big'>No keys left! Sorry.</p></div>";
	}
}

add_shortcode("keys", "sh9_shortcode");  

function sh9bk_styles(){
	wp_register_style( 'sh9bk-style',  SH9BKPLUGINURL. '/css/style.css', array(), '', 'all' );
	wp_enqueue_style( 'sh9bk-style' );
}
add_action( 'wp_enqueue_scripts', 'sh9bk_styles' );

function sh9bk_scripts(){
	wp_register_script( 'getkey-script', SH9BKPLUGINURL . '/js/getkey.js', array( 'jquery' ) );
  wp_localize_script( 'getkey-script', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
	wp_enqueue_script( 'getkey-script' );
}
add_action( 'wp_enqueue_scripts', 'sh9bk_scripts' );

function sh9_getip(){
  if(!empty($_SERVER['HTTP_CLIENT_IP'])){
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
    $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
  }else{
    $ip=$_SERVER['REMOTE_ADDR'];
  }
  return $ip;
}

add_action("admin_menu", 'sh9_adddonate');

function sh9_adddonate(){
  add_submenu_page('edit.php?post_type=sh9_betakey', 'Donate', 'Donate', 'manage_options', basename(__FILE__), 'sh9_donate');
}

function sh9_donate(){
  echo "<h2>Donate</h2>";
  echo "<p>If you think this plugin is useful and want to donate some money for the developer, you can click the button bellow:</p>";
?>
  <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
  <input type="hidden" name="cmd" value="_s-xclick">
  <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
  ">
  <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
  <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
  </form>
  
<?php
}
