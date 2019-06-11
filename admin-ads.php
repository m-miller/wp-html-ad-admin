<?php
/*
Plugin Name: Admin Ad Manager
Plugin URI: 
Description: Admin for ad content HTML generator
Author: Martin Miller
Version: 2.5
Author URI: 
*/

/*  TODO:  	update button styles and selection
 * 			coding standards, update phpdoc
 *			optimize for gutenberg
 *
 *
*/

define( 'LIA_PATH', plugin_dir_url( __FILE__ ) );

$uploads = wp_upload_dir(); 

define( 'ADSPATH', $uploads['basedir'] . '/gdlogin/' );
define( 'ADSURL', $uploads['baseurl'] . '/gdlogin/' );

//require_once( dirname( __FILE__ ) . '/includes/settings.php' );

add_action( 'plugins_loaded', array( LogInAds::get_instance(), 'get_instance' ) );
	
class LogInAds {
	
	const LIA_POST_TYPE	= "loginads";
	const LIA_TAXONOMY 	= "LIA_category";
	const LIA_PRODUCTS = "gcdntags";
	const LIA_TEXTDOMAIN = "LIA_textdomain";
	
	private static $instance;
	private $settings;
	
	private $postmeta_keys = array( 'lia_button_link', 'lia_button_text', 'lia_button_class', 'lia_button_size', 'lia_bg_img_url', 'lia_bg_img_pos', 'lia_bg_img_css', 'lia_startdate', 'lia_enddate' );
	
	/**
	  *  singleton class init
	  *
	  * @param private $instance
	  * @return class instance
	  */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function __construct() {
		add_action( 'init', array( $this, 'lia_cpt' ), 0 );
		
		add_action( 'admin_init', array( $this, 'admin_init' ) );	
		
		add_action ( 'default_content', array( $this, 'ad_default_content' ), 10, 2 );
		
			// remove gravity form add form button
		//add_filter( 'gform_display_add_form_button', function(){ return false; } );	
		
			// remove soliloquy add slider button
		add_action( 'admin_head', array( $this, 'remove_soliloquy_slider_button' ), 98 );
		add_action( 'do_meta_boxes', array( $this, 'remove_addthis_metabox' ) );	
		
		add_action( 'wp_ajax_LIA_save_postmeta', array( $this, 'LIA_save_postmeta' ) );
		
		add_action( 'admin_enqueue_scripts', array( $this, 'LIA_admin_enqueue' ) );
		
		//$this->settings = new loginads_options();	
		// settings: view/edit admanifest json, category inclusion, use location(?), 	
		
	} // __construct
	
	public function LIA_admin_enqueue( $hook ) {
		global $typenow;
		// only load the admin js on the edit-page admin screen
		    if ( ( self::LIA_POST_TYPE == $typenow ) AND 'edit.php' == $hook  ) {
				wp_enqueue_script( 'lia-admin', LIA_PATH. '/includes/js/lia-admin.js' );
			
		    } else {
				return;
		}
		wp_register_style( 'datepicker-css', plugins_url( '/includes/css/datepicker.css', __FILE__ ), array( 'jquery-ui-core', 'jquery-ui-datepicker' ),'','all' );
		wp_enqueue_style( 'datepicker-css' );
	} // LIA_admin_enqueue
	
	private function postmetakeys(){
		return $this->postmeta_keys;
	}
	
