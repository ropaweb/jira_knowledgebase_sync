<?php

use rex_addon;
use rex_file;
use rex_yform_manager_table;
use rex_yform_manager_table_api;

$addon = rex_addon::get('jira_knowledgebase_sync');

/* Tablesets aktualisieren */
if (rex_addon::get('yform')->isAvailable() && !rex::isSafeMode()) {
    rex_yform_manager_table_api::importTablesets(rex_file::get(__DIR__ . '/install/rex_jira_knowledgebase_sync_entry.tableset.json'));
    rex_yform_manager_table_api::importTablesets(rex_file::get(__DIR__ . '/install/rex_jira_knowledgebase_sync_category.tableset.json'));
    rex_yform_manager_table::deleteCache();
}
