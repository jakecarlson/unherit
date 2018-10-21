<?php
/**
 * The template part for the default header content
 */
?>

<section class="hero <?php echo rf_default_header_class(); ?>" <?php rf_header_styles() ?>>
	<?php

	// Maps in Hero (header)
	$maps = false;
	if (function_exists('show_destination_map') && show_destination_map( get_the_ID())) {
		// Load Maps
		include( 'destinations-maps.php' );
		$maps = true;
	}

	?>

	<div class="bg-overlay" <?php if ($maps) { echo 'style="position:relative;"'; } // for overlay gradient, but no click/drag for maps ?>>
		<div class="container" <?php rf_header_container_styles() ?> >

			<div class="intro-wrap">
			<?php

			$title = get_the_title();
			$content = apply_filters('theme_header_subtitle', '');

			// Clean up
			if (isset($content)) {
				$content = html_entity_decode($content);
				$content = '<p>'.stripslashes($content).'</p>';
			}

			// Filter
			$title   = apply_filters('theme_header_title', $title);
			$content = apply_filters('theme_header_content', $content);

			do_action('before_header_title'); // make accessible to add custom content before title

			// Output the title and content text
			if (!empty($title)) {
				?>
				<h1 class="intro-title"><?php echo wp_kses_post($title); ?></h1>
				<?php
			}

			do_action('after_header_title'); // make accessible to add custom content after title

			if (!empty($content) && $content !== '<p></p>' ) {
				?>
				<div class="intro-text">
					<?php echo wp_kses_post($content); ?>
				</div>
				<?php
			}

			do_action('after_header_intro_text'); // make accessible to add custom content after intro text

			?>
			</div>
		</div>
	</div>
</section>
