<?php
/**
 * Search exclude from all posts.
 *
 * @wordpress-plugin
 * Plugin Name: Exclude from search
 * Plugin URI:  https://github.com/protappro/
 * Description: This plugin helps to exclude one or more posts from Wordpress Search.
 * Version:     1.0.0.0
 * Author:      Protap Mondal
 * Author URI:  https://github.com/protappro/
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class PMExcludeSearch
{

    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'exclude_search_meta_box_add']);
        add_action('save_post', [$this, 'exclude_search_save_meta_box']);
        add_action('pre_get_posts',[$this, 'exclude_posts']);
        add_filter('manage_posts_columns', [$this, 'add_post_column']);
    	add_filter('manage_pages_columns', [$this, 'add_page_column']);
    	add_action('manage_posts_custom_column', [$this, 'exclude_posts_column_content'], 10, 2);
    	add_action('manage_pages_custom_column', [$this, 'exclude_pages_column_content'], 10, 2);
    	add_action('admin_menu', [$this, 'exclude_search_custom_submenu_page']);
    }

    public function exclude_posts($query)
    {
        if(!is_admin() && $query->is_main_query())
        {
            if($query->is_search())
            {
                
                $excluded_posts = get_option('pm_exclude_search');                
                $excluded_posts = empty($excluded_posts) ? [] : $excluded_posts;
                $post_ids = array_map(function($val){ return str_replace("-1", "", $val); }, $excluded_posts);  
                $query->set('post__not_in',$post_ids);
            }
        }

        $exclude =
            (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX))
            && $query->is_search;

        $exclude = apply_filters('searchexclude_filter_search', $exclude, $query);

        $excluded_posts = get_option('pm_exclude_search');
                $excluded_posts = empty($excluded_posts) ? [] : $excluded_posts;
                $post_ids = array_map(function($val){ return str_replace("-1", "", $val); }, $excluded_posts);

        if ($exclude) {
            $query->set('post__not_in', array_merge(array(), $post_ids));
        }

        return $query;
   
    }

    
    function exclude_search_meta_box_add()
    {
        //$page_post_array = array('page', 'post');
        /*
        add_meta_box('exclude-search-meta-box-id', 'Exclude Search', [$this, 'exclude_search_meta_box_callback'], null, 'side', 'default', array(
            '__block_editor_compatible_meta_box' => false,
        ));
		*/
		add_meta_box('exclude-search-meta-box-id', 'Exclude Search', [$this, 'exclude_search_meta_box_callback'], null, 'side', 'default');
    }

    function exclude_search_meta_box_callback($post)
    {
        wp_nonce_field('exclude_search_meta_box_nonce', 'exclude_search_nonce');
        $get_exclude        = get_option('pm_exclude_search');
        $get_option_exclude = !empty($get_exclude) ? $get_exclude : [];
        $get_exclude_status = false;
        $index = array_search($post->ID . "-1", $get_option_exclude);
        echo '<div class="form-field">
                <label for="exclude_search">
                <input type="checkbox" name="pm_exclude_search" value="1"';
        if ($index !== false ) {echo " checked ";}

        echo '/>Exclude from Search Results</label></div>';
    }

    function exclude_search_save_meta_box($post_id)
    {

        if (!isset($_POST['exclude_search_nonce']) || !wp_verify_nonce($_POST['exclude_search_nonce'], 'exclude_search_meta_box_nonce')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $exclude       = isset($_POST['pm_exclude_search']) ? $_POST['pm_exclude_search'] : 0;

        $option_name           = 'pm_exclude_search';
        $existing_option_value = get_option($option_name);
        $existing_option_value = !empty($existing_option_value) ? $existing_option_value : [];
        $index = array_search($post_id . "-1", $existing_option_value);
        if ($exclude == 1 && $index === false) {
            array_push($existing_option_value, $post_id . "-" . $exclude);
            update_option($option_name, $existing_option_value);            
        } else {
            if ($exclude == 0 && $index !== false) {
                unset($existing_option_value[$index]);
                $existing_option_value = array_values($existing_option_value);
                update_option($option_name, $existing_option_value);            
            }
        }
    }

    function add_post_column($defaults)
    {
        $defaults['exclude_search'] = 'Exclude Search';
        return $defaults;
    }
    

    function add_page_column($defaults)
    {
        $defaults['exclude_search'] = 'Exclude Search';
        return $defaults;
    }    

    function exclude_posts_column_content($column_name, $post_ID)
    {
        //echo $post_ID;
        if ($column_name == 'exclude_search') {
            $get_exclude        = get_option('pm_exclude_search');
            $get_exclude_status = false;
            if (!empty($get_exclude)) {
                foreach ($get_exclude as $excludekey => $excludevalue) {
                    $get_post_value = $excludevalue;
                    $post_value     = explode("-", $get_post_value);
                    if ($post_value[0] == $post_ID) {
                        $get_exclude_status = true;
                    }
                }
            }
            if ($get_exclude_status == true) {
                echo "<div id='search-exclude' data-search_exclude='' title='Hidden from search results'>Hidden</div>";
            } else {
                echo "<div id='search-exclude' data-search_exclude='' title='Visible from search results'>Visible</div>";
            }
        }
    }
    
    function exclude_pages_column_content($column_name, $post_ID)
    {

        if ($column_name == 'exclude_search') {
            $get_exclude        = get_option('pm_exclude_search');
            $get_exclude_status = false;
            if (!empty($get_exclude)) {
                foreach ($get_exclude as $excludekey => $excludevalue) {
                    $get_post_value = $excludevalue;
                    $post_value     = explode("-", $get_post_value);
                    if ($post_value[0] == $post_ID) {
                        $get_exclude_status = true;
                    }
                }
            }
            if ($get_exclude_status == true) {
                echo "<div id='search-exclude' data-search_exclude='' title='Hidden from search results'>Hidden</div>";
            } else {
                echo "<div id='search-exclude' data-search_exclude='' title='Visible from search results'>Visible</div>";
            }
        }
    }


    function exclude_search_custom_submenu_page()
    {
        add_submenu_page(
            'options-general.php',
            'Exclude from search',
            'Exclude from search',
            'manage_options',
            'exclude_search',
            'exclude_search_page_callback');
    }
}

$unused = new PMExcludeSearch();