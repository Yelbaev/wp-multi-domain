<?php

namespace yelbaev\wpmd;

/**
 * Base functionality in dynamic domain replacement
 * Implement options page
 */
class Domain {
  public const NAME = 'WP-Multi-Domain';
  public const DOMAIN = 'wpmd';
  public const HOSTS_FIELD = 'wpmd-hosts';
  public const CONFIG_FIELD = 'wpmd-hosts-config';

  public $default;

  public $api;
  public $page;
  public $post;
  public $menus;

  /**
   * List of filters applied to get option 
  */
  protected const OPTION_FILTERS = [
    'home'            => [ 'priority' => 1, 'params' => 1, 'fn' => 'replace_host' ],
    'siteurl'         => [ 'priority' => 1, 'params' => 1, 'fn' => 'replace_host' ],
    'show_on_front'   => [ 'priority' => 1, 'params' => 2, 'fn' => 'hosts_config_replace' ],
    'page_on_front'   => [ 'priority' => 1, 'params' => 2, 'fn' => 'hosts_config_replace' ],
    'page_for_posts'  => [ 'priority' => 1, 'params' => 2, 'fn' => 'hosts_config_replace' ],    
  ];

  public function __construct(){
    $this->init();

    $this->page = new Page;
    $this->post = new Post;
    $this->menus = new Menus;

    $this->api = new Api;
      $this->api->base = $this;
      $this->api->page = $this->page;
      $this->api->post = $this->post;
      $this->api->menus = $this->menus;

    // $home = 
    // $blog =
  }

  public function init(){
    // replace options values based on current host
    $this->add_option_filters();

    // replace WP_CONTEN_URL based on current host
    add_filter( 'home_url',    [$this,'replace_content_url'], 1, 2 );
    add_filter( 'content_url', [$this,'replace_content_url'], 1, 2 );

    // init settings and options page
    add_action( 'admin_init', [$this,'init_settings'] );
    add_action( 'admin_menu', [$this,'add_options_page'] );

    // make sure sitemap has last_mod
    add_action( 'wp_sitemaps_init',         [$this,'sitemap_init'], 11, 1 );
    add_filter( 'wp_sitemaps_posts_entry',  [$this,'sitemap_add_lastmod'], 10, 2 );

    $this->default = $this->default_host();

    
    // list of 
    // wp_internal_hosts

    // filters:
    // post_type_link - custom posts
    // post_type_archive_link
    // post_type_archive_feed_link
    // preview_post_link
    // get_edit_post_link
    // page_link - static pages
    // attachment_link - 
    // year_link
    // month_link
    // day_link
    // feed_link
    // post_comments_feed_link
    // author_feed_link
    // category_feed_link
    // tag_feed_link
    // taxonomy_feed_link
    // edit_tag_link
    // edit_term_link
    // search_link
    // search_feed_link
  } // init

  /**
   * Adds options filters
   */
  public function add_option_filters(){
    foreach( self::OPTION_FILTERS as $option => $filter ) {
      if( !method_exists($this, $filter['fn']) ) {
        continue;
      }

      add_filter( "option_$option", [$this,$filter['fn']], $filter['priority'], $filter['params'] );
    }
  } // add option filters

  /**
   * Removes filters of this plugin to allow to get default WP values
   */
  public function remove_option_filters(){
    foreach( self::OPTION_FILTERS as $option => $filter ) {
      remove_filter( "option_$option", [$this,$filter['fn']], $filter['priority'] );
    }
  } // remove option filters

  /**
   * replace host to current one by default
   * this prevents from redirecting to the default host
   */
  public function replace_host( $url ) {
    $scheme = wp_parse_url( $url, PHP_URL_SCHEME );
    $host = wp_parse_url( $url, PHP_URL_HOST );

    $newHost = $_SERVER['HTTP_HOST'];

    if( strcmp( $host, $newHost ) === 0 ) {
      return $url;
    }

    $reg = "/^$scheme:\/\/$host/";
    $newUrl = preg_replace( $reg, "$scheme://$newHost", $url );

    return $newUrl;
  }

