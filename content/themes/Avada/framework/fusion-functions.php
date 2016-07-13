<?php
/**
 * Contains all framework specific functions that are not part od a separate class
 *
 * @author      ThemeFusion
 * @package     FusionFramework
 * @since       Version 1.0
 */


if( ! function_exists( 'fusion_get_related_posts' ) ) {
	/**
	 * Get related posts by category
	 * @param  integer  $post_id       current post id
	 * @param  integer  $number_posts  number of posts to fetch
	 * @return object                  object with posts info
	 */
	function fusion_get_related_posts( $post_id, $number_posts = -1 ) {
		global $smof_data;
		$query = new WP_Query();

		$args = '';

		if( $number_posts == 0 ) {
			return $query;
		}

		$args = wp_parse_args( $args, array(
			'category__in'          => wp_get_post_categories( $post_id ),
			'ignore_sticky_posts'   => 0,
			'posts_per_page'        => $number_posts,
			'post__not_in'          => array( $post_id ),
		));
		
		// If placeholder images are disabled, add the _thumbnail_id meta key to the query to only retrieve posts with featured images
		if ( ! $smof_data['featured_image_placeholder'] ) {
			$args['meta_key'] = '_thumbnail_id';
		}		

		$query = new WP_Query( $args );

		return $query;
	}
}

if( ! function_exists( 'fusion_get_related_projects' ) ) {
	/**
	 * Get related posts by portfolio_category taxonomy
	 * @param  integer  $post_id       current post id
	 * @param  integer  $number_posts  number of posts to fetch
	 * @return object                  object with posts info
	 */
	function fusion_get_related_projects( $post_id, $number_posts = 8 ) {
		global $smof_data;
		$query = new WP_Query();

		$args = '';

		if( $number_posts == 0 ) {
			return $query;
		}

		$item_cats = get_the_terms( $post_id, 'portfolio_category' );
		
		$item_array = array();
		if( $item_cats ) {
			foreach( $item_cats as $item_cat ) {
				$item_array[] = $item_cat->term_id;
			}
		}

		if( ! empty( $item_array ) ) {
			$args = wp_parse_args( $args, array(
				'ignore_sticky_posts' => 0,
				'posts_per_page' => $number_posts,
				'post__not_in' => array( $post_id ),
				'post_type' => 'avada_portfolio',
				'tax_query' => array(
					array(
						'field' => 'id',
						'taxonomy' => 'portfolio_category',
						'terms' => $item_array,
					)
				)
			));
			
            // If placeholder images are disabled, add the _thumbnail_id meta key to the query to only retrieve posts with featured images
            if ( ! $smof_data['featured_image_placeholder'] ) {
                $args['meta_key'] = '_thumbnail_id';
            }
			
			$query = new WP_Query( $args );
		}

		return $query;
	}
}

/**
 * Function to apply attributes to HTML tags.
 * Devs can override attr in a child theme by using the correct slug
 *
 *
 * @param  string $slug         Slug to refer to the HTML tag
 * @param  array  $attributes   Attributes for HTML tag
 * @return string               Attributes in attr='value' format
 */
if( ! function_exists( 'fusion_attr' ) ) {
	function fusion_attr( $slug, $attributes = array() ) {

		$out = '';
		$attr = apply_filters( "fusion_attr_{$slug}", $attributes );

		if ( empty( $attr ) ) {
			$attr['class'] = $slug;
		}

		foreach ( $attr as $name => $value ) {
			$out .= !empty( $value ) ? sprintf( ' %s="%s"', esc_html( $name ), esc_attr( $value ) ) : esc_html( " {$name}" );
		}

		return trim( $out );

	}
}

