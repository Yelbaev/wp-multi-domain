<?php

namespace yelbaev\wpmd;

/**
 * Sitemap renderer
 */
class Sitemap extends \WP_Sitemaps_Renderer {
  public $original_renderer;

  public function __construct() {
    parent::__construct();
	}

  public function init( $wp_sitemaps ){
    $this->original_renderer = $wp_sitemaps->renderer;

    $wp_sitemaps->renderer = $this;
  }

  public function get_sitemap_xml( $url_list ) {
		$urlset = new \SimpleXMLElement(
			sprintf(
				'%1$s%2$s%3$s',
				'<?xml version="1.0" encoding="UTF-8" ?>',
				$this->stylesheet,
				'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" 
                 xmlns:xhtml="http://www.w3.org/1999/xhtml" 
                 />'
			)
		);

		foreach ( $url_list as $url_item ) {
      if( empty($url_item) ) {
        continue;
      }

			$url = $urlset->addChild( 'url' );

			// Add each element as a child node to the <url> entry.
			foreach ( $url_item as $name => $value ) {
				if ( 'loc' === $name ) {
					$url->addChild( $name, esc_url( $value ) );
				} elseif ( in_array( $name, array( 'lastmod', 'changefreq', 'priority' ), true ) ) {
					$url->addChild( $name, esc_xml( $value ) );
        } elseif ( 'xhtml:link' === $name ) {
          $links = json_decode( $value, true );
          foreach( $links as $link ) {
            $xhtmlLink = $url->addChild( 'link', null, "http://www.w3.org/1999/xhtml" );
            foreach( $link as $attr_name => $attr_value ) {
              $xhtmlLink->addAttribute( $attr_name, $attr_value );
            }
          }
				} else {
					_doing_it_wrong(
						__METHOD__,
						sprintf(
							/* translators: %s: List of element names. */
							esc_attr__( 'Fields other than %s are not currently supported for sitemaps.' ),
							implode( ',', array( 'loc', 'lastmod', 'changefreq', 'priority' ) )
						),
						'5.5.0'
					);
				}
			}
		}

		return $urlset->asXML();
	}
}