  /**
   * This content URL based on the domain. 
   */
  public function replace_content_url( $url, $path ) {
    return $this->replace_host( $url );
  } // replace_content_url

  /**
   * Registers options param 
   */
  public function init_settings(){
    register_setting(
      'wpmd',                // option group
      'wpmd-hosts',          // option name
      array(                 // args
        'type'=> 'array',
        'default' => array(
        ),
        'description' => __( "List of supported domain names", 'wpmd' )
      )
    );
  } // init_settings

  /**
   * add settings page
   */ 
  public function add_options_page(){
    add_options_page( 
      __( 'WP Multi Domain Options', 'wpmd' ), 
      __( 'Multi Domain', 'wpmd' ), 
      'manage_options', 
      'wpmd-options',
      [$this,'options_page'], 
      null  // position
    );
  } // add_options_page

  /**
   * Displays options page. 
   */
  public function options_page(){
    try {
      $assets_file = plugin_dir_path( __DIR__ ) . "assets/js/options/build/asset-manifest.json";
      if( !file_exists( $assets_file ) ) {
        throw new \Exception("Dashboard app is not ready: assets-manifest.json not found ");
      }
  
      $assets_txt = file_get_contents( $assets_file );
      if( empty($assets_txt) ) {
        throw new \Exception("Empty Dashboard app assets file");
      }

      $assets = json_decode( $assets_txt, true );
      if( is_null($assets) ) {
        throw new \Exception("Dashboard app assets file can't be parsed");
      }

      $js = "assets/js/options/build" . $assets['files']['main.js'];
      $js_url = plugins_url( $js, __DIR__ );
      $js_path = plugin_dir_path( __DIR__ ) . $js;
      if( !file_exists( $js_path ) ) {
        throw new \Exception("Dashboard app script not found");
      }

      wp_enqueue_script( 'wpmd-options-script', 
        $js_url,
        [],
        filemtime( $js_path ),
        ["in_footer" => true]
      );

      $css = "assets/js/options/build" . $assets['files']['main.css'];
      $css_url = plugins_url( $css, __DIR__ );
      $css_path = plugin_dir_path( __DIR__ ) . $css;

      wp_enqueue_style( 'wpmd-options-style', 
        $css_url,
        [],
        filemtime( $css_path )
      );
        
      echo '<div class="wrap">';
        echo '<h1>', esc_html( "Multi Domain Options", 'wpmd' ), '</h1>';
        echo '<div id="wpmd-options"></div>';
      echo '</div>';
    }
    catch( \Exception $e ){
      echo '<div>', esc_html( $e->getMessage() ), '</div>';
    }
  } // options_page

  /**
   * Retrieves default host from the database. And returns it as a url. 
   * @method
   * @return url
   */
  public function default_host(){

    if( $this->default ) {
      return $this->default;
    }

    $this->remove_option_filters();

    $home = get_option('home');
    if( empty($home) ) {
      $home = get_option('siteurl');
    }

    $this->add_option_filters();    

    if( empty($home) ) {
      return $_SERVER['HTTP_HOST'];
    }

    $host = parse_url( $home, PHP_URL_HOST );

    if( empty($host) ) {
      return $home;
    }

    return $host;   
  } // default_host

  public function current_host(){
    return $_SERVER['HTTP_HOST'];
  } // current_host

  /**
   * Get list of hosts from the database and returns it as an array. 
   * @return array
   */
  public function hosts(){
    $default = $this->default_host();
    $hosts = get_option( self::HOSTS_FIELD, [] );

    if( !in_array($default, $hosts) ) {
      array_unshift( $hosts, $default );
    }

    return $hosts;
  } // hosts

