# Jira Knowledgebase-Sync Add-on für REDAXO 5

Synchronisiere Jira-Knowledgebase-Artikel mit deiner REDAXO-Installation, basierend auf YForm und YOrm.

## Features

### `Ropaweb\JiraKnowledgebaseSync\Entry`

Ein Jira-Knowledgebase-Artikel wird als `Entry`-Objekt repräsentiert. Es enthält alle relevanten Informationen, die für die Synchronisation mit REDAXO benötigt werden.

### `RopaWeb\JiraKnowledgebaseSync\Category`

Eine Kategorie in Jira wird als `Category`-Objekt repräsentiert.

### Cronjob `Ropaweb\JiraKnowledgebaseSync\Cronjob\Sync`

Synchronisiert Jira-Knowledgebase-Artikel mit REDAXO-Artikeln.

### Einstellungen

`url`, `user` und `api_key`: Jira-URL, Benutzername und API-Key für die Authentifizierung.

### Einstellungs-Seite

Beginne mit einem Konfigurations-Formular, das bereits best practice in REDAXO umsetzt - mit Links zu den wichtigsten API-Docs.

## Lizenz

MIT Lizenz, siehe [LICENSE.md](https://github.com/alexplusde/jira_knowledgebase_sync/blob/master/LICENSE.md)  

## Autoren

* **Iris Werner**: <https://github.com/iriswerner>

## Credits

* **Alexander Walther**: <https://github.com/alxndr-w>
* Basierend auf **Blaupause**: <https://github.com/alexplusde/blaupause>
