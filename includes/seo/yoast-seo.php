<?php 
namespace aw2\seo;

add_filter( 'wpseo_sitemap_index', '\aw2\seo\yoast_seo::add_apps_to_sitemap' );

//To remove all the app pages and app post from the sitemap_index
add_filter( 'wpseo_sitemap_exclude_post_type', '\aw2\seo\yoast_seo::sitemap_exclude_post_type', 10, 2 );
add_action('init','\aw2\seo\yoast_seo::wp_init',10);

class yoast_seo{
		/***
	 * This function will remove all the sitemap.xml files,
	 * of posts and pages of awesome app
	 */
	static function sitemap_exclude_post_type( $value, $post_type ) {
		$registered_apps=&\aw2_library::get_array_ref('apps');
		$remove_cpt_from_sitemap=array();
		foreach($registered_apps as $key=>$app){
			if(isset($app['collection']['pages']))
				array_push($remove_cpt_from_sitemap,$app['collection']['pages']['post_type']);
			
			if(isset($app['collection']['posts']))
				array_push($remove_cpt_from_sitemap,$app['collection']['posts']['post_type']);
		}
		if( in_array( $post_type, $remove_cpt_from_sitemap ) ) return true;
	}
	
	static function  add_apps_to_sitemap(){
		global $wpseo_sitemaps;
		global $wpdb;
		
		$sql  = $wpdb->prepare(" SELECT MAX(p.post_modified_gmt) AS lastmod
						FROM	$wpdb->posts AS p
						WHERE post_status IN ('publish') AND post_type = %s ", 'aw2_app' );
		$mod = $wpdb->get_var( $sql )."+00:00";
				
		//$date = $wpseo_sitemaps->get_last_modified('aw2_app');
		//if(!class_exists(WPSEO_Date_Helper)){
		///	$timezone =  new \WPSEO_Sitemap_Timezone();
		//	$mod = $timezone->format_date($mod );
		//}
		//else{
			$date = new \WPSEO_Date_Helper();
			$mod = $date->format($mod );
		//}
		
		$registered_apps=&\aw2_library::get_array_ref('apps');
		
		
		$smp ='';
		foreach($registered_apps as $key=>$app){

			if(!\app_seo::enable_sitemap($app)) continue;
			
			$smp .= '<sitemap>' . "\n";
			$smp .= '<loc>' . site_url() .'/'.$app['slug'].'-app-sitemap.xml</loc>' . "\n";
			$smp .= '<lastmod>' . htmlspecialchars( $mod ) . '</lastmod>' . "\n";
			$smp .= '</sitemap>' . "\n";
		}
		//\util::var_dump($smp);
		return $smp;
		
	}
	
	static function wp_init(){
			
		$registered_apps=&\aw2_library::get_array_ref('apps');

		foreach($registered_apps as $key=>$app){
			if(!\app_seo::enable_sitemap($app)) continue;
			\aw2\seo\yoast_seo::setup_seo_links($app['slug']);	
		}
	}
	
	static function setup_seo_links($slug){
		add_action( "wpseo_do_sitemap_".$slug."-app",  function() use ($slug){
														\aw2\seo\yoast_seo::awesome_apps_pages_sitemap($slug);
												});
	}
	
	static function awesome_apps_pages_sitemap($slug){
		global $wpseo_sitemaps;
		global $wpdb;
		
		$registered_apps=&\aw2_library::get_array_ref('apps');

		$skip_slugs=array('single','archive','header','footer');
		
		$output = '';
		$app=$registered_apps[$slug];
		
		//foreach($registered_apps as $key => $app){
		if(!\app_seo::enable_sitemap($app)) {
            $wpseo_sitemaps->bad_sitemap = true;
			return;
		}	
			
		if(isset($app['collection']['pages']['post_type'])){
			$args = array(
				'posts_per_page'   => 500,
				'orderby'          => 'post_date',
				'order'            => 'DESC',
				'post_type'        => $app['collection']['pages']['post_type'],
				'post_status'      => 'publish',
				'meta_query'  => array(
					'relation' => 'OR',
				   array(
				   'key'      => '_yoast_wpseo_meta-robots-noindex',
				   'compare' => 'NOT EXISTS'
				   )
				   ,array(
				   'key'      => '_yoast_wpseo_meta-robots-noindex',
				   'value'      => '2'
				   )
			   ),
				'suppress_filters' => true
			);
			
			$app_pages = new \WP_Query( $args );
			
			
			if( $app_pages->have_posts() ){
				$chf = 'weekly';
				$pri = 1.0;
				foreach ( $app_pages->posts as $p ) {
					if(in_array($p->post_name,$skip_slugs)){
						continue;
					}
					$slug= $p->post_name.'/';
					if($slug=='home/')
						$slug='';
					
					$url = array();
					if ( isset( $p->post_modified_gmt ) && $p->post_modified_gmt != '0000-00-00 00:00:00' && $p->post_modified_gmt > $p->post_date_gmt ) {
						$url['mod'] = $p->post_modified_gmt;
					} else {
						if ( '0000-00-00 00:00:00' != $p->post_date_gmt ) {
							$url['mod'] = $p->post_date_gmt;
						} else {
							$url['mod'] = $p->post_date;
						}
					}
					$url['loc'] = $app['path'].'/'.$slug;
					$url['chf'] = $chf;
					$url['pri'] = $pri;
					$output .= $wpseo_sitemaps->renderer->sitemap_url( $url );
				}
			}
		}
			
		
		if(isset($app['collection']['posts']['post_type'])){
			$args = array(
				'posts_per_page'   => 500,
				'orderby'          => 'post_date',
				'order'            => 'DESC',
				'post_type'        => $app['collection']['posts']['post_type'],
				'post_status'      => 'publish',
				'meta_query'  => array(
					'relation' => 'OR',
				   array(
				   'key'      => '_yoast_wpseo_meta-robots-noindex',
				   'compare' => 'NOT EXISTS'
				   )
				   ,array(
				   'key'      => '_yoast_wpseo_meta-robots-noindex',
				   'value'      => '2'
				   )
			   ),
				'suppress_filters' => true
			);
			
			$app_posts = new \WP_Query( $args );
			
			
			if( $app_posts->have_posts() ){
				$chf = 'weekly';
				$pri = 1.0;
				foreach ( $app_posts->posts as $p ) {
							
					$url = array();
					if ( isset( $p->post_modified_gmt ) && $p->post_modified_gmt != '0000-00-00 00:00:00' && $p->post_modified_gmt > $p->post_date_gmt ) {
						$url['mod'] = $p->post_modified_gmt;
					} else {
						if ( '0000-00-00 00:00:00' != $p->post_date_gmt ) {
							$url['mod'] = $p->post_date_gmt;
						} else {
							$url['mod'] = $p->post_date;
						}
					}
					$url['loc'] = site_url().'/'.$app['slug'].'/'.$p->post_name.'/';
					$url['chf'] = $chf;
					$url['pri'] = $pri;
					$output .= $wpseo_sitemaps->renderer->sitemap_url( $url );
				}
			}
		}
			
			
		$arr=\aw2_library::get_module($app['collection']['config'],'settings');
		$default_taxonomy = \aw2_library::get_post_meta($arr['id'],'default_taxonomy');
		
			
		if(!empty($default_taxonomy)){
			$sql  = $wpdb->prepare(" SELECT MAX(p.post_modified_gmt) AS lastmod
					FROM	$wpdb->posts AS p
					WHERE post_status IN ('publish') AND post_type = %s ", $app['collection']['posts']['post_type'] );
			$mod = $wpdb->get_var( $sql );

			$terms = get_terms( array(
						'taxonomy' => $default_taxonomy,
						'hide_empty' => false,
					) );
			if( ! empty( $terms ) && ! is_wp_error( $terms )  ){
				$chf = 'weekly';
				$pri = 1.0;
				foreach ( $terms as $term  ) {

					$url = array();
					$url['loc'] = site_url().'/'.$app['slug'].'/'.$term->slug.'/';
					$url['pri'] = $pri;
					$url['mod'] = $mod;
					$url['chf'] = $chf;
					$output .= $wpseo_sitemaps->renderer->sitemap_url( $url );
					
				}
			}
			
		} 
			
	
		
		
		if(\aw2_library::get_module($app['collection']['config'],'custom-sitemap',true))
			$output .= \aw2_library::module_run($app['collection']['config'],'custom-sitemap');
		
		//Build the full sitemap
        $sitemap = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
        $sitemap .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" ';
        $sitemap .= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $sitemap .= $output . '</urlset>';
        //echo $sitemap;
        $wpseo_sitemaps->set_sitemap($sitemap);
	}
}