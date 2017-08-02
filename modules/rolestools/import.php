<?php

/** @var eZModule $module */
$module = $Params['Module'];
$roleName = $Params['RoleName'];
$remoteUrl = eZHTTPTool::instance()->getVariable('remote');

if (!$remoteUrl){
    $remoteUrl = eZINI::instance('ocrolestools.ini')->variable('NetworkSettings', 'DefaultRemoteUrl');
}

if (!empty($remoteUrl)) {
    $remoteRequest = rtrim($remoteUrl, '/') . '/rolestools/definition/' . urlencode($roleName);
    eZDebug::writeDebug($remoteRequest, __FILE__);
    $data = eZHTTPTool::instance()->getDataByURL($remoteRequest, false, eZStaticCache::USER_AGENT);

    $remoteRole = OcRoleSerializer::fromString($roleName, $data);
    if (empty($remoteRole->getData())){
        $module->handleError(eZError::KERNEL_MODULE_NOT_FOUND, 'kernel');
        return;
    }
    $localRole = eZRole::fetchByName($roleName);

    $importer = new OcRoleImportTools($remoteRole);

    $errors = $importer->getErrors();
    $fixMe = $importer->getFixRequired();


    $fixMeData = array_fill_keys(array_keys($fixMe), '');
    $isImportAction = eZHTTPTool::instance()->hasPostVariable('ImportRole');
    if ($isImportAction) {
        $fixMeData = $_POST;
        unset($fixMeData['ImportRole']);

        $importer->fix($fixMeData);
        $fixMe = $importer->getFixRequired();
    }

    if ( count($errors) > 0 || count($fixMe) > 0 || !$isImportAction){
        $tpl = eZTemplate::factory();
        $tpl->setVariable('remote', $remoteRole->getData());
        $tpl->setVariable('errors', $errors);
        $tpl->setVariable('fixes', $fixMe);
        $tpl->setVariable('fixes_data', $fixMeData);
        $tpl->setVariable('data', $importer->getImportParameters());
        $tpl->setVariable('locale', $localRole);
        $tpl->setVariable('remote_url', $remoteUrl);

        $Result = array();
        $Result['content'] = $tpl->fetch('design:roletools/import.tpl');
    }else{
        $role = $importer->import();
        $module->redirectTo('role/view/' . $role->attribute('id'));
    }


}else{
    $module->handleError(eZError::KERNEL_MODULE_NOT_FOUND, 'kernel');
    return;
}