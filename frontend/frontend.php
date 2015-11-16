<?php
Class NTM_Frontend
{
	function frontend()
	{
		error_reporting(0);
		if(!$this->check_login())
		{
			echo "Invalid Autologin link!!!";
			return false;
		}
		
		global $ntm_mail, $current_user, $wpdb;
	
		$endorser_letter = get_user_meta($current_user->ID, 'endorsement_letter', true);
		if($endorser_letter)
		{
			$res = $wpdb->get_row("select * from ".$wpdb->prefix . "mailtemplates where id=".$endorser_letter);
			$mailtemplate = $res->content;
		}
		else
		{
			$mailtemplate 	 	= 	$ntm_mail->get_invitation_mail ();
			$mailtemplate = $mailtemplate['content'];
		}
		$invitation_status = $this->frontend_action();
	?>
    <link rel="stylesheet" type="text/css" href="<?php _e(NTM_PLUGIN_URL);?>/assets/css/ckeditor.css" media="all" />
    <script type='text/javascript' src='<?php _e(NTM_PLUGIN_URL);?>/assets/js/ckeditor/ckeditor.js'></script>
    <script>
	  (function(u){
		var d=document,s='script',a=d.createElement(s),m=d.getElementsByTagName(s)[0];
		a.async=1;a.src=u;m.parentNode.insertBefore(a,m);
	  })('//api.cloudsponge.com/widget/<?php echo get_option('cloudsponge');?>.js');
	  window.csPageOptions = { 
				textarea_id: "contact_list" ,
				skipSourceMenu:true, // suppresses the source menu unless linked to directly
			  // delay making the links that launch a popup clickable
			  // until after the widget has initialized completly. a popup window must 
			  // be opened in an onclick handler, so we don't support queueing these actions
			  afterInit:function() {
				var i, links = document.getElementsByClassName('delayed');
				for (i = 0; i < links.length; i++) {
				  // make the links that launch a popup clickable by setting the href property
				  links[i].href = "#";
				}

				// if this is not a mobile browser, we can show and enable the desktop-only links
				if (!cloudsponge.mobile) {
				  links = document.getElementsByClassName('desktop-only');
				  for (i = 0; i < links.length; i++) {
					// show it
					links[i].style.display = "";
					// make it clickable
					links[i].href = "#";
				  }
				}
			  }
		};
	</script>
    <div id="poststuff" class="wrap">
    <?php if($invitation_status){?>
    <div id="message" class="updated"><p>Your invitation sent successfully.</p></div>
    <?php }?>
		<p>Welcome <?php echo get_user_meta( $current_user->ID, 'first_name', true).' '.get_user_meta($current_user->ID, 'last_name', true);?></p>
		<div class="postbox">
            <div class="inside group">
            	<form name="myform" method="post" >
					<div class="social_button">
						<a class="deep-link desktop-only" style="display: none;" onclick="return cloudsponge.launch('linkedin');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/linkedin.png"/></a>
						<a class="deep-link delayed" onclick="return cloudsponge.launch('yahoo');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/yahoo.png"/></a>
						<a class="deep-link delayed" onclick="return cloudsponge.launch('windowslive');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/outlook.com.png"/></a>
						<a class="deep-link delayed" onclick="return cloudsponge.launch('gmail');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/gmail.png"/></a>
						<a class="deep-link delayed" onclick="return cloudsponge.launch('aol');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/aol.png"/></a>
						<a href="#" class="deep-link" onclick="return cloudsponge.launch('plaxo');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/plaxo.png"/></a>
						<a class="deep-link desktop-only" style="display: none;" onclick="return cloudsponge.launch('addressbook');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/apple-desktop.png"/></a>
						<a class="deep-link desktop-only" style="display: none;" onclick="return cloudsponge.launch('outlook');"><img src="<?php _e(plugin_dir_url( __FILE__ ));?>../icon-set/outlook-desktop.png"/></a>
					</div>
					<br>
					<textarea name="contact_list" id="contact_list" rows="5" cols="73"></textarea>
					<br><br>
					<textarea cols="80" id="editor" name="" rows="10"><?php _e($mailtemplate);?></textarea>
					<script>
						CKEDITOR.replace( 'editor' );
					</script>
					<br>
					<p class="submit">
					<input name="send_invitation" class="button-primary seeker_btn" value="<?php _e('Invite your friends'); ?>" type="submit" />
					</p>
                </form>
            </div>
        </div>
    </div>  
	<?php 
	}
	
	function check_login(){
		
		global $current_user;
		
		$autologin = explode("#", base64_decode(base64_decode($_GET['autologin'])));
		$creds = array();
		$creds['user_login'] = $autologin[0];
		$creds['user_password'] = $autologin[1];
		$creds['remember'] = true;
		$current_user = wp_signon( $creds, false );
		if ( is_wp_error($current_user) )
			return false;
		
		return true;
	}
	
	function frontend_action(){
		global $wpdb, $current_user, $ntm_mail;
		
		if(isset($_POST['send_invitation']))
		{
			$contact_list = explode(",", $_POST['contact_list']);
			
			foreach($contact_list as $res)
			{
				$ex1 = explode("<", $res);
				$ex2 = explode(">", $ex1[1]);
				
				$info = array(
					"name" => $ex1[0], 
					"created" => date("Y-m-d H:i:s"), 
					"email" => $ex2[0],
					"endorser_id" => $current_user->ID,
					"tracker_id" => wp_generate_password( $length=12, $include_standard_special_chars=false )
				);
				$wpdb->insert($wpdb->prefix . "endorsements", $info);
				$ntm_mail->send_invitation_mail($info, $current_user->ID, $wpdb->insert_id);
			}
			
			update_user_meta($current_user->ID, "invitation_sent", (update_user_meta($current_user->ID, "invitation_sent", true) + count($contact_list)));
			
			return true;
		}
		return false;
	}
}