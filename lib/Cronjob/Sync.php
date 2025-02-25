<?php

namespace Ropaweb\JiraKnowledgebaseSync\Cronjob;

use rex_cronjob;
use rex_i18n;
use rex_addon;

use Ropaweb\JiraKnowledgebaseSync\Entry;


class Sync extends rex_cronjob
{
    private const ENDPOINT = '/rest/servicedeskapi/knowledgebase/article';

     /** @var array<string,array<string,int>> */
     private $counter = [
        'entry' => ['created' => 0, 'updated' => 0],
    ];


    public function execute()
    {
        $fields = $this->getParamFields();
        $i = 1;
        $start = 0;
        $cursor = "";
        $repeate = true; // Schleife solange wiederholen bis keine Daten mehr kommen

        $url = rtrim($fields['url'], '/') . self::ENDPOINT;
        $user = $fields['user'];
        $key = $fields['key'];
        $data = [];

       

        while($repeate === true)
        {
            // Query Parameter festlegen 
            $query_params = http_build_query(array(
                "query" => "' '", // space to simulate empty
                "start" => $start,
                "cursor" => $cursor, // Replace with the actual cursor value if needed
                "limit" => 50, // Mehr als 50 sind nicht möglich (über Schleife lösen)
            ));

            
            // Append query parameters to the API endpoint
            $query_url =  $url . '?' . $query_params;
            $query_url = str_replace('&amp;', '&', $query_url);

            // Initialize cURL session
            $ch = curl_init();

            // Set cURL options
            curl_setopt($ch, CURLOPT_URL, $query_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "$user:$key");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            // Execute cURL request
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $this->setMessage(sprintf(rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_error'), curl_error($ch)));
                return false;
            }
            else 
            {
                // Decode JSON response
                $data = json_decode($response, true);

                // Generate DB-Entry for each article
                foreach ($data['values'] as $entry) {
                    $this->createEntry($entry);
                }  
            }

            // Close cURL session
            curl_close($ch);

            // Query Parameter neu setzen
            $start=$start+50;

            // Cursor aus String next holen
            $cursor_string = $data['_links']['next'];
            $cursor_start_pos = strpos( $cursor_string, '&cursor=');
            $cursor_end_pos = strpos($cursor_string, '&prev=');
            $cursor = substr($cursor_string, $cursor_start_pos+8, $cursor_end_pos - $cursor_start_pos-8);
            
            if($data['isLastPage']=== true)
            {
                $repeate = false;
            }
        }

        $this->setMessage(sprintf(rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_success'), $this->counter['entry']['created'] + $this->counter['entry']['updated'], $this->counter['entry']['created'], $this->counter['entry']['updated']));
        return true;
    }

    public function getTypeName()
    {
        return rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_name');
    }

    public function getParamFields()
    {
        $addon = rex_addon::get('jira_knowledgebase_sync');
        $params = $addon->getConfig();

        $fields = [
            
                'url' => $params['url'],
                'user' => $params['user'],
                'key' => $params['api_key']
            
        ];

        
        if (empty($fields['url']) || empty($fields['user']) || empty($fields['key'])) {

            $this->setMessage(rex_i18n::msg('jira_knowledgebase_sync_cronjob_task_error_connection'));
            return false;
        }
        return $fields;
    }


    public function createEntry(array $current): void
    {
        
        //dump($current);
        //dump($current['content']['iframeSrc']);

        //Prüfen ob es den Eintrag bereits gibt
        $entry = Entry::query()->where('jiraid', $current['source']['pageId'])->findOne();

       if (null === $entry) {
            //print 'Datensatz muss angelegt werden';

            $entry = Entry::create();
            $entry->setValue('jiraid', $current['source']['pageId']);
            $entry->setValue('status', 0);
            $entry->setValue('createdate', date('Y-m-d H:i:s'));

            // User lässt sich nicht überschreiben immer der Benutzer der als API-Key-User hinterlegt ist
            //$entry->setValue('createuser', 'JiraSyncCron');

            
            ++$this->counter['entry']['created'];
        } else {
            //print 'Datensatz muss aktualisiert werden';
            ++$this->counter['entry']['updated'];
        }


        // Felder die immer überschrieben werden egal ob create oder update
        $entry->setValue('name', $current['title']);
        $entry->setValue('jiraproject', $current['source']['spaceKey']);

        
        
        // Content aus iframeSrc holen
        // klappt lokal nicht
        //$content = $this->getContent($current['content']['iframeSrc']);
        //$entry->setValue('jiracontent', $content);
        $entry->setValue('jiracontent', $current['content']['iframeSrc']);

        $entry->setValue('updatedate', date('Y-m-d H:i:s'));
        
        // User lässt sich nicht überschreiben immer der Benutzer der als API-Key-User hinterlegt ist
        //$entry->setValue('updateuser', 'JiraSyncCron');
      
  
        $entry->save();
    }

    public function getContent( $content_link)
    {
        $iframe_content = file_get_contents($content_link);
        
        $start_pos = strpos($iframe_content, '<div id="content">');
        $end_pos = strpos($iframe_content, '</div>', $start_pos) + strlen('</div>');
        $modified_iframe_content = substr($iframe_content, $start_pos, $end_pos - $start_pos);

        return $modified_iframe_content;
        }
}


