<?php
/**
 * Header
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <!-- Set up Meta -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta charset="<?php bloginfo('charset'); ?>">

    <!-- Set the viewport width to device width for mobile -->
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">

    <!-- Add Google Fonts -->
<!--    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400italic,400,300,600,700,800' rel='stylesheet'-->
<!--          type='text/css'>-->

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Sora:wght@100..800&display=swap" rel="stylesheet">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-9KR9HKWZQB"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        gtag('config', 'G-9KR9HKWZQB');
    </script>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-7928817515623617"
            crossorigin="anonymous"></script>

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<!-- BEGIN of header -->
<header class="header ">
    <?php
    $ticker_html = do_shortcode('[ads_banner location="top-header" type="ticker" banner_type="ticker"]');

    if (!empty(trim($ticker_html))) : ?>
        <div class="header__top">
            <?php echo $ticker_html; ?>
        </div>
    <?php endif; ?>
    <div class="header__main">
        <div class="container">
            <div class="row medium-uncollapse small-collapse header__wrapper">
                <div class="col-12 col-md-3 columns">
                    <div class="logo text-center medium-text-left">
                        <div class="logo text-center medium-text-left">
                            <?php show_custom_logo(); ?>
                        </div>

                    </div>
                </div>
                <div class="col-9 col-md-5 col-lg-8 columns mob-header-order text-center">

                    <?php echo do_shortcode('[kotelva_air_alert]'); ?>
                    <nav class="navbar navbar-expand-md">
                        <div class="navbar-header">
                            <button type="button" class="navbar-toggler" data-toggle="collapse"
                                    data-target="#main-menu-links" aria-expanded="false">
                                <span class="navbar-toggler-title">Menu</span>
                            </button>
                        </div>
                        <div class="collapse navbar-collapse" id="main-menu-links">
                            <button type="button" class="navbar-toggler-closed" data-toggle="collapse"
                                    data-target="#main-menu-links" aria-expanded="false" aria-label="Закрити меню">
                                <svg class="burger-close-icon" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                     aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18 6L6 18"
                                          stroke="currentColor"
                                          stroke-width="2"
                                          stroke-linecap="round"
                                          stroke-linejoin="round"/>
                                    <path d="M6 6L18 18"
                                          stroke="currentColor"
                                          stroke-width="2"
                                          stroke-linecap="round"
                                          stroke-linejoin="round"/>
                                </svg>
                                <span class="navbar-toggler-title">Closed</span>
                            </button>
                            <?php
                            if (has_nav_menu('header-menu')) {
                                wp_nav_menu(array(
                                        'menu' => 'primary',
                                        'theme_location' => 'header-menu',
                                        'container' => 'div',
                                        'container_class' => '',
                                        'container_id' => 'main-menu-link',
                                        'menu_class' => 'navbar-nav header-menu',
                                        'fallback_cb' => 'Bootstrap_Navigation::fallback',
                                        'walker' => new Bootstrap_Navigation()
                                ));
                            }
                            ?>
                        </div>
                    </nav>
                </div>
                <div class="col-3 col-md-4 col-lg-1 columns mobile-style-auth-links">

                    <?= do_shortcode('[yp_auth_links]'); ?>


                    <?php if ($tel = get_field('phone', 'option')): ?>
                        <a class="link-icon" href="tel:<?= preparePhone($tel); ?>"><img
                                    src="<?= get_template_directory_uri(); ?>/images/icon-phone.svg" alt=""></a>
                    <?php endif; ?>
                </div>


            </div>
        </div>
    </div>
</header>
<!-- END of header -->
