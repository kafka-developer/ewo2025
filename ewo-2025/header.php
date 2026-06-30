<?php
/**
 * The header for EWO 2025.
 *
 * @package EWO_2025
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'ewo-2025' ); ?></a>

	<header id="masthead" class="site-header">
		<div class="site-header__inner">
			<div class="site-branding">
				<a class="site-branding__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
					<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/ewo-logo.png' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				</a>
				<div class="site-branding__text">
					<?php if ( is_front_page() && is_home() ) : ?>
						<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
					<?php else : ?>
						<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
					<?php endif; ?>

					<?php
					$ewo_2025_description = get_bloginfo( 'description', 'display' );
					if ( $ewo_2025_description || is_customize_preview() ) :
						?>
						<p class="site-description"><?php echo esc_html( $ewo_2025_description ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<div class="site-header__actions">
				<?php if ( ewo_2025_feature_enabled( 'header_chips' ) ) { ewo_2025_render_header_chips(); } ?>

				<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Primary menu', 'ewo-2025' ); ?>">
					<button class="menu-toggle" type="button" aria-controls="primary-menu" aria-expanded="false">
						<span class="menu-toggle__bar" aria-hidden="true"></span>
						<span class="menu-toggle__bar" aria-hidden="true"></span>
						<span class="menu-toggle__bar" aria-hidden="true"></span>
						<span class="screen-reader-text"><?php esc_html_e( 'Toggle primary menu', 'ewo-2025' ); ?></span>
					</button>

					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'menu_class'     => 'primary-menu',
							'menu_id'        => 'primary-menu',
							'container'      => false,
							'fallback_cb'    => 'ewo_2025_primary_menu_fallback',
						)
					);
					?>
				</nav>
			</div>
		</div>
	</header>