if( ! function_exists( 'fusion_pagination' ) ) {
	/**
	 * Number based pagination
	 * @param  string  $pages         Maximum number of pages
	 * @param  integer $range         
	 * @param  string  $current_query
	 * @return void
	 */
	function fusion_pagination( $pages = '', $range = 2, $current_query = '' ) {
		global $smof_data;

		$showitems = ($range * 2)+1;

		if( $current_query == '' ) {
			global $paged;
			if( empty( $paged ) ) $paged = 1;
		} else {
			$paged = $current_query->query_vars['paged'];
		}

		if( $pages == '' ) {
			if( $current_query == '' ) {
				global $wp_query;
				$pages = $wp_query->max_num_pages;
				if(!$pages) {
					 $pages = 1;
				}
			} else {
				$pages = $current_query->max_num_pages;
			}
		}

		 if(1 != $pages)
		 {
			if ( ( $smof_data['blog_pagination_type'] != 'Pagination' && ( is_home() || is_search() || ( get_post_type() == 'post' && ( is_author() || is_archive() ) ) ) ) || 
				 ( $smof_data['grid_pagination_type'] != 'Pagination' && ( avada_is_portfolio_template() || is_post_type_archive( 'avada_portfolio' ) || is_tax( 'portfolio_category' ) || is_tax( 'portfolio_skills' )  || is_tax( 'portfolio_tags' ) ) )
			) {
				echo "<div class='pagination infinite-scroll clearfix'>";
			} else {
				echo "<div class='pagination clearfix'>";
			}
			 //if($paged > 2 && $paged > $range+1 && $showitems < $pages) echo "<a href='".get_pagenum_link(1)."'><span class='arrows'>&laquo;</span> First</a>";
			 if($paged > 1) echo "<a class='pagination-prev' href='".get_pagenum_link($paged - 1)."'><span class='page-prev'></span>".__('Previous', 'Avada')."</a>";

			 for ($i=1; $i <= $pages; $i++)
			 {
				 if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems ))
				 {
					 echo ($paged == $i)? "<span class='current'>".$i."</span>":"<a href='".get_pagenum_link($i)."' class='inactive' >".$i."</a>";
				 }
			 }

			 if ($paged < $pages) echo "<a class='pagination-next' href='".get_pagenum_link($paged + 1)."'>".__('Next', 'Avada')."<span class='page-next'></span></a>";
			 //if ($paged < $pages-1 &&  $paged+$range-1 < $pages && $showitems < $pages) echo "<a href='".get_pagenum_link($pages)."'>Last <span class='arrows'>&raquo;</span></a>";
			 echo "</div>\n";
		 }
	}
}

if( ! function_exists( 'fusion_breadcrumbs' ) ) {
	/**
	 * Render the breadcrumbs with help of class-breadcrumbs.php
	 *
	 * @return void
	 */
	function fusion_breadcrumbs() {
		$breadcrumbs = new Fusion_Breadcrumbs();
		$breadcrumbs->get_breadcrumbs();
	}
}

if( ! function_exists( 'fusion_strip_unit' ) ) {
	/**
	 * Strips the unit from a given value
	 * @param  string	$value The value with or without unit
	 * @param  string	$unit_to_strip The unit to be stripped
	 *
	 * @return string	the value without a unit
	 */		
	function fusion_strip_unit( $value, $unit_to_strip = 'px' ) {
		$value_length = strlen( $value );
		$unit_length = strlen( $unit_to_strip );

		if ( $value_length > $unit_length &&
			 substr_compare( $value, $unit_to_strip, $unit_length * (-1), $unit_length ) === 0
		) {
			return substr( $value, 0, $value_length - $unit_length );
		} else {
			return $value;
		}
	}
}

if( ! function_exists( 'fusion_string_limit_words' ) ) {
	/**
	 * Limit the content by number of words
	 * @param  string  $string     Content to strip by number of words
	 * @param  integer $word_limit Number of words
	 * @return string              Content within the word limit
	 */
	function fusion_string_limit_words( $string, $word_limit ) {
		$words = explode( ' ', $string, ( $word_limit + 1 ) );

		if ( count( $words ) > $word_limit ) {
			array_pop( $words );
		}

		return implode( ' ', $words );
	}
}

add_filter( 'feed_link', 'fusion_feed_link', 1, 2 );
/**
 * Replace default WP RSS feed link with theme option RSS feed link
 * @param  string $output Feed link
 * @param  string $feed   Feed type
 * @return string         Return modified feed link
 */
