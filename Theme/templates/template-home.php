<?php
/**
 * Template Name: Home Page
 */
get_header(); ?>


    <!--HOME PAGE SLIDER-->
<?php //home_slider_template(); ?>
    <!--END of HOME PAGE SLIDER-->


    <div class="text-center" style="margin-top: 20px; padding: 0 10px 10px; border-bottom: 10px dashed #fcbb1d">
        <h3 class="text-center">Сайт на стадії розробки</h3>
        <p>Ви можете спробувати <a href="https://kotelva.info/reyestratsiya/">зареєструватися</a> та подати оголошення
        </p>
    </div>


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

    <div class="container ">
        <div class="row">
            <div class="col-12 col-md-8">
                <?php show_template('part-flexible'); ?>
            </div>


            <?php get_sidebar('right'); ?>

            <!--        <div class="col-12 col-md-4">-->

            <!--            --><?php //echo do_shortcode( '[ads_banner location="sidebar" type="single" banner_type="image"]' ); ?>
            <!--        </div>-->

        </div>
    </div>


<?php get_footer(); ?>