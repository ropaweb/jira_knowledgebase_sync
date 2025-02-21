<?php

/* Nutze das Addon YMCA, um die Model-Klasse zu generieren */

namespace Ropaweb\JiraKnowledgebaseSync\Api;

use Ropaweb\JiraKnowledgebaseSync\JiraKnowledgebaseSync;
use rex_yform_rest;
use rex_yform_rest_route;

class Restful
{
    public static function init(): void
    {
        $rex_jira_knowledgebase_sync_route = new rex_yform_rest_route(
            [
                'path' => '/jira_knowledgebase_sync/thing/1.0.0/',
                'auth' => '\rex_yform_rest_auth_token::checkToken',
                'type' => JiraKnowledgebaseSync::class,
                'query' => JiraKnowledgebaseSync::query(),
                'get' => [
                    'fields' => [
                        'rex_jira_knowledgebase_sync' => [
                            'id',
                            'status',
                            'name',
                            'createdate',
                            'createuser',
                            'updatedate',
                            'updateuser',
                            'uuid',
                        ],
                    ],
                ],
                'post' => [
                    'fields' => [
                        'rex_jira_knowledgebase_sync' => [
                            'status',
                            'name',
                            'createdate',
                            'createuser',
                            'updatedate',
                            'updateuser',
                            'uuid',
                        ],
                    ],
                ],
                'delete' => [
                    'fields' => [
                        'rex_jira_knowledgebase_sync' => [
                            'id',
                        ],
                    ],
                ],
            ],
        );

        rex_yform_rest::addRoute($rex_jira_knowledgebase_sync_route);
    }
}
