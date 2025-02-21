<?php

use rex_addon;
use rex_article;
use rex_config;
use rex_file;
use rex_sql;
use rex_yform_manager_table;
use rex_yform_manager_table_api;
use Tracks\🦖;

$addon = rex_addon::get('jira_knowledgebase_sync');

/* Tabellen anlegen, beschleunigt den späteren Import mit YForm und legt zusätzliche Einstellungen fest, bspw. Unique-Prüfung direkt an der DB */
include __DIR__ . '/install/table-scheme.php';

/* Tablesets aktualisieren */
if (rex_addon::get('yform')->isAvailable() && !rex::isSafeMode()) {
    rex_yform_manager_table_api::importTablesets(rex_file::get(__DIR__ . '/install/rex_jira_knowledgebase_sync_entry.tableset.json'));
    rex_yform_manager_table_api::importTablesets(rex_file::get(__DIR__ . '/install/rex_jira_knowledgebase_sync_category.tableset.json'));
    rex_yform_manager_table::deleteCache();
}

/* URL-Profile installieren */
if (rex_addon::get('url') && rex_addon::get('url')->isAvailable()) {
    if (false === rex_config::get('jira_knowledgebase_sync', 'url_profile', false)) {
        $rex_jira_knowledgebase_sync_category = array_filter(rex_sql::factory()->getArray("SELECT * FROM rex_url_generator_profile WHERE `table_name` = '1_xxx_rex_jira_knowledgebase_sync_category'"));
        if (!$rex_jira_knowledgebase_sync_category) {
            $query = str_replace('999999', rex_article::getSiteStartArticleId(), rex_file::get(__DIR__ . '/install/rex_url_profile_jira_knowledgebase_sync_category.sql'));
            rex_sql::factory()->setQuery($query);
        }
        $rex_jira_knowledgebase_sync_entry = array_filter(rex_sql::factory()->getArray("SELECT * FROM rex_url_generator_profile WHERE `table_name` = '1_xxx_rex_jira_knowledgebase_sync_entry'"));
        if (!$rex_jira_knowledgebase_sync_entry) {
            $query = str_replace('999999', rex_article::getSiteStartArticleId(), rex_file::get(__DIR__ . '/install/rex_url_profile_jira_knowledgebase_sync_entry.sql'));
            rex_sql::factory()->setQuery($query);
        }
        /* URL-Profile wurden bereits einmal installiert, daher nicht nochmals installieren und Entwickler-Einstellungen respektieren */
        rex_config::set('jira_knowledgebase_sync', 'url_profile', true);
    }
}

/* Todo: Wildcard aktualisieren */

/* Nutzt du T-Racks? <https://github.com/alexplusde/tracks> Module und Addons mit installieren */

if (rex_addon::exists('tracks')) {
    🦖::forceBackup('school'); // Sichert standardmäßig Module und Templates
    🦖::updateModule('school'); // Synchronisiert Module
    🦖::updateTemplate('school'); // Synchronisiert Templates
}

rex_delete_cache();
