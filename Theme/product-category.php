<?php
/**
 * Template Name: Категорії товарів
 */

get_header();
?>

    <div class="container">
        <h1 class="page-title">Наші товари</h1>

        <?php
        // Виводимо 20 товарів (4 в рядок × 5 рядків)
        echo do_shortcode('[products limit="20" columns="4"]');
        ?>
    </div>

<?php
get_footer();
