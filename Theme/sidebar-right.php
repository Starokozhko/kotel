<?php
/**
 * Sidebar Right
 */
?>

<div class="col-12 col-md-4">
    <?php if ( is_active_sidebar( 'bootstrap_sidebar_right' ) ) : ?>
        <aside class="site-sidebar site-sidebar--right" style="padding: 10px 5px;">
            <?php dynamic_sidebar( 'bootstrap_sidebar_right' ); ?>
        </aside>
    <?php endif; ?>
</div>