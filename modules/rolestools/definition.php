<?php

/** @var eZModule $module */
$module = $Params['Module'];
$roleName = $Params['RoleName'];

$serializer = new OcRoleSerializer();
$serializer->fromRoleName($roleName);

header('Content-Type: application/json');
echo json_encode( $serializer );
eZExecution::cleanExit();
