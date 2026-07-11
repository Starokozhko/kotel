<?php
/**
 * Sidebar Three
 */
?>

    <?php if ( is_active_sidebar( 'bootstrap_sidebar_three' ) ) : ?>
        <div class="site-sidebar site-sidebar--three"  aria-label="<?php esc_attr_e('Реклама', 'yellow-paper-classifieds'); ?>">
            <?php dynamic_sidebar( 'bootstrap_sidebar_three' ); ?>
        </div>
    <?php endif; ?>
