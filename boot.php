<?php

namespace Ropaweb\JiraKnowledgebaseSync;

use rex;
use rex_addon;
use rex_cronjob_manager;
use rex_extension;
use rex_yform_manager_dataset;

// Die boot.php wird bei jedem Seitenaufruf im Frontend und Backend aufgeführt, je nach Reihenfolge von Abhängigkeiten in der package.yml vor oder nach anderen Addons.

// Beispiel YOrm Model-Klasse registrieren, wenn das Addon mit einer eigenen YForm Tabelle kommt.

if (rex_addon::get('yform')->isAvailable() && !rex::isSafeMode()) {
    rex_yform_manager_dataset::setModelClass(
        rex::getTable('jira_knowledgebase_sync_entry'),
        Entry::class,
    );
    rex_yform_manager_dataset::setModelClass(
        rex::getTable('jira_knowledgebase_sync_category'),
        Category::class,
    );
}

// Prüfen, ob ein anderes Addon installiert ist, bspw. Cronjob-Addon
if (rex_addon::get('cronjob')->isAvailable() && !rex::isSafeMode()) {
    rex_cronjob_manager::registerType(Cronjob\Sync::class);
}

// Listendarstellung verändern
if (rex::isBackend()) {
    rex_extension::register('YFORM_DATA_LIST', Entry::epYformDataList(...));
    rex_extension::register('YFORM_DATA_LIST', Category::epYformDataList(...));
}
