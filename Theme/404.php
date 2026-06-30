<?php
/**
 * The template for displaying 404 pages (Not Found)
 */

get_header(); ?>
<!-- BEGIN of 404 page -->
	<div class="row col  not-found">
        <div class="text-center">
		<h1><?php _e('404: Таку сторінку ми ще НЕ створили', 'foundation'); ?></h1>
		<h3><?php _e('Продовщуйте шукати...', 'foundation'); ?></h3>
		<p><?php printf(__('Перевірте УРЛ ще раз, або перейдіть на <a class="label" href="%1s">Домашню сторінку</a>', 'foundation'), get_bloginfo('url')); ?></p>
        </div>
	</div>
<!-- END of 404 page -->
<?php get_footer(); ?>