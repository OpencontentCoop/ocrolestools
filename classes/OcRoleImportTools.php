<?php

class OcRoleImportTools
{
    /**
     * @var OcRoleSerializer
     */
    private $remote;

    private $isParsed;

    private $errors = array();

    private $fixRequired = array();

    private $importParameters = array();

    public function __construct(OcRoleSerializer $remote)
    {
        $this->remote = $remote;
        $this->parse();
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getImportParameters()
    {
        return $this->importParameters;
    }

    /**
     * @return array
     */
    public function getFixRequired()
    {
        return $this->fixRequired;
    }

    public function fix($fixData)
    {
        $importParameters = $this->importParameters;
        foreach ($importParameters['policies'] as $index => $policy) {
            foreach ($policy['Limitation'] as $limitationName => $limitationValues) {
                if (is_string($limitationValues) && isset($fixData[$limitationValues]) && isset($this->fixRequired[$limitationValues])) {
                    $fixValue = $this->fixLimitationValue($limitationName, $fixData[$limitationValues]);
                    if ($fixValue) {
                        $this->importParameters['policies'][$index]['Limitation'][$limitationName] = $fixValue;
                        unset($this->fixRequired[$limitationValues]);
                    }
                }
            }
        }
    }

    /**
     * @return eZRole
     */
    public function import()
    {
        $name = $this->importParameters['name'];
        $role = eZRole::fetchByName($name);
        if (!$role instanceof eZRole) {
            $role = eZRole::create($name);
            $role->store();
        } else {
            $role->removePolicies();
        }

        foreach ($this->importParameters['policies'] as $policy) {
            $role->appendPolicy($policy['ModuleName'], $policy['FunctionName'], $policy['Limitation']);
        }

        return $role;
    }


    private function parse()
    {
        if (!$this->isParsed) {
            $role = $this->remote->getData();

            $this->errors = array();
            $this->importParameters = $role;
            unset($this->importParameters['warnings']);

            foreach ($role['policies'] as $index => $policy) {

                if ($policy['ModuleName'] != '*') {
                    $module = eZModule::exists($policy['ModuleName']);
                    if (!$module instanceof eZModule) {
                        $this->errors[] = "Module {$policy['ModuleName']} not found";
                        unset($this->importParameters['policies'][$index]);
                        continue;
                    }
                }

                if ($policy['FunctionName'] != '*') {
                    $functions = $module->attribute('available_functions');
                    if (!isset($functions[$policy['FunctionName']])) {
                        $this->errors[] = "Module function {$policy['ModuleName']}/{$policy['FunctionName']} not found";
                        unset($this->importParameters['policies'][$index]);
                        continue;
                    }
                }

                foreach ($policy['Limitation'] as $limitationName => $limitationValues) {
                    $fixId = "$index/{$policy['ModuleName']}/{$policy['FunctionName']}/$limitationName";
                    try {
                        $value = $this->createLimitationValue($limitationName, $limitationValues);
                        $this->importParameters['policies'][$index]['Limitation'][$limitationName] = $value;
                    } catch (RuntimeException $e) {
                        $this->fixRequired['FIX-' . $fixId] = array(
                            'name' => $limitationName,
                            'values' => $limitationValues
                        );
                        $this->importParameters['policies'][$index]['Limitation'][$limitationName] = 'FIX-' . $fixId;
                    } catch (Exception $e) {
                        $this->errors[] = $e->getMessage()
                            . ' in ' . $policy['ModuleName'] . '/' . $policy['FunctionName'] . ' ' . $limitationName;
                    }

                }
            }
        }
    }

    private function fixLimitationValue($identifier, $stringValue)
    {
        $values = explode(',', $stringValue);
        switch ($identifier) {
            case 'ParentClass':
            case 'Class':
                $classIdentifiers = array();
                foreach ($values as $value) {
                    $classIdentifier = eZContentClass::classIDByIdentifier($value);
                    if ($classIdentifier) {
                        $classIdentifiers[] = $classIdentifier;
                    } else {
                        return null;
                    }
                }

                return $classIdentifiers;
                break;

            case 'Node':
                $nodes = array();
                foreach ($values as $value) {
                    $node = eZContentObjectTreeNode::fetch(intval($value));
                    if ($node) {
                        $nodes[] = $node->attribute('path_string');
                    } else {
                        return null;
                    }
                }

                return $nodes;
                break;

            case 'Subtree':
                $nodes = array();
                foreach ($values as $value) {
                    $node = eZContentObjectTreeNode::fetch(intval($value));
                    if ($node) {
                        $nodes[] = $node->attribute('path_string');
                    } else {
                        return null;
                    }
                }

                return $nodes;
                break;

            case 'Section':
                $sectionIdentifiers = array();
                foreach ($values as $value) {
                    $section = eZSection::fetchByIdentifier($value);
                    if ($section instanceof eZSection) {
                        $sectionIdentifiers[] = $section->attribute('identifier');
                    } else {
                        return null;
                    }
                }

                return $sectionIdentifiers;
                break;

            default:

                if (strpos($identifier, 'StateGroup_') === 0 || $identifier == 'NewState') {
                    $stateIdentifiers = array();
                    $groupIdentifier = str_replace('StateGroup_', '', $identifier);
                    $group = eZContentObjectStateGroup::fetchByIdentifier($groupIdentifier);
                    if (!$group) {
                        return null;
                    }
                    foreach ($values as $value) {
                        $state = eZContentObjectState::fetchByIdentifier($value, $group->attribute('id'));
                        if ($state) {
                            $stateIdentifiers[] = $state->attribute('identifier');
                        } else {
                            return null;
                        }
                    }

                    return $stateIdentifiers;
                }

                return null;
        }
    }

    private function createLimitationValue($identifier, $values)
    {
        switch ($identifier) {
            case 'ParentClass':
            case 'Class':
                $classIdentifiers = array();
                foreach ($values as $value) {
                    $classIdentifier = eZContentClass::classIDByIdentifier($value);
                    if ($classIdentifier) {
                        $classIdentifiers[] = $classIdentifier;
                    } else {
                        throw new RuntimeException("$identifier $value not found");
                    }
                }

                return $classIdentifiers;
                break;

            case 'Node':
                $nodes = array();
                foreach ($values as $value) {
                    $node = $this->fetchNode($value);
                    if ($node) {
                        $nodes[] = $node->attribute('path_string');
                    } else {
                        throw new RuntimeException("$identifier {$value['name']} not found");
                    }
                }

                return $nodes;
                break;

            case 'Subtree':
                $nodes = array();
                foreach ($values as $value) {
                    $node = $this->fetchNode($value);
                    if ($node) {
                        $nodes[] = $node->attribute('path_string');
                    } else {
                        throw new RuntimeException("$identifier {$value['name']} not found");
                    }
                }

                return $nodes;
                break;

            case 'Section':
                $sectionIdentifiers = array();
                foreach ($values as $value) {
                    $section = eZSection::fetchByIdentifier($value);
                    if ($section instanceof eZSection) {
                        $sectionIdentifiers[] = $section->attribute('identifier');
                    } else {
                        throw new RuntimeException("$identifier $value not found");
                    }
                }

                return $sectionIdentifiers;
                break;

            default:

                if (strpos($identifier, 'StateGroup_') === 0 || $identifier == 'NewState') {
                    $stateIdentifiers = array();
                    $groupIdentifier = str_replace('StateGroup_', '', $identifier);
                    $group = eZContentObjectStateGroup::fetchByIdentifier($groupIdentifier);
                    if (!$group) {
                        throw new Exception("$identifier state group not found");
                    }
                    foreach ($values as $value) {
                        $state = eZContentObjectState::fetchByIdentifier($value, $group->attribute('id'));
                        if ($state) {
                            $stateIdentifiers[] = $state->attribute('identifier');
                        } else {
                            throw new Exception("$identifier $value not found");
                        }
                    }

                    return $stateIdentifiers;
                }

                return $values;
        }
    }

    /**
     * @param $value
     * @return eZContentObjectTreeNode
     */
    private function fetchNode($value)
    {
        $node = eZContentObjectTreeNode::fetchByRemoteID($value['node_remote_id']);
        if ($node instanceof eZContentObjectTreeNode) {
            return $node;
        }

        $object = eZContentObject::fetchByRemoteID($value['object_remote_id']);
        if ($object instanceof eZContentObject) {
            return $object->attribute('main_node');
        }

        return null;
    }

}