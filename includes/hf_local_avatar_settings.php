<?php

class HF_Load_Avathar_Settings {

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct() {

        if (!empty($_GET['alert']) && $_GET['alert'] === 'show' && $_GET['page'] === 'hf-load-avatar') {

            function sample_admin_notice__success() {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings Saved!', 'hf_load_gravatar_local'); ?></p>
                </div>
                <?php
            }

            add_action('admin_notices', 'sample_admin_notice__success');
        }
        if (!empty($_GET['alert']) && $_GET['alert'] === 'delete' && $_GET['page'] === 'hf-load-avatar') {

            
            function sample_admin_notice__success() {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Records Deleted Sucessfully!', 'hf_load_gravatar_local'); ?></p>
                </div>
                <?php
            }
            global $wpdb;
            $query = "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_bb_pt_avatar_%'";
            $wpdb->query($query);
            add_action('admin_notices', 'sample_admin_notice__success');
        }
        if (!empty($_GET['alert']) && $_GET['alert'] === 'deletemeta' && $_GET['page'] === 'hf-load-avatar') {

            
            function sample_admin_notice__success() {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Records Deleted Sucessfully!', 'hf_load_gravatar_local'); ?></p>
                </div>
                <?php
            }
            global $wpdb;
            $query = "DELETE FROM $wpdb->usermeta WHERE meta_key = 'xa_local_avatar'";
            $wpdb->query($query);
            add_action('admin_notices', 'sample_admin_notice__success');
        }
        
        
        
        add_action('admin_menu', array($this, 'add_plugin_page'));
        // add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
        // This page will be under "Settings"
        add_options_page(
                'Settings Admin', 'Xa Load Avatar', 'manage_options', 'hf-load-avatar', array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
        include('market.php');
        $option_value = get_option('_hf_load_av_data');
        $option_value = !empty($option_value) ? $option_value : update_option('_hf_load_av_data', 'yes');
        
        $img_resolution_value = get_option('v_img_resolution');
        $img_resolutions = array(100,200,404,600,800,1024, 2048);
        
        // Set class property
        ?>
        <div class="wrap">
            <h1>Optimize Gravatar Avatar Settings</h1>
            <p>The following options used to configure the Gravatar Avatar plugin. </p>
            <form method="post" action="<?php echo admin_url('admin.php?page=hf_load_gravatar&action=settings') ?>" >
                <h3> Guest User Settings :</h3>
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="v_limit"><?php _e('Use Guest Meta', 'hf_load_gravatar_local'); ?></label>
                        </th>
                        <td>
                            <?php if ($option_value === 'no') { ?>
                                <input type="checkbox" id="hf_load_av_enable"  name="hf_load_av_enable" />Enable
                            <?php } else { ?>
                                <input type="checkbox" id="hf_load_av_enable" checked="true" name="hf_load_av_enable" />Enable
                            <?php } ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="v_delete"><?php _e('Delete Guest Meta', 'hf_load_gravatar_local'); ?></label>
                        </th>
                        <td> 
                            <a onClick="javascript: return confirm('Are you sure want to delete Guest Users Meta?');" href="<?php echo admin_url( '/options-general.php?page=hf-load-avatar&alert=delete'); ?>" class="button button-secondary"> Delete All </a></td>
                    </tr>
                    <tr>
                        <th>
                            <label for="v_delete"><?php _e('Delete All Registerd Meta', 'hf_load_gravatar_local'); ?></label>
                        </th>
                        <td> 
                            <a onClick="javascript: return confirm('Are you sure want to delete  Users Meta?');" href="<?php echo admin_url( '/options-general.php?page=hf-load-avatar&alert=deletemeta'); ?>" class="button button-secondary"> Delete All </a></td>
                    </tr>
                </table>
                <h3> Image Settings :</h3>
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="v_img_resolution"><?php _e('Default Image Resolution', 'hf_load_gravatar_local'); ?></label>
                        </th>
                        <td> 
                            <select id="v_img_resolution"  name="v_img_resolution">
                                <?php      
                                $default = ($img_resolution_value)? $img_resolution_value : 404;
                                foreach ($img_resolutions as $value) {
                                            echo '<option value="'.$value.'"'.  selected($default , $value).'>'.$value.'</option>';
                                        }
                                ?>
                            </select> 
                        </td>
                    </tr>
                </table>
                <?php
                // This prints out all hidden setting fields
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    public function delete_all_meta()
    {
        wp_die();
    }

}

if (is_admin())
    $my_settings_page = new HF_Load_Avathar_Settings();
