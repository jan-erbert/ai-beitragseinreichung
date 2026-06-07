<?php

defined('ABSPATH') || exit;

/**
 * Laedt die Darstellung fuer Zusatzbild-Slider nur auf passenden Beitraegen.
 */
add_action('wp_enqueue_scripts', function () {
    if (!is_singular('post')) {
        return;
    }

    $post_id = get_queried_object_id();
    $content = $post_id ? get_post_field('post_content', $post_id) : '';

    if (strpos((string) $content, 'beitrag-gallery-slider') === false && strpos((string) $content, 'beitrag-gallery-single') === false) {
        return;
    }

    $version = defined('BEITRAGSEINREICHUNG_VERSION') ? BEITRAGSEINREICHUNG_VERSION : '1.2.4';

    wp_register_style('beitragseinreichung-gallery-slider', false, [], $version);
    wp_enqueue_style('beitragseinreichung-gallery-slider');
    wp_add_inline_style(
        'beitragseinreichung-gallery-slider',
        '
        .beitrag-gallery-single,
        .beitrag-gallery-slider {
            margin: 2rem 0;
        }

        .beitrag-gallery-single__image,
        .beitrag-gallery-slider__image {
            border-radius: 8px;
            display: block;
            height: auto;
            width: 100%;
        }

        .beitrag-gallery-slider {
            position: relative;
        }

        .beitrag-gallery-slider__viewport {
            overflow: hidden;
            position: relative;
        }

        .beitrag-gallery-slider__slide[hidden] {
            display: none;
        }

        .beitrag-gallery-slider__counter {
            background: rgba(0, 0, 0, 0.68);
            border-radius: 999px;
            bottom: 12px;
            color: #fff;
            font-size: 0.875rem;
            line-height: 1;
            padding: 7px 10px;
            position: absolute;
            right: 12px;
        }

        .beitrag-gallery-slider__controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 12px;
        }

        .beitrag-gallery-slider__button {
            align-items: center;
            background: #1d2327;
            border: 0;
            border-radius: 999px;
            color: #fff;
            cursor: pointer;
            display: inline-flex;
            font-size: 28px;
            height: 42px;
            justify-content: center;
            line-height: 1;
            width: 42px;
        }

        .beitrag-gallery-slider__button:hover,
        .beitrag-gallery-slider__button:focus {
            background: #2271b1;
        }
        '
    );

    wp_register_script('beitragseinreichung-gallery-slider', false, [], $version, true);
    wp_enqueue_script('beitragseinreichung-gallery-slider');
    wp_add_inline_script(
        'beitragseinreichung-gallery-slider',
        "
        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-beitrag-slider-action]');
            if (!button) {
                return;
            }

            var slider = button.closest('.beitrag-gallery-slider');
            if (!slider) {
                return;
            }

            var slides = Array.prototype.slice.call(slider.querySelectorAll('.beitrag-gallery-slider__slide'));
            if (!slides.length) {
                return;
            }

            var current = parseInt(slider.getAttribute('data-current-slide') || '0', 10);
            var direction = button.getAttribute('data-beitrag-slider-action') === 'next' ? 1 : -1;
            var next = (current + direction + slides.length) % slides.length;

            slides[current].hidden = true;
            slides[next].hidden = false;
            slider.setAttribute('data-current-slide', String(next));
        });
        "
    );
});
