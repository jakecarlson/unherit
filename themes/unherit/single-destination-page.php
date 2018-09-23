<?php
/**
 * Destination Sub Page
 *
 * The template for displaying destination pages.
 *
 */

require_once('single-destination_setup.php');
?>

<main class="main">
    <div class="container">
        <div class="row">

			<div class="col-md-9 col-sm-12">

			    <div class="row">

			        <div class="col-md-9 col-md-push-3 col-sm-8 col-sm-push-4">

			        	<?php while (have_posts()) : the_post(); ?>

				            <!-- Destination Guide List -->
				            <article class="guide-list">
				                
								<header class="page-header">
									<h1 class="page-title"><?php the_title() ?></h1>
									<?php 
									$intro = get_destination_intro();
									if ( !empty($intro) ) {
										?>
										<p class="lead"><?php echo wp_kses_post($intro); ?></p>
										<?php
									} ?>
								</header>

								<?php 
								// Thumbnail 
								if ( has_post_thumbnail() ) : ?>
									<p class="entry-thumbnail">
										<?php the_post_thumbnail(); ?>
									</p><!-- .entry-thumbnail -->
									<?php
								endif; // has_post_thumbnail ?>
								<div class="entry-content"><?php the_content(); ?></div>
							
				            </article> <!-- /.guide-list -->

			            <?php endwhile; // end of the loop. ?>

			        </div>

			        <div class="col-md-3 col-md-pull-9 col-sm-4 col-sm-pull-8 page-navigation">
			            <?php require_once('single-destination_menu.php'); ?>
			        </div><!-- /.page-navigation -->

			    </div>

			</div>

			<div class="col-md-3 col-sm-12">
			    <?php get_sidebar(); ?>
			</div><!-- /sidebar -->

        </div>
    </div>
</main>

<?php get_footer(); ?>