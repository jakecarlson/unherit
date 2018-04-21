<?php
/**
 * Destination Home Page
 *
 */

// This template includes built-in layout containers.
add_filter('theme_template_has_layout', function(){ return true; });

// Helpers
$dest = $post;
$settings = get_destination_settings();

// Check for content sections
$sub_nav_items = destination_sub_navigation( false ); // only return

get_header(); 

		get_template_part( 'templates/parts/destinations-sub-nav' ); ?>

		<!-- Main Section
		================================================== -->
        <section class="main">
			<div class="container">
				<div class="row">

					<div class="col-sm-12">
					<?php


					// Start the WP loop
					while ( have_posts() ) : the_post(); ?>

<!--						<div class="intro">-->
<!--							<p class="lead">--><?php //echo get_destination_intro(); ?><!--</p>-->
<!--							<div class="entry-content">--><?php //the_content(); ?><!--</div>-->
<!--						</div>-->

                         <?php require_once('single-destination_sites.php'); ?>
                         <?php require_once('single-destination_articles.php'); ?>

					<?php endwhile; // end of the loop. ?>

					</div>

				</div><!-- /.row -->
			</div>
		</section>

<?php get_footer(); ?>