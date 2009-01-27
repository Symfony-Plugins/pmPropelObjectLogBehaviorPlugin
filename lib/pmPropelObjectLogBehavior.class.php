<?php

/**
 * @author Patricio Mac Adden <pmacadden@desarrollo.cespi.unlp.edu.ar>
 */

class pmPropelObjectLogBehavior
{
  // contains the current objects between preSave and postSave methods
  private static $current_objects = array();

  public function preSave($object, $con = null)
  {
    // skip all if the object is new
    if ($object->isNew()) return;

    $peer_class = get_class($object).'Peer';

    $sf_user = sfContext::getInstance()->getUser();
    $sf_user_id = $sf_user->isAuthenticated() ? $sf_user->getGuardUser()->getId() : null;

    $object_class = get_class($object);

    $object_key = $object->getId();

    // get the previous snapshot of the object
    $previous_object = call_user_func(array($peer_class, 'retrieveByPk'), $object->getId());

    foreach (call_user_func(array($peer_class, 'getPhpNameMap')) as $column) {
      // guess the field name
      $field_name = sfInflector::underscore(get_class($object)).'.'.$column;
      // ... and the attribute name
      $attribute = call_user_func(array($peer_class, 'translateFieldName'), $field_name, BasePeer::TYPE_COLNAME, BasePeer::TYPE_PHPNAME);

      $previous_value = call_user_func(array($previous_object, 'get'.$attribute));

      $actual_value = call_user_func(array($object, 'get'.$attribute));

      // if previous value and actual value does not match...
      if ($previous_value != $actual_value) {
        // ... create the object log object and populate it
        $pm_object_log = new pmObjectLog();
        $pm_object_log->setUserId($sf_user_id);
        $pm_object_log->setObjectClass($object_class);
        $pm_object_log->setObjectKey($object_key);
        $pm_object_log->setColumnName($attribute);
        $pm_object_log->setPreviousValue($previous_value);
        $pm_object_log->setActualValue($actual_value);

        // do not save it, it will be saved later, in postSave method
        self::$current_objects[] = $pm_object_log;
      }
    }
  }

  public function postSave($object, $con = null)
  {
    // save the object logs
    foreach (self::$current_objects as $current_object)
      $current_object->save();
    // reset the current objects array
    self::$current_objects = array();
  }

  public function getObjectLogs($object, $criteria = null, $con = null)
  {
    if ($criteria === null) {
      $criteria = new Criteria();
    } elseif ($criteria instanceof Criteria) {
      $criteria = clone $criteria;
    }

    $peer_class = get_class($object).'Peer';

    $criteria->add(pmObjectLogPeer::OBJECT_CLASS, get_class($object));
    $criteria->add(pmObjectLogPeer::OBJECT_KEY, $object->getPrimaryKey());

    return pmObjectLogPeer::doSelect($criteria);
  }
}
