<?php
declare(strict_types=1);

const SCHEMA_VERSION = 7;

function column_exists(string $table, string $col): bool {
    return (int)db_value('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $col]) > 0;
}

/** Idempotent upgrades for sites installed with an older version. */
function migrate(): void {
    if ((int)setting('schema_version', '1') >= SCHEMA_VERSION) return;
    if (!column_exists('pages', 'blocks')) db_exec('ALTER TABLE pages ADD COLUMN blocks MEDIUMTEXT NULL AFTER content');
    db_exec("CREATE TABLE IF NOT EXISTS messages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(190) NOT NULL, email VARCHAR(190) NOT NULL DEFAULT '', phone VARCHAR(60) NOT NULL DEFAULT '',
        message TEXT NOT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (!column_exists('galleries', 'opts')) db_exec('ALTER TABLE galleries ADD COLUMN opts TEXT NULL AFTER layout');
    db_exec("CREATE TABLE IF NOT EXISTS categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, slug VARCHAR(190) NOT NULL UNIQUE, sort INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db_exec("CREATE TABLE IF NOT EXISTS posts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, slug VARCHAR(190) NOT NULL UNIQUE, excerpt TEXT NULL, cover VARCHAR(255) NOT NULL DEFAULT '',
        blocks MEDIUMTEXT NULL, category_id INT UNSIGNED NULL, is_published TINYINT(1) NOT NULL DEFAULT 0, published_at DATETIME NOT NULL,
        meta_title VARCHAR(190) NOT NULL DEFAULT '', meta_description VARCHAR(300) NOT NULL DEFAULT '', created_at DATETIME NOT NULL,
        INDEX idx_pub (is_published, published_at), INDEX idx_cat (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if ((int)setting('schema_version', '1') < 4 && setting('palette') === '') {      // carry over the stage 2 look into the new design settings
        $old = ['accent' => setting('accent'), 'bg' => setting('bg'), 'font' => setting('font')];
        $fm = ['serif' => ['lora', 'lora'], 'sans' => ['inter', 'inter']][$old['font']] ?? ['cormorant', 'manrope'];
        set_setting('f_head', $fm[0]); set_setting('f_body', $fm[1]); set_setting('f_quote', $fm[0]);
        if (preg_match('~^#[0-9a-fA-F]{6}$~', $old['accent']) && strtolower($old['accent']) !== '#3f6a62') {
            set_setting('palette', 'custom'); set_setting('c_accent', $old['accent']);
            if ($old['bg'] === 'soft') set_setting('c_bg', '#f4f5f4');
        } else set_setting('palette', $old['bg'] === 'soft' ? 'mist' : 'air');
    }
    if (setting('theme') === 'starter' || setting('theme') === '') set_setting('theme', 'air');

    // v5: hierarchy of pages + menu parent
    if (!column_exists('pages', 'parent_id')) {
        db_exec('ALTER TABLE pages ADD COLUMN parent_id INT UNSIGNED NULL DEFAULT NULL AFTER sort');
        db_exec('ALTER TABLE pages ADD INDEX idx_parent (parent_id)');
    }
    if (!column_exists('pages', 'menu_parent')) {
        // Separate menu hierarchy (can differ from page hierarchy)
        db_exec('ALTER TABLE pages ADD COLUMN menu_parent INT UNSIGNED NULL DEFAULT NULL AFTER parent_id');
        db_exec('ALTER TABLE pages ADD INDEX idx_menu_parent (menu_parent)');
    }
    if (!column_exists('pages', 'og_image')) {
        db_exec('ALTER TABLE pages ADD COLUMN og_image VARCHAR(255) NOT NULL DEFAULT \'\' AFTER meta_description');
    }
    if (!column_exists('posts', 'og_image')) {
        db_exec('ALTER TABLE posts ADD COLUMN og_image VARCHAR(255) NOT NULL DEFAULT \'\' AFTER meta_description');
    }

    // v6: gallery hierarchy, page cover, page categories
    if (!column_exists('galleries', 'parent_id')) {
        db_exec('ALTER TABLE galleries ADD COLUMN parent_id INT UNSIGNED NULL DEFAULT NULL AFTER sort');
        db_exec('ALTER TABLE galleries ADD INDEX idx_g_parent (parent_id)');
    }
    if (!column_exists('pages', 'cover')) {
        db_exec('ALTER TABLE pages ADD COLUMN cover VARCHAR(255) NOT NULL DEFAULT \'\' AFTER og_image');
    }
    if (!column_exists('pages', 'category_id')) {
        db_exec('ALTER TABLE pages ADD COLUMN category_id INT UNSIGNED NULL DEFAULT NULL AFTER cover');
        db_exec('ALTER TABLE pages ADD INDEX idx_page_cat (category_id)');
    }
    db_exec("CREATE TABLE IF NOT EXISTS page_categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(190) NOT NULL,
        slug VARCHAR(190) NOT NULL UNIQUE,
        parent_id INT UNSIGNED NULL DEFAULT NULL,
        sort INT NOT NULL DEFAULT 0,
        INDEX idx_pc_parent (parent_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // v7: SEO — noindex/canonical/ключевая фраза/updated_at, редиректы, журнал 404
    foreach (['pages', 'posts', 'galleries'] as $tb) {
        if (!column_exists($tb, 'noindex')) db_exec("ALTER TABLE `$tb` ADD COLUMN noindex TINYINT(1) NOT NULL DEFAULT 0");
        if (!column_exists($tb, 'canonical')) db_exec("ALTER TABLE `$tb` ADD COLUMN canonical VARCHAR(255) NOT NULL DEFAULT ''");
        if (!column_exists($tb, 'focus_kw')) db_exec("ALTER TABLE `$tb` ADD COLUMN focus_kw VARCHAR(120) NOT NULL DEFAULT ''");
        if (!column_exists($tb, 'updated_at')) db_exec("ALTER TABLE `$tb` ADD COLUMN updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");
    }
    if (!column_exists('galleries', 'meta_title')) db_exec("ALTER TABLE galleries ADD COLUMN meta_title VARCHAR(190) NOT NULL DEFAULT ''");
    if (!column_exists('galleries', 'meta_description')) db_exec("ALTER TABLE galleries ADD COLUMN meta_description VARCHAR(300) NOT NULL DEFAULT ''");
    if (!column_exists('galleries', 'og_image')) db_exec("ALTER TABLE galleries ADD COLUMN og_image VARCHAR(255) NOT NULL DEFAULT ''");
    db_exec("CREATE TABLE IF NOT EXISTS redirects (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, from_path VARCHAR(190) NOT NULL UNIQUE, to_url VARCHAR(500) NOT NULL,
        code SMALLINT NOT NULL DEFAULT 301, hits INT UNSIGNED NOT NULL DEFAULT 0, created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db_exec("CREATE TABLE IF NOT EXISTS log404 (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, path VARCHAR(190) NOT NULL UNIQUE, hits INT UNSIGNED NOT NULL DEFAULT 1,
        referrer VARCHAR(190) NOT NULL DEFAULT '', last_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    set_setting('schema_version', (string)SCHEMA_VERSION);
}
