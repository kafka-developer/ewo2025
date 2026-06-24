<?php
/**
 * v0.1 full-article extractor.
 *
 * Fetches the original article page and pulls out readable body text. No
 * summarizing, rewriting, or ranking — it strips chrome (script/style/nav/
 * footer/header/aside/…), prefers an <article> element, and otherwise falls
 * back to the largest paragraph-dense block, preserving paragraph order.
 *
 * Defensive by design: missing DOMDocument, fetch failures, or odd markup
 * degrade to an empty result rather than fatally erroring.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Readable-content extraction.
 */
class EWO_RSS_Article_Extractor {

	/* Tags removed before extraction. */
	const STRIP_TAGS = array( 'script', 'style', 'nav', 'footer', 'header', 'aside', 'form', 'noscript', 'iframe', 'svg', 'figure', 'figcaption' );

	/* Minimum characters for a paragraph to count as body text. */
	const MIN_PARAGRAPH_CHARS = 40;

	/**
	 * Fetch and extract an article.
	 *
	 * @param string $url Article URL.
	 * @return array{content:string,source_domain:string,ok:bool}
	 */
	public static function extract( $url ) {
		$url    = trim( (string) $url );
		$result = array(
			'content'       => '',
			'source_domain' => self::domain( $url ),
			'ok'            => false,
		);

		if ( '' === $url ) {
			return $result;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 15,
				'redirection' => 5,
				'user-agent'  => 'Mozilla/5.0 (compatible; EWO-RSS-Engine/1.0; +https://emergingworldorder.com)',
				'headers'     => array( 'Accept' => 'text/html,application/xhtml+xml' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $result;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 400 ) {
			return $result;
		}

		$body = (string) wp_remote_retrieve_body( $response );
		if ( '' === trim( $body ) ) {
			return $result;
		}

		$content           = self::extract_from_html( $body );
		$result['content'] = $content;
		$result['ok']      = '' !== $content;

		return $result;
	}

	/**
	 * Extract readable paragraphs from an HTML document.
	 *
	 * @param string $html Raw HTML.
	 * @return string Sanitized `<p>`-wrapped content, or empty string.
	 */
	public static function extract_from_html( $html ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return '';
		}

		$previous = libxml_use_internal_errors( true );
		$doc      = new DOMDocument();

		// Hint UTF-8 so multibyte text survives parsing.
		$loaded = $doc->loadHTML( '<?xml encoding="UTF-8">' . $html );

		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			return '';
		}

		self::strip_chrome( $doc );

		$container = self::preferred_container( $doc );
		if ( ! $container ) {
			return '';
		}

		$paragraphs = self::paragraphs( $container );

		if ( empty( $paragraphs ) ) {
			$text = self::clean_text( $container->textContent );
			return '' !== $text ? '<p>' . esc_html( $text ) . '</p>' : '';
		}

		$out = '';
		foreach ( $paragraphs as $text ) {
			$out .= '<p>' . esc_html( $text ) . "</p>\n";
		}

		return $out;
	}

	/**
	 * Remove non-content chrome elements in place.
	 *
	 * @param DOMDocument $doc Document.
	 */
	protected static function strip_chrome( DOMDocument $doc ) {
		foreach ( self::STRIP_TAGS as $tag ) {
			$nodes = $doc->getElementsByTagName( $tag );
			// Iterate backwards: the NodeList is live and shrinks as we remove.
			for ( $i = $nodes->length - 1; $i >= 0; $i-- ) {
				$node = $nodes->item( $i );
				if ( $node && $node->parentNode ) {
					$node->parentNode->removeChild( $node );
				}
			}
		}
	}

	/**
	 * Choose the content container: first <article>, else the largest readable
	 * block, else <body>.
	 *
	 * @param DOMDocument $doc Document.
	 * @return DOMElement|null
	 */
	protected static function preferred_container( DOMDocument $doc ) {
		$articles = $doc->getElementsByTagName( 'article' );
		if ( $articles->length > 0 && self::paragraph_chars( $articles->item( 0 ) ) >= self::MIN_PARAGRAPH_CHARS ) {
			return $articles->item( 0 );
		}

		$largest = self::largest_text_block( $doc );
		if ( $largest ) {
			return $largest;
		}

		$body = $doc->getElementsByTagName( 'body' );
		return $body->length > 0 ? $body->item( 0 ) : null;
	}

	/**
	 * Find the block element with the most paragraph text.
	 *
	 * @param DOMDocument $doc Document.
	 * @return DOMElement|null
	 */
	protected static function largest_text_block( DOMDocument $doc ) {
		$best     = null;
		$best_len = 0;

		foreach ( array( 'main', 'section', 'div' ) as $tag ) {
			$nodes = $doc->getElementsByTagName( $tag );
			foreach ( $nodes as $node ) {
				$len = self::paragraph_chars( $node );
				if ( $len > $best_len ) {
					$best_len = $len;
					$best     = $node;
				}
			}
		}

		return $best_len >= self::MIN_PARAGRAPH_CHARS ? $best : null;
	}

	/**
	 * Total character length of direct/descendant paragraph text in a node.
	 *
	 * @param DOMNode $node Node.
	 * @return int
	 */
	protected static function paragraph_chars( $node ) {
		if ( ! $node instanceof DOMElement ) {
			return 0;
		}
		$len = 0;
		foreach ( $node->getElementsByTagName( 'p' ) as $p ) {
			$len += strlen( trim( $p->textContent ) );
		}
		return $len;
	}

	/**
	 * Collect ordered paragraph text from a container.
	 *
	 * @param DOMElement $container Container element.
	 * @return string[]
	 */
	protected static function paragraphs( DOMElement $container ) {
		$paragraphs = array();
		foreach ( $container->getElementsByTagName( 'p' ) as $p ) {
			$text = self::clean_text( $p->textContent );
			if ( strlen( $text ) >= self::MIN_PARAGRAPH_CHARS ) {
				$paragraphs[] = $text;
			}
		}
		return $paragraphs;
	}

	/**
	 * Collapse whitespace and trim.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	protected static function clean_text( $text ) {
		return trim( preg_replace( '/\s+/', ' ', (string) $text ) );
	}

	/**
	 * Host (minus www.) for a URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function domain( $url ) {
		$host = wp_parse_url( (string) $url, PHP_URL_HOST );
		if ( ! $host ) {
			return '';
		}
		return preg_replace( '#^www\.#', '', strtolower( $host ) );
	}
}
