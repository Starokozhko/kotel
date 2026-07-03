(function () {
    'use strict';

    var root = document.querySelector('.yp-single-listing');

    if (!root) {
        return;
    }

    var mainImage = root.querySelector('[data-yp-gallery-main]');
    var mainLink = root.querySelector('[data-yp-gallery-link]');
    var thumbs = Array.prototype.slice.call(root.querySelectorAll('[data-yp-gallery-thumb]'));
    var prevButton = root.querySelector('[data-yp-gallery-prev]');
    var nextButton = root.querySelector('[data-yp-gallery-next]');
    var activeIndex = 0;

    function setActiveThumb(index) {
        thumbs.forEach(function (thumb, thumbIndex) {
            var isActive = thumbIndex === index;
            thumb.classList.toggle('is-active', isActive);
            thumb.setAttribute('aria-current', isActive ? 'true' : 'false');
        });
    }

    function showImage(index) {
        if (!mainImage || !thumbs[index]) {
            return;
        }

        var thumb = thumbs[index];
        activeIndex = index;
        mainImage.src = thumb.getAttribute('data-large') || mainImage.src;
        mainImage.alt = thumb.getAttribute('data-alt') || '';

        if (mainLink) {
            mainLink.href = thumb.getAttribute('data-full') || mainLink.href;
        }

        setActiveThumb(index);
    }

    thumbs.forEach(function (thumb, index) {
        thumb.addEventListener('click', function () {
            showImage(index);
        });
    });

    if (prevButton) {
        prevButton.addEventListener('click', function () {
            var nextIndex = activeIndex - 1;

            if (nextIndex < 0) {
                nextIndex = thumbs.length - 1;
            }

            showImage(nextIndex);
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            var nextIndex = activeIndex + 1;

            if (nextIndex >= thumbs.length) {
                nextIndex = 0;
            }

            showImage(nextIndex);
        });
    }
    function getFancyboxItems() {
        if (thumbs.length) {
            return thumbs.map(function (thumb) {
                return {
                    src: thumb.getAttribute('data-full') || thumb.getAttribute('data-large') || '',
                    type: 'image',
                    opts: {
                        caption: thumb.getAttribute('data-alt') || '',
                        thumb: thumb.querySelector('img') ? thumb.querySelector('img').src : ''
                    }
                };
            }).filter(function (item) {
                return item.src !== '';
            });
        }

        if (mainLink) {
            return [{
                src: mainLink.href,
                type: 'image',
                opts: {
                    caption: mainImage ? mainImage.alt : '',
                    thumb: mainImage ? mainImage.src : ''
                }
            }];
        }

        return [];
    }

    function openFancyboxGallery(event) {
        var fancybox = window.jQuery && window.jQuery.fancybox;
        var items = getFancyboxItems();

        if (!fancybox || !items.length) {
            return;
        }

        event.preventDefault();
        fancybox.open(items, {
            loop: items.length > 1,
            buttons: [
                'zoom',
                'slideShow',
                'thumbs',
                'close'
            ]
        }, activeIndex);
    }

    if (mainLink) {
        mainLink.addEventListener('click', openFancyboxGallery);
    }

    function getCookie(name) {
        var parts = document.cookie ? document.cookie.split('; ') : [];

        for (var i = 0; i < parts.length; i += 1) {
            var pair = parts[i].split('=');

            if (decodeURIComponent(pair[0]) === name) {
                return decodeURIComponent(pair.slice(1).join('='));
            }
        }

        return '';
    }

    function setCookie(name, value, maxAge) {
        document.cookie = encodeURIComponent(name) + '=' + encodeURIComponent(value) + '; path=/; max-age=' + maxAge + '; SameSite=Lax';
    }

    var listingId = parseInt(root.getAttribute('data-listing-id'), 10);

    if (listingId > 0) {
        var recent = getCookie('yp_recently_viewed')
            .split(',')
            .map(function (value) {
                return parseInt(value, 10);
            })
            .filter(function (value, index, arr) {
                return value > 0 && value !== listingId && arr.indexOf(value) === index;
            });

        recent.unshift(listingId);
        setCookie('yp_recently_viewed', recent.slice(0, 10).join(','), 60 * 60 * 24 * 30);
    }
}());