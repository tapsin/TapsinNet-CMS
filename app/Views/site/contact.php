<?php declare(strict_types=1); ?>
<?php
/**
 * İLETİŞİM SAYFASI
 * Form: CSRF + hız sınırı + honeypot + zaman tuzağı (controller'da uygulanır).
 * Doğrudan iletişim bilgileri ayarlardan okunur — hiçbir şey uydurulmaz.
 */
$email    = setting('email', '');
$phone    = setting('phone', '');
$whatsapp = setting('whatsapp', '');
$address  = setting('address', '');
$hours    = setting('working_hours', '');
$kvkk     = setting('contact_kvkk_text', '');
?>
<section class="section section--tight">
    <div class="wrap">
        <div class="section__head section__head--simple">
            <h1 class="section__title"><?= e(t('contact.title')) ?></h1>
            <p class="section__text"><?= e(str_limit(setting('contact_intro', t('contact.intro')), 300)) ?></p>
        </div>

        <div class="card-grid card-grid--2">
            <!-- ══════════ Form ══════════ -->
            <div>
                <h2 class="section__eyebrow"><?= e(t('contact.form')) ?></h2>
                <?= partial('site.partials.forms.contact', [
                    'services' => $services ?? [],
                    'returnUrl' => '/iletisim',
                ]) ?>
            </div>

            <!-- ══════════ Doğrudan iletişim ══════════ -->
            <aside>
                <h2 class="section__eyebrow"><?= e(t('contact.direct')) ?></h2>
                <dl class="dl">
                    <?php if ($email !== ''): ?>
                        <div class="dl__row">
                            <dt class="dl__key"><?= e(t('contact.email')) ?></dt>
                            <dd class="dl__value"><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($phone !== ''): ?>
                        <div class="dl__row">
                            <dt class="dl__key"><?= e(t('contact.phone')) ?></dt>
                            <dd class="dl__value"><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>"><?= e($phone) ?></a></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($whatsapp !== ''): ?>
                        <div class="dl__row">
                            <dt class="dl__key">WhatsApp</dt>
                            <dd class="dl__value">
                                <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $whatsapp)) ?>"
                                   rel="noopener noreferrer nofollow" target="_blank"><?= e($whatsapp) ?></a>
                            </dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($hours !== ''): ?>
                        <div class="dl__row">
                            <dt class="dl__key"><?= e(t('contact.hours')) ?></dt>
                            <dd class="dl__value"><?= e($hours) ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($address !== ''): ?>
                        <div class="dl__row">
                            <dt class="dl__key"><?= e(t('nav.contact')) ?></dt>
                            <dd class="dl__value"><?= e($address) ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>

                <?php $socials = array_filter([
                    'linkedin'  => setting('linkedin', ''),
                    'github'    => setting('github', ''),
                    'instagram' => setting('instagram', ''),
                    'twitter'   => setting('twitter', ''),
                    'youtube'   => setting('youtube', ''),
                    'dribbble'  => setting('dribbble', ''),
                ]); ?>
                <?php if ($socials !== []): ?>
                    <div class="footer__social">
                        <?php foreach ($socials as $net => $href): ?>
                            <a href="<?= e($href) ?>" rel="noopener noreferrer nofollow" target="_blank"
                               aria-label="<?= e(ucfirst($net)) ?>"><?= e(ucfirst($net)) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($kvkk !== ''): ?>
                    <details class="faq-card">
                        <summary><?= e(t('contact.field.kvkk')) ?></summary>
                        <div class="faq-card__answer"><?= safe_html($kvkk, false) ?></div>
                    </details>
                <?php endif; ?>
            </aside>
        </div>

        <?php if (!empty($projects)): ?>
            <section class="section section--flush-top">
                <div class="section__head">
                    <!-- Başlık ve buton AYNI metni kullanıyordu ("Tümünü gör"
                         iki kez). Bölüm bir liste değil, öneri alanı. -->
                    <h2 class="section__title"><?= e(t('detail.related')) ?></h2>
                    <a href="<?= e(module_url('projects')) ?>" class="btn--quiet"><?= e(t('home.view_all')) ?></a>
                </div>
                <div class="card-grid card-grid--3">
                    <?php foreach ($projects as $row): ?>
                        <?= card_for('projects', $row) ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>
