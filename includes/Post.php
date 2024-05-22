<?php
namespace yelbaev\wpmd;

class Post extends Domain {

  public const CONFIG_FIELD = 'wpmd-posts';

  public $config;

  public function __construct(){
    $this->config = $this->getConfig();

    add_filter( 'page_link',        [$this, 'process_link' ], 1, 3 );
    add_filter( 'post_link',        [$this, 'process_link' ], 1, 3 );
    add_filter( 'post_type_link',   [$this, 'process_link' ], 1, 4 );

    add_action( 'template_redirect', [$this,'redirect_to_right_domain'], 11 );    

    add_filter( 'wp_sitemaps_post_types', [$this,'sitemap_exclude_post_types'],  10, 1 );      
  }

  public function types(){
    return get_post_types(['public'=>true], 'objects');
  }

  public function getConfig(){
    if( !empty($this->config) ) {
      return $this->config;
    }

    // $hosts = $this->hosts();
    $config = get_option( self::CONFIG_FIELD, [] );
    $post_types = $this->types();

    foreach( $post_types as $post_type ) {
      if( !isset( $config[ $post_type->name ] ) ) {
        $config[ $post_type->name ] = null;
      }
    } // foreach

    return $config;
  } // getConfig

  public function save(){
    try {
      $config = [];
      
      $post_types = $this->types();
      $hosts = $this->hosts();

      $data = $_POST;
  
      foreach( $post_types as $type ) {
        if( !empty($_POST[ $type->name ]) ) {
          $host = $_POST[ $type->name ];
          
          $check = in_array( $host, $hosts );

          if( $check ) {
            $config[ $type->name ] = $host;
          }
          else {
            $config[ $type->name ] = null;
          }
        }
        else {
          $config[ $type->name ] = null;
        }
      } // foreach
  
      update_option( self::CONFIG_FIELD, $config );

      return true;
    }
    catch(\Exception $e ) {
      return false;
    }    
  } // save

  public function process_link( $link, $p, $leavename, $sample = false ){
    $config = $this->getConfig();

    $post = is_integer($p) ? get_post( $p ) : $p;
    
    $host = $config[ $post->post_type ];

    if( empty($host) ) {
      return $link;
    }

    $linkHost = parse_url( $link, PHP_URL_HOST );

    if( strcmp( $host, $linkHost ) === 0 ) {
      return $link;
    }

    return str_replace( "//$linkHost", "//$host", $link );
  } // process_link  

  // for page, runs after Page::redirect_to_right_domain
  public function redirect_to_right_domain(){
    if( is_admin() ) {
      return;
    }

    if( !is_singular() ) {
      return;
    }
    
    $host = $_SERVER['HTTP_HOST'];
    $post = get_post();
    $type = $post->post_type;
    $config = $this->getConfig();

    if( empty($config[$type]) ) {
      return;
    }

    // additional check to avoid loops for page
    if( is_page() ) {
      $domain = get_post_meta( $post->ID, WpMultiDomain_Page::META_FIELD, true );
      if( !empty($domain) ) {
        $config[$type] = $domain;
      }
    }

    if( !empty($config[$type]) && $host != $config[$type] ) {
      $url = get_the_permalink( $post );
      wp_redirect( $url, 301, self::NAME . " - post redirected to the configured host" );
      if( headers_sent() ) {
        echo "<script>location?.replace('" . esc_attr($url) . "')</script>";
      }
      die();
    }
  } // redirect_to_right_domain  


  public function sitemap_exclude_post_types( $post_types ) {
    $config = $this->getConfig();
    $host = $this->current_host();
    $default = $this->default_host();
    $is_default = $host === $default;

    foreach( $post_types as $name => $pt ) {   
      if( $name == 'page' && empty($config[$name]) ) {
        continue;
      }

      if( ( empty($config[$name]) && !$is_default ) || ( !empty($config[$name]) && $host !== $config[$name] ) ) {
        unset(  $post_types[ $name ] );
      }
    }  
  
    return $post_types;
  } // sitemap_exclude_post_types
}
