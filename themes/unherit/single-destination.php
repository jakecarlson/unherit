<?php
// Places (child destinations)
// -------------------------------------------------

require_once('single-destination_setup.php');

$places_query = unherit_get_places_query($dest->ID);

$list = get_guide_lists_by_category($dest->ID, $term_ids, 'Sorted IDs'); // we're only returning a sorted list
?>

<main class="main">
    <div class="container">
        <div class="row">

            <div class="col-md-9 col-sm-12">

                <div class="row">

                    <div class="col-md-9 col-md-push-3 col-sm-8 col-sm-push-4">
                        <div class="clearfix">
                            <?php require_once('single-destination_sites-header.php'); ?>
                        </div>

                        <!-- Destination Guide List -->
                        <section class="guide-list">
                            <?php require_once('single-destination_sites-list.php'); ?>
                        </section> <!-- /.guide-list -->

                    </div><!-- /.page-content -->

                    <div class="col-md-3 col-md-pull-9 col-sm-4 col-sm-pull-8 page-navigation">
                        <?php require_once('single-destination_menu-left.php'); ?>
                    </div><!-- /.page-navigation -->

                </div>

            </div>

            <div class="col-md-3 col-sm-12">
                <?php require_once('single-destination_menu-right.php'); ?>
            </div><!-- /sidebar -->

        </div>
    </div>
</main>
    
<?php get_footer(); ?>