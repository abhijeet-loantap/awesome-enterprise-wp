<?php
if (!defined('ABSPATH'))
{
	exit;
}

class Monoframe
{

	public static function load()
	{
		add_filter('acf/settings/remove_wp_meta_box', '__return_false', 20);
				 
		$plugin_path=plugin_dir_path( __DIR__ );
	
		require_once( $plugin_path . '/libraries/editor/aw-code-editor.php' );
		require_once( $plugin_path . '/libraries/editor/devcap.php' );
		require(  $plugin_path . '/libraries/menu-walkers/navwalkers.php' ); 

		require_once( $plugin_path . '/libraries/metaboxes.php' );
		require_once( $plugin_path . '/libraries/acf-blocks.php' );
		require_once( $plugin_path . '/libraries/export-apps/import-export-apps.php' ); 
		
	}
		
		// use the action to create a place for your meta box
	public function add_before_editor($post) {
	  global $post;
	  do_meta_boxes(get_current_screen(), 'monoframe_pre_editor', $post);
	}
	
	static function is_awesome_post_type($post){
		$blocks = self::get_awesome_post_type();			
		return in_array($post->post_type,$blocks);
	}

	static function get_awesome_post_type(){
		$default_post_types=array( 
						'aw2_core',
						'awesome_core'
					);
		$app_post_types=array();	
		$registered_apps=&aw2_library::get_array_ref('apps');
		foreach ($registered_apps as $app){
			
			foreach($app['collection'] as $collection_name => $collection){
				if($collection_name == 'posts')
					continue;
				if(isset($collection['post_type']))
					$app_post_types[]=$collection['post_type'];
			}
		}
		
		$app_post_types = array_merge($default_post_types, $app_post_types);
		
		
		$service_post_type= array();
		$handlers=&aw2_library::get_array_ref('handlers');
		
		foreach($handlers as $key => $handler){
			if(!isset($handler['post_type']))
				continue;
			
			if(isset($handler['service']) && strtolower($handler['service']) === 'yes'){
				$service_post_type[] =  $handler['post_type'];
			} 
			elseif(isset($handler['@service']) && $handler['@service'] === true){
				$service_post_type[] =  $handler['post_type'];
			}	
		}
				
		$app_post_types = array_merge($app_post_types, $service_post_type);
		unset($service_post_type);
		
		$additional_slugs= aw2_library::get('settings.opt-editor-settings');
		if(!empty($additional_slugs)){
			$additional_slugs= explode(',',$additional_slugs);
			$app_post_types = array_merge($app_post_types, $additional_slugs);
		}
		
		return apply_filters('monoframe-awesome-post-types',$app_post_types);
		
	}
   
   static function setup_constants(){
		global $table_prefix;
		
		if(current_user_can('develop_for_awesomeui'))
				define('DEVELOP_FOR_AWESOMEUI', true);
		else	
				define('DEVELOP_FOR_AWESOMEUI', false);
		

		//standard setup for cache
		aw2_library::setup_env_cache('env_cache'.$table_prefix.DB_NAME); 		
	   
   }
   
   /**
   * Add a nice red to the admin bar when we're in development mode
   */
  function mono_dev_colorize() { 
	global $monomyth_options;
	$MM_PRODUCTION = false;
	if(isset($monomyth_options['dev_mode']))
		$MM_PRODUCTION = $monomyth_options['dev_mode'];
    if(!$MM_PRODUCTION) {
    ?>
    <style>
      
      <?php if ( is_admin_bar_showing() ) : ?>
        html { 
          padding-top: 5px; 
        }
      <?php endif; ?>
      #wpadminbar {
        border-top: 5px solid #d84315;
        -moz-box-sizing: content-box !important;
        box-sizing: content-box !important;
      }
      #wp-admin-bar-site-name > a {
        background-color: #d84315;
        color: #f1f1f1;
      }
    </style>
    <?php }
  }
}

monoframe::load();
$monoframe = new monoframe();
add_action('edit_form_after_title',array($monoframe,'add_before_editor'));
add_filter( 'admin_head', array($monoframe,'mono_dev_colorize' ));
add_filter( 'wp_head', array($monoframe,'mono_dev_colorize' ));


add_filter('upload_mimes', 'monoframe_upload_mimes');

function monoframe_upload_mimes ( $existing_mimes=array() ) {

	// add the file extension to the array

	$existing_mimes['svg'] = 'mime/type';

    // call the modified list of extensions
	return $existing_mimes;

}

function custom_taxonomy_tree_walker( $taxonomy, $parent = 0,$level = 1 ) {
    $terms = get_terms( $taxonomy, array( 'parent' => $parent, 'hide_empty' => false ) );
    if( count($terms) > 0 ) {
        $output = '';
        foreach ($terms as $term) {
            // function calls itself to display child elements, if any
            $output .= '<option value="'.$term->slug.'"> ' .str_repeat("-", $level).' '. $term->name . '</option>'. custom_taxonomy_tree_walker($taxonomy, $term->term_id,$level+1);
        }
         
        return $output;
    }
    return false;
}

add_filter( 'cptui_attach_taxonomies_to_post_type', 'cptui_all_objects', 10, 1 );
add_filter( 'cptui_attach_post_types_to_taxonomy', 'cptui_all_objects', 10, 1 );
function cptui_all_objects( $args) {
	unset($args['public']);
	return $args;
}

add_filter('wp_nav_menu', 'monoframe_do_menu_shortcodes'); 	
function monoframe_do_menu_shortcodes( $menu ){ 
        return aw2_library::parse_shortcode( $menu ); 
}