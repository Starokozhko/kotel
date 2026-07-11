<?php
/**
 * Template Name: Home Page
 */
get_header(); ?>


    <!--HOME PAGE SLIDER-->
<?php //home_slider_template(); ?>
    <!--END of HOME PAGE SLIDER-->


<!--    <div class="text-center" style="margin-top: 20px; padding: 0 10px 10px; border-bottom: 10px dashed #fcbb1d">-->
<!--        <h3 class="text-center">Сайт на стадії розробки</h3>-->
<!--        <p>Ви можете спробувати <a href="https://kotelva.info/reyestratsiya/">зареєструватися</a> та подати оголошення-->
<!--        </p>-->
<!--    </div>-->


    <!-- BEGIN of main content -->
    <div class="main-content">
        <div class="container">
            <div class=" col">
                <?php if (have_posts()) : ?>
                    <?php while (have_posts()) : the_post(); ?>
                        <?php the_content(); ?>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>


                <?php show_template('part-flexible-m'); ?>

    </div>
    <!-- END of main content -->

<!--    <div class="container ">-->
<!--        <div class="row">-->
<!--            <div class="col-12 col-md-8">-->
<!--                --><?php //show_template('part-flexible'); ?>
<!--            </div>-->
<!--            --><?php //get_sidebar('right'); ?>
<!--        </div>-->
<!--    </div>-->

<?php
$info_strip_sections = function_exists('lita_get_yp_find_yours_info_strip_sections') ? lita_get_yp_find_yours_info_strip_sections() : array();
$info_strip_partial = function_exists('lita_locate_yp_template') ? lita_locate_yp_template('parts/mini-listing-section.php') : locate_template('yellow-paper-classifieds/parts/mini-listing-section.php');
?>

<?php if (!empty($info_strip_sections) && $info_strip_partial) : ?>
    <section class="yp-find-yours-info-strip" style="margin: 40px 0;" aria-label="<?php esc_attr_e('Корисні оголошення', 'yellow-paper-classifieds'); ?>">
        <div class="container">
        <div class="yp-find-yours-info-strip__grid">
            <?php foreach ($info_strip_sections as $mini_section) : ?>
                <?php include $info_strip_partial; ?>
            <?php endforeach; ?>


            <?php get_sidebar('three'); ?>
        </div>
        </div>
    </section>
<?php endif; ?>

<?php get_footer(); ?>