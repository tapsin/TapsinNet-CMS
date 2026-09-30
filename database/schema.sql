-- =============================================================================
--  TapsinNet — Freelance Portfolio CMS
--  SQLite şeması
--
--  Tasarım notları
--  --------------
--  * Tüm metin alanları `<alan>_tr` / `<alan>_en` çiftiyle tutulur.
--    Admin formları iki kolonlu basit kalır, sorgular indeksli olur ve
--    N+1 / EAV karmaşıklığı oluşmaz.
--  * `deleted_at` ile yumuşak silme. Benzersiz slug indeksi KISMİ kapsamlıdır
--    (partial index): silinmiş kaydın slug'ı yeniden kullanılabilir.
--  * `is_active` her içerik tablosunda vardır → modül + kayıt düzeyinde
--    yayın kontrolü tek sütunla yapılır.
--  * Yabancı anahtarlar açık (PRAGMA foreign_keys = ON), silme davranışı
--    CASCADE / SET NULL ile tanımlıdır.
-- =============================================================================

PRAGMA foreign_keys = ON;

-- -----------------------------------------------------------------------------
--  users — yönetici hesabı
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    name           TEXT    NOT NULL,
    username       TEXT,
    email          TEXT    NOT NULL,
    password_hash  TEXT    NOT NULL,
    role           TEXT    NOT NULL DEFAULT 'admin',
    avatar         TEXT,
    is_active      INTEGER NOT NULL DEFAULT 1,
    last_login_at  TEXT,
    last_login_ip  TEXT,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at     TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email ON users (email) WHERE deleted_at IS NULL;
-- Giriş ekranı kullanıcı adı YA DA e-posta kabul eder. Kullanıcı adı
-- benzersizdir ama boş bırakılabilir; o zaman yalnızca e-posta ile girilir.
CREATE UNIQUE INDEX IF NOT EXISTS idx_users_username ON users (username) WHERE deleted_at IS NULL AND username IS NOT NULL;

-- -----------------------------------------------------------------------------
--  modules — modül kataloğu durumu
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS modules (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    slug        TEXT    NOT NULL UNIQUE,
    is_active   INTEGER NOT NULL DEFAULT 1,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- -----------------------------------------------------------------------------
--  settings — anahtar/değer site ayarları
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    key         TEXT    NOT NULL UNIQUE,
    value_tr    TEXT,
    value_en    TEXT,
    type        TEXT    NOT NULL DEFAULT 'text',  -- text|textarea|image|color|bool|number|url
    grp         TEXT    NOT NULL DEFAULT 'general',
    created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_settings_grp ON settings (grp);

-- =============================================================================
--  İÇERİK TABLOLARI
-- =============================================================================

-- -----------------------------------------------------------------------------
--  services — hizmetler
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    slug           TEXT    NOT NULL,
    title_tr       TEXT    NOT NULL,
    title_en       TEXT,
    excerpt_tr     TEXT,
    excerpt_en     TEXT,
    body_tr        TEXT,
    body_en        TEXT,
    icon           TEXT,                       -- görsel ikon adı / emoji
    price_from     TEXT,                       -- "1.500 ₺" gibi serbest metin
    duration       TEXT,
    deliverables_tr TEXT,                      -- virgüllü teslim kalemleri
    deliverables_en TEXT,
    link_url       TEXT,
    cover_image    TEXT,
    meta_title_tr  TEXT,
    meta_title_en  TEXT,
    meta_desc_tr   TEXT,
    meta_desc_en   TEXT,
    is_featured    INTEGER NOT NULL DEFAULT 0,
    is_active      INTEGER NOT NULL DEFAULT 1,
    sort_order     INTEGER NOT NULL DEFAULT 0,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at     TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_services_slug ON services (slug) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_services_active ON services (is_active, deleted_at, sort_order);
CREATE INDEX IF NOT EXISTS idx_services_recent ON services (is_active, deleted_at, created_at DESC);

-- -----------------------------------------------------------------------------
--  projects — tamamlanan işler / portföy
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    slug            TEXT    NOT NULL,
    title_tr        TEXT    NOT NULL,
    title_en        TEXT,
    summary_tr      TEXT,
    summary_en      TEXT,
    body_tr         TEXT,
    body_en         TEXT,
    client          TEXT,
    client_url      TEXT,
    category        TEXT,                      -- web|app|marka|seo|...
    tags            TEXT,                      -- virgüllü
    location        TEXT,
    project_url     TEXT,
    project_date    TEXT,                      -- YYYY-MM
    completed_at    TEXT,
    cover_image     TEXT,
    gallery         TEXT,                      -- JSON: ["uploads/...","..."]
    tech_stack      TEXT,
    duration        TEXT,
    views           INTEGER NOT NULL DEFAULT 0,
    meta_title_tr   TEXT,
    meta_title_en   TEXT,
    meta_desc_tr    TEXT,
    meta_desc_en    TEXT,
    is_featured     INTEGER NOT NULL DEFAULT 0,
    is_active       INTEGER NOT NULL DEFAULT 1,
    sort_order      INTEGER NOT NULL DEFAULT 0,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at      TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_projects_slug ON projects (slug) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_projects_active ON projects (is_active, deleted_at, sort_order);
CREATE INDEX IF NOT EXISTS idx_projects_recent ON projects (is_active, deleted_at, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_projects_cat    ON projects (category, is_active);
CREATE INDEX IF NOT EXISTS idx_projects_featured ON projects (is_featured, is_active);

-- -----------------------------------------------------------------------------
--  certificates — sertifikalar / belgeler
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS certificates (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    slug           TEXT    NOT NULL,
    title_tr       TEXT    NOT NULL,
    title_en       TEXT,
    issuer         TEXT,                       -- veren kurum
    issuer_tr      TEXT,
    issuer_en      TEXT,
    credential_id  TEXT,                       -- belge numarası
    issue_date     TEXT,
    expiry_date    TEXT,                       -- süresiz ise NULL
    score          TEXT,
    url            TEXT,                       -- doğrulama linki
    file_path      TEXT,                       -- PDF yüklemesi
    cover_image    TEXT,
    description_tr TEXT,
    description_en TEXT,
    is_featured    INTEGER NOT NULL DEFAULT 0,
    is_active      INTEGER NOT NULL DEFAULT 1,
    sort_order     INTEGER NOT NULL DEFAULT 0,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at     TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_certificates_slug ON certificates (slug) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_certificates_active ON certificates (is_active, deleted_at, sort_order);

-- -----------------------------------------------------------------------------
--  gallery_items — görsel galeri
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS gallery_items (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    slug         TEXT    NOT NULL,
    title_tr     TEXT,
    title_en     TEXT,
    caption_tr   TEXT,
    caption_en   TEXT,
    alt_text     TEXT,                          -- erişilebilirlik için
    image_path   TEXT    NOT NULL,
    thumb_path   TEXT,                          -- küçük görsel (yoksa image_path)
    album        TEXT,                          -- albüm/etiket grubu
    width        INTEGER,
    height       INTEGER,
    is_featured  INTEGER NOT NULL DEFAULT 0,
    is_active    INTEGER NOT NULL DEFAULT 1,
    sort_order   INTEGER NOT NULL DEFAULT 0,
    created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at   TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at   TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_gallery_slug ON gallery_items (slug) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_gallery_active  ON gallery_items (is_active, deleted_at, sort_order);
CREATE INDEX IF NOT EXISTS idx_gallery_recent  ON gallery_items (is_active, deleted_at, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_gallery_album   ON gallery_items (album, is_active);

-- -----------------------------------------------------------------------------
--  videos — video galeri (YouTube / Vimeo embed)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS videos (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    slug           TEXT    NOT NULL,
    title_tr       TEXT    NOT NULL,
    title_en       TEXT,
    description_tr TEXT,
    description_en TEXT,
    provider       TEXT    NOT NULL DEFAULT 'youtube',  -- youtube|vimeo
    video_id       TEXT    NOT NULL,                    -- yalnızca kimlik, ham HTML değil
    watch_url      TEXT,
    cover_image    TEXT,                                -- youtube thumbnail değil, kendi görselimiz
    duration       TEXT,
    album          TEXT,
    views          INTEGER NOT NULL DEFAULT 0,
    is_featured    INTEGER NOT NULL DEFAULT 0,
    is_active      INTEGER NOT NULL DEFAULT 1,
    sort_order     INTEGER NOT NULL DEFAULT 0,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at     TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_videos_slug ON videos (slug) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_videos_active ON videos (is_active, deleted_at, sort_order);
CREATE INDEX IF NOT EXISTS idx_videos_recent ON videos (is_active, deleted_at, created_at DESC);

-- -----------------------------------------------------------------------------
--  news — haberler / blog
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS news (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    slug           TEXT    NOT NULL,
    title_tr       TEXT    NOT NULL,
    title_en       TEXT,
    summary_tr     TEXT,
    summary_en     TEXT,
    body_tr        TEXT,
    body_en        TEXT,
    author         TEXT,
    category       TEXT,
    tags           TEXT,
    cover_image    TEXT,
    published_at   TEXT,
    read_minutes   INTEGER,
    views          INTEGER NOT NULL DEFAULT 0,
    meta_title_tr  TEXT,
    meta_title_en  TEXT,
    meta_desc_tr   TEXT,
    meta_desc_en   TEXT,
    is_featured    INTEGER NOT NULL DEFAULT 0,
    is_active      INTEGER NOT NULL DEFAULT 1,
    sort_order     INTEGER NOT NULL DEFAULT 0,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at     TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_news_slug ON news (slug) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_news_active    ON news (is_active, deleted_at, published_at DESC);
CREATE INDEX IF NOT EXISTS idx_news_recent    ON news (is_active, deleted_at, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_news_featured  ON news (is_featured, is_active);

-- -----------------------------------------------------------------------------
--  profiles — ekip / iş ortağı / müşteri profilleri
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS profiles (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    slug           TEXT    NOT NULL,
    name           TEXT    NOT NULL,
    title_tr       TEXT,                       -- ünvan / rol
    title_en       TEXT,
    company        TEXT,
    bio_tr         TEXT,
    bio_en         TEXT,
    avatar         TEXT,
    email          TEXT,
    phone          TEXT,
    website        TEXT,
    location       TEXT,
    profile_type   TEXT    NOT NULL DEFAULT 'team',  -- team|client|partner|other
    linkedin       TEXT,
    github         TEXT,
    instagram      TEXT,
    twitter        TEXT,
    is_featured    INTEGER NOT NULL DEFAULT 0,
    is_active      INTEGER NOT NULL DEFAULT 1,
    sort_order     INTEGER NOT NULL DEFAULT 0,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at     TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_profiles_slug ON profiles (slug) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_profiles_active ON profiles (is_active, deleted_at, sort_order);
CREATE INDEX IF NOT EXISTS idx_profiles_recent ON profiles (is_active, deleted_at, created_at DESC);

-- -----------------------------------------------------------------------------
--  testimonials — müşteri referansları
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS testimonials (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    slug           TEXT    NOT NULL,
    author_name    TEXT    NOT NULL,
    author_title   TEXT,
    author_company TEXT,
    company_url    TEXT,
    author_avatar  TEXT,
    quote_tr       TEXT    NOT NULL,
    quote_en       TEXT,
    rating         INTEGER NOT NULL DEFAULT 5,   -- 1..5
    project_slug   TEXT,                          -- ilişkili iş
    is_featured    INTEGER NOT NULL DEFAULT 0,
    is_active      INTEGER NOT NULL DEFAULT 1,
    sort_order     INTEGER NOT NULL DEFAULT 0,
    created_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at     TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_testimonials_slug ON testimonials (slug) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_testimonials_active ON testimonials (is_active, deleted_at, sort_order);

-- -----------------------------------------------------------------------------
--  faq_items — sıkça sorulan sorular
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS faq_items (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    slug          TEXT    NOT NULL,
    question_tr   TEXT    NOT NULL,
    question_en   TEXT,
    answer_tr     TEXT    NOT NULL,
    answer_en     TEXT,
    category      TEXT,
    is_featured   INTEGER NOT NULL DEFAULT 0,
    is_active     INTEGER NOT NULL DEFAULT 1,
    sort_order    INTEGER NOT NULL DEFAULT 0,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at    TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_faq_slug ON faq_items (slug) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_faq_active ON faq_items (is_active, deleted_at, sort_order);

-- -----------------------------------------------------------------------------
--  social_posts — sosyal medya akışı
--    NOT: ham embed HTML saklanmaz. Görünüm sağlayıcıya göre embed
--    URL'sini KENDİSİ kurar → XSS yüzeyi yok.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS social_posts (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    slug          TEXT    NOT NULL,
    provider      TEXT    NOT NULL DEFAULT 'instagram',  -- instagram|youtube|linkedin|x
    permalink     TEXT    NOT NULL,            -- gönderi linki
    embed_id      TEXT,                         -- IG shortcode / YT video id (isteğe bağlı)
    caption_tr    TEXT,
    caption_en    TEXT,
    media_path    TEXT,                         -- yerel kapak görseli
    external_thumb TEXT,                        -- uzak thumb (yalnızca admin girdi, https zorunlu)
    is_featured   INTEGER NOT NULL DEFAULT 0,
    is_active     INTEGER NOT NULL DEFAULT 1,
    sort_order    INTEGER NOT NULL DEFAULT 0,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at    TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_social_slug ON social_posts (slug) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_social_active ON social_posts (is_active, deleted_at, sort_order);
CREATE INDEX IF NOT EXISTS idx_social_recent ON social_posts (is_active, deleted_at, created_at DESC);

-- -----------------------------------------------------------------------------
--  pages — kurumsal / yasal sayfalar
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pages (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    slug          TEXT    NOT NULL,
    title_tr      TEXT    NOT NULL,
    title_en      TEXT,
    body_tr       TEXT,
    body_en       TEXT,
    excerpt_tr    TEXT,
    excerpt_en    TEXT,
    cover_image   TEXT,
    is_system     INTEGER NOT NULL DEFAULT 0,   -- KVKK/çerez/gizlilik: silinemez
    show_in_footer INTEGER NOT NULL DEFAULT 0,
    show_in_menu  INTEGER NOT NULL DEFAULT 0,
    meta_title_tr TEXT,
    meta_title_en TEXT,
    meta_desc_tr  TEXT,
    meta_desc_en  TEXT,
    is_active     INTEGER NOT NULL DEFAULT 1,
    sort_order    INTEGER NOT NULL DEFAULT 0,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at    TEXT
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_pages_slug ON pages (slug) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_pages_active ON pages (is_active, deleted_at, sort_order);

-- =============================================================================
--  ETKİLEŞİM
-- =============================================================================

-- -----------------------------------------------------------------------------
--  messages — iletişim formu mesajları
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT    NOT NULL,
    email        TEXT    NOT NULL,
    phone        TEXT,
    subject      TEXT,
    message      TEXT    NOT NULL,
    source_page  TEXT,
    module       TEXT,
    locale       TEXT,
    ip           TEXT,
    user_agent   TEXT,
    is_read      INTEGER NOT NULL DEFAULT 0,
    is_starred   INTEGER NOT NULL DEFAULT 0,
    is_archived  INTEGER NOT NULL DEFAULT 0,
    admin_note   TEXT,
    replied_at   TEXT,
    created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_messages_inbox ON messages (is_archived, is_read, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_messages_unread ON messages (is_read, is_archived);

-- -----------------------------------------------------------------------------
--  comments — ziyaretçi yorumları (onaylı yayınlanır)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS comments (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    content_type  TEXT    NOT NULL,           -- project|news|service|page
    entity_id     INTEGER NOT NULL,
    parent_id     INTEGER,
    author_name   TEXT    NOT NULL,
    author_email  TEXT    NOT NULL,
    body          TEXT    NOT NULL,
    locale        TEXT    NOT NULL DEFAULT 'tr',
    is_approved   INTEGER NOT NULL DEFAULT 0,
    is_spam       INTEGER NOT NULL DEFAULT 0,
    admin_note    TEXT,                        -- yönetici notu
    ip            TEXT,
    user_agent    TEXT,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    deleted_at    TEXT
);
CREATE INDEX IF NOT EXISTS idx_comments_entity ON comments (content_type, entity_id, is_approved, deleted_at);
CREATE INDEX IF NOT EXISTS idx_comments_queue  ON comments (is_approved, is_spam, created_at DESC);
