<?php
/*
Plugin Name: WP Multi Domain
Plugin URI: https://yelbaev.com/wp-multi-domain
Description: 
Author: Andrii Ielbaiev
Version: 0.3.1
Requires PHP: 7.0
Requires at least: 5.5
Author URI: https://yelbaev.com
*/

spl_autoload_register(function($name){
  if( !preg_match( '/yelbaev\\\\wpmd\\\\/', $name ) ) {
    return false;
  }

  $className = basename( str_replace( '\\', '/', $name ) );
  $classPath = str_replace( ['\\','/'], DIRECTORY_SEPARATOR, "/includes/{$className}.php" );

  $path = plugin_dir_path(__FILE__) . $classPath;
  if( file_exists($path) ) {
    require $path;
  }
});


if( !class_exists( '\yelbaev\wpmdpro\Domain' ) ) {
  $wpMultiDomain = new \yelbaev\wpmd\Domain;
}
