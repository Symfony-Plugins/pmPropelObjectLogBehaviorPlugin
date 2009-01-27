<?php

sfPropelBehavior::registerMethods('pm_object_log',
                                  array(array('pmPropelObjectLogBehavior', 'getObjectLogs')));

sfPropelBehavior::registerHooks('pm_object_log',
                                array(':save:pre' => array('pmPropelObjectLogBehavior', 'preSave'),
                                      ':save:post' => array('pmPropelObjectLogBehavior', 'postSave')));
