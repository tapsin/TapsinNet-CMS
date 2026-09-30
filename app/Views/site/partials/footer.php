<?php declare(strict_types=1); ?>
<footer class="footer" role="contentinfo">
    <div class="footer__inner wrap">
        <div class="footer__brand">
            <p class="footer__wordmark"><?= e($siteName) ?></p>
            <?php if (($tagline = setting('site_tagline', '')) !== ''): ?>
                <p class="footer__tagline"><?= e($tagline) ?></p>
            <?php endif; ?>
        </div>

        <div class="footer__cols">
            <?php if (!empty($footerPages)): ?>
                <nav class="footer__col" aria-label="<?= t('footer.quick_links') ?>">
                    <h3 class="footer__heading"><?= t('footer.quick_links') ?></h3>
                    <ul class="footer__links">
                        <?php foreach ($footerPages as $page): ?>
                            <li>
                                <a href="<?= e(url('/sayfa/' . $page['slug'])) ?>"><?= e(loc($page, 'title')) ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            <?php endif; ?>

            <?php if (module('services', 'active')): ?>
                <nav class="footer__col" aria-label="<?= t('footer.services') ?>">
                    <h3 class="footer__heading"><?= t('footer.services') ?></h3>
                    <ul class="footer__links">
                        <li><a href="<?= e(module_url('services')) ?>"><?= t('nav.services') ?></a></li>
                    </ul>
                </nav>
            <?php endif; ?>

            <address class="footer__col footer__col--contact" aria-label="<?= t('footer.contact') ?>">
                <h3 class="footer__heading"><?= t('footer.contact') ?></h3>
                <?php if (($email = setting('email', '')) !== ''): ?>
                    <p><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p>
                <?php endif; ?>
                <?php if (($phone = setting('phone', '')) !== ''): ?>
                    <p><a href="tel:<?= e($phone) ?>"><?= e($phone) ?></a></p>
                <?php endif; ?>
                <?php if (($address = setting('address', '')) !== ''): ?>
                    <p><?= e($address) ?></p>
                <?php endif; ?>
            </address>
        </div>

        <div class="footer__bar">
            <p class="footer__legal"><?= t('footer.rights', ['year' => date('Y'), 'name' => $siteName]) ?></p>
            <?php
            // Proje kredisi — kaynak depo bağlantısı ayarlardan okunur.
            $repoUrl = setting('github', '');
            $author  = 'TAPSIN';
            ?>
            <p class="footer__credit">
                <?= t('footer.built_with') ?> &amp;
                <?php if ($repoUrl !== ''): ?>
                    <a href="<?= e($repoUrl) ?>" rel="noopener noreferrer nofollow" target="_blank" class="footer__credit-link">
                        <svg aria-hidden="true" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>
                        <?= e($author) ?>
                    </a>
                <?php else: ?>
                    <strong><?= e($author) ?></strong>
                <?php endif; ?>
            </p>
            <div class="footer__social" role="list" aria-label="<?= t('nav.social') ?>">
                <?php
                    $socials = [
                        'linkedin' => setting('social_linkedin', ''),
                        'github'   => setting('social_github', ''),
                        'twitter'  => setting('social_twitter', ''),
                        'instagram'=> setting('social_instagram', ''),
                        'youtube'  => setting('social_youtube', ''),
                    ];
                ?>
                <?php foreach ($socials as $platform => $url): ?>
                    <?php if ($url !== ''): ?>
                        <a href="<?= e($url) ?>" class="footer__social-link" rel="noopener noreferrer" aria-label="<?= e(ucfirst($platform)) ?>" target="_blank">
                            <?php
                                $icons = [
                                    'linkedin' => '<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
                                    'github'   => '<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/></svg>',
                                    'twitter'  => '<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>',
                                    'instagram'=> '<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>',
                                    'youtube'  => '<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 7.434 0 12 0 12s0 4.565.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 16.566 24 12 24 12s0-4.565-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
                                ];
                                echo $icons[$platform] ?? '';
                            ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="button" class="footer__top" aria-label="<?= t('footer.back_to_top') ?>" data-scroll-top hidden>
            <svg aria-hidden="true" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 15l-6-6-6 6"/></svg>
        </button>
    </div>
</footer>