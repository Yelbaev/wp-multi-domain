<?php
namespace yelbaev\wpmd;

/**
 * Implements ajax and point for the plugin. 
 * Utilizes request param `wpmd-action` for routing of the request
 */
class Api {
  /* these variables are populated in Domain class */
  public $base;
  public $page;
  public $post;
  public $menus;

  public function __construct(){
    add_action( 'wp_ajax_wpmd-options-action', [$this,'options_endpoint'] );
  } // construct

  public function options_endpoint(){
    try {
      $action = $_REQUEST['wpmd-action'];

      if( method_exists( $this, $action ) ) {
        $this->$action();
      }
      else if( method_exists( $this->page, $action ) ) {
        $this->page->$action();
      }
      else if( method_exists( $this->post, $action ) ) {
        $this->post->$action();
      }
      else if( method_exists( $this->menus, $action ) ) {
        $this->menus->$action();
      }      
      else {
        throw new \Exception( __("Unknown api endpoint", 'wpmd' ) );
      }
    }
    catch( \Exception $e ) {
      wp_send_json( [ 'success' => false, 'error' => $e->getMessage() ] );
    }
    wp_die();
  } // options_endpoint

  public function hosts(){
    $hosts = $this->base->hosts();

    wp_send_json_success( $hosts );
    wp_die();
  } // hosts

  public function hosts_config(){
    $hosts = $this->base->hosts_config();

    wp_send_json_success( $hosts );
    wp_die();
  } // hosts

  public function hosts_config_save(){
    if( empty($_POST['config']) ) {
      wp_send_json_error();
      wp_die();
    }

    $data = $_POST['config'];
    $result = $this->base->hosts_config_save( $data );

    if( true === $result ) {
      wp_send_json_success();
    }
    else {
      wp_send_json_error( ['error' => $result, 'post' => $_POST, 'data' => $data ] );
    }
    
    wp_die();
  } // hosts

  public function host_add(){
    $host = $_POST['host'];
    wp_send_json( ['success' => $this->base->add_host( $host ) ] );
    wp_die();
  } // add_host

  public function host_remove(){
    $host = $_POST['host'];
    wp_send_json( ['success' => $this->base->remove_host( $host ) ] );
    wp_die();
  } // add_host

  public function menus(){
    // theme menus location
    $menus_locations = get_registered_nav_menus();
    $assigned_menus = get_nav_menu_locations();
    $menus = wp_get_nav_menus();
    $_menus = [];
    foreach( $menus as $menu ) {
      $_menus[ $menu->term_id ] = $menu;
    }

    $locations = array();
    foreach( $menus_locations as $location => $label ) {
      $locations[] = [
        'location'  => $location,
        'label'     => $label,
        'current'   => !empty( $assigned_menus[$location] ) ? $_menus[ $assigned_menus[ $location ] ] : null,
      ];
    }

    $data = [
      'hosts'     => $this->base->hosts(),
      'locations' => $locations,
      'menus'     => $menus,
      'config'    => $this->menus->getConfig()
    ];

    wp_send_json_success( $data );
    wp_die();
  } // menus  

  public function menus_save(){
    wp_send_json( ['success' => $this->menus->save()] );
    wp_die();
  } // menus_save

  public function post_types(){
    $types = $this->post->types();

    $data = [
      'hosts'   => $this->base->hosts(),
      'types'   => array_values( $types ),
      'config'  => $this->post->getConfig()
    ];

    wp_send_json_success( $data );
    wp_die();
  } // post_types  

  public function post_types_save(){
    wp_send_json( ['success' => $this->post->save()] );
    wp_die();
  } // post_types_save  

  public function search_posts(){
    $search = $_REQUEST['s'];
    $pt = $_REQUEST['t'];

    $pt_filter = 'any';
    if( !empty($pt) ) {
      if( is_string($pt) ) {
        $pt_filter = explode(',', $pt);
      }
      else if( is_array($pt) ) {
        $pt_filter = $pt;
      }
    }

    $search = trim( $search );
  
    $args = array(
      's' => $search,
      'post_type' => $pt_filter,
      'posts_per_page' => 50
    );
  
    $posts = get_posts( $args );
  
    $list = array();
  
    foreach( $posts as $item ) {
      $list[] = array(
        'ID'  => $item->ID,
        'url' => get_the_permalink( $item ),
        'post_title' => $item->post_title,
        'post_type'  => $item->post_type
      );
    }
  
    wp_send_json( ['success' => true, 'data' => $list] );
    wp_die();
  } // search_posts

  public function get_post(){
    $post_ID = $_REQUEST['id'];

    wp_send_json( ['success' => true, 'data' => get_post($post_ID) ] );
    wp_die();
  }
}