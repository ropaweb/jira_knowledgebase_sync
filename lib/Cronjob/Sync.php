<?php

namespace Ropaweb\JiraKnowledgebaseSync\Cronjob;

use rex_addon;
use rex_cronjob;
use rex_i18n;
use Ropaweb\JiraKnowledgebaseSync\Entry;

use function sprintf;

use const CURLOPT_HTTPHEADER;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;
use const CURLOPT_USERPWD;

class Sync extends rex_cronjob
{
    private const ENDPOINT = '/rest/servicedeskapi/knowledgebase/article';

    /** @var array<string,array<string,int>> */
    private $counter = [
        'entry' => ['created' => 0, 'updated' => 0],
    ];

    public function execute()
    {
        $addon = rex_addon::get('jira_knowledgebase_sync');
        $params = $addon->getConfig();

        $fields = [
            'url' => $params['url'],
            'user' => $params['user'],
            'key' => $params['api_key'],
        ];

        if (empty($fields['url']) || empty($fields['user']) || empty($fields['key'])) {
            $this->setMessage(rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_error_connection'));
            return false;
        }

        $start = 0;
        $cursor = '';
        $repeat = true;

        $url = rtrim($fields['url'], '/') . self::ENDPOINT;
        $user = $fields['user'];
        $key = $fields['key'];

        while ($repeat === true) {
            // Query Parameter festlegen
            $query_params = http_build_query([
                'query' => "' '",
                'start' => $start,
                'cursor' => $cursor,
                'limit' => 50,
            ]);
            $query_url = $url . '?' . $query_params;
            $query_url = str_replace('&amp;', '&', $query_url);

            // cURL-Session für die API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $query_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "$user:$key");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $this->setMessage(sprintf(rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_error'), curl_error($ch)));
                return false;
            }

            $data = json_decode($response, true);
            curl_close($ch);

            // === Parallel: iframeSrc-Links sammeln ===
            $entriesToProcess = [];
            $iframeLinks = [];
            foreach ($data['values'] as $entry) {
                $entriesToProcess[] = $entry;
                $iframeLinks[] = $entry['content']['iframeSrc'] ?? null;
            }

            // Inhalte parallel holen (nur gültige Links)
            $htmlResults = $this->fetchMultipleContents($iframeLinks);

            // === Einträge anlegen ===
            foreach ($entriesToProcess as $i => $entry) {
                // Aus HTML nur das <div id="content"> extrahieren
                $content = $this->extractContentDiv($htmlResults[$i]);
                $this->createEntry($entry, $content);
            }

            // Query Parameter neu setzen
            $start += 50;

            // Cursor aktualisieren
            $cursor_string = $data['_links']['next'] ?? '';
            if ($cursor_string && strpos($cursor_string, '&cursor=') !== false) {
                $cursor_start_pos = strpos($cursor_string, '&cursor=');
                $cursor_end_pos = strpos($cursor_string, '&prev=');
                $cursor = substr($cursor_string, $cursor_start_pos + 8, $cursor_end_pos - $cursor_start_pos - 8);
            } else {
                $cursor = '';
            }

            if (isset($data['isLastPage']) && $data['isLastPage'] === true) {
                $repeat = false;
            }
        }

        $this->setMessage(sprintf(rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_success'), $this->counter['entry']['created'] + $this->counter['entry']['updated'], $this->counter['entry']['created'], $this->counter['entry']['updated']));
        return true;
    }

    /**
     * Holt mehrere Inhalte parallel via curl_multi
     */
    private function fetchMultipleContents(array $urls): array
    {
        $multiHandle = curl_multi_init();
        $curlHandles = [];
        $results = [];

        foreach ($urls as $i => $url) {
            if (!$url) {
                $results[$i] = '';
                continue;
            }
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $curlHandles[$i] = $ch;
            curl_multi_add_handle($multiHandle, $ch);
        }

        do {
            $status = curl_multi_exec($multiHandle, $active);
            curl_multi_select($multiHandle);
        } while ($active && $status == CURLM_OK);

        foreach ($curlHandles as $i => $ch) {
            $results[$i] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }
        curl_multi_close($multiHandle);

        // Reihenfolge stimmt zu den $urls/$entries
        return $results;
    }

    /**
     * Extrahiert das <div id="content"> aus HTML.
     */
    private function extractContentDiv($iframe_content)
    {
        if (!$iframe_content) {
            return '';
        }
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($iframe_content);
        libxml_clear_errors();

        $content_div = $dom->getElementById('content');
        $modified_iframe_content = $content_div ? $dom->saveHTML($content_div) : '';
        return $modified_iframe_content;
    }

    /**
     * createEntry erhält jetzt den fertigen HTML-Content
     */
    public function createEntry(array $current, $content = ''): void
    {
        $entry = Entry::query()->where('jiraid', $current['source']['pageId'])->findOne();

        if (null === $entry) {
            $entry = Entry::create();
            $entry->setValue('jiraid', $current['source']['pageId']);
            $entry->setValue('status', 0);
            $entry->setValue('createdate', date('Y-m-d H:i:s'));
            ++$this->counter['entry']['created'];
        } else {
            ++$this->counter['entry']['updated'];
        }

        $entry->setValue('name', $current['title']);
        $entry->setValue('jiraproject', $current['source']['spaceKey']);
        $entry->setValue('jiracontent', $content);
        $entry->setValue('updatedate', date('Y-m-d H:i:s'));
        $entry->save();
    }

    public function getTypeName()
    {
        return rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_name');
    }
}
