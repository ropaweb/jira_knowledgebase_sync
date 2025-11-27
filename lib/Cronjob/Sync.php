<?php

namespace Ropaweb\JiraKnowledgebaseSync\Cronjob;

use Dom\HTMLDocument;
use Dom\XPath;
use rex_addon;
use rex_cronjob;
use rex_i18n;
use Ropaweb\JiraKnowledgebaseSync\Entry;

use function sprintf;

use const CURLINFO_HTTP_CODE;
use const CURLM_OK;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const CURLOPT_USERPWD;
use const LIBXML_NOERROR;

class Sync extends rex_cronjob
{
    private const ENDPOINT = '/rest/servicedeskapi/knowledgebase/article';

    /** @var int Timeout in seconds for fetching content */
    private const FETCH_TIMEOUT = 5;

    /** @var int Connection timeout in seconds */
    private const CONNECT_TIMEOUT = 2;

    /** @var array<string> HTML tags to be removed for XSS prevention */
    private const DISALLOWED_TAGS = ['iframe', 'script', 'object', 'embed', 'link', 'meta', 'form', 'button', 'input', 'select'];

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

        while (true === $repeat) {
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
            curl_setopt($ch, CURLOPT_TIMEOUT, self::FETCH_TIMEOUT);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
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
            $htmlResults = array_values($htmlResults);

            // === Einträge anlegen ===
            foreach ($entriesToProcess as $i => $entry) {
                // Aus HTML nur das <div id="content"> extrahieren
                $content = $this->extractContentDiv($htmlResults[$i]);
                $this->createEntry($entry, $content);
            }

            // Query Parameter neu setzen
            $start += 50;

            // Cursor aktualisieren mittels parse_url/parse_str für sicheres Parsing
            $cursor_string = $data['_links']['next'] ?? '';
            $cursor = '';
            if ($cursor_string) {
                $parsed_url = parse_url($cursor_string);
                if (isset($parsed_url['query'])) {
                    parse_str($parsed_url['query'], $query_params_parsed);
                    $cursor = $query_params_parsed['cursor'] ?? '';
                }
            }

            if (isset($data['isLastPage']) && true === $data['isLastPage']) {
                $repeat = false;
            }
        }

        $this->setMessage(sprintf(rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_success'), $this->counter['entry']['created'] + $this->counter['entry']['updated'], $this->counter['entry']['created'], $this->counter['entry']['updated']));
        return true;
    }

    /**
     * Holt mehrere Inhalte parallel via curl_multi.
     */
    private function fetchMultipleContents(array $urls): array
    {
        $multiHandle = curl_multi_init();
        // Limit the number of parallel requests to avoid memory exhaustion
        $batchSize = 10; // You can adjust this value as needed
        $results = array_fill(0, count($urls), '');
        foreach (array_chunk($urls, $batchSize, true) as $chunk) {
            $multiHandle = curl_multi_init();
            $curlHandles = [];

            foreach ($chunk as $i => $url) {
                if (!$url) {
                    $results[$i] = '';
                    continue;
                }
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, self::FETCH_TIMEOUT);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
                $curlHandles[$i] = $ch;
                curl_multi_add_handle($multiHandle, $ch);
            }

            do {
                $status = curl_multi_exec($multiHandle, $active);
                curl_multi_select($multiHandle);
            } while ($active && CURLM_OK == $status);

            foreach ($curlHandles as $i => $ch) {
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                if ($error || $httpCode < 200 || $httpCode >= 300) {
                    $results[$i] = '';
                } else {
                    $results[$i] = curl_multi_getcontent($ch);
                }
                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
            }
            curl_multi_close($multiHandle);
        }

        // Reihenfolge entspricht den $urls/$entries
        return $results;
    }

    /**
     * Extrahiert das <div id="content"> aus HTML.
     *
     * @param string|null $iframe_content The HTML content to extract from
     * @return string The extracted and sanitized content div HTML, or empty string if not found
     */
    private function extractContentDiv(?string $iframe_content): string
        if (!$iframe_content) {
            return '';
        }

        // PHP 8.4+ HTML5 compliant DOM parser
        $dom = HTMLDocument::createFromString($iframe_content, LIBXML_NOERROR, 'UTF-8');

        $content_div = $dom->getElementById('content');
        if (!$content_div) {
            return '';
        }

        // Remove disallowed tags for XSS prevention
        foreach (self::DISALLOWED_TAGS as $tag) {
            $elements = $content_div->getElementsByTagName($tag);
            // Iterate backwards to safely remove elements
            for ($i = $elements->length - 1; $i >= 0; --$i) {
                $element = $elements->item($i);
                if ($element && $element->parentNode) {
                    $element->parentNode->removeChild($element);
                }
            }
        }

        // Remove dangerous event handler attributes (onclick, onerror, onload, etc.)
        $xpath = new XPath($dom);
        $elementsWithEvents = $xpath->query('//*[@*[starts-with(name(), "on")]]', $content_div);
        foreach ($elementsWithEvents as $element) {
            // Iterate over a copy of attributes to avoid mutating during iteration
            foreach (iterator_to_array($element->attributes) as $attr) {
                if (0 === stripos($attr->name, 'on')) {
                    $element->removeAttribute($attr->name);
                }
            }
        }

        // Remove javascript: URLs in href and src attributes
        $elementsWithUrls = $xpath->query('//*[@href or @src]', $content_div);
        foreach ($elementsWithUrls as $element) {
            foreach (['href', 'src'] as $attrName) {
                if ($element->hasAttribute($attrName)) {
                    $value = $element->getAttribute($attrName);
                    if (preg_match('/^\s*(javascript|data):/i', $value)) {
                        $element->removeAttribute($attrName);
                    }
                }
            }
        }

        // Remove all style attributes for defense in depth
        $elementsWithStyle = $xpath->query('//*[@style]', $content_div);
        foreach ($elementsWithStyle as $element) {
            $element->removeAttribute('style');
        }
        return $dom->saveHTML($content_div);
    }

    /**
     * createEntry erhält jetzt den fertigen HTML-Content.
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
