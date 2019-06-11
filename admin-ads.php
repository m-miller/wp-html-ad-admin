<?php
/*
Plugin Name: Admin Ad Manager
Plugin URI: 
Description: Admin for ad content HTML generator
Author: Martin Miller
Version: 2.0
Author URI:
*/

/*
 * TODO:   namespace
 *
 *
*/

define( 'LIA_PATH', plugin_dir_url( __FILE__ ) );

//require_once( dirname( __FILE__ ) . '/includes/settings.php' );

add_action( 'plugins_loaded', array( LogInAds::get_instance(), 'get_instance' ) );

//$postmeta_keys = array( 'lia_button_link', 'lia_button_text', 'lia_button_class', 'lia_button_size', 'lia_bg_img_url', 'lia_bg_img_pos', 'lia_startdate', 'lia_enddate' );
	
class LogInAds {
	

	const LIA_POST_TYPE	= "loginads";
	const LIA_TAXONOMY 	= "LIA_category";
	const LIA_TEXTDOMAIN = "LIA_textdomain";
	
	private static $instance;
	private $settings;
	
	
	private $postmeta_keys = array( 'lia_button_link', 'lia_button_text', 'lia_button_class', 'lia_button_size', 'lia_bg_img_url', 'lia_startdate', 'lia_enddate' );
	
	
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
		add_action( 'init', array( $this, 'lia_cpt' ) );
		
		add_action( 'admin_init', array( $this, 'admin_init' ) );	
		
			// remove gravity form add form button
		add_filter( 'gform_display_add_form_button', function(){ return false; } );	
		
			// remove soliloquy add slider button
		add_action( 'admin_head', array( $this, 'remove_soliloquy_slider_button' ), 98 );	
		
		add_action( 'wp_ajax_LIA_save_postmeta', array( $this, 'LIA_save_postmeta' ) );
		
		add_action( 'admin_enqueue_scripts', array( $this, 'LIA_admin_enqueue' ) );
		
		//$this->settings = new loginads_options();		
		
	} // __construct
	
	public function LIA_admin_enqueue($hook) {
		global $typenow;
		// only load the admin js on the edit-page admin screen
		    if ( ( self::LIA_POST_TYPE == $typenow ) AND 'edit.php' == $hook  ) {
				wp_enqueue_script( 'lia-admin', LIA_PATH. '/includes/js/lia-admin.js' );
		    } else {
				return;
		}
	} // LIA_admin_enqueue
	
	private function postmetakeys(){
		return $this->postmeta_keys;
	}
	
	public function LIA_cpt() {		
	
		add_filter ( 'manage_' . self::LIA_POST_TYPE . '_posts_columns' , array( $this, 'columns_add' ) );
		add_filter ( 'manage_' . self::LIA_POST_TYPE . '_posts_custom_column' , array( $this, 'columns_output' ), 10, 2 );
		
		add_action ( 'save_post_' . self::LIA_POST_TYPE, array( $this, 'save_post' ) );
	
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
		'taxonomies'						=>   	array(self::LIA_TAXONOMY),
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
						array_push( $tax_html, '<a href="' . get_term_link( $tax_cats->slug, self::LIA_TAXONOMY ) . '">' . $tax_cats->name . '</a>' );	
					}
					echo implode( $tax_html, ", " );
				} else {
					echo "None";
				}
			break;
			
			case "ID":
				echo $post_id;
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
				//var_dump($this->postmetakeys());
			break;
			
		}
	}
	
	
	// ajax function
	public function LIA_save_postmeta() {
		
		// ajax get post ID of checked box...
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


	public function save_post( $post_id ) {
		
		$postmeta_keys = $this->postmetakeys();
		$btnlink = filter_var( $_POST['bt-link'], FILTER_VALIDATE_URL );  // or FILTER_SANITIZE_URL
		$btntext = sanitize_text_field( $_POST['bt-text'] );
		$btstyle = $_POST['lia_button_class'];
		$btsz = $_POST['lia_button_size'];
		$bkgroundimg = filter_var( $_POST['imgsrc'], FILTER_VALIDATE_URL );
			// check if no img, then use fallback
		$bkimgpos = $_POST['lia_bg_img_pos'];

		$start = $_POST['stdate'];
		$end = $_POST['enddate'];
		
		$vars = array( $btnlink, $btntext, $btstyle, $btsz, $bkgroundimg, $bkimgpos, $start, $end );
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return; 	
		if ( get_post_type() != self::LIA_POST_TYPE  ) return;
		if ( !current_user_can ( 'edit_page', $post_id ) || !current_user_can ( 'edit_post', $post_id ) ) return $post_id;
			// else continue the save
			// white postmeta to db first, then export file
			
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
	     	wp_enqueue_script('get-dn-link');
			wp_enqueue_script('jquery-ui-datepicker');
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
		<select name = "lia_bg_img_pos" style="margin-left: 1em;">
			<?php
				$imgposopts = array( 'left' => 'Left', 'center' => 'Center', 'right' => 'Right');
				echo $this->get_selected( $imgposopts, $val['lia_bg_img_pos'] );
			?>
		</select>
		
		</div>
		
		<div id="dates" style="margin-bottom: 1em;">
			<label for="liastdate" style="width: 100px; display: inline-block;">Start Date: </label><input type="text" style="margin-right: 2em;" name = "stdate" id="liastdate" value = "<?php echo $val['lia_startdate']; ?>" />
			
			<label for="liaenddate" style="width: 75px; display: inline-block;">End Date: </label><input type="text" name = "enddate" id="liaenddate" value = "<?php echo $val['lia_enddate']; ?>" />
		</div>
		</form>
		<?php	
		//var_dump($val);	
		//print_r($this->return_path());
	}

/**
 * 
 */
	public function push_to_file() {
		global $post;
		$vals = $this->get_postmeta( $post->ID );
		//$postmeta_keys = array( 'lia_button_link', 'lia_button_text', 'lia_button_class', 'lia_button_size', 'lia_bg_img_url', 'lia_bg_img_pos', 'lia_startdate', 'lia_enddate' );
		$head = "<!DOCTYPE HTML>\n<html>\n<head>\n<meta charset='utf-8'>\n<meta http-equiv='x-ua-compatible' content='ie=edge'>\n<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n<meta http-equiv='Pragma' content='no-cache'>\n<meta http-equiv='Expires' content='-1'>\n<meta http-equiv='CACHE-CONTROL' content='NO-CACHE'>\n";
		$title = "<title>" . $post->post_title . "</title>\n";
	
		// file get contents, perhaps? or xml path
		$css = "<style>
		@import url('buttons.css');
		
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
          .ad-bg-image {
            height: 100vh;
            width: 100vw;
          }
          #maincontent {
            padding-top: 2em;
            padding-left: 3em;
            width: 50%;
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
	
		$closehead = "</head>\n";
	
		$body = "<body>\n";
		
		$bttxt = $vals['lia_button_text'];
		$btcls = $vals['lia_button_class'];
		$btlink = $vals['lia_button_link'];
		$btsz = $vals['lia_button_size'];
		
		// add the postmeta (btn, text, and links here) - do we need the editor for content? 
		$content_start = '<div id="maincontent"><div class="top-spacer"></div>';

		$content = do_shortcode($post->post_content);

		$button = '<div class="button-bar"><a class="btn '.$btcls.' '.$btsz.'" href="'.$btlink.'" target-"_blank">'.$bttxt.'</a></div>';
		$closebody = "</div></body>\n";
		$closehtml = '</html>';
	
		$output = $head.$title.$css.$closehead.$body.$content_start.$content.$button.$closebody.$closehtml;

		$e = file_put_contents( get_home_path() . 'ads/ad' . $post->ID . '.html', $output);
		
		// if file_exists ($path) ... 
		// update ad manifest if anything changed...
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
		// settings for what to save to the manifest in options
		// $postmeta_keys = array( 'lia_button_link', 'lia_button_text', 'lia_button_class', 'lia_button_size', 'lia_bg_img_url', 'lia_startdate', 'lia_enddate' );
		$baseurl = get_site_url() . '/ads/'; 
		$files = $this->return_path();
			// pull the saved filenames
		$manifest = array();
		foreach( $files as $filename ){
				// parse the post id from each filename
			$base = basename( $filename );
			$dotpos = strpos( $base, '.' );
			$adpos = strpos( $base, 'ad' );
			$adid = substr( $base, $adpos+2, $dotpos-2 );
				// have post id, so now get postmeta values
			$include = get_post_meta( $adid, 'lia_ad_rotate', true );
			$url = $baseurl.$base;
			if ( $include  ) {
				$manifest[] = array( 'ID' => $adid, 'start' => get_post_meta( $adid, 'lia_startdate', true ), 'end' => get_post_meta( $adid, 'lia_enddate', true ), 'url' => $url );
			}
		}
			
		$fp = fopen( get_home_path(). 'ads/' . 'admanifest.json', 'w' );
		fwrite( $fp, json_encode( $manifest ) );
		fclose( $fp );
		
	}
	
	/**
	  *  gets list of html files
	  * 
	  * @param 
	  * @return array
	  *
      *  example echo $this->get_selected( array( 'btn-large' => 'Large', 'btn-medium' => 'Medium', 'btn-small' => 'Small' ), $val['lia_button_size'] );		
	  */
	private function return_path() {
		$dir = get_home_path(). 'ads/*.html';
		foreach ( glob( $dir ) as $filename ) {
		   $z[] = $filename;
		}
		return $z;
	}
	

	

} // end class