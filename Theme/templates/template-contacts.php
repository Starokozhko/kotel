<?php
/**
 * Template Name: Контакти
 * Template Post Type: page
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

if (!function_exists('lita_render_contacts_gravity_form')) {
    function lita_render_contacts_gravity_form($form)
    {
        if (empty($form)) {
            return;
        }

        if (is_string($form) && strpos($form, '[gravityform') !== false) {
            echo do_shortcode($form);
            return;
        }

        $form_id = 0;

        if (is_numeric($form)) {
            $form_id = absint($form);
        } elseif (is_array($form)) {
            if (!empty($form['id'])) {
                $form_id = absint($form['id']);
            } elseif (!empty($form['ID'])) {
                $form_id = absint($form['ID']);
            } elseif (!empty($form['form_id'])) {
                $form_id = absint($form['form_id']);
            }
        } elseif (is_object($form)) {
            if (!empty($form->id)) {
                $form_id = absint($form->id);
            } elseif (!empty($form->ID)) {
                $form_id = absint($form->ID);
            }
        }

        if ($form_id > 0 && function_exists('gravity_form')) {
            gravity_form($form_id, false, false, false, null, true);
        }
    }
}
?>
<style>
    .gform-footer input[type="submit"]{
        padding: 10px 40px;
        border-radius: 8px;
        font-weight: 700;
        background-color: #0f4b90;
        color: #fff;
        border-color: #0f4b90;
    }
    .gform-footer input[type="submit"]:hover {
        background-color: #fff;
        color: #0f4b90;
    }
    .gform_confirmation_message {
        font-weight: 700;
        font-size: 26px;
        color: #047d04;
        text-align: center;
    }
</style>
<main class="main-content contacts-page">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col">
                <?php if (have_posts()) : ?>
                    <?php while (have_posts()) : the_post(); ?>
                        <article <?php post_class('contacts-page__article'); ?>>
                            <h1 class="page-title" style="margin-top: 0;"><?php the_title(); ?></h1>

                            <div class="contacts-page__content">
                                <?php the_content(); ?>
                            </div>

                            <?php
                            $contact_form = function_exists('get_field') ? get_field('contact_form') : null;

                            if (empty($contact_form) && function_exists('get_field')) {
                                $contact_form = get_field('contact_form');
                            }
                            ?>

                            <?php if (!empty($contact_form)) : ?>
                                <div class="contacts-page__form" style="padding: 40px 0;">
                                    <?php lita_render_contacts_gravity_form($contact_form); ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php get_footer(); ?>
