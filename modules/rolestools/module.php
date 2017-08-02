<?php

$ViewList['import'] = array(
    'functions' => array('import'),
    'script' => 'import.php',
    'ui_context' => 'edit',
    'default_navigation_part' => 'ezsetupnavigationpart',
    'single_post_actions' => array(
        'SyncButton' => 'Sync',
        'InstallButton' => 'Install'
    ),
    'params' => array('RoleName'),
    'unordered_params' => array()
);

$ViewList['definition'] = array(
    'functions' => array('definition'),
    'script' => 'definition.php',
    'params' => array('RoleName'),
    'default_navigation_part' => 'ezsetupnavigationpart',
    'unordered_params' => array()
);

$FunctionList['import'] = array();
$FunctionList['definition'] = array();