  /**
   * Adds host to the database to the list of known hosts. 
   * @param host - domain name to add
   * @return bool
   */
  public function add_host( $host ) {
    try {
      $hosts = $this->hosts();

      // validate
      $name = trim( $host );
      $name = strtolower( $name );

      if( !in_array($name, $hosts ) ) {
        $hosts[] = $name;
      }

      update_option( self::HOSTS_FIELD, $hosts );

      return true;
    }
    catch(\Exception $e ) {
      return false;
    }
  } // add_host

  /**
   * Removes host from list of known hosts. 
   * @param host - domain name to add
   * @return bool
   */
  public function remove_host( $host ){
    try {
      $hosts = $this->hosts();

      // validate
      $name = trim( $host );

      if( in_array($host, $hosts ) ) {
        array_splice( $hosts, array_search( $host, $hosts ), 1 );
      }

      update_option( self::HOSTS_FIELD, $hosts );

      return true;
    }
    catch(\Exception $e ) {
      return false;
    }
  } // remove_host

  /**
   * Get configuration of the hosts from the database and returns it as an associative array. 
   * @return array
   */
  public function hosts_config(){
    $config = get_option( self::CONFIG_FIELD, [] );
    if( empty($config) || !is_array($config) ) {
      $config = [];
    }
    
    $default = $this->default_host();

    $this->remove_option_filters();
    $show_on_front = get_option( 'show_on_front' );
    $page_on_front = get_option( 'page_on_front' );
    $page_for_posts = get_option( 'page_for_posts' );
    $this->add_option_filters();

    $config[ $default ] = [
      'show_on_front'  => $show_on_front->option_value,
      'page_on_front'  => $page_on_front->option_value,
      'page_for_posts' => $page_for_posts->option_value,
      'is_default'     => true
    ];

    return $config;
  } // hosts_config

  /**
   * Savss the configuration of the hosts to the database. 
   * @return bool|string
   */
  public function hosts_config_save( $data ){
    try {
      if( empty($data) ) {
        throw new \Exception("No data provided");
      }

      foreach( $data as $host => $options ) {
        // validate info
        if( !empty($options['page_on_front']) && is_numeric($options['page_on_front']) ) {
          $page = get_post( $options['page_on_front'] );
          if( empty($page) ) {
            throw new \Exception( "Page {$options['page_on_front']} not found for $host as page on front" );
          }
        }

        if( !empty($options['page_for_posts']) && is_numeric($options['page_for_posts']) ) {
          $page = get_post( $options['page_for_posts'] );
          if( empty($page) ) {
            throw new \Exception( "Page {$options['page_for_posts']} not found for $host as page for posts" );
          }
        }
      }

      return update_option( self::CONFIG_FIELD, $data );
    }
    catch( \Exception $e ) {
      return $e->getMessage();
    }
  } // hosts_config_save

  /**
   * Replaces configuration for the current domain based on the configuration in the database 
   */
  public function hosts_config_replace($value, $option_name){
    $host = $_SERVER['HTTP_HOST'];
    $config = $this->hosts_config();
    
    if( empty($config[$host]) ) {
      return $value;
    }

    $options = $config[$host];

    if( empty($options[$option_name]) ) {
      return $value;
    }

    switch( $option_name ) {
      case "page_on_front":
      case "page_for_posts":
        $page = get_post( $options[$option_name] );
        if( $page && ($page instanceof \WP_Post) && $page->post_status == 'publish' ) {
          return $page->ID;
        }
        break;
    }

    return $value;
  } // hosts_config_replace


  public function sitemap_add_lastmod( $entry, $post ){
    if( !isset($entry['lastmod']) ) {
      $entry['lastmod'] = date( DATE_W3C, strtotime( $post->post_modified_gmt ) );
    }
  
    return $entry;
  }

  public static function sitemap_init( $wp_sitemaps ){
    $renderer = new Sitemap();
  
    $renderer->init( $wp_sitemaps );
  }  
}
