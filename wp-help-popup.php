<?php
/*
Plugin Name: WP Help Popup 
Description: A Wordpress Extension to show quick help using What's app chat and site search using a widget.
Author: K. Kumar
Author URI: https://profiles.wordpress.org/krishna121
Version: 1.0.9
Text Domain:wp-help-popup
*/


if(!(defined('ABSPATH')))
exit();

if(!class_exists('WP_Help_Popup')){
/**
* 
*/
class WP_Help_Popup 
{
	public $plugin_prefix="wphp";
	public $form_respnse = '';
	public $result = array();


	function __construct()
	{	
		$this->wphp_constants();
		$this->wphp_plugin_hooks();		
	}
	function wphp_plugin_hooks(){

		add_action( 'admin_menu', array($this,'wphp_plugin_menu' ));
		add_action( 'wp_enqueue_scripts', array($this,'wphp_frontend_scripts' ));
		add_action( 'wp_footer', array($this,'wphp_helpbox_markup' ));
		add_action( 'admin_init', array($this,'wphp_plugin_settings' ));
		add_action( 'admin_enqueue_scripts', array($this,'wphp_enqueue_admin_script' ),10,1);
		add_action( 'wp_ajax_nopriv_wphp_ajax_call', array($this,'wphp_ajax_call' ));
		add_action( 'wp_ajax_wphp_ajax_call', array($this,'wphp_ajax_call' ));
		register_activation_hook( __FILE__, array($this,'wphp_plugin_activate'));

		add_action( 'admin_head', array($this,'wphp_admin_css' ) );


	}

	function wphp_admin_css(){
		?>
		<style>
			.toplevel_page_wp_help_overview img{
				padding-top: 3px !important;
			}
		</style>
		<?php



	}

	function wphp_ajax_call(){
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { 
			
    		if (isset($_POST['nonce']) && !wp_verify_nonce( $_POST['nonce'], 'wpnonce_value' ) )
       		die ( 'Busted!');


			$ajax_handler = isset($_POST['ajax_handler']) ? $_POST['ajax_handler'] :'';
			$response = $this->$ajax_handler($_POST);
			echo  json_encode($response);
			exit;
		}
	}

	function wphp_ajax_handler($data){

		$title = isset($data['post_title']) ? $data['post_title'] :'';
		$result_posts = array();
		global $wpdb;
		$setting =  get_option('wphp_options_group');
		$post_type  = !empty($setting['wphp_choose_post_type'])? $setting['wphp_choose_post_type'] : 'post';
		$args =  array( 's' => $title,
						'post_type'=>$post_type
				);
		$result_query = new WP_Query($args);
		if ( $result_query->have_posts() ) {
			$result_posts = $result_query->posts;
		}
		wp_reset_postdata();
		wp_reset_query();
		return $result_posts;
	}


	function wphp_enqueue_admin_script($hook){

		if($hook=="toplevel_page_wp_help_overview" || $hook=='wp-help-popup_page_wp_our_plugins' ||  $hook=="wp-help-popup_page_wp_help_settings"){
			wp_enqueue_style( 'wpic-admin-css', WPHP_CSS.'/wpic-admin.css');
			wp_enqueue_style( 'select2-css', WPHP_CSS.'/select2.css');
			wp_enqueue_style( 'select2-bootstrap-css', WPHP_CSS.'/select2-bootstrap.css');
			wp_enqueue_script( 'select2-full-js', WPHP_JS.'/select2.full.js');
			wp_enqueue_script( 'wpic-admin-js', WPHP_JS.'/wpic-admin.js');
		}
	}

	function wphp_check_valdation($data){

		$error = false;
		$enable = (!empty($data['wp_enable_popup'])) ? true : false;
		$enable_pages = (!empty($data['wp_enable_pages'])) ? true : false;

		if($enable_pages && empty($data['wphp_choose_pages'])){
			$this->result['error'] = esc_html__('Please select at least one page.','wp-help-popup');
			$error= true;
		}
		if($enable && !empty($data['wphp_noof_recent_posts']) &&  !(is_numeric($data['wphp_noof_recent_posts']))  ){
			$this->result['error'] = esc_html__('Please insert no of posts in number.','wp-help-popup');
			$error= true;
		}
		return $error;  
	}
	

// save setting
	function wphp_plugin_settings(){

		$settings = array();
	   register_setting( 'wphp_options_group','wphp_dboptions');
	   if(isset($_POST) && isset($_POST['setting-name']) && ( sanitize_text_field( wp_unslash($_POST['setting-name']))=="Save")  ){
	   	
	   	if ( isset( $_POST['wphp_nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['wphp_nonce'] ) );
		}
  		$checknonce = wp_verify_nonce($nonce,'wphp');
  		if($checknonce){
  			$error = $this->wphp_check_valdation($_POST);
  			if(!$error){

  				$settings['wphp_searchbox_placeholder'] = (!empty( $_POST['wphp_searchbox_placeholder'])) ? sanitize_text_field( wp_unslash( $_POST['wphp_searchbox_placeholder'] ) ) : '';

  				$settings['wphp_popup_title'] = (!empty($_POST['wphp_popup_title'])) ? sanitize_text_field( wp_unslash( $_POST['wphp_popup_title'] ) ) : '';  
  				$settings['wphp_popupbtn_text'] = (!empty($_POST['wphp_popupbtn_text'])) ?sanitize_text_field( wp_unslash( $_POST['wphp_popupbtn_text'] ) ) : '' ;
  				
  				$settings['wphp_noresult_text'] = (!empty($_POST['wphp_noresult_text'])) ?sanitize_text_field( wp_unslash( $_POST['wphp_noresult_text'] ) ) : '' ;
  				$settings['wphp_whatsapp_text'] = (!empty($_POST['wphp_whatsapp_text'])) ?sanitize_text_field( wp_unslash( $_POST['wphp_whatsapp_text'] ) ) : '' ;
  				$settings['wphp_whatsapp_message'] = (!empty($_POST['wphp_whatsapp_message'])) ?sanitize_text_field( wp_unslash( $_POST['wphp_whatsapp_message'] ) ) : '' ;
  				$settings['wphp_phone_number'] = (!empty($_POST['wphp_phone_number'])) ?sanitize_text_field( wp_unslash( $_POST['wphp_phone_number'] ) ) : '' ;
  				$settings['wphp_enable_chat'] = (!empty($_POST['wphp_enable_chat'])) ?sanitize_text_field( wp_unslash( $_POST['wphp_enable_chat'] ) ) : '' ;
  				$settings['wphp_enable_call'] = (!empty($_POST['wphp_enable_call'])) ?sanitize_text_field( wp_unslash( $_POST['wphp_enable_call'] ) ) : '' ;
  				  if(!empty($_FILES["wphp_whatsapp_icon"]["tmp_name"]))
					    {           
					        require_once( ABSPATH . 'wp-admin/includes/file.php' );
					        $urls = wp_handle_upload($_FILES["wphp_whatsapp_icon"], array('test_form' => false));
					        $settings['wphp_whatsapp_icon'] = $urls['url'];
					        
					    }
					      if(!empty($_FILES["wphp_help_icon"]["tmp_name"]))
					    {           
					        require_once( ABSPATH . 'wp-admin/includes/file.php' );
					        $urlshelp = wp_handle_upload($_FILES["wphp_help_icon"], array('test_form' => false));
					        $settings['wphp_help_icon'] = $urlshelp['url'];
					        
					    }
					      if(!empty($_FILES["wphp_chat_icon"]["tmp_name"]))
					    {           
					        require_once( ABSPATH . 'wp-admin/includes/file.php' );
					        $urlschat = wp_handle_upload($_FILES["wphp_chat_icon"], array('test_form' => false));
					        $settings['wphp_chat_icon'] = $urlschat['url'];
					        
					    }
					     if(!empty($_FILES["wphp_call_icon"]["tmp_name"]))
					    {           
					        require_once( ABSPATH . 'wp-admin/includes/file.php' );
					        $urlscall = wp_handle_upload($_FILES["wphp_call_icon"], array('test_form' => false));
					        $settings['wphp_call_icon'] = $urlscall['url'];
					        
					    }
					    
			 
					  
  				if(!empty($_POST['wphp_noof_recent_posts'])){
  					$wphp_noof_recent_posts = sanitize_text_field( wp_unslash( $_POST['wphp_noof_recent_posts']));
  					$settings['wphp_noof_recent_posts'] = filter_var( $wphp_noof_recent_posts, FILTER_SANITIZE_NUMBER_INT );
  				}
  				$settings['wphp_choose_pages'] = (!empty($_POST['wphp_choose_pages'])) ? (array)$_POST['wphp_choose_pages'] :array();
  				$settings['wphp_choose_post_type'] = (!empty($_POST['wphp_choose_post_type'])) ? (array)$_POST['wphp_choose_post_type'] :array();

  				$settings['wphp_enable_searchbox'] = (!empty($_POST['wphp_enable_searchbox'])) ? true : false;  				
  				$settings['wp_enable_popup'] = (!empty($_POST['wp_enable_popup'])) ? true : false;
  				$settings['wp_enable_pages'] = (!empty($_POST['wp_enable_pages'])) ? true : false;
  				$settings['wp_enable_post'] = (!empty($_POST['wp_enable_post'])) ? true : false;
  				update_option('wphp_options_group',$settings);
  				$this->result['success'] = esc_html__('Settings Saved Successfully.','wp-help-popup');



  			}

  		}
	   }
	}

	function wphp_frontend_scripts(){

		wp_enqueue_script('jquery');
		wp_enqueue_style( 'bootstrap-modal-css', WPHP_CSS.'/bootstrap-modal.css');		
		wp_enqueue_script( 'bootstrap-modal-js', WPHP_JS.'/bootstrap-modal.js');
		wp_enqueue_script( 'wphp-frontend', WPHP_JS.'wphp_frontend.js');
		wp_enqueue_style( 'wphp-frontendsd', WPHP_CSS.'/wphp-frontend.css');
		$setting =  get_option('wphp_options_group');
		wp_localize_script( 'wphp-frontend', 'ajax_obj', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce'=> wp_create_nonce('wpnonce_value'),
			'not_found_text' =>isset($setting['wphp_noresult_text']) ? $setting['wphp_noresult_text'] : esc_html__('We’re sorry, no results were found.','wp-help-popup')
		));
	}
// markup array search
	function wphp_helpbox_markup(){
		global $post;
		//print_r($post);
		$setting =  get_option('wphp_options_group');
		$popup_enable = isset($setting['wp_enable_popup']) ? $setting['wp_enable_popup'] :'';
		$popup_post = isset($setting['wp_enable_post']) ? $setting['wp_enable_post'] :'';
		$popup_page = isset($setting['wp_enable_pages']) ? $setting['wp_enable_pages'] :'';
		$selected_pages = isset($setting['wphp_choose_pages']) ? $setting['wphp_choose_pages'] : array() ;
		$selected_posttypes = isset($setting['wphp_choose_post_type']) ? $setting['wphp_choose_post_type'] : array() ;
       // print_r($selected_posttypes);
		$needtoshomarkup=false;
		if(!empty($popup_enable)){
			$needtoshomarkup=true;
		}

		if(!($needtoshomarkup) && !empty($popup_page) && !empty($selected_pages) && count($selected_pages)>0 && in_array($post->ID, $selected_pages)){
           $needtoshomarkup=true;
		}
		if(!($needtoshomarkup) && !empty($popup_post) && !empty($selected_posttypes) && count($selected_posttypes)>0 && in_array($post->post_type, $selected_posttypes)){
           $needtoshomarkup=true;
		}
		if($needtoshomarkup){

	      	$modal_title = !empty($setting['wphp_popup_title'])? $setting['wphp_popup_title'] :esc_html__('How can we assist you?','wp-help-popup');
	      	
	      	$search_placeholder = !empty($setting['wphp_searchbox_placeholder'])? $setting['wphp_searchbox_placeholder'] :esc_attr__('Type you question here..','wp-help-popup');
	      	$help_text = !empty($setting['wphp_popupbtn_text'])? $setting['wphp_popupbtn_text'] :esc_html__('Help','wp-help-popup');

					$help_text_icon = !empty($setting['wphp_whatsapp_icon']) ? 
										$setting['wphp_whatsapp_icon'] : 
										 WPHP_IMAGE.'whatsapp.png';;

					$call_icon = !empty($setting['wphp_call_icon']) ? 
										$setting['wphp_call_icon'] : 
										 WPHP_IMAGE.'call_ion.png';;

					$help_icon = !empty($setting['wphp_help_icon']) ? 
										$setting['wphp_help_icon'] : 
										 WPHP_IMAGE.'help_icon.png';;

					$chat_icon = !empty($setting['wphp_chat_icon']) ? 
										$setting['wphp_chat_icon'] : 
										 WPHP_IMAGE.'chat_icon.png';;

	      	$isSearchEnable = (!empty($setting['wphp_enable_searchbox']) && $setting['wphp_enable_searchbox']=="on") ? true : false;
	      	
	      	$ischatEnable = (!empty($setting['wphp_enable_chat']) && $setting['wphp_enable_chat']=="on") ? true : false;
	      	$iscallEnable = (!empty($setting['wphp_enable_call']) && $setting['wphp_enable_call']=="on") ? true : false;
	      	


	      	ob_start();

			 ?>
			 <div class="wp_icon">
			 	<button type="button" class="call-modal-btn wp_hide" data-toggle="modal" data-target="#wphpHelpbox"><img src="<?php echo esc_url($chat_icon); ?>" class="help1"></button>
			 </div>
		
			 <?php
    if($iscallEnable && (!empty($setting['wphp_whatsapp_text']))){?>
    	<div class="call_icon1 wp_hide">
			<?php $call_url = 'https://wa.me/'.$setting['wphp_whatsapp_text'].'?text='.$setting["wphp_whatsapp_message"];  ?>

    		<a href="<?php echo esc_url($call_url); ?>" class="help-modal-btn5 " data-toggle="modal" data-target="#call"><img src="<?php echo esc_url($call_icon); ?>" class="help1"></a>
    	</div>
    	
    	<?php
    }	
    ?>

		
		
	<?php
    if($ischatEnable && (!empty($setting['wphp_whatsapp_text']))){?>
    	<div class="wp_icon1 wp_hide">
			<?php $whatsapp_url = 'https://wa.me/'.$setting['wphp_whatsapp_text'].'?text='.$setting["wphp_whatsapp_message"];  ?>

    		<a href="<?php echo esc_url($whatsapp_url); ?>" class="help-modal-btn1 " ><img src="<?php echo esc_url($help_text_icon); ?>" class="help1"></a>
    	</div>
    	
    	<?php
    }	
    ?>

    

    <div class="wp_div_icon">
    	<button type="button" class="help-modal-btn2" id="formButton"><img src="<?php echo esc_url($help_icon); ?>" class="help1"></button>
    </div>
    
			<div id="wphpHelpbox" class="modal fade right" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content">
			      <div class="modal-header">
			        <h5 class="modal-title"><?php echo esc_html($modal_title); ?></h5>
			        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
			          <span aria-hidden="true">&times;</span>
			        </button>
			      </div>
			      <div class="modal-body">
			      	<?php if($isSearchEnable) : ?>
			      		<div class="search-form">
			      			<input type="search" name="ajax-search" class="ajax-search" placeholder="<?php esc_attr_e($search_placeholder,'wp-help-popup'); ?>"  />
			      		</div>
			      		<div style="display:none;" class="search_text_desc"><?php echo esc_html__('Please enter','wp-help-popup').' <span class="wpic_desc_count">'.esc_html__('3','wp-help-popup').'</span>'.esc_html__(' or more characters.','wp-help-popup'); ?></div>
			      		<?php endif; ?>
			      		<div class="wphp_result_post">
			      			<?php 	
			      			$number_of_posts  = !empty($setting['wphp_noof_recent_posts'])? $setting['wphp_noof_recent_posts'] : 4;
			      			$post_type  = !empty($setting['wphp_choose_post_type'])? $setting['wphp_choose_post_type'] : 'post';

							$next_args = array(
							                'post_type' => $post_type,
							                'post_status' => 'publish',
							                'posts_per_page'=>$number_of_posts,
							                'order'=>'DESC',
							                'orderby'=>'ID',
							                );
							$next_the_query = new WP_Query( $next_args );
							if($next_the_query->have_posts()){
								echo "<ul class='wp_result_list'>";
							    while ( $next_the_query->have_posts() ) {
							        $next_the_query->the_post();
							        $id =  $next_the_query->post->ID;
							        $title = get_the_title($id);
							        $title = substr($title, 0, 40);?>
							        <li><a target="_blank" href="<?php the_permalink(); ?>" ><?php echo $title; ?></a></li>
							        <?php
							    }
							    echo "</ul>";
							    echo "<ul class='wp_ajax_result'></ul>";
							}
							wp_reset_postdata();
							?>
			      		</div>
			        </div>
			      <div class="modal-footer">
			        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo esc_html__('Close','wp-help-popup'); ?></button>
			      </div>
			    </div>
			  </div>
			</div>

			<!--call modal-->
					<div id="call" class="modal fade right" tabindex="-1" role="dialog">
			  <div class="modal-dialog" role="document">
			    <div class="modal-content">
			      <div class="modal-header">
			        <!-- <h5 class="modal-title"><?php echo esc_html($modal_title); ?></h5> -->
			        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
			          <span aria-hidden="true">&times;</span>
			        </button>
			      </div>
			      <div class="modal-body">
			      		<p>  Please Call Us:  <?php 	
			      			echo $setting['wphp_whatsapp_text'];
							?> </p>
			      
			    </div>
			  </div>
			</div>
		<?php
		$popup_markup = ob_get_contents();
		ob_clean();
		echo $popup_markup;
		}
	}
	//WPHP_IMAGE.'help_icon.png'
	function wphp_plugin_menu(){

		add_menu_page( 
			 'WP Help Popup',
			__('WP Help Popup','wp-help-popup'),
			'manage_options',
			'wp_help_overview',
			array($this,'wphp_plugin_overview'),
      		WPHP_IMAGE.'setting_pop_icon.PNG');

		add_submenu_page( 'wp_help_overview',
		 __('Settings','wp-help-popup'), 
		 __('Settings','wp-help-popup'),
    	'manage_options',
    	'wp_help_settings',
    	array($this,'wphp_setting_page'));

		add_submenu_page( 'wp_help_overview',
		 __('Our Plugins','wp-help-popup'), 
		 __('Our Plugins','wp-help-popup'),
    	'manage_options',
    	'wp_our_plugins',
    	array($this,'wphp_our_plugins'));

		add_submenu_page(
			'wp_help_overview',
			esc_html__( 'Upgrade to Pro', 'wp-help-popup' ),
			sprintf(
				'<span style="color:#FF8C39; font-weight: 600;"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: bottom;" ><rect x="0.5" y="0.5" width="19" height="19" rx="2.5" fill="#FF8C39" stroke="#FF8C39"/><path d="M10 5L13 13H7L10 5Z" fill="#EFEFEF"/><path fill="white" fill-rule="evenodd" d="M5 7L5.71429 13H14.2857L15 7L10 11.125L5 7ZM14.2857 13.5714H5.71427V15H14.2857V13.5714Z" clip-rule="evenodd"/></svg><span style="margin-left:5px;">%s</span></span>',
				esc_html__( 'Upgrade to Pro', 'wp-help-popup' )
			),
			'manage_options',
			esc_url_raw( 'https://kninfotech.in/product/wp-help-popup-pro/' )
		);
	}

	function wphp_our_plugins(){
		ob_start();
		require_once(plugin_dir_path( __FILE__ )."/view/ourplugins.php");
		$welcome = ob_get_contents();
		ob_clean();
		echo $welcome;
	}

	//setting function
	function wphp_setting_page(){

		$plugin_settings = maybe_unserialize(get_option('wphp_options_group'));

    	if(!empty($this->result) && array_key_exists('error',$this->result) ){
    			$error = isset($this->result['error']) ? $this->result['error'] :'';
    			echo "<div class='error notice is-dismissible'><p><strong>".$error."</strong></p><button type='button' class='notice-dismiss'><span class='screen-reader-text'>".esc_html__('Dismiss this notice.','wp-help-popup')."</span></button></div>";

    		}else if(array_key_exists('success',$this->result)){

    			$success = isset($this->result['success']) ? $this->result['success'] : '';

    			echo "<div class='updated'><p>".$success."</p></div>";
    		}

    	$isEnable = (!empty($plugin_settings['wp_enable_popup'])) ?'checked':''; 
    	$isEnablepages = (!empty($plugin_settings['wp_enable_pages'])) ?'checked':''; 
    	$isEnablepost = (!empty($plugin_settings['wp_enable_post'])) ?'checked':''; 

    	$isSearchEnable = (!empty($plugin_settings['wphp_enable_searchbox'])) ?'checked':'';
    	$ischatEnable = (!empty($plugin_settings['wphp_enable_chat'])) ?'checked':'';
    	$iscallEnable = (!empty($plugin_settings['wphp_enable_call'])) ?'checked':'';
    	
    	ob_start();

    	?>
		  <div class="wpic_plugin_setting">
		  		<div class="wpic_setting_container">
			    	<h2 class="wpic-plugin-header"> <?php echo esc_html__('WP Help Popup Setting','wp-help-popup'); ?> </h2>
				    <form method="post" action="" id="wphp_plugin_settting" enctype="multipart/form-data">
				    	
						 <div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label for="enable_disable"><?php echo esc_html__('Enable Popup ','wp-help-popup'); ?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
								<div class="wpic-row"> 
				    				<div class="wpic-cd-3">
						    		<div class="wpic_checkout_container">
						    			<label><?php echo esc_html__('Entire Website ', 'wp-help-popup');?></label>
									  <input type="checkbox" name="wp_enable_popup" class="wpic-form-control" <?php echo $isEnable; ?>   >
									  <span class="wpic_checkmark"></span>
									</div>
									</div>
									<div class="wpic-cd-3">
									<div class="wpic_checkout_container">
						    			<label><?php echo esc_html__('Selected Pages', 'wp-help-popup');?></label>
									  <input type="checkbox" name="wp_enable_pages" id="selectedpages" class="wpic-form-control" <?php echo $isEnablepages; ?>   >
									  <span class="wpic_checkmark"></span>
									</div>
									</div>
									<div class="wpic-cd-3">
									<div class="wpic_checkout_container">
						    			<label><?php echo esc_html__('Selected Post Type', 'wp-help-popup');?></label>
									  <input type="checkbox" name="wp_enable_post" id="selectedpost" class="wpic-form-control" <?php echo $isEnablepost; ?>   >
									  <span class="wpic_checkmark"></span>
									</div>
									</div>
								</div>
						    	</div>
				    		</div>
				    	</div>

				    	<?php 
				    	$pageshowclass=(empty($isEnablepages)) ? 'pages': '' ;
				    	?>
				    	<div class="wpic-group <?php echo $pageshowclass; ?>  pages1">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Select Pages to show help Box','wp-help-popup'); ?> </label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		<select name="wphp_choose_pages[]" class="wpic_select2 wpic-form-control" multiple="multiple">
						    		<?php 
						    		$next_args = array(
							                'post_type' => 'page',
							                'post_status' => 'publish',
							                'posts_per_page'=>-1,
							                'order'=>'DESC',
							                'orderby'=>'ID',
							                );
									$next_the_query = new WP_Query( $next_args );
									if ( $next_the_query->have_posts() ) {
									    while ( $next_the_query->have_posts() ) {
									        $next_the_query->the_post();
									        $id =  $next_the_query->post->ID;
									        $title = get_the_title($id);
									        $title = substr($title, 0, 40);
									        $selected = (!empty($plugin_settings['wphp_choose_pages'])  &&  in_array($id, $plugin_settings['wphp_choose_pages']))?'selected': ''; ?>
									        <option <?php echo $selected; ?> value="<?php echo $id; ?>"><?php echo $title; ?></option>
									        <?php
									    }
									}
									wp_reset_postdata(); ?> 
						    		</select>
						    	</div>
				    		</div>
				    	</div>
				    	<?php 
				    	$pageshowclass=(empty($isEnablepost)) ? 'post': '' ;
				    	?>

				    	<div class="wpic-group <?php echo $pageshowclass; ?> post1">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Select post type for show in popup and search','wp-help-popup'); ?> </label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		<select name="wphp_choose_post_type[]" class="wpic_select2 wpic-form-control" multiple="multiple">
						    		<?php 
						    		$args = array(
									   'public'   => true,
									   '_builtin' => false
									);
									$post_types = get_post_types( $args); 
									$post_types['page']='Page';
									$post_types['post']='Post';
									foreach ( $post_types  as $key => $post_type ) {
									    	$selected = (!empty($plugin_settings['wphp_choose_post_type']))  && is_array($plugin_settings['wphp_choose_post_type']) &&  in_array($key, $plugin_settings['wphp_choose_post_type'])?'selected': ''; 
									
									    	?>
									        <option <?php echo $selected; ?> value="<?php echo $key; ?>"><?php echo $post_type; ?></option>

								    <?php	} ?> 
									</select>
						    	</div>
				    		</div>
				    	</div>
				    	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Enable Search box','wp-help-popup'); ?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
					    			<div class="wpic_checkout_container">
						    			<input type="checkbox" name="wphp_enable_searchbox" <?php echo $isSearchEnable; ?>  />
						    			<span class="wpic_checkmark"></span>
						    		</div>
						    	</div>
				    		</div>
				    	</div>
				    	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Search Box Placeholder','wp-help-popup'); ?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		<?php $wphp_searchbox_placeholder =(!empty($plugin_settings['wphp_searchbox_placeholder']))?$plugin_settings['wphp_searchbox_placeholder']:''; ?>
						    		<input type="text" class="wpic-form-control" name="wphp_searchbox_placeholder" placeholder="Type your question here.." value="<?php echo esc_attr($wphp_searchbox_placeholder); ?>" />
						    	</div>
				    		</div>
				    	</div>

				    	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Popup Title','wp-help-popup'); ?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		<?php $wphp_popup_title =(!empty($plugin_settings['wphp_popup_title']))?$plugin_settings['wphp_popup_title']:''; ?>
						    		<input type="text" class="wpic-form-control" name="wphp_popup_title" placeholder="How can we assist you?" value="<?php echo esc_attr($wphp_popup_title); ?>" />
						    	</div>
				    		</div>
				    	</div>
				    	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Popup Button Text', 'wp-help-popup');?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		<?php $wphp_popupbtn_text =(!empty($plugin_settings['wphp_popupbtn_text']))?$plugin_settings['wphp_popupbtn_text']:''; ?>
						    		<input type="text" class="wpic-form-control" name="wphp_popupbtn_text" placeholder="Help" value="<?php echo esc_attr($wphp_popupbtn_text); ?>" />
				    			</div>
				    		</div>
				    	</div>
				    	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('No results Message', 'wp-help-popup');?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		<?php $wphp_noresult_text =(!empty($plugin_settings['wphp_noresult_text']))?$plugin_settings['wphp_noresult_text']:''; ?>
						    		<input type="text" class="wpic-form-control" name="wphp_noresult_text" placeholder="We’re sorry, no results were found." value="<?php echo esc_attr($wphp_noresult_text); ?>" />
				    			</div>
				    		</div>
				    	</div>
				    	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Number Of Recent Posts in Popup','wp-help-popup'); ?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		<?php $wphp_noof_recent_posts =(!empty($plugin_settings['wphp_noof_recent_posts']))?$plugin_settings['wphp_noof_recent_posts']:''; ?>
						    		<input type="text" class="wpic-form-control" name="wphp_noof_recent_posts" value="<?php echo esc_attr($wphp_noof_recent_posts); ?>" />
				    			</div>
				    		</div>
				    	</div>

				    	 	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Enable Whatsapp chat','wp-help-popup'); ?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
					    			<div class="wpic_checkout_container">
						    			<input type="checkbox" name="wphp_enable_chat" <?php echo $ischatEnable; ?>  />
						    			<span class="wpic_checkmark"></span>
						    		</div>
						    	</div>
				    		</div>
				    	</div>
				    	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Enter Your WhatsApp Number', 'wp-help-popup');?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		<?php $wphp_whatsapp_text =(!empty($plugin_settings['wphp_whatsapp_text']))?$plugin_settings['wphp_whatsapp_text']:''; ?>
						    		<input type="text" class="wpic-form-control" name="wphp_whatsapp_text" placeholder="Enter Your Whatsapp Number." pattern="[1-9]{1}[0-9]{9}" value="<?php echo esc_attr($wphp_whatsapp_text); ?>" />
				    			</div>
				    		</div>
				    	</div>
				    	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Enter Your Default WhatsApp Message', 'wp-help-popup');?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		<?php $wphp_whatsapp_message =(!empty($plugin_settings['wphp_whatsapp_message'])) ? $plugin_settings['wphp_whatsapp_message']:''; ?>
						    		<textarea type="text" class="wpic-form-control" name="wphp_whatsapp_message" placeholder="Enter Your Default Whatsapp Message." /><?php echo esc_attr($wphp_whatsapp_message); ?></textarea>
				    			</div>
				    		</div>
				    	</div>
				    	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Upload your whatsApp icon', 'wp-help-popup');?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		
						    		<?php $help_text_icon = !empty($plugin_settings['wphp_whatsapp_icon']) ? 
										$plugin_settings['wphp_whatsapp_icon'] : 
										 WPHP_IMAGE.'whatsapp.png';
										 ob_start();

			                           ?>
									    <div class="file_upload_container" >
										<div class="file-upload-wrapper" data-text="Select your file">
											<input name="wphp_whatsapp_icon" type="file" class="file-upload-field" value="">
										</div>
										<div class="uploaded_file_preview" >
										<img src="<?php echo esc_url($help_text_icon); ?>">
										</div>
										</div>
				    		<?php
		                  $popup_markup = ob_get_contents();
		                  ob_clean();
		                 echo $popup_markup;
		
				    		 ?>
				    			</div>
				    		</div>
				    	</div>

				    	    <div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Upload Help Button Icon', 'wp-help-popup');?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		
						    		<?php $help_text_icon = !empty($plugin_settings['wphp_help_icon']) ? 
										$plugin_settings['wphp_help_icon'] : 
										 WPHP_IMAGE.'help_circled_icon.png';
										 ob_start();

			                           ?>
									      <div class="file_upload_container" >
										<div class="file-upload-wrapper" data-text="Select your file">
											<input name="wphp_help_icon" type="file" class="file-upload-field" value="">
										</div>
										<div class="uploaded_file_preview" >
												<img src="<?php echo esc_url($help_text_icon); ?>">
										</div>
										</div>
				    		<?php
		                  $popup_markup = ob_get_contents();
		                  ob_clean();
		                 echo $popup_markup;
		
				    		 ?>
				    			</div>
				    		</div>
				    	</div>
				    	
				    	    <div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Upload Chat Button Icon', 'wp-help-popup');?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		
						    		<?php $wphp_chat_icon = !empty($plugin_settings['wphp_chat_icon']) ? 
										$plugin_settings['wphp_chat_icon'] : 
										 WPHP_IMAGE.'chat_icon.png';
										 ob_start();
			                           ?>
									      <div class="file_upload_container" >
										<div class="file-upload-wrapper" data-text="Select your file">
											<input name="wphp_chat_icon" type="file" class="file-upload-field" value="">
										</div>
										<div class="uploaded_file_preview" >
											<img src="<?php echo esc_url($wphp_chat_icon); ?>">
										</div>
										</div>
										<?php
											$popup_markup = ob_get_contents();
											ob_clean();
											echo $popup_markup;
					
										?>
				    			</div>
				    		</div>
				    	</div>


				    	 	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Enable Supports Call','wp-help-popup'); ?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
					    			<div class="wpic_checkout_container">
						    			<input type="checkbox" name="wphp_enable_call" <?php echo $iscallEnable; ?>  />
						    			<span class="wpic_checkmark"></span>
						    		</div>
						    	</div>
				    		</div>
				    	</div>

				    	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Enter Your Phone Number', 'wp-help-popup');?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
						    		<?php $wphp_phone_number =(!empty($plugin_settings['wphp_phone_number']))?$plugin_settings['wphp_whatsapp_text']:''; ?>
						    		<input type="text" class="wpic-form-control" name="wphp_phone_number" placeholder="Enter Your Phone Number." value="<?php echo esc_attr($wphp_phone_number); ?>" />
				    			</div>
				    		</div>
				    	</div>

				    	<div class="wpic-group">
				    		<div class="wpic-row"> 
				    			<div class="wpic-cd-4">
						    		<div class="wpic-label">
						    			<label><?php echo esc_html__('Upload Your Call Icon', 'wp-help-popup');?></label>
						    		</div>
						    	</div>
						    	<div class="wpic-cd-8">
								<?php $wphp_call_icon = !empty($plugin_settings['wphp_call_icon']) ? 
										$plugin_settings['wphp_call_icon'] : 
										 WPHP_IMAGE.'call_ion.png';
									
										 ob_start();
			                           ?>
									   <div class="file_upload_container" >
										<div class="file-upload-wrapper" data-text="Select your file">
											<input name="wphp_call_icon" type="file" class="file-upload-field" value="">
										</div>
										<div class="uploaded_file_preview" >
											<img src="<?php echo esc_url($wphp_call_icon); ?>">
										</div>
										</div>
						    		
				    		<?php
		                  $popup_markup = ob_get_contents();
		                  ob_clean();
		                 echo $popup_markup;
		
				    		 ?>
				    			</div>
				    		</div>
				    	</div>

						<input type="hidden" name="wphp_nonce" value="<?php echo wp_create_nonce("wphp"); ?>"/>
						<input type="submit" name="setting-name" class="button button-primary button-large" value="Save" />
				    </form>
				</div>
		  </div>
		<?php
		$plugin_setting_markup = ob_get_contents();
		ob_clean();
		echo $plugin_setting_markup;
	}

	function wphp_plugin_activate(){

		$plugin_settings = get_option('wphp_options_group');
		if(!($plugin_settings)){
			$activaton_setting = array(
				'wphp_searchbox_placeholder' =>'Type your question here..',
				'wphp_popup_title' =>'How can we assist you?',
				'wphp_popupbtn_text' =>'Help',
				'wphp_choose_post_type' =>'post',
				'wphp_noresult_text' =>'We’re sorry, no results were found.',
				'wphp_noof_recent_posts' =>4,
				'wphp_enable_searchbox' =>'on',
				'wp_enable_popup'  =>'on',
				);

			update_option('wphp_options_group',$activaton_setting);

		}



	}
	function wphp_plugin_overview(){
		ob_start();
		require_once(plugin_dir_path( __FILE__ )."/view/welcome.php");
		$welcome = ob_get_contents();
		ob_clean();
		echo $welcome;
	}
	function wphp_constants(){


		if(!defined('WPHP_PLUGIN_DIR'))
			define('WPHP_PLUGIN_DIR',plugin_dir_url(__FILE__));


		if(!defined('WPHP_ASSET'))
			define('WPHP_ASSET',WPHP_PLUGIN_DIR.'assets/');

		if(!defined('WPHP_CSS'))
			define('WPHP_CSS',WPHP_ASSET.'css/');	
			
		if(!defined('WPHP_JS'))
			define('WPHP_JS',WPHP_ASSET.'js/');

		if(!defined('WPHP_IMAGE'))
			define('WPHP_IMAGE',WPHP_ASSET.'images/');

		if(!defined('WPHP_INC'))
			define('WPHP_INC',WPHP_PLUGIN_DIR.'include/');

		if(!defined('WPHP_CLASSES'))
			define('WPHP_CLASSES',WPHP_INC.'classes/');

		if(!defined('WPHP_VIEW'))
			define('WPHP_VIEW',WPHP_INC.'view/');

	}

}
return new WP_Help_Popup();
}



?>