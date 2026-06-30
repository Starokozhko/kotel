<?php
/**
 * Page
 */
get_header(); ?>

  <div class="container">
    <div class="row">
      <!-- BEGIN of page content -->
      <div class="col-lg-12 col-md-12 col-sm-12 col">
        <main class="main-content">
          <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); ?>
              <article <?php post_class(); ?>>
                <h1 class="page-title"><?php the_title(); ?></h1>
                <?php if ( has_post_thumbnail() ) : ?>
                  <div title="<?php the_title_attribute(); ?>" class="thumbnail">
                    <?php the_post_thumbnail( 'large' ); ?>
                  </div>
                <?php endif; ?>
                <?php the_content( '', true ); ?>
              </article>
            <?php endwhile; ?>
          <?php endif; ?>
        </main>
      </div>
      <!-- END of page content -->
      <!-- BEGIN of sidebar -->
      <!--		<div class="col-lg-4 .col-md-4 col-sm-12 col sidebar">-->
      <!--			--><?php //get_sidebar('right'); ?>
      <!--		</div>-->
      <!-- END of sidebar -->
    </div>
  </div>

<?php get_footer(); ?>