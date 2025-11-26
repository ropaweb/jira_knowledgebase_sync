<?php

namespace Ropaweb\JiraKnowledgebaseSync\Cronjob;

use rex_cronjob;
use rex_i18n;
use Ropaweb\JiraKnowledgebaseSync\Entry;

use function sprintf;

class Publish extends rex_cronjob
{
    /**
     * @return bool
     */
    public function execute()
    {
        /* Collection von Einträgen, die noch nicht veröffentlicht sind, aber es sein sollten. (Kategorie ist vergeben) */
        $entry_to_publish = Entry::query()->where('status', Entry::STATUS_DRAFT)->find();
        $i = 0;

        foreach ($entry_to_publish as $entry) {
            if ($entry->getRelatedDataset('jira_knowledgebase_sync_category_id')) {
                $entry->setValue('status', Entry::STATUS_ACTIVE);

                if (!$entry->save()) {
                    $this->setMessage(sprintf(rex_i18n::msg('publish_save_error'), $entry->jiraid));
                    return false;
                }

                ++$i;
                $this->setMessage(sprintf(rex_i18n::msg('publish_task_success'), $i));
            }
        }

        if (0 == $i) {
            $this->setMessage(rex_i18n::msg('publish_task_no_entries'));
        }

        return true;
    }

    public function getTypeName()
    {
        return rex_i18n::msg('publish_task_name');
    }

    public function getParamFields()
    {
        return [];
    }
}