if( ! function_exists( 'fusion_feed_link' ) ) {
	function fusion_feed_link( $output, $feed ) {
		if( isset( $smof_data['rss_link'] ) && $smof_data['rss_link'] ) {
			$feed_url = $smof_data['rss_link'];

			$feed_array = array('rss' => $feed_url, 'rss2' => $feed_url, 'atom' => $feed_url, 'rdf' => $feed_url, 'comments_rss2' => '');
			$feed_array[ $feed ] = $feed_url;
			$output = $feed_array[ $feed ];
		}

		return $output;
	}
}

/**
 * Add paramater to current url
 * @param  string $url         URL to add param to
 * @param  string $param_name  Param name
 * @param  string $param_value Param value
 * @return array               params added to url data
 */
if( ! function_exists( 'fusion_add_url_parameter' ) ) {
	function fusion_add_url_parameter( $url, $param_name, $param_value ) {
		 $url_data = parse_url($url);
		 if(!isset($url_data["query"]))
			 $url_data["query"]="";

		 $params = array();
		 parse_str($url_data['query'], $params);
		 
		 if( is_array( $param_value ) ) {
			$param_value = $param_value[0];
		 }
		 
		 $params[$param_name] = $param_value;

		 if( $param_name == 'product_count' ) {
			$params['paged'] = '1';
		 }

		 $url_data['query'] = http_build_query($params); 
		 return fusion_build_url($url_data);
	}
}

/**
 * Build final URL form $url_data returned from fusion_add_url_paramtere
 * 
 * @param  array $url_data  url data with custom params
 * @return string           fully formed url with custom params
 */
if( ! function_exists( 'fusion_build_url' ) ) {
	function fusion_build_url( $url_data ) {
		$url = '';
		if( isset( $url_data['host'] ) ) {
			$url .= $url_data['scheme'] . '://';
			if( isset ( $url_data['user'] ) ) {
				$url .= $url_data['user'];
				if( isset( $url_data['pass'] ) ) {
					$url .= ':' . $url_data['pass'];
				}
				$url .= '@';
			}
			$url .= $url_data['host'];
			if( isset ( $url_data['port'] ) ) {
				$url .= ':' . $url_data['port'];
			}
		}
		
		if( isset( $url_data['path'] ) ) {
			$url .= $url_data['path'];
		}

		if( isset( $url_data['query'] ) ) {
			$url .= '?' . $url_data['query'];
		}

		if( isset( $url_data['fragment'] ) ) {
			$url .= '#' . $url_data['fragment'];
		}

		return $url;
	}
}

/**
 * Lightens/darkens a given colour (hex format), returning the altered colour in hex format.7
 * @param str $hex Colour as hexadecimal (with or without hash);
 * @percent float $percent Decimal ( 0.2 = lighten by 20%(), -0.4 = darken by 40%() )
 * @return str Lightened/Darkend colour as hexadecimal (with hash);
 */
 if ( ! function_exists( 'fusion_color_luminance' ) ) {
	function fusion_color_luminance( $hex, $percent ) {
		// validate hex string

		$hex = preg_replace( '/[^0-9a-f]/i', '', $hex );
		$new_hex = '#';

		if ( strlen( $hex ) < 6 ) {
			$hex = $hex[0] + $hex[0] + $hex[1] + $hex[1] + $hex[2] + $hex[2];
		}

		// convert to decimal and change luminosity
		for ($i = 0; $i < 3; $i++) {
			$dec = hexdec( substr( $hex, $i*2, 2 ) );
			$dec = min( max( 0, $dec + $dec * $percent ), 255 ); 
			$new_hex .= str_pad( dechex( $dec ) , 2, 0, STR_PAD_LEFT );
		}		

		return $new_hex;
	}
}

/**
 * Convert Calculate the brightness of a color
 * @param  string $color Color (Hex) Code
 * @return integer brightness level
 */
if( ! function_exists( 'fusion_calc_color_brightness' ) ) {
	function fusion_calc_color_brightness( $color ) {

		if( strtolower( $color ) == 'black' ||
			strtolower( $color ) == 'navy' ||
			strtolower( $color ) == 'purple' ||
			strtolower( $color ) == 'maroon' ||
			strtolower( $color ) == 'indigo' ||
			strtolower( $color ) == 'darkslategray' ||
			strtolower( $color ) == 'darkslateblue' ||
			strtolower( $color ) == 'darkolivegreen' ||
			strtolower( $color ) == 'darkgreen' ||
			strtolower( $color ) == 'darkblue' 
		) {
			$brightness_level = 0;
		} elseif( strpos( $color, '#' ) === 0 ) {
			$color = fusion_hex2rgb( $color );

			$brightness_level = sqrt( pow( $color[0], 2) * 0.299 + pow( $color[1], 2) * 0.587 + pow( $color[2], 2) * 0.114 );			
		} else {
			$brightness_level = 150;
		}

		return $brightness_level;
	}
}

