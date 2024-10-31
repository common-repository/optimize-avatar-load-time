<?php
if (!defined('ABSPATH')) {
    return;
}
Class HF_Load_AVA_Settings {

	/**
	 * Product Exporter Tool
	 */
	public static function save_settings( ) {
	
           
		   update_option( '_hf_load_av_data', !empty($_POST['hf_load_av_enable'])? 'yes' : 'no' );
                   update_option( 'v_img_resolution', $_POST['v_img_resolution']);
                   wp_redirect( admin_url( '/options-general.php?page=hf-load-avatar&alert=show')  );
                   exit();
                                
	}
        
}
new HF_Load_AVA_Settings();