	public function ad_default_content( $content, $post ) {
		if ( $post->post_type == self::LIA_POST_TYPE ) {
			$content = "<div class=\"l1\"> </div>\n<div class=\"l2\"> </div>\n<div class=\"l3\"> </div>";
		}
		return $content;
	}
	
	
	public function LIA_cpt() {		
	
		add_filter ( 'manage_' . self::LIA_POST_TYPE . '_posts_columns' , array( $this, 'columns_add' ) );
		add_filter ( 'manage_' . self::LIA_POST_TYPE . '_posts_custom_column' , array( $this, 'columns_output' ), 10, 2 );
		
		add_action ( 'save_post_' . self::LIA_POST_TYPE, array( $this, 'save_post' ) );
		// action for moved from publish to draft - remove from manifest
		add_action ( 'trashed_post', array( $this, 'trash_post' ) );
		
	
		// Register custom taxonomy
				 $taxlabels = array(
		    			'name'			 			=> 		_x( 'Ad Categories', 'taxonomy general name' ),
		   			 	'singular_name' 			=> 		_x( 'Ad Category', 'taxonomy singular name' ),
		    			'search_items' 				=>  	__( 'Search Ad Categories' ),
		    			'all_items' 				=> 		__( 'All Ad Categories' ),
		    			'parent_item' 				=> 		__( 'Parent Ad Category' ),
		    			'parent_item_colon' 		=> 		__( 'Parent Ad Category:' ),
		    			'edit_item' 				=> 		__( 'Edit Ad Category' ), 
		    			'update_item' 				=> 		__( 'Update Ad Category' ),
		   				'add_new_item' 				=> 		__( 'Add New Ad Category' ),
		    			'new_item_name' 			=> 		__( 'New Ad Category Name' ),
		    			'menu_name' 				=> 		__( 'Ad Categories' ),
		 			 ); 	

		register_taxonomy ( self::LIA_TAXONOMY, array( self::LIA_POST_TYPE ), array(
		   				'hierarchical' 				=> 		true,
		    			'labels' 					=> 		$taxlabels,
		    			'show_ui' 					=> 		true,
						'show_in_nav_menus'			=>		false,
						'show_tagcloud' 			=>		true,
		    			'query_var' 				=> 		true,
		  				'rewrite' 					=> 		false,
		  ) );	// register taxonomy
		
	// Register UKDN custom post type
	register_post_type ( self::LIA_POST_TYPE , array(
		'labels'							=> 		array(
			'name'							=>		_x( 'Login Ads', 'post type general name' , 'LIA_textdomain' ),
			'singular_name' 				=> 		_x( 'Login Ad', 'post type singular name' , 'LIA_textdomain' ),
			'add_new' 						=> 		_x( 'Add New Login Ad', 'login Ad' , 'LIA_textdomain' ),
			'add_new_item' 					=> 		__( 'Add New Login Ad' , 'LIA_textdomain' ),
			'edit_item' 					=> 		__( 'Edit Login Ad' , 'LIA_textdomain' ),
			'new_item' 						=> 		__( 'New Login Ad' , 'LIA_textdomain' ),
			'all_items' 					=> 		__( 'All Login Ads' , 'LIA_textdomain' ),
			'view_item' 					=> 		__( 'View Login Ad' , 'LIA_textdomain' ),
			'search_items' 					=> 		__( 'Search Login Ads' , 'LIA_textdomain' ),
			'not_found' 					=>  	__( 'No Login Ads found' , 'LIA_textdomain' ),
			'not_found_in_trash' 			=> 		__( 'No Login Ads found in Trash' , 'LIA_textdomain' ), 
			'parent_item_colon' 			=> 		__( 'Parent Login Ad',  'LIA_textdomain' ),
			'menu_name' 					=>		__( 'Login Ads', 'LIA_textdomain' ),
		 											),
		'description' 						=> 		__( 'Login Ads' ),
		'singular_label' 					=>		__( 'Login Ad' ),
		'public' 							=> 		true,
		'taxonomies'						=>   	array(self::LIA_TAXONOMY, self::LIA_PRODUCTS),
		'exclude_from_search'				=>		true,
		'show_ui'							=>	 	true, 								// UI in admin panel
		'show_in_nav_menus'					=>		false,
		'publicly_queryable'				=>		false,	
		'capability_type' 					=> 		'post',
		'hierarchical' 						=> 		false,
		'has_archive'						=>		false,
		'rewrite' 							=> 		array(								// Permalinks
			'slug' 							=> 		self::LIA_POST_TYPE,
			'with_front'					=>		false,
														), 					
		'menu_icon' 						=> 		'dashicons-email-alt', 
		'menu_position' 					=> 		3,
		'query_var' 						=> 		self::LIA_POST_TYPE, 						// WP_Query schema
		'supports' 							=> 		array ( 'title','editor','author' )
	) );
		
		add_filter( 'post_updated_messages', array( $this, 'LIA_updated_messages' ) );

	flush_rewrite_rules();

	} // lia function 
	
	
	// CSS columns
	public function columns_add( $columns ) {
		$columns = array(
			"cb" 							=> 		"<input type=\"checkbox\" />",
			"ID"							=>		"ID",
			"title" 						=> 		"Title",
			"author" 						=> 		"Author",
			"date" 							=> 		"Date",
			self::LIA_TAXONOMY 				=> 		"Category",
			"start"							=>		"Start Date",
			"end"							=>		"End Date",
			"include"						=>		"Include in rotation",
			"image" 						=> 		"Background Image", // add image thumb
	);
		return $columns;
	}
	
	
	public function columns_output( $columns, $post_id ) {
		switch ( $columns ) {
			case "LIA_category":
			$cats = get_the_terms( $post_id, self::LIA_TAXONOMY );
			$tax_html = array();
			if ( $cats ) {
				foreach ( $cats as $tax_cats ) {
					array_push( $tax_html, $tax_cats->name );	// output without a link
				}
				echo implode( $tax_html, ", " );
			} else {
				echo "None";
			}
		break;
			break;
			
			case "ID":
				echo $post_id;
			break;
			
			case "start":
				echo get_post_meta( $post_id, 'lia_startdate', true );
			break;
			
			case "end":
				echo get_post_meta( $post_id, 'lia_enddate', true );
			break;
			
			case "include":
				$ischk = get_post_meta( $post_id, 'lia_ad_rotate', true );
				if ( ! empty( $ischk ) ) {
					$chk = 'checked';
				} else {
					$chk = '';
				}
				echo '<input type="checkbox" id="ad-rotate-'.$post_id.'" title="Check to include in ad rotation." '.$chk.' />';
			break;
		
			case "image":
				$img = get_post_meta( $post_id, 'lia_bg_img_url', true );
				echo "<img class='attachment-50x70 size-50x70 wp-post-image' src='$img' height='50' width='70'>";
			break;
			
		}
	}
	
	
	// ajax function
	public function LIA_save_postmeta() {
		// hmm... delete files not being used? wp cron weekly?
		$postid = $_REQUEST['postid'];
		$checked = $_REQUEST['checked'];
		if  ( $checked == 'true' ) {
			update_post_meta( $postid, 'lia_ad_rotate', $postid );
		} else {
			delete_post_meta( $postid, 'lia_ad_rotate' );
		}
		// export the admanifest.json file
		$this->output_json_ad_manifest();
		exit();
	} // save in postmeta 
	


	// remove soliloquy slider button
	public function remove_soliloquy_slider_button() {
		if ( class_exists( 'Soliloquy_Editor') ) {
			$soliloquy_editor_button = Soliloquy_Editor::get_instance();
			remove_filter( 'media_buttons', array( $soliloquy_editor_button, 'media_button' ), 98 );
		}
	}
	
	public function remove_addthis_metabox(){
		remove_meta_box( 'at_widget', self::LIA_POST_TYPE, 'normal' );
	}

	public function LIA_updated_messages( $messages ) {
  	  global $post;

  		$messages[self::LIA_POST_TYPE] = array(
    		0 	=>		'', // Unused. Messages start at index 1.
    		1 	=> 		sprintf( __( 'Login ad updated. <a href="%s">View login ad</a>' ), esc_url( get_permalink( $post->ID ) ) ),
    		2 	=> 		__( 'Custom field updated.' ),
    		3 	=>		__( 'Custom field deleted.' ),
    		4 	=> 		__( 'Login ad updated.' ),						/* translators: %s: date and time of the revision */
   			5 	=>		isset( $_GET['revision'] ) ? sprintf( __( 'Login ad restored to revision from %s' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    		6 	=> 		sprintf( __(' Login ad published. <a href="%s">View login ad</a>' ), esc_url( get_permalink( $post->ID ) ) ),
    		7 	=> 		__( 'Login ad saved.' ),
    		8 	=> 		sprintf( __( 'Login ad submitted. <a target="_blank" href="%s">Preview login ad</a>' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ),
    		9 	=>		sprintf( __( 'Login ad scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview login ad</a>' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post->ID ) ) ),
    		10 	=>		sprintf( __( 'Login ad draft updated. <a target="_blank" href="%s">Preview login ad</a>' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ),
  		);
  		return $messages;
	}


	// on trash post, needs to remove from admanifest...
	public function trash_post( $post_id ) {
    	global $post_type;
    	if ( $post_type == self::LIA_POST_TYPE ) {
			unlink( ADSPATH . 'a' . $post_id . '.html' );
			$this->output_json_ad_manifest();	
		}
	}
	
	public function get_manifest(){
		$manifest = file_get_contents( ADSPATH . 'manifest.json' );
		$retval = json_decode( $manifest, TRUE );
		return $retval;	 
	}

	public function save_post( $post_id ) {
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return; 	
		if ( get_post_type() != self::LIA_POST_TYPE  ) return;
		if ( !current_user_can ( 'edit_page', $post_id ) || !current_user_can ( 'edit_post', $post_id ) ) return $post_id;

		$postmeta_keys = $this->postmetakeys();

		$btnlink = filter_var( $_POST['bt-link'], FILTER_VALIDATE_URL );  // or FILTER_SANITIZE_URL
		$btntext = sanitize_text_field( $_POST['bt-text'] );
		$btstyle = $_POST['lia_button_class'];
		$btsz = $_POST['lia_button_size'];
		$bkgroundimg = filter_var( $_POST['imgsrc'], FILTER_VALIDATE_URL );
			// check if no img, then use fallback
		$bkimgpos = $_POST['bg_img_pos'];
		$bg_img_css = $_POST['bg_img_css'];
		$start = $_POST['stdate'];
		$end = $_POST['enddate'];
		
		$vars = array( $btnlink, $btntext, $btstyle, $btsz, $bkgroundimg, $bkimgpos, $bg_img_css, $start, $end );
	
		foreach ( $vars as $n => $var ) { 
			if ( isset( $var ) ) {
				update_post_meta( $post_id, $postmeta_keys[$n], $var );
			} else {
				delete_post_meta( $post_id, $var );
			}
		}
		$this->push_to_file();
	}
	

	public function enqueue_link_check(){
    	global $post_type;
    	if ( $post_type == self::LIA_POST_TYPE ) {
	     	wp_register_script( 'get-dn-link', plugins_url( '/includes/js/get-dn-link.js', __FILE__ ), array( 'jquery','media-upload','thickbox' ) );
			
	     	wp_enqueue_script( 'get-dn-link' );
			//wp_enqueue_script( 'jquery-ui-datepicker' );
			
		}
	}
	
	public function admin_styles($hook){
    	global $post_type;
    	if ( $post_type == self::LIA_POST_TYPE ) {
			wp_enqueue_script('jquery-ui-datepicker');
			    wp_register_style('jquery-ui', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
			    wp_enqueue_style('jquery-ui');
		}
	}

	/**
	  *  On admin init, load metaboxes and relevant admin js
	  *
	  * @param none
	  * @return void
	  */
	public function admin_init() {			
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'admin_print_scripts-post-new.php', array( $this, 'enqueue_link_check' ), 11 );
		add_action( 'admin_print_scripts-post.php', array( $this, 'enqueue_link_check' ), 11 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );

	} //  public function admin_init()
		
	// hook into WP add_meta_boxes action
	public function add_meta_box() {
		add_meta_box ( 
			sprintf( 'link_%s_section', self::LIA_POST_TYPE ),
			sprintf( 'Link', ucwords( str_replace( "_", " ", self::LIA_POST_TYPE ) ) ),
			array( $this, 'add_link_meta_box' ),
			self::LIA_POST_TYPE,
			'advanced',
			'high'
		);	
			
	} 
	
	/**
	  *  Fetches all the relevant postmeta for loginads object
	  *
	  * @param string $postid 
	  * @global array postmeta keys
	  * @return array
	  */
	private function get_postmeta( $postid ) {
		$postmeta_keys = $this->postmetakeys();
		foreach ( $postmeta_keys as $n => $keys ) {
			$val[$keys] = get_post_meta( $postid, $keys, true );
		}
		return $val;
	}
	
	/**
	  *  Builds the options for the metabox selects
	  *
	  * @param array $drops 
	  * @param string $val
	  * @return string
	  *
      *  example echo $this->get_selected( array( 'btn-large' => 'Large', 'btn-medium' => 'Medium', 'btn-small' => 'Small' ), $val['lia_button_size'] );		
	  */
	private function get_selected( $drops, $val ) {
		$selections = '';
		foreach ( $drops as $co => $opt ) {
			$selections .= "<option value=\"$co\"" . ( $val == $co ? 'selected="selected"' : '' ).">$opt</option>";
		}
		return $selections;
	}
	
 
	// button link, button text, button color
	// TODO: move inline CSS to enqueue
	
	public function add_link_meta_box() {		
		global $post;
		$val = $this->get_postmeta($post->ID);
		// $postmeta_keys = array( 'lia_button_link', 'lia_button_text', 'lia_button_class', 'lia_button_size', 'lia_bg_img_url', 'lia_startdate', 'lia_enddate' );
		?>
		<form id="button-link">
		<div id="btlink" style="margin-bottom: 1em;"><label for="btlink" style="width: 100px; display: inline-block;">Button Link: </label><input style="margin-left: 1em;" type="text" size="80" name="bt-link" id="btn-link" value="<?php echo $val['lia_button_link']; ?>" /></div>
		
		<div id="bttext" style="margin-bottom: 1em;"><label for="bttext" style="width: 100px; display: inline-block;">Button Text : </label><input style="margin-left: 1em;" type="text" size="80" name="bt-text" id="btn-text" value="<?php echo $val['lia_button_text']; ?>" /></div>
		
		<div id="btstyle" style="margin-bottom: 1em;"><label for="btstyle" style="width: 100px; display: inline-block;">Button Style: </label>
			<select name = "lia_button_class" style="margin-left: 1em;">
				<?php 
					$btnopts = array( 'btn-bluemain' => 'Blue', 'btn-greenmain' => 'Green' );
					echo $this->get_selected( $btnopts, $val['lia_button_class'] );
				?>
			</select>
			
			<label for="btsz" style="margin-left: 2em; display: inline-block;">Button Size: </label>
			<select name = "lia_button_size" style="margin-left: 1em;">
				<?php 
					$btnszopts = array( 'btn-large' => 'Large', 'btn-medium' => 'Medium', 'btn-small' => 'Small' );
					echo $this->get_selected( $btnszopts, $val['lia_button_size'] );
				?>
			</select>
			
		</div>
		
		<div id="imgsrc" style="margin-bottom: 1em;"><label for="imgsrc" style="width: 100px; display: inline-block;">Background Image: </label><input style="margin-left: 1em;" type="text" size="100" name="imgsrc" id="img-src" value="<?php echo $val['lia_bg_img_url']; ?>" />
			
		<label for="imgalign" style="margin-left: 2em; display: inline-block;">Image Align: </label>
		<select name = "bg_img_pos" style="margin-left: 1em;">
			<?php
			// just use featured image? 
				$imgposopts = array( 'left' => 'Left', 'center' => 'Center', 'right' => 'Right');
				echo $this->get_selected( $imgposopts, $val['lia_bg_img_pos'] );
				
			?>
		</select>
		<label for="imgcss" style="margin-left: 2em; display: inline-block;">Image Gradient: </label>
		<select name = "bg_img_css" style="margin-left: 1em;">
			<?php
				$imgcssopts = array( 'dark' => 'Dark', 'light' => 'Light', 'none' => 'None' );
				echo $this->get_selected( $imgcssopts, $val['lia_bg_img_css'] );
			?>
		</select>
		<?php 
		
		echo "<div style='margin: 1em 0 0 110px; border: 1px solid #aaa; width: 120px; height: 80px;'><img src='{$val['lia_bg_img_url']}' height='80' width='120'></div>"; ?>
		</div>
		
		<div id="dates" style="margin-bottom: 1em;">
			<label for="liastdate" style="width: 100px; display: inline-block;">Start Date: </label><input type="text" style="margin-right: 2em;" name = "stdate" id="liastdate" value = "<?php echo $val['lia_startdate']; ?>" />
			
			<label for="liaenddate" style="width: 75px; display: inline-block;">End Date: </label><input type="text" name = "enddate" id="liaenddate" value = "<?php echo $val['lia_enddate']; ?>" />
		</div>
		</form>
		<?php	
		
	}

/**
 * 
 */
	public function push_to_file() {
		global $post;
		$vals = $this->get_postmeta( $post->ID );
		$head = "<!DOCTYPE HTML>
			<html>
			<head>
			<meta charset='utf-8'>
			<meta http-equiv='x-ua-compatible' content='ie=edge'>
			<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
		
		$title = "<title>" . $post->post_title . "</title>\n";

		$script = '<!-- start Mixpanel --><script type="text/javascript">(function(e,a){if(!a.__SV){var b=window;try{var c,l,i,j=b.location,g=j.hash;c=function(a,b){return(l=a.match(RegExp(b+"=([^&]*)")))?l[1]:null};g&&c(g,"state")&&(i=JSON.parse(decodeURIComponent(c(g,"state"))),"mpeditor"===i.action&&(b.sessionStorage.setItem("_mpcehash",g),history.replaceState(i.desiredHash||"",e.title,j.pathname+j.search)))}catch(m){}var k,h;window.mixpanel=a;a._i=[];a.init=function(b,c,f){function e(b,a){var c=a.split(".");2==c.length&&(b=b[c[0]],a=c[1]);b[a]=function(){b.push([a].concat(Array.prototype.slice.call(arguments,
0)))}}var d=a;"undefined"!==typeof f?d=a[f]=[]:f="mixpanel";d.people=d.people||[];d.toString=function(b){var a="mixpanel";"mixpanel"!==f&&(a+="."+f);b||(a+=" (stub)");return a};d.people.toString=function(){return d.toString(1)+".people (stub)"};k="disable time_event track track_pageview track_links track_forms register register_once alias unregister identify name_tag set_config reset people.set people.set_once people.increment people.append people.union people.track_charge people.clear_charges people.delete_user".split(" ");
for(h=0;h<k.length;h++)e(d,k[h]);a._i.push([b,c,f])};a.__SV=1.2;b=e.createElement("script");b.type="text/javascript";b.async=!0;b.src="undefined"!==typeof MIXPANEL_CUSTOM_LIB_URL?MIXPANEL_CUSTOM_LIB_URL:"file:"===e.location.protocol&&"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js".match(/^\/\//)?"https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js":"//cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";c=e.getElementsByTagName("script")[0];c.parentNode.insertBefore(b,c)}})(document,window.mixpanel||[]);mixpanel.init("fd8c0d3725f827cc6f6b944689647e15");</script><!-- end Mixpanel -->';
		
		$css = "<style>
		@import url('".ADSURL."buttons.css');
		
          html, body {
            margin: 0;
            padding: 0;
            height: 100%;
          }
          html {
            background: url('".$vals['lia_bg_img_url']."') no-repeat ".$vals['lia_bg_img_pos']." center fixed;
            -webkit-background-size: cover;
            -moz-background-size: cover;
            -o-background-size: cover;
            background-size: cover;           
          }
          body.dark {
background: rgba(41,62,80,0.69);
background: -moz-linear-gradient(left, rgba(41,62,80,0.69) 0px, rgba(17,17,17,0.69) 0px, rgba(43,43,43,0.69) 0px, rgba(41,62,80,0.69) 0px, rgba(41,62,80,0.58) 400px, rgba(41,62,80,0.25) 600px, rgba(41,62,80,0) 100%);
background: -webkit-gradient(left top, right top, color-stop(0px, rgba(41,62,80,0.69)), color-stop(0px, rgba(17,17,17,0.69)), color-stop(0px, rgba(43,43,43,0.69)), color-stop(0px, rgba(41,62,80,0.69)), color-stop(250px, rgba(41,62,80,0.58)), color-stop(400px, rgba(41,62,80,0.25)), color-stop(100%, rgba(41,62,80,0)));
background: -webkit-linear-gradient(left, rgba(41,62,80,0.69) 0px, rgba(17,17,17,0.69) 0px, rgba(43,43,43,0.69) 0px, rgba(41,62,80,0.69) 0px, rgba(41,62,80,0.58) 400px, rgba(41,62,80,0.25) 600px, rgba(41,62,80,0) 100%);
background: -o-linear-gradient(left, rgba(41,62,80,0.69) 0%, rgba(17,17,17,0.69) 0%, rgba(43,43,43,0.69) 0%, rgba(41,62,80,0.69) 0%, rgba(41,62,80,0.58) 35%, rgba(41,62,80,0.25) 65%, rgba(41,62,80,0) 100%);
background: -ms-linear-gradient(left, rgba(41,62,80,0.69) 0%, rgba(17,17,17,0.69) 0%, rgba(43,43,43,0.69) 0%, rgba(41,62,80,0.69) 0%, rgba(41,62,80,0.58) 35%, rgba(41,62,80,0.25) 65%, rgba(41,62,80,0) 100%);
background: linear-gradient(to right, rgba(41,62,80,0.69) 0px, rgba(17,17,17,0.69) 0px, rgba(43,43,43,0.69) 0px, rgba(41,62,80,0.69) 0px, rgba(41,62,80,0.58) 400px, rgba(41,62,80,0.25) 600px, rgba(41,62,80,0) 100%);
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#293e50', endColorstr='#293e50', GradientType=1 );
          }
          body.light {
background: rgba(255,255,255,1);
background: -moz-linear-gradient(left, rgba(255,255,255,1) 0%, rgba(232,237,241,0.58) 59%, rgba(229,235,240,0.25) 66%, rgba(226,233,238,0) 74%, rgba(216,225,232,0) 100%);
background: -webkit-gradient(left top, right top, color-stop(0%, rgba(255,255,255,1)), color-stop(59%, rgba(232,237,241,0.58)), color-stop(66%, rgba(229,235,240,0.25)), color-stop(74%, rgba(226,233,238,0)), color-stop(100%, rgba(216,225,232,0)));
background: -webkit-linear-gradient(left, rgba(255,255,255,1) 0%, rgba(232,237,241,0.58) 59%, rgba(229,235,240,0.25) 66%, rgba(226,233,238,0) 74%, rgba(216,225,232,0) 100%);
background: -o-linear-gradient(left, rgba(255,255,255,1) 0%, rgba(232,237,241,0.58) 59%, rgba(229,235,240,0.25) 66%, rgba(226,233,238,0) 74%, rgba(216,225,232,0) 100%);
background: -ms-linear-gradient(left, rgba(255,255,255,1) 0%, rgba(232,237,241,0.58) 59%, rgba(229,235,240,0.25) 66%, rgba(226,233,238,0) 74%, rgba(216,225,232,0) 100%);
background: linear-gradient(to right, rgba(255,255,255,1) 0px, rgba(232,237,241,0.58) 400px, rgba(229,235,240,0.25) 530px, rgba(226,233,238,0) 650px, rgba(216,225,232,0) 100%);
filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#d8e1e8', GradientType=1 );
          }
          body.light .l1,
          body.light .l2,
          body.light .l3 {
            color: #293E50;
          }
          .ad-bg-image {
            height: 100vh;
            width: 100vw;
          }
          #maincontent {
            padding-top: 4.2em;
            padding-left: 3em;
            max-width: 600px;
            min-width: 500px;
          }
          .l2 {
            font-family: 'sofia-pro', sans-serif;
            font-size: 4em;
            line-height: 1em;
            font-weight: 400;
            color: #f2f2f2;
            margin-top: 10px;
            margin-bottom: 15px;
          }
          .l1, .l3 {
            font-family: 'fira-sans', sans-serif;
            font-size: 2em;
            line-height: .85em;
            color: #f2f2f2;
          }
          .button-bar {
            margin-top: 40px;
          }
          .top-spacer {
            display: block;
            height: 15px;
          }
		 
        </style>";
	
		$close_head = "</head>\n";
		$bodyclass = $vals['lia_bg_img_css'];
		if ( $bodyclass == 'none') {
			$bodyc = '<body>';
		} else {
			$bodyc = '<body class="'.$bodyclass.'">';
		}
	
		$body = $bodyc."\n";
		
		$bttxt = $vals['lia_button_text'];
		$btcls = $vals['lia_button_class'];
		$btlink = $vals['lia_button_link'];
		$btsz = $vals['lia_button_size'];
		
		$content_start = '<div id="maincontent"><div class="top-spacer"></div>';

		$content = apply_filters('the_content', $post->post_content); 

		$button = '<div class="button-bar"><a id="cta" class="cta-link btn '.$btcls.' '.$btsz.'" href="'.$btlink.'" target="_blank">'.$bttxt.'</a></div>';
		$closebody = '</div> <script src="https://use.typekit.net/wtr3ozr.js"></script><script src="https://use.typekit.net/nep8jxr.js"></script><script>try{Typekit.load({ async: true });}catch(e){}</script><script type="text/javascript">window.onload=attachButton;var cta = document.getElementById("cta");mixpanel.track("admin login ad: view");function attachButton() { cta.addEventListener("click",function(){mixpanel.track("admin login ad: click cta")})};</script></body>';
		$closehtml = '</html>';
	
		$output = $head.$title.$script.$css.$close_head.$body.$content_start.$content.$button.$closebody.$closehtml;
		
		$fp = fopen( ADSPATH . 'a' . $post->ID . '.html', 'w' );
		fwrite( $fp, $output );
		fclose( $fp );

		$this->output_json_ad_manifest();
	}
	
	/**
	  *  Output json file
	  * 
	  * @param 
	  * @return json file
	  *		
	  */
	private function output_json_ad_manifest() {
		$files = $this->return_path();
			// pull the saved filenames
		$manifest = array();
		foreach( $files as $filename ){
				// parse the post id from each filename
			$base = basename( $filename );
			$dotpos = strpos( $base, '.' );
			$adpos = strpos( $base, 'a' );
			$adid = substr( $base, $adpos+1, $dotpos-1 );
				// have post id, so now get postmeta values
			$include = get_post_meta( $adid, 'lia_ad_rotate', true );
			$url = ADSURL.$base;
			if ( $include  ) {
				$manifest[] = array( 'ID' => $adid, 'start' => get_post_meta( $adid, 'lia_startdate', true ), 'end' => get_post_meta( $adid, 'lia_enddate', true ), 'url' => $url );
			}
		}
			
		$fp = fopen( ADSPATH . 'manifest.json', 'w' );
		fwrite( $fp, json_encode( $manifest ) );
		fclose( $fp );
		
	}
	
	/**
	  *  gets list of html files
	  * 
	  * @param 
	  * @var $dir string
	  * @return array
	  *		
	  */
	private function return_path() {
		$dir = ADSPATH . '*.html';
		foreach ( glob( $dir ) as $filename ) {
		   $z[] = $filename;
		}
		return $z;
	}

} // end class