(function () {
    function setButtonState(button, isAvailable) {
        if (!button) {
            return;
        }

        button.hidden = !isAvailable;
        button.disabled = !isAvailable;
        button.setAttribute('aria-hidden', isAvailable ? 'false' : 'true');
    }

    function initNativeSlider(section) {
        var track = section.querySelector('.swiper-wrapper');
        var prev = section.querySelector('.yp-category-slider__prev');
        var next = section.querySelector('.yp-category-slider__next');

        if (!track || (!prev && !next)) {
            return;
        }

        function getStep() {
            return Math.max(track.clientWidth * 0.85, 240);
        }

        function updateNavigation() {
            var maxScrollLeft = Math.max(0, track.scrollWidth - track.clientWidth);
            var current = track.scrollLeft;
            var tolerance = 2;

            setButtonState(prev, current > tolerance);
            setButtonState(next, current < maxScrollLeft - tolerance);
        }

        if (prev) {
            prev.addEventListener('click', function () {
                track.scrollBy({
                    left: -getStep(),
                    behavior: 'smooth'
                });
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                track.scrollBy({
                    left: getStep(),
                    behavior: 'smooth'
                });
            });
        }

        track.addEventListener('scroll', updateNavigation, { passive: true });
        window.addEventListener('resize', updateNavigation);
        updateNavigation();
        window.setTimeout(updateNavigation, 250);
    }

    function initSwiperSlider(section) {
        var slider = section.querySelector('.yp-category-slider__slider');
        var prev = section.querySelector('.yp-category-slider__prev');
        var next = section.querySelector('.yp-category-slider__next');

        if (!slider || typeof window.Swiper === 'undefined') {
            initNativeSlider(section);
            return;
        }

        var swiper = new window.Swiper(slider, {
            slidesPerView: 1.2,
            spaceBetween: 20,
            watchOverflow: true,
            navigation: {
                prevEl: prev,
                nextEl: next
            },
            breakpoints: {
                768: {
                    slidesPerView: 3
                },
                1200: {
                    slidesPerView: 5
                }
            }
        });

        function updateNavigation() {
            var visibleSlides = typeof swiper.params.slidesPerView === 'number' ? swiper.params.slidesPerView : 1;
            var locked = swiper.isLocked || swiper.slides.length <= visibleSlides;

            setButtonState(prev, !locked && !swiper.isBeginning);
            setButtonState(next, !locked && !swiper.isEnd);
        }

        swiper.on('init slideChange resize breakpoint lock unlock reachBeginning reachEnd fromEdge', updateNavigation);
        updateNavigation();
        window.setTimeout(updateNavigation, 250);
    }

    function initCategorySliders() {
        document.querySelectorAll('.yp-category-slider').forEach(initSwiperSlider);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCategorySliders);
    } else {
        initCategorySliders();
    }
}());