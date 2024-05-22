<?php
namespace yelbaev\wpmd;
/**
 * @class
 */
class Page extends Domain {

  const META_FIELD = 'wpmd-host';
  const NONCE_FIELD =  '_wpmd_nonce';

  public function __construct(){
    add_action( 'template_redirect', [$this,'redirect_to_right_domain'], 10 );

    add_action( 'add_meta_boxes', [$this,'add_meta_box'], 10, 2 );
    add_action( 'save_post', [$this, 'save_meta_box'], 10, 3 );

    add_action( 'wp_ajax_wpmd-save-host', [$this, 'save_ajax'] );

    add_filter( 'page_link', [$this, 'process_link' ], 10, 3 );

    // pages list
    add_filter( 'manage_pages_columns', [$this, 'register_column'], 10, 1 );
    add_action( 'manage_pages_custom_column', [$this, 'add_column'], 10, 2 );
    add_action( 'admin_enqueue_scripts', [$this,'add_columns_style'], 10, 1 );

    // exclude pages with a different domain
    add_filter( 'wp_sitemaps_posts_entry',  [$this,'sitemap_filter'], 10, 3 );
  }

  /**
   * @method - based on post configuration adds a gutenberg plugin or a classic meta box
   * @return void
   */
  public function add_meta_box( $post_type, $the_post ){

    if( function_exists('use_block_editor_for_post_type') 
          && use_block_editor_for_post_type($post_type) ) {
      
      $wpmd_post = [
        'ID'    => $the_post->ID,
        'title' => html_entity_decode( get_the_title( $the_post->ID ) ),
        'name'  => $the_post->post_name,
        'type'  => $the_post->post_type,
        'status'=> $the_post->post_status,
        'nonce' => $this->_make_nonce( $the_post->ID ),
        'hosts' => $this->hosts(),
        'host'  => get_post_meta( $the_post->ID, self::META_FIELD, true ),
        'field' => self::META_FIELD
      ];
      
      wp_enqueue_script( 'wpmd-host-script', 
        plugins_url("blocks/host/build/index.js", __DIR__),
        ['wp-element', 'wp-edit-post','wp-plugins', 'wp-i18n'],
        filemtime( plugin_dir_path( __DIR__ ) . "blocks/host/build/index.js" ),
        ["in_footer" => true]
      );
      
      wp_add_inline_script( 'wpmd-host-script', 'const wpmd_post = ' . json_encode( $wpmd_post ), 'before' );
    }
    else {
      add_meta_box( 
        'wpmd-page-host',
        __('Multi domain host', 'wpmd'),
        [$this,'meta_box'],
        'page',     // sreen
        'side',     // context
        'default',  // priority
        null        // callback params
      );
    }
  } // add_meta_box

  /**
   * @method - outputs html code of the classic meta box
   * @return void
   */
  public function meta_box( $post, $metabox ){
    wp_nonce_field( self::NONCE_FIELD . $post->ID, self::NONCE_FIELD );
    $current = get_post_meta( $post->ID, self::META_FIELD, true );

    $options = $this->hosts();
    ?>
    <div>
      <select name="<?php echo esc_attr( self::META_FIELD );?>">
        <option value=""><?php esc_html_e( 'Default Host', 'wpmd' );?></option>

        <?php foreach( $options as $host ): ?>
          <option value="<?php echo esc_attr($host)?>" <?php echo $host == $current ? 'selected' : '';?>>
            <?php echo esc_html($host);?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php
  } // meta_box

  /**
   * @method - saves host meta field on post save
   * @return void
   */
  public function save_meta_box( $post_id, $post, $update ) {
    // check if this is a revision
    if ( $parent_id = wp_is_post_revision( $post_id ) ) {
      return;
    }
    
    if( !isset( $_POST[ self::NONCE_FIELD ] ) 
          || false === wp_verify_nonce( $_POST[ self::NONCE_FIELD ], self::NONCE_FIELD . $post_id ) ) {
      return;
    }

    if( function_exists('use_block_editor_for_post_type') && use_block_editor_for_post_type($post_type) ) {
      return;
    }
    
    update_post_meta( $post_id, self::META_FIELD, isset($_POST[ self::META_FIELD ]) ? $_POST[ self::META_FIELD ] : null );
  } // save_meta