/**
 * Convert Hex Code to RGB
 * @param  string $hex Color Hex Code
 * @return array       RGB values
 */
if( ! function_exists( 'fusion_hex2rgb' ) ) {
	function fusion_hex2rgb( $hex ) {
		if ( strpos( $hex,'rgb' ) !== FALSE ) {

			$rgb_part = strstr( $hex, '(' );
			$rgb_part = trim($rgb_part, '(' );
			$rgb_part = rtrim($rgb_part, ')' );
			$rgb_part = explode( ',', $rgb_part );

			$rgb = array($rgb_part[0], $rgb_part[1], $rgb_part[2], $rgb_part[3]);

		} elseif( $hex == 'transparent' ) {
			$rgb = array( '255', '255', '255', '0' );
		} else {

			$hex = str_replace( '#', '', $hex );

			if( strlen( $hex ) == 3 ) {
				$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
				$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
				$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
			} else {
				$r = hexdec( substr( $hex, 0, 2 ) );
				$g = hexdec( substr( $hex, 2, 2 ) );
				$b = hexdec( substr( $hex, 4, 2 ) );
			}
			$rgb = array( $r, $g, $b );
		}

		return $rgb; // returns an array with the rgb values
	}
}

/**
 * Convert RGB to HSL color model
 * @param  string $hex Color Hex Code of RGB color
 * @return array       HSL values
 */
if( ! function_exists( 'fusion_rgb2hsl' ) ) {
	function fusion_rgb2hsl( $hex_color ) {

		$hex_color  = str_replace( '#', '', $hex_color );

		if( strlen( $hex_color ) < 3 ) {
			str_pad( $hex_color, 3 - strlen( $hex_color ), '0' );
		}

		$add         = strlen( $hex_color ) == 6 ? 2 : 1;
		$aa       = 0;
		$add_on   = $add == 1 ? ( $aa = 16 - 1 ) + 1 : 1;

		$red         = round( ( hexdec( substr( $hex_color, 0, $add ) ) * $add_on + $aa ) / 255, 6 );
		$green     = round( ( hexdec( substr( $hex_color, $add, $add ) ) * $add_on + $aa ) / 255, 6 );
		$blue       = round( ( hexdec( substr( $hex_color, ( $add + $add ) , $add ) ) * $add_on + $aa ) / 255, 6 );

		$hsl_color  = array( 'hue' => 0, 'sat' => 0, 'lum' => 0 );

		$minimum     = min( $red, $green, $blue );
		$maximum     = max( $red, $green, $blue );

		$chroma   = $maximum - $minimum;

		$hsl_color['lum'] = ( $minimum + $maximum ) / 2;

		if( $chroma == 0 ) {
			$hsl_color['lum'] = round( $hsl_color['lum'] * 100, 0 );

			return $hsl_color;
		}

		$range = $chroma * 6;

		$hsl_color['sat'] = $hsl_color['lum'] <= 0.5 ? $chroma / ( $hsl_color['lum'] * 2 ) : $chroma / ( 2 - ( $hsl_color['lum'] * 2 ) );

		if( $red <= 0.004 || 
			$green <= 0.004 || 
			$blue <= 0.004 
		) {
			$hsl_color['sat'] = 1;
		}

		if( $maximum == $red ) {
			$hsl_color['hue'] = round( ( $blue > $green ? 1 - ( abs( $green - $blue ) / $range ) : ( $green - $blue ) / $range ) * 255, 0 );
		} else if( $maximum == $green ) {
			$hsl_color['hue'] = round( ( $red > $blue ? abs( 1 - ( 4 / 3 ) + ( abs ( $blue - $red ) / $range ) ) : ( 1 / 3 ) + ( $blue - $red ) / $range ) * 255, 0 );
		} else {
			$hsl_color['hue'] = round( ( $green < $red ? 1 - 2 / 3 + abs( $red - $green ) / $range : 2 / 3 + ( $red - $green ) / $range ) * 255, 0 );
		}

		$hsl_color['sat'] = round( $hsl_color['sat'] * 100, 0 );
		$hsl_color['lum']  = round( $hsl_color['lum'] * 100, 0 );

		return $hsl_color;
	}
}

