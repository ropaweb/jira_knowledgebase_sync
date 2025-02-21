<?php

// in der uninstall.php sollten Befehle ausgeführt werden, die alle Änderungen, die mit der Installation kamen, entfernen.

// Konfiguration entfernen
// rex_config::removeNamespace("jira_knowledgebase_sync");

// Installierte Metainfos entfernen
// rex_metainfo_delete_field('art_jira_knowledgebase_sync');
// rex_metainfo_delete_field('cat_jira_knowledgebase_sync');
// rex_metainfo_delete_field('med_jira_knowledgebase_sync');
// rex_metainfo_delete_field('clang_jira_knowledgebase_sync');

// Zusäzliche Verzeichnisse entfernen, z.B.
// rex_dir::delete(rex_path::get('jira_knowledgebase_sync'), true);

// YForm-Tabellen löschen (die YForm-Tabellendefinition wird gelöscht, nicht die Datenbank-Tabellen)
// if (rex_addon::get('yform')->isAvailable() && !rex::isSafeMode()) {
// rex_yform_manager_table_api::removeTable('rex_jira_knowledgebase_sync');
// }

// Weitere Vorgänge
// ...