  /**
   * @method - used by gutenberg plugin to save host field
   * @return json
   */
  public static function save_ajax(){
    try {
      if( !current_user_can('edit_posts') ) {
        throw new \Exception( "Not allowed" ); 
      }

      if( empty($_POST['post_ID']) ) {
        throw new \Exception( "Post ID not specified" );
      }

      $post_ID = $_POST['post_ID'];
      $post = get_post( $post_ID);

      if( !$post ) {
        throw new \Exception( "Post not found" );
      }

      if( !isset( $_POST[ self::NONCE_FIELD ] ) 
          || false === wp_verify_nonce( $_POST[ self::NONCE_FIELD ], self::NONCE_FIELD . $post_ID ) ) {
        throw new \Exception( "Sorry, nonce not verified" );;
      }

      update_post_meta( $post_ID, self::META_FIELD, isset($_POST[ self::META_FIELD ]) ? $_POST[ self::META_FIELD ] : null );

      $result = array( 
        'status' => true, 
        'nonce' => [
          'name' => self::NONCE_FIELD,
          'value' => wp_create_nonce( self::NONCE_FIELD . $post_ID )
        ] 
      );
    }
    catch( \Exception $e ) {
      $result = array( 'status' => false, 'error' => $e->getMessage() );
    }

    wp_send_json( $result );
    wp_die();
  } // save_ajax

  /**
   * @return array
   */
  private function _make_nonce( $post_ID ){
    return [
      'name' => self::NONCE_FIELD,
      'value' => wp_create_nonce( self::NONCE_FIELD . $post_ID )
    ];
  } // _make_nonce

  /**
   * @method - filters page link (url) and replaces the host with the host, configured in metabox 
   * @return URL
   */
  public function process_link( $link, $post_ID, $sample ){
    $host = get_post_meta( $post_ID, self::META_FIELD, true );

    if( empty($host) ) {
      return $link;
    }

    $linkHost = parse_url( $link, PHP_URL_HOST );

    if( strcmp( $host, $linkHost ) === 0 ) {
      return $link;
    }

    return str_replace( "//$linkHost", "//$host", $link );
  } // process_link

  /**
   * @method - adds a column to the list of pages table with a domain
   * @return array
   */
  public function register_column( $columns ) {
    return array_merge( $columns, array( 'wpmd' => __( 'Domain', 'wpmd' ) ) );
  } // register_column

  /**
   * @method - outputs domain to a page list item
   */
  public function add_column( $column, $post_ID ) {
    if( $column != 'wpmd' ) {
      return;
    }

    $domain = get_post_meta( $post_ID, self::META_FIELD, true );

    if( empty($domain) ) {
      return;
    }


    echo '<span>', esc_html($domain), '</span>'; 
  } // add_column
  
  /**
   * @method - adds styles for the domain column in page list
   */
  public function add_columns_style( $hook ){
    if ( 'edit.php' != $hook || $_GET['post_type'] != 'page' ) {
      return;
    }

    wp_enqueue_style( 
      'wpmd-page-style', 
      plugin_dir_url( __DIR__ ) . 'assets/css/admin.page.css', 
      array(), 
      filemtime( plugin_dir_path( __DIR__ ) . 'assets/css/admin.page.css' )
    );
  } // add_columns_style

  /**
   * @method - redirects user to the correct domain based on page meta field
   * @return void
   */
  public function redirect_to_right_domain(){
    if( is_admin() ) {
      return;
    }

    if( !is_page() ) {
      return;
    }
    
    $host = $_SERVER['HTTP_HOST'];
    $page = get_post();
    $domain = get_post_meta( $page->ID, self::META_FIELD, true );

    if( !empty($domain) && $host != $domain ) {
      $url = get_the_permalink( $page );
      wp_redirect( $url, 301, self::NAME . " - page redirected to the configured host: $host != $domain" );
      if( headers_sent() ) {
        echo "<script>location?.replace('" . esc_attr($url) . "')</script>";
      }
      die();
    }
  } // redirect_to_right_domain

  public function sitemap_filter( $entry, $post, $post_type ){
    if( $post_type !== 'page' ) {
      return $entry;
    }
    
    $host = $this->current_host();
    $domain = get_post_meta( $post->ID, self::META_FIELD, true );

    if( $host !== $domain ) {
      return null;
    }

    return $entry;
  } // sitemap_filter

}
