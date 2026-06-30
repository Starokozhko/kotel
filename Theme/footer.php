<?php
/**
 * Footer
 */
?>

<!-- BEGIN of footer -->
<footer class="footer ">
    <div class="container">
        <div class="row footer__header">
            <div class="col-lg-12 col-md-12 col-sm-12 col">
                <?php
                if (has_nav_menu('footer-menu')) {
                    wp_nav_menu(array('theme_location' => 'footer-menu', 'menu_class' => 'footer-menu', 'depth' => 1));
                }
                //?>
            </div>
            <div class="col-12">
                <?php if( $workHours = get_field('work_hours','option') ): ?>
                  <div class="footer__content"><?= $workHours; ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="row footer__bottom">
            <?php if( $copyright = get_field('copyright','option') ): ?>
              <p>© <?= date('Y'); ?> <?= $copyright ?></p>
            <?php endif; ?>
            <?php if( $marketing_by = get_field('marketing_by','option') ): ?>
              <p><?= $marketing_by; ?></p>
            <?php endif; ?>
        </div>
    </div>
</footer>
<!-- END of footer -->

<?php wp_footer(); ?>
</body>
</html>