/**
 * Get theme option value
 * @param  string $theme_option ID of theme option
 * @return string               Value of theme option
 */
if( ! function_exists( 'fusion_get_theme_option' ) ) {
	function fusion_get_theme_option( $theme_option ) {
		global $smof_data;

		if( $theme_option && isset( $smof_data[ $theme_option ] ) ) {
			return $smof_data[ $theme_option ];
		}

		return FALSE;
	}
}


/**
 * Get page option value
 * @param  string  $page_option ID of page option
 * @param  integer $post_id     Post/Page ID
 * @return string               Value of page option
 */
if( ! function_exists( 'fusion_get_page_option' ) ) {
	function fusion_get_page_option( $page_option, $post_id ) {
		if( $page_option && 
			$post_id 
		) {
			return get_post_meta( $post_id, 'pyre_' . $page_option, true );
		}

		return FALSE;
	}
}

/**
 * Get theme option or page option
 * @param  string  $theme_option Theme option ID
 * @param  string  $page_option  Page option ID
 * @param  integer $post_id      Post/Page ID
 * @return string                Theme option or page option value
 */
if( ! function_exists( 'fusion_get_option' ) ) {
	function fusion_get_option( $theme_option, $page_option, $post_id ) {
		if ( $theme_option && 
			 $page_option && 
			 $post_id
		) {
			$page_option = strtolower( fusion_get_page_option( $page_option, $post_id ) );
			$theme_option = strtolower( fusion_get_theme_option( $theme_option ) );

			if ( $page_option != 'default' && 
				 ! empty ( $page_option ) 
			) {
				return $page_option;
			} else {
				return $theme_option;
			}
		}

		return FALSE;
	}
}

/**
 * Compress CSS
 * @param  string $minify CSS to compress
 * @return string         Compressed CSS
 */
if( ! function_exists( 'fusion_compress_css' ) ) {
	function fusion_compress_css( $minify ) {
		/* remove comments */
		$minify = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $minify );

		/* remove tabs, spaces, newlines, etc. */
		$minify = str_replace( array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $minify );
			
		return $minify;
	}
}

if ( ! function_exists( 'fusion_get_post_content' ) ) {
	/**
	 * Return the post content, either excerpted or in full length
	 * @param  string	$page_id		The id of the current page or post
	 * @param  string 	$excerpt		Can be either 'blog' (for main blog page), 'portfolio' (for portfolio page template) or 'yes' (for shortcodes)
	 * @param  integer	$excerpt_length Length of the excerpts
	 * @param  boolean	$strip_html		Can be used by shortcodes for a custom strip html setting
	 *
	 * @return string Post content
	 **/
	function fusion_get_post_content( $page_id = '', $excerpt = 'blog', $excerpt_length = 55, $strip_html = FALSE ) {
		global $smof_data;
		
		$content_excerpted = FALSE;
		
		// Main blog page
		if ( $excerpt == 'blog' ) {
		
			// Check if the content should be excerpted
			if ( strtolower( fusion_get_theme_option( 'content_length' ) ) == 'excerpt' ) {
				$content_excerpted = TRUE;
				
				// Get the excerpt length
				$excerpt_length = fusion_get_theme_option( 'excerpt_length_blog' );
			}
		
			// Check if HTML should be stripped from contant
			if ( fusion_get_theme_option( 'strip_html_excerpt' ) ) {
				$strip_html = TRUE; 
			}
		
		// Portfolio page templates
		} elseif ( $excerpt == 'portfolio' ) {
		
			// Check if the content should be excerpted
			if ( fusion_get_option( 'portfolio_content_length', 'portfolio_content_length', $page_id ) == 'excerpt' ) {
				$content_excerpted = TRUE;
				
				// Determine the correct excerpt length
				if ( fusion_get_page_option( 'portfolio_excerpt', $page_id ) ) {
					$excerpt_length = fusion_get_page_option( 'portfolio_excerpt', $page_id );
				} else {
					$excerpt_length =  fusion_get_theme_option( 'excerpt_length_portfolio' );
				}
			}
		
			// Check if HTML should be stripped from contant
			if ( fusion_get_theme_option( 'portfolio_strip_html_excerpt' ) ) {
				$strip_html = TRUE; 
			}
		// Shortcodes
		} elseif( $excerpt == 'yes' ) {			
			$content_excerpted = TRUE;
		}

		// Return excerpted content					
		if ( $content_excerpted ) {
			$stripped_content = fusion_get_post_content_excerpt( $excerpt_length, $strip_html );

			return $stripped_content;
		
		// Return full content
		} else {
			ob_start();
			the_content();

			return ob_get_clean();
		}
	}
}

