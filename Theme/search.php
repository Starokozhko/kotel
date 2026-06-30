<?php
/**
 * Index
 *
 * Standard loop for the search result page
 */
get_header(); ?>

  <!-- BEGIN of search results -->
  <div class="container">
    <div class="row posts-list">
      <main class="main-content">
        <h1 class="page-title"><?php printf( __( 'Search Results for: %s', 'bootstrap' ), '<span>' . esc_html( get_search_query() ) . '</span>' ); ?></h1>
        <?php get_search_form(); ?>
        <?php if ( have_posts() ) : ?>
          <?php while ( have_posts() ) : the_post(); ?>
            <!-- BEGIN of Post -->
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
              <div class="row">
                <?php if ( has_post_thumbnail() ) : ?>
                  <div class="medium-4 columns">
                    <a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                      <?php the_post_thumbnail( 'medium' ); ?>
                    </a>
                  </div>
                <?php endif; ?>
                <div class="<?php echo has_post_thumbnail() ? '	.col-md-8' : ''; ?> col">
                  <h3>
                    <a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'bootstrap' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark">
                      <?php the_title(); ?>
                    </a>
                  </h3>
                  <?php if ( is_sticky() ) : ?>
                    <span class="secondary label"><?php _e( 'Sticky', 'bootstrap' ); ?></span>
                  <?php endif; ?>
                  <?php the_excerpt(); // Use wp_trim_words() instead if you need specific number of words ?>

                  <p class="entry-meta">Written by <?php the_author_posts_link(); ?> on <?php the_time( get_option( 'date_format' ) ); ?></p>
                </div>
              </div>
            </article>
            <!-- END of Post -->
          <?php endwhile; ?>
        <?php else: ?>
          <p><?php _e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'bootstrap' ); ?></p>
        <?php endif; ?>
        <!-- BEGIN of pagination -->
        <?php bootstrap_pagination(); ?>
        <!-- END of pagination -->
      </main>
    </div>
  </div>

  <!-- END of search results -->

<?php get_footer(); ?>