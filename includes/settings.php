<?php

/*  class
 *  
 *
 *
 *
*/

// TODO:  
// list ads in dir, choose which to rotate (or dates)

class loginads_options {
	
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'loginads_options_menu' ) );
		add_action( 'admin_notices', array( $this, 'loginads_update_notice' ) );
		// load css for this page 
	}	
									
	public function loginads_options_menu() {
		add_submenu_page( 'edit.php?post_type=loginads', 'Settings', 'Settings', 'edit_pages', 'loginads-settings', array( $this, 'loginads_settings' ) );
	}
	 
	public function loginads_update_notice() {
		$screen = get_current_screen();
		if ( ( $screen->id == 'loginads_page_loginads-settings')  && isset ( $_POST['submit'] ) ) {
    		echo '<div class="updated">';
        	echo '<p>Settings updated!</p>';
    		echo '</div>';
		}
	}
	
	public function loginads_settings() {

		?>
		<div class="wrap" style="width: 75%;">
        	<div id="icon-tools" class="icon32"></div>
        	<h2>Options</h2><br />
            
			<form method="post" action="">
			<div style="border: 1px solid #ccc; padding: 15px; margin: 20px; width: 40%; background: white;">
				<label for="numukdn"></label>
				<select id="numukdn" name="numukdn" style="float: right; margin: -5px 0 0 5px; padding: 5px; background: whitesmoke;">
				<?php
				
				?>
				<select>
			</div>	
			<div style="border: 1px solid #ccc; padding: 15px; margin: 20px; width: 40%; background: white;">
				<label for="useform"></label>
				<select id="useform" name="useform" style="float: right; margin: -5px 0 0 5px; padding: 5px; background: whitesmoke;">
				<?php 
				
				?>
				<select>
				</div>
			
	<?php  submit_button();	 ?>
		</form>
		</div><!--wrap-->
        <?php
			if ( isset ( $_POST['submit'] ) ) {
				//update_option( 'dnperpage', $_POST['numukdn'] ); 
				//update_option( 'dneloquaformid', $_POST['useform'] );	
			}	
	}  // end function
} //class 
?>