<?php

use rex_addon;
use rex_file;
use rex_yform_manager_table;
use rex_yform_manager_table_api;

$addon = rex_addon::get('jira_knowledgebase_sync');

rex_sql_table::get(rex::getTable('jira_knowledgebase_sync_category'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('prio', 'int(11)'))
    ->ensureColumn(new rex_sql_column('name', 'varchar(191)', false, ''))
    ->ensureColumn(new rex_sql_column('jira_knowledgebase_sync_entry_ids', 'int(11)'))
    ->ensureColumn(new rex_sql_column('status', 'int(11)'))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime'))
    ->ensureColumn(new rex_sql_column('createuser', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('updatedate', 'datetime'))
    ->ensureColumn(new rex_sql_column('updateuser', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('uuid', 'varchar(36)'))
    ->ensure();

rex_sql_table::get(rex::getTable('jira_knowledgebase_sync_entry'))
    ->ensurePrimaryIdColumn()
    ->ensureColumn(new rex_sql_column('name', 'varchar(191)', false, ''))
    ->ensureColumn(new rex_sql_column('jira_knowledgebase_sync_category_id', 'int(11)'))
    ->ensureColumn(new rex_sql_column('status', 'int(11)'))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime'))
    ->ensureColumn(new rex_sql_column('createuser', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('updatedate', 'datetime'))
    ->ensureColumn(new rex_sql_column('updateuser', 'varchar(191)'))
    ->ensureColumn(new rex_sql_column('uuid', 'varchar(36)'))
    ->ensureColumn(new rex_sql_column('jiraid', 'decimal(10,0)', true))
    ->ensureColumn(new rex_sql_column('jiraproject', 'varchar(191)', false, ''))
    ->ensureColumn(new rex_sql_column('jiracontent', 'text'))
    ->ensureIndex(new rex_sql_index('name', ['name']))
    ->ensure();

/* Tablesets aktualisieren */
if (rex_addon::get('yform')->isAvailable() && !rex::isSafeMode()) {
    rex_yform_manager_table_api::importTablesets(rex_file::get(__DIR__ . '/install/rex_jira_knowledgebase_sync_entry.tableset.json'));
    rex_yform_manager_table_api::importTablesets(rex_file::get(__DIR__ . '/install/rex_jira_knowledgebase_sync_category.tableset.json'));
    rex_yform_manager_table::deleteCache();
}