if ( ! function_exists( 'fusion_get_post_content_excerpt' ) ) {
	/**
	 * Do the actual custom excerpting for of post/page content
	 * @param  string 	$limit 		Maximum number of words or chars to be displayed in excerpt
	 * @param  boolean 	$strip_html Set to TRUE to strip HTML tags from excerpt
	 *
	 * @return string 				The custom excerpt
	 **/
	function fusion_get_post_content_excerpt( $limit, $strip_html ) {
		global $smof_data, $more;

		$content = '';
		
		$limit = (int) $limit;
		
		// If excerpt length is set to 0, return empty
		if ( $limit === 0 ) {
			return $content;
		}

		// Set a default excerpt limit if none is set
		if ( ! $limit && 
			$limit != 0
		) {
			$limit = 285;		
		}

		// Make sure $strip_html is a boolean
		if ( $strip_html == "true" || 
			$strip_html == TRUE
		) {
			$strip_html = TRUE;
		} else {
			$strip_html = FALSE;
		}

		$custom_excerpt = FALSE;

		$post = get_post( get_the_ID() );

		// Check if the more tag is used in the post
		$pos = strpos( $post->post_content, '<!--more-->' );

		// Check if the read more [...] should link to single post
		$read_more_text = apply_filters( 'avada_blog_read_more_excerpt', '&#91;...&#93;' );
		
		if ( $smof_data['link_read_more'] ) {
			$read_more = sprintf( ' <a href="%s">%s</a>', get_permalink( get_the_ID() ), $read_more_text );
		} else {
			$read_more = ' ' . $read_more_text;
		}

		if ( $smof_data['disable_excerpts'] ) {
			$read_more = '';
		}

		// HTML tags should be stripped
		if ( $strip_html ) {	
			$more = 0;
			$raw_content = strip_tags( get_the_content( $read_more ), '<p>' );
			
			// Strip out all attributes
			$raw_content = preg_replace('/<(\w+)[^>]*>/', '<$1>', $raw_content);
			
			if ( $post->post_excerpt || 
				$pos !== FALSE
			) {
				$more = 0;
				if ( ! $pos ) {
					$raw_content = strip_tags( rtrim( get_the_excerpt(), '[&hellip;]' ) . $read_more, '<p>' );
				}
				$custom_excerpt = TRUE;
			}
		// HTML tags remain in excerpt
		} else {	
			$more = 0;
			$raw_content = get_the_content( $read_more );
			if ( $post->post_excerpt ||
				$pos !== FALSE
			) {
				$more = 0;
				if ( ! $pos ) {
					$raw_content = rtrim( get_the_excerpt(), '[&hellip;]' ) . $read_more;
				}
				$custom_excerpt = TRUE;
			}
		}

		// We have our raw post content and need to cut it down to the excerpt limit
		if ( $raw_content && 
			$custom_excerpt == FALSE
		) {
			$pattern = get_shortcode_regex();
			$content = preg_replace_callback("/$pattern/s", 'avada_process_tag', $raw_content);

			// Check if the excerpting should be char or word based
			if ( $smof_data['excerpt_base'] == 'Characters' ) {
				$content = mb_substr($content, 0, $limit);
				if ( $limit != 0 && 
					! $smof_data['disable_excerpts']
				) {
					$content .= $read_more;
				}
			// Excerpting is word based
			} else {
				$content = explode( ' ', $content, $limit + 1 );
				if ( count( $content ) > $limit ) {
					array_pop( $content );
					if ( $smof_data['disable_excerpts'] ) {
						$content = implode( ' ', $content );
					} else {
						$content = implode( ' ', $content);
						if ( $limit != 0 ) {
							if ( $smof_data['link_read_more'] ) {
								$content .= $read_more;
							} else {
								$content .= $read_more;
							}
						}
					}
				} else {
					$content = implode( ' ', $content );
				}
			}

			if ( $limit != 0 ) {
				$content = preg_replace( '~(?:\[/?)[^/\]]+/?\]~s', '', $content ); // strip shortcode and keep the content
				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );
			}

			$content = do_shortcode( $content );

			return $content;
		}

		// If we have a custom excerpt, e.g. using the <!--more--> tag
		if ( $custom_excerpt == TRUE ) {
			$pattern = get_shortcode_regex();
			$content = preg_replace_callback( '/$pattern/s', 'avada_process_tag', $raw_content );		
			if ( $strip_html == TRUE ) {
				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );
				$content = do_shortcode( $content );
			} else {
				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );
			}
		}

		// If the custom excerpt field is used, just use that contents
		if ( has_excerpt() ) {
			$content = do_shortcode( get_the_excerpt() );
		}

		return $content;
	}
}

