<?php
/**
 * Index
 *
 * Standard loop for the front-page
 */
get_header(); ?>

  <div class="container">
    <div class="row">
      <!-- BEGIN of main content -->
      <div class="col-lg-8 col-md-8 col-sm-12 col">
        <main class="main-content">
          <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); ?>
              <?php the_content(); // Post item ?>
            <?php endwhile; ?>
          <?php endif; ?>

        </main>
      </div>
      <!-- END of main content -->
      <!-- BEGIN of sidebar -->
      <div class="col-lg-4 col-md-4 col-sm-12 columns sidebar">
        <?php get_sidebar('right'); ?>

      </div>
      <!-- END of sidebar -->
    </div>
  </div>

<?php get_footer(); ?>