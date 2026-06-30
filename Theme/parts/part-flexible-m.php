<?php if (have_rows('marketiong_sections')): ?>

    <?php while (have_rows('marketiong_sections')): the_row(); ?>


        <?php if (get_row_layout()): ?>
            <?php if (get_sub_field('switcher') == 'on'): ?>

            <?php $post_id = get_the_ID(); ?>
                    <?php show_template('flexible-m/part-' . get_row_layout(), array('postID'=>$post_id)) ?>
            <?php endif; ?>

        <?php endif; ?>
    <?php endwhile; ?>
<?php endif; ?>
