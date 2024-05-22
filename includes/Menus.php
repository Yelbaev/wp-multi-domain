<?php
namespace yelbaev\wpmd;

class Menus extends Domain {
  
  public const CONFIG_FIELD = 'wpmd-menus';

  public $config;

  public function __construct(){
    $this->config = $this->getConfig();

    add_filter( 'has_nav_menu', [$this,'adjust_has_nav'], 1, 2 ); 
    add_filter( 'wp_nav_menu_args', [$this,'adjust_menu_args'], 1, 1 );
  }

  public function getConfig(){
    if( !empty($this->config) ) {
      return $this->config;
    }

    $config = get_option( self::CONFIG_FIELD, [] );

    $menus_locations = get_registered_nav_menus();
    $hosts = $this->hosts();

    foreach( $menus_locations as $location => $label ) {
      if( !isset( $config[ $location ] ) ) {
        $config[ $location ] = [];
      }

      foreach( $hosts as $host ) {
        if( !isset( $config[ $location ][ $host ] ) ) {
          $config[ $location ][ $host ] = null;
        }
      }
    } // foreach

    return $config;
  } // getConfig

  public function save(){
    try {
      $config = [];
      
      $menus_locations = get_registered_nav_menus();
      $hosts = $this->hosts();
  
      foreach( $menus_locations as $location => $label ) {
        if( !isset( $config[ $location ] ) ) {
          $config[ $location ] = [];
        }
  
        foreach( $hosts as $host ) {
          if( !empty($_POST[ $location ]) && !empty($_POST[ $location ][ $host ]) ) {
            $menu_id = $_POST[ $location ][ $host ];
            
            $menu = wp_get_nav_menu_object( $menu_id );

            if( $menu ) {
              $config[ $location ][ $host ] = $menu_id;
            }
            else {
              $config[ $location ][ $host ] = null;
            }
          }
          else {
            $config[ $location ][ $host ] = null;
          }
        }
      } // foreach
  
      update_option( self::CONFIG_FIELD, $config );

      return true;
    }
    catch(\Exception $e ) {
      return false;
    }    
  } // save

  public function adjust_has_nav( $has, $location ){
    $config = $this->getConfig();

    if( !isset($config[$location]) ) {
      return $has;
    }

    $host = $_SERVER['HTTP_HOST'];

    if( empty($config[$location][$host]) ) {
      // not set or don't override, do nothing
      return $has;
    }

    return true;
  } // has_nav_menu

  // replace menu args with one with menu ID
  public function adjust_menu_args( $args ){
    if( !empty($args['menu']) ) {
      // do nothing, since specific menu is loaded
      return $args;
    }

    if( empty($args['theme_location']) ) {
      // do nothing, since we can't know what menu should be loaded
      return $args;
    }

    $config = $this->getConfig();
    
    $host = $_SERVER['HTTP_HOST'];

    if( empty($config[ $args['theme_location'] ][ $host ]) ) {
      // nothing to replace with
      return $args;
    }

    // check if menu id is still valid 
    $menu_id = $config[ $args['theme_location'] ][ $host ];
    $menu = wp_get_nav_menu_object( $menu_id );
    
    if( $menu ) {
      $args['theme_location'] = null;
      $args['menu'] = $menu_id;
    }

    return $args;
  } // adjust_menu_args

}