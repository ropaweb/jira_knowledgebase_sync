<?php

use rex_config_form;
use rex_fragment;
use rex_i18n;
use rex_view;

echo rex_view::title(rex_i18n::msg('jira_knowledgebase_sync.title'));

$addon = rex_addon::get('jira_knowledgebase_sync');

$form = rex_config_form::factory($addon->getName());

$field = $form->addInputField('text', 'url', null, ['class' => 'form-control']);
$field->setLabel(rex_i18n::msg('jira_knowledgebase_sync_config_url_label'));
$field->setNotice(rex_i18n::msg('jira_knowledgebase_sync_config_url_notice'));

$field = $form->addInputField('text', 'user', null, ['class' => 'form-control']);
$field->setLabel(rex_i18n::msg('jira_knowledgebase_sync_config_user_label'));
$field->setNotice(rex_i18n::msg('jira_knowledgebase_sync_config_user_notice'));

$field = $form->addInputField('text', 'api_key', null, ['class' => 'form-control']);
$field->setLabel(rex_i18n::msg('jira_knowledgebase_sync_config_api_key_label'));
$field->setNotice(rex_i18n::msg('jira_knowledgebase_sync_config_api_key_notice'));

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $addon->i18n('jira_knowledgebase_sync_config'), false);
$fragment->setVar('body', $form->get(), false);
echo $fragment->parse('core/page/section.php');