/**
 * Get attachment data by URL
 * @param  string 	$image_url 		The Image URL
 *
 * @return array 					Image Details
 **/
if( ! function_exists( 'fusion_get_attachment_data_by_url' ) ) {
	function fusion_get_attachment_data_by_url( $image_url, $logo_field = '' ) {
		global $wpdb, $smof_data;

		$attachment = $wpdb->get_col( $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url ) );

		if( $attachment ) {
			return wp_get_attachment_metadata( $attachment[0] );
		} else { // import the image to media library 
			$import_image = fusion_import_to_media_library( $image_url, $logo_field );
			if( $import_image ) {
				return wp_get_attachment_metadata( $import_image );
			} else {
				return false;
			}
		}
	}
}

if( ! function_exists( 'fusion_import_to_media_library' ) ) {
	function fusion_import_to_media_library( $url, $theme_option = '' ) {
		global $smof_data;

		// gives us access to the download_url() and wp_handle_sideload() functions
		require_once(ABSPATH . 'wp-admin/includes/file.php');

		$timeout_seconds = 30;

		// download file to temp dir
		$temp_file = download_url( $url, $timeout_seconds );

		if( ! is_wp_error( $temp_file ) ) {
			// array based on $_FILE as seen in PHP file uploads
			$file = array(
				'name' => basename( $url ), // ex: wp-header-logo.png
				'type' => 'image/png',
				'tmp_name' => $temp_file,
				'error' => 0,
				'size' => filesize( $temp_file ),
			);

			$overrides = array(
				// tells WordPress to not look for the POST form
				// fields that would normally be present, default is true,
				// we downloaded the file from a remote server, so there
				// will be no form fields
				'test_form' => false,

				// setting this to false lets WordPress allow empty files, not recommended
				'test_size' => true,

				// A properly uploaded file will pass this test. 
				// There should be no reason to override this one.
				'test_upload' => true, 
			);

			// move the temporary file into the uploads directory
			$results = wp_handle_sideload( $file, $overrides );

			if ( ! empty( $results['error'] ) ) {
				return false;
			} else {
				$attachment = array(
					'guid'           => $results['url'], 
					'post_mime_type' => $results['type'],
					'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $results['file'] ) ),
					'post_content'   => '',
					'post_status'    => 'inherit'
				);

				// Insert the attachment.
				$attach_id = wp_insert_attachment( $attachment, $results['file'] );

				// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
				require_once( ABSPATH . 'wp-admin/includes/image.php' );

				// Generate the metadata for the attachment, and update the database record.
				$attach_data = wp_generate_attachment_metadata( $attach_id, $results['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );

				if( $theme_option ) {
					$smof_data[ $theme_option ] = $results['url'];
					update_option( OPTIONS, $smof_data );
				}
				
				return $attach_id;
			}
		} else {
			return false;
		}
	}
}