<?php

namespace Ropaweb\JiraKnowledgebaseSync\Cronjob;

use rex_cronjob;
use rex_i18n;

/* Umbenennen, z.B. in Sync, Task */

class Sync extends rex_cronjob
{
    public function execute()
    {
        /* Tu was */
        if (false) {
            $this->setMessage(rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_error'));
            return false;
        }

        $this->setMessage(rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_success'));
        return true;
    }

    public function getTypeName()
    {
        return rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_name');
    }

    public function getParamFields()
    {
        $fields = [
            [
                'label' => rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_field_label'),
                'name' => 'jira_knowledgebase_sync_field',
                'type' => 'link', // text, textarea, link, media, select, checkbox
                'notice' => rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_field_notice'),
            ],
        ];

        return $fields;
    }
}
