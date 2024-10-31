<?php

/*
  Plugin Name: Optimize Gravatar Avatar
  Plugin URI: http://www.xadapter.com/
  Description: Improve your page performance by loading avatar only a single time ( in reviews, comments,forums,topics, etc. ) from gravatar.com and store it locally.
  Author: PluginHive
  Author URI: https://www.pluginhive.com/
  Version: 1.4.6
  Text Domain: hf_load_gravatar_local
 */

  if (!defined('ABSPATH')) {
    return;
}

define("hf_load_gravatar_local", "hf_load_gravatar_local");

global $option, $wpdb, $post;
$option_value = get_option('_hf_load_av_data');
$option_value = !empty($option_value) ? $option_value : update_option('_hf_load_av_data', 'yes');
if (!class_exists('hf_load_gravatar_local')) :

    class hf_load_gravatar_local {

        /**
         * Constructor
         */
        public function __construct() {
            define('HF_load_gravatar_local_FILE', __FILE__);
            add_action('init', array($this, 'load_plugin_textdomain'));
            add_action('init', array($this, 'catch_save_settings'), 20);
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'hf_bbPress_Product_tab_plugin_link'));
            
            $this->img_resolution_value = get_option('v_img_resolution');
            if(!$this->img_resolution_value) $this->img_resolution_value = 404;

            $option_value_get = get_option('_hf_load_av_data');
            $option_value_set = !empty($option_value_get) ? $option_value_get : 'yes';

            if ($option_value_set === 'yes') {
                add_filter('get_avatar', array($this, 'cyd_get_avatar'), 10, 5);
            } else {
                add_filter('pre_get_avatar', array($this, 'get_my_avatar'), 10, 3);
            }
            include_once( 'includes/hf_local_avatar_settings.php' );
        }

        public function hf_bbPress_Product_tab_plugin_link($links) {
            $setting_link = admin_url('options-general.php?page=hf-load-avatar');
            $plugin_links = array(
                '<a href="' . $setting_link . '">' . __('Settings', 'hf_load_gravatar_local') . '</a>','<a href=" https://www.xadapter.com/category/product/optimize-gravatar-avatar-wordpress-plugin/" target="_blank">' . __('Documentation', 'hf_load_gravatar_local') . '</a>','<a href="https://www.xadapter.com/" target="_blank">' . __('Premium Plugins', 'hf_load_gravatar_local') . '</a>',
                );
            return array_merge($plugin_links, $links);
        }

        public function catch_save_settings() {
            if (!empty($_GET['action']) && !empty($_GET['page']) && $_GET['page'] == 'hf_load_gravatar') {
                switch ($_GET['action']) {
                    case "settings" :
                    include_once( 'includes/hf_local_avatat_save_settings.php' );
                    HF_Load_AVA_Settings::save_settings();
                    break;
                }
            }
        }

        public function cyd_get_avatar($avatar, $id_or_email, $size, $default, $alt) {



            if (is_numeric($id_or_email))
                $user_id = (int) $id_or_email;
            elseif (is_string($id_or_email) && ( $user = get_user_by('email', $id_or_email) )) {
                $user_id = $user->ID;
            } else if (is_string($id_or_email)) {
                $user_id = 0;

                if ($user = get_user_by('email', $id_or_email)) {
                    $user_id = $user->ID;
                } else {
                    $class_author = is_author($user_id) ? ' current-author' : '';
                    $local_avatar = get_post_meta('1', '_bb_pt_avatar_' . $id_or_email, true);

                    if (empty($local_avatar)) {
                        $is_valid_gravatar = $this->validate_gravatar($id_or_email);
                        if ($is_valid_gravatar) {
                            $avatar_url = get_avatar_url($id_or_email, array('size' => $this->img_resolution_value));
                            $uploaded = $this->fetch_remote_file($avatar_url);
                            $pt_url_data = $uploaded['url'];
                            update_post_meta('1', '_bb_pt_avatar_' . $id_or_email, $pt_url_data);
                            return "<img alt='" . esc_attr($alt) . "' src='" . esc_url($pt_url_data) . "' class='hf-bb-pro-tab-img-circle avatar avatar-{$size}{$class_author} photo' height='{$size}' width='{$size}' />";
                        } else {
                            $pt_url_data = 'images/default.png';
                            update_post_meta('1', '_bb_pt_avatar_' . $id_or_email, $pt_url_data);
                            return "<img alt='" . esc_attr($alt) . "' src='" . esc_url(plugins_url('images/default.png', __FILE__)) . "' class='hf-bb-pro-tab-img-circle avatar avatar-{$size}{$class_author} photo' height='{$size}' width='{$size}' />";
                        }
                    } else {
                        if (strpos($local_avatar, 'default.png') !== false) {
                            $img_url = plugins_url('images/default.png', __FILE__);
                        } else {
                            $img_url = $local_avatar;
                        }
                        $remote_av = "<img alt='" . esc_attr($alt) . "' src='" . esc_url($img_url) . "' class='hf-bb-pro-tab-img-circle avatar avatar-{$size}{$class_author} photo' height='{$size}' width='{$size}' />";
                        return $remote_av;
                    }
                }
            } else if (is_object($id_or_email) && !empty($id_or_email->user_id))
            $user_id = (int) $id_or_email->user_id;
            elseif (!empty($id_or_email->comment_author_email) && ( $user = get_user_by('email', $id_or_email->comment_author_email)))
                $user_id = $user->ID;

            if (empty($user_id)) {
                return $avatar;
            }
            if (isset($_GET['deletemeta'])) {
                echo 'deleting user meta';
                delete_user_meta($user_id, 'xa_local_avatar');
            }
            $alt = get_the_author_meta('display_name', $user_id);
            $author_class = is_author($user_id) ? ' current-author' : '';
            $arg['size'] = $size;
            // fetch local avatar from meta and make sure it's properly ste
            $local_avatar = get_user_meta($user_id, 'xa_local_avatar', true);
            if (empty($local_avatar)) {

                $user_info = get_userdata($user_id);
                $user_email = $user_info->user_email;
                $is_valid_gravatar = $this->validate_gravatar($user_email);

                if ($is_valid_gravatar) {
                    // Store and Display the gravatar
                    $avatar_url = get_avatar_url($user_id, array('size' => $this->img_resolution_value));
                    //ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
                    $uploaded = $this->fetch_remote_file($avatar_url);
                    $xa_url = $uploaded['url'];

                    update_user_meta($user_id, 'xa_local_avatar', $xa_url);
                    return "<img alt='" . esc_attr($alt) . "' src='" . esc_url($xa_url) . "' class='hf-bb-pro-tab-img-circle avatar avatar-{$arg['size']}{$author_class} photo' height='{$arg['size']}' width='{$arg['size']}' />";
                } else {
                    //Display and store local image URL
                    $xa_url = 'images/default.png';
                    update_user_meta($user_id, 'xa_local_avatar', $xa_url);

                    return "<img alt='" . esc_attr($alt) . "' src='" . esc_url(plugins_url('images/default.png', __FILE__)) . "' class='hf-bb-pro-tab-img-circle avatar avatar-{$arg['size']}{$author_class} photo' height='{$arg['size']}' width='{$arg['size']}' />";
                }
            } else {
                if (strpos($local_avatar, 'default.png') !== false) {
                    $img_url = plugins_url('images/default.png', __FILE__);
                } else {
                    $img_url = $local_avatar;
                }

                $original_avatar = "<img alt='" . esc_attr($alt) . "' src='" . esc_url($img_url) . "' class='hf-bb-pro-tab-img-circle avatar avatar-{$arg['size']}{$author_class} photo' height='{$arg['size']}' width='{$arg['size']}' />";
                return $original_avatar;
            }
        }

        public function get_my_avatar($avatar, $id_or_email, $arg) {

            if (is_numeric($id_or_email))
                $user_id = (int) $id_or_email;
            elseif (is_string($id_or_email) && ( $user = get_user_by('email', $id_or_email) ))
                $user_id = $user->ID;
            elseif (is_object($id_or_email) && !empty($id_or_email->user_id))
                $user_id = (int) $id_or_email->user_id;
            elseif (!empty($id_or_email->comment_author_email) && ( $user = get_user_by('email', $id_or_email->comment_author_email)))
                $user_id = $user->ID;
            if (empty($user_id))
                return $avatar;

            if (isset($_GET['deletemeta'])) {
                echo 'deleting user meta';
                delete_user_meta($user_id, 'xa_local_avatar');
            }

            $alt = get_the_author_meta('display_name', $user_id);
            $author_class = is_author($user_id) ? ' current-author' : '';

            // fetch local avatar from meta and make sure it's properly ste
            $local_avatar = get_user_meta($user_id, 'xa_local_avatar', true);
            if (empty($local_avatar)) {

                $user_info = get_userdata($user_id);
                $user_email = $user_info->user_email;
                $is_valid_gravatar = $this->validate_gravatar($user_email);

                if ($is_valid_gravatar) {
                    // Store and Display the gravatar
                    $avatar_url = get_avatar_url($user_id , array('size' => $this->img_resolution_value));

                    $uploaded = $this->fetch_remote_file($avatar_url);
                    $xa_url = $uploaded['url'];

                    update_user_meta($user_id, 'xa_local_avatar', $xa_url);
                    return "<img alt='" . esc_attr($alt) . "' src='" . esc_url($xa_url) . "' class='avatar avatar-{$arg['size']}{$author_class} photo' height='{$arg['size']}' width='{$arg['size']}' />";
                } else {
                    //Display and store local image URL
                    $xa_url = 'images/default.png';
                    update_user_meta($user_id, 'xa_local_avatar', $xa_url);

                    return "<img alt='" . esc_attr($alt) . "' src='" . esc_url(plugins_url('images/default.png', __FILE__)) . "' class='avatar avatar-{$arg['size']}{$author_class} photo' height='{$arg['size']}' width='{$arg['size']}' />";
                }
            } else {
                if (strpos($local_avatar, 'default.png') !== false) {
                    $img_url = plugins_url('images/default.png', __FILE__);
                } else {
                    $img_url = $local_avatar;
                }

                $original_avatar = "<img alt='" . esc_attr($alt) . "' src='" . esc_url($img_url) . "' class='avatar avatar-{$arg['size']}{$author_class} photo' height='{$arg['size']}' width='{$arg['size']}' />";
                return $original_avatar;
            }
        }

        /**
         * Attempt to download a remote file attachment ( from Gravatar.com in this context )
         */
        public function fetch_remote_file($url) {

            // extract the file name and extension from the url
            $file_name = basename(current(explode('?', $url)));
            $wp_filetype = wp_check_filetype($file_name, null);
            $parsed_url = @parse_url($url);

            // Check parsed URL
            if (!$parsed_url || !is_array($parsed_url))
                return new WP_Error('import_file_error', 'Invalid URL');

            // Ensure url is valid
            $url = str_replace(" ", '%20', $url);
            // Get the file
            $response = wp_remote_get($url, array(
                'timeout' => 10
                ));

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200)
                return new WP_Error('import_file_error', 'Error getting remote image');

            // Ensure we have a file name and type
            if (!$wp_filetype['type']) {

                $headers = wp_remote_retrieve_headers($response);

                if (isset($headers['content-disposition']) && strstr($headers['content-disposition'], 'filename=')) {

                    $disposition = @end(explode('filename=', $headers['content-disposition']));
                    $disposition = sanitize_file_name($disposition);
                    $file_name = $disposition;
                } elseif (isset($headers['content-type']) && strstr($headers['content-type'], 'image/')) {

                    $file_name = 'image.' . str_replace('image/', '', $headers['content-type']);
                }
                unset($headers);
            }
            // Upload the file
            $upload = wp_upload_bits($file_name, '', wp_remote_retrieve_body($response));

            if(is_ssl())
            {

                $upload['url'] = str_replace( 'http://', 'https://', $upload['url'] );
            }

            if ($upload['error'])
                return new WP_Error('upload_dir_error', $upload['error']);

            // Get filesize
            $filesize = filesize($upload['file']);

            if (0 == $filesize) {
                @unlink($upload['file']);
                unset($upload);
                return new WP_Error('import_file_error', __('Zero size file downloaded', 'wf_csv_import_export'));
            }

            unset($response);

            return $upload;
        }

        public function validate_gravatar($email) {
            // Craft a potential url and test its headers
            $hash = md5(strtolower(trim($email)));

            if(is_ssl())
            {
                $uri ='https://www.gravatar.com/avatar/' . $hash . '?s='.$this->img_resolution_value;
            }else
            {
                $uri = 'http://www.gravatar.com/avatar/' . $hash . '?s='.$this->img_resolution_value;
            }

            $headers = @get_headers($uri);
            if (!preg_match("|200|", $headers[0])) {
                $has_valid_avatar = FALSE;
            } else {
                $has_valid_avatar = TRUE;
            }
            return $has_valid_avatar;
        }

        /**
         * Handle localisation
         */
        public function load_plugin_textdomain() {
            load_plugin_textdomain('hf_load_gravatar_local', false, dirname(plugin_basename(__FILE__)) . '/lang/');
        }

    }

    endif;

    new hf_load_gravatar_local();