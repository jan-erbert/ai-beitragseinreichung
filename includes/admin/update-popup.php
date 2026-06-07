<?php

defined('ABSPATH') || exit;

/**
 * Liefert die aktuelle Plugin-Version fuer Admin-Hinweise.
 */
function beitragseinreichung_get_plugin_version()
{
    return defined('BEITRAGSEINREICHUNG_VERSION') ? BEITRAGSEINREICHUNG_VERSION : '1.2.4';
}

/**
 * Liefert den User-Meta-Schluessel fuer den Versionshinweis.
 */
function beitragseinreichung_get_update_popup_meta_key()
{
    return 'beitragseinreichung_update_popup_seen_' . str_replace('.', '_', beitragseinreichung_get_plugin_version());
}

/**
 * Prueft, ob der aktuelle Admin-Screen zum Plugin gehoert.
 */
function beitragseinreichung_is_plugin_admin_page()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check for conditional admin UI.
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

    return in_array(
        $page,
        [
            'beitragseinreichung',
            'beitragseinreichung_einstellungen',
            'beitragseinreichung_ki_protokoll',
        ],
        true
    );
}

/**
 * Prueft, ob der Versionshinweis auf dieser Seite manuell geoeffnet werden kann.
 */
function beitragseinreichung_is_update_popup_trigger_page()
{
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check for conditional admin UI.
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

    return $page === 'beitragseinreichung_einstellungen' && current_user_can('beitragseinreichung_settings');
}

/**
 * Prueft, ob der aktuelle Nutzer den Hinweis sehen darf.
 */
function beitragseinreichung_user_can_see_update_popup()
{
    return current_user_can('beitragseinreichung_submit') || current_user_can('beitragseinreichung_settings');
}

/**
 * Speichert, dass der aktuelle Nutzer den Versionshinweis gesehen hat.
 */
add_action('wp_ajax_beitragseinreichung_update_popup_dismiss', function () {
    if (!beitragseinreichung_user_can_see_update_popup()) {
        wp_send_json_error('Keine Berechtigung');
    }

    check_ajax_referer('beitragseinreichung_update_popup');

    update_user_meta(
        get_current_user_id(),
        beitragseinreichung_get_update_popup_meta_key(),
        current_time('mysql')
    );

    wp_send_json_success();
});

/**
 * Rendert den Versionshinweis im Plugin-Adminbereich.
 */
add_action('admin_footer', function () {
    if (!beitragseinreichung_is_plugin_admin_page() || !beitragseinreichung_user_can_see_update_popup()) {
        return;
    }

    $user_id = get_current_user_id();
    $meta_key = beitragseinreichung_get_update_popup_meta_key();
    $has_seen = get_user_meta($user_id, $meta_key, true);
    $can_open_manually = beitragseinreichung_is_update_popup_trigger_page();

    if ($has_seen && !$can_open_manually) {
        return;
    }

    $version = beitragseinreichung_get_plugin_version();
    $nonce = wp_create_nonce('beitragseinreichung_update_popup');
    $can_manage_settings = current_user_can('beitragseinreichung_settings');
    $release_lottie_path = plugin_dir_path(dirname(__DIR__, 2) . '/wp-form.php') . 'assets/lottie/release-animation.json';
    $release_lottie_url = plugin_dir_url(dirname(__DIR__, 2) . '/wp-form.php') . 'assets/lottie/release-animation.json';
    ?>
    <div class="beitrag-update-popup" role="dialog" aria-modal="true" aria-labelledby="beitrag-update-popup-title" <?php echo ($has_seen || $can_open_manually) ? 'hidden' : ''; ?>>
        <div class="beitrag-update-popup__panel">
            <button type="button" class="beitrag-update-popup__close" aria-label="<?php echo esc_attr__('Hinweis schliessen', 'ai-beitragseinreichung'); ?>">×</button>

            <div class="beitrag-update-popup__intro">
                <div class="beitrag-update-popup__visual" aria-hidden="true">
                    <?php if (file_exists($release_lottie_path)) : ?>
                        <lottie-player
                            src="<?php echo esc_url($release_lottie_url); ?>"
                            background="transparent"
                            speed="0.85"
                            style="width: 58px; height: 58px;"
                            loop
                            autoplay>
                        </lottie-player>
                    <?php else : ?>
                        <span>✨</span>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="beitrag-update-popup__eyebrow"><?php echo esc_html(sprintf('Release %s', $version)); ?></p>
                    <h2 id="beitrag-update-popup-title"><?php echo esc_html__('Die Beitragseinreichung ist deutlich gewachsen', 'ai-beitragseinreichung'); ?></h2>
                </div>
            </div>

            <p><?php echo esc_html__('Seit der letzten größeren Version ist das Einreichen, Prüfen und Überarbeiten von Beiträgen spürbar angenehmer geworden. Du kannst Inhalte jetzt besser vorbereiten, in Ruhe prüfen und gezielter mit der KI arbeiten.', 'ai-beitragseinreichung'); ?></p>

            <div class="beitrag-update-popup__highlights">
                <div>
                    <strong><?php echo esc_html__('Vorschau vor dem Speichern', 'ai-beitragseinreichung'); ?></strong>
                    <p><?php echo esc_html__('Beiträge können jetzt vor dem finalen Einreichen geprüft und mit einem konkreten Änderungswunsch erneut überarbeitet werden.', 'ai-beitragseinreichung'); ?></p>
                </div>
                <div>
                    <strong><?php echo esc_html__('Bessere KI-Unterstützung', 'ai-beitragseinreichung'); ?></strong>
                    <p><?php echo esc_html__('Titel, Textauszug und Schlagwörter arbeiten nun besser zusammen. KI-Schlagwörter können automatisch vorgeschlagen oder pro Beitrag manuell gepflegt werden.', 'ai-beitragseinreichung'); ?></p>
                </div>
                <div>
                    <strong><?php echo esc_html__('Schönere Bilder und Benachrichtigungen', 'ai-beitragseinreichung'); ?></strong>
                    <p><?php echo esc_html__('Zusatzbilder werden sauberer dargestellt und E-Mails zeigen neue Einreichungen übersichtlicher als Vorschau.', 'ai-beitragseinreichung'); ?></p>
                </div>
                <?php if ($can_manage_settings) : ?>
                    <div>
                        <strong><?php echo esc_html__('Aufgeräumte Einstellungen', 'ai-beitragseinreichung'); ?></strong>
                        <p><?php echo esc_html__('Modelle, Stilgruppen, Benachrichtigungen und Schlagwörter sind klarer organisiert und leichter zu pflegen.', 'ai-beitragseinreichung'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="beitrag-update-popup__actions">
                <a class="button button-primary" href="<?php echo esc_url('https://github.com/jan-erbert/ai-beitragseinreichung/wiki'); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo esc_html__('Wiki öffnen', 'ai-beitragseinreichung'); ?>
                </a>
                <?php if ($can_manage_settings) : ?>
                    <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=beitragseinreichung_einstellungen')); ?>">
                        <?php echo esc_html__('Einstellungen ansehen', 'ai-beitragseinreichung'); ?>
                    </a>
                <?php endif; ?>
                <button type="button" class="button beitrag-update-popup__confirm">
                    <?php echo esc_html__('Verstanden', 'ai-beitragseinreichung'); ?>
                </button>
            </div>
        </div>
    </div>

    <style>
        .beitrag-update-popup {
            align-items: center;
            background: rgba(0, 0, 0, 0.52);
            display: flex;
            inset: 0;
            justify-content: center;
            padding: 24px;
            position: fixed;
            z-index: 100000;
        }

        .beitrag-update-popup[hidden] {
            display: none;
        }

        .beitrag-update-popup__panel {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
            max-width: 680px;
            padding: 28px;
            position: relative;
            width: min(680px, 100%);
        }

        .beitrag-update-popup__close {
            background: transparent;
            border: 0;
            color: #646970;
            cursor: pointer;
            font-size: 28px;
            line-height: 1;
            padding: 8px;
            position: absolute;
            right: 12px;
            top: 10px;
        }

        .beitrag-update-popup__eyebrow {
            color: #2271b1;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin: 0 36px 6px 0;
            text-transform: uppercase;
        }

        .beitrag-update-popup__intro {
            align-items: center;
            display: grid;
            gap: 16px;
            grid-template-columns: auto minmax(0, 1fr);
            margin: 0 36px 14px 0;
        }

        .beitrag-update-popup__visual {
            align-items: center;
            background: linear-gradient(180deg, #f8fbff 0%, #edf6ff 100%);
            border: 1px solid #d8e7f7;
            border-radius: 50%;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85), 0 8px 18px rgba(34, 113, 177, 0.12);
            display: flex;
            height: 72px;
            justify-content: center;
            overflow: hidden;
            width: 72px;
        }

        .beitrag-update-popup__visual span {
            font-size: 28px;
            line-height: 1;
        }

        .beitrag-update-popup h2 {
            font-size: 24px;
            line-height: 1.25;
            margin: 0;
        }

        .beitrag-update-popup p {
            color: #3c434a;
            font-size: 15px;
            line-height: 1.55;
            margin: 0 0 16px;
        }

        .beitrag-update-popup ul {
            list-style: disc;
            margin: 0 0 22px 20px;
        }

        .beitrag-update-popup li {
            margin: 8px 0;
        }

        .beitrag-update-popup__highlights {
            display: grid;
            gap: 10px;
            margin: 0 0 22px;
        }

        .beitrag-update-popup__highlights > div {
            background: #fff;
            border: 1px solid #dcdcde;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            padding: 12px;
        }

        .beitrag-update-popup__highlights strong {
            display: block;
            margin-bottom: 4px;
        }

        .beitrag-update-popup__highlights p {
            font-size: 14px;
            margin: 0;
        }

        .beitrag-update-popup__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        @media (max-width: 600px) {
            .beitrag-update-popup {
                align-items: flex-start;
                padding: 16px;
            }

            .beitrag-update-popup__panel {
                padding: 22px;
            }

            .beitrag-update-popup__intro {
                grid-template-columns: 1fr;
                margin-right: 36px;
            }

            .beitrag-update-popup__visual {
                height: 62px;
                width: 62px;
            }

            .beitrag-update-popup__actions {
                justify-content: stretch;
            }

            .beitrag-update-popup__actions .button {
                text-align: center;
                width: 100%;
            }
        }
    </style>

    <script>
        (function () {
            var popup = document.querySelector('.beitrag-update-popup');
            var showButton = document.getElementById('beitrag-update-popup-show');
            if (!popup) {
                return;
            }

            function dismissPopup() {
                var body = new window.FormData();
                body.append('action', 'beitragseinreichung_update_popup_dismiss');
                body.append('_ajax_nonce', '<?php echo esc_js($nonce); ?>');

                window.fetch(window.ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: body
                }).finally(function () {
                    if (showButton) {
                        popup.hidden = true;
                        return;
                    }

                    popup.remove();
                });
            }

            if (showButton) {
                showButton.addEventListener('click', function () {
                    popup.hidden = false;
                });
            }

            popup.querySelector('.beitrag-update-popup__close').addEventListener('click', dismissPopup);
            popup.querySelector('.beitrag-update-popup__confirm').addEventListener('click', dismissPopup);
        }());
    </script>
    <?php
});
