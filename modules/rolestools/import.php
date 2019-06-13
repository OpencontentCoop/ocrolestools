<?php

/** @var eZModule $module */
$module = $Params['Module'];
$tpl = eZTemplate::factory();
$roleName = $Params['RoleName'];
$remoteUrl = eZHTTPTool::instance()->getVariable('remote');
$tpl->setVariable('remote_url', $remoteUrl);

if (!$remoteUrl){
    $remoteUrl = eZINI::instance('ocrolestools.ini')->variable('NetworkSettings', 'DefaultRemoteUrl');
}

if (!empty($remoteUrl)) {
    $remoteRequest = rtrim($remoteUrl, '/') . '/rolestools/definition/' . urlencode($roleName);
    eZDebug::writeDebug($remoteRequest, __FILE__);
    $data = eZHTTPTool::instance()->getDataByURL($remoteRequest, false, eZStaticCache::USER_AGENT);

    if ($data == '[]'){
        $tpl->setVariable('errors', array("Remote role not found"));
        $tpl->setVariable('remote', array('name' => $roleName));
        $Result = array();
        $Result['content'] = $tpl->fetch('design:roletools/import.tpl');
        return;
    }

    $remoteRole = OcRoleSerializer::fromString($roleName, $data);
    if (empty($remoteRole->getData())) {
        $module->handleError(eZError::KERNEL_MODULE_NOT_FOUND, 'kernel');
        return;
    }
    $localRole = eZRole::fetchByName($roleName);

    $importer = new OcRoleImportTools($remoteRole);

    $errors = $importer->getErrors();
    $fixMe = $importer->getFixRequired();
    $fixMeErros = $importer->getFixErrors();

    $fixMeData = array_fill_keys(array_keys($fixMe), '');
    $isImportAction = eZHTTPTool::instance()->hasPostVariable('ImportRole');
    if ($isImportAction) {
        $fixMeData = $_POST;
        unset($fixMeData['ImportRole']);

        $importer->fix($fixMeData);
        $fixMe = $importer->getFixRequired();
        $fixMeErros = $importer->getFixErrors();
    }

    if ( count($errors) > 0 || count($fixMe) > 0 || !$isImportAction){
        $tpl->setVariable('remote', $remoteRole->getData());
        $tpl->setVariable('errors', $errors);
        $tpl->setVariable('fixes', $fixMe);
        $tpl->setVariable('fix_errors', $fixMeErros);
        $tpl->setVariable('fixes_data', $fixMeData);
        $tpl->setVariable('data', $importer->getImportParameters());
        $tpl->setVariable('locale', $localRole);
        $sectionList = eZSection::fetchList();
        $sections = array();
        foreach ($sectionList as $section){
            $sections[$section->attribute('id')] = $section->attribute('identifier');
        }
        $tpl->setVariable('sections', $sections);

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