<#1>
<?php
global $DIC;
$db = $DIC->database();

if (!$db->tableExists('silr_schedules')) {
    $db->createTable('silr_schedules', [
        'id' => ['type' => 'integer', 'notnull' => true],
        'name' => ['type' => 'text', 'notnull' => true],
        'users' => ['type' => 'integer', 'notnull' => true],
        'frequency' => ['type' => 'text', 'notnull' => true],
        'frequency_data' => ['type' => 'text', 'notnull' => true],
        'created_at' => ['type' => 'timestamp', 'notnull' => true],
        'email_notifications' => ['type' => 'integer'],
        'days_in_advance' => ['type' => 'integer'],
        'notification_subject' => ['type' => 'text'],
        'notification_template' => ['type' => 'text'],
        'last_run' => ['type' => 'timestamp', 'notnull' => false],
        'last_notification' => ['type' => 'timestamp', 'notnull' => false],
    ]);

    $db->addPrimaryKey('silr_schedules', ['id']);

    $db->createSequence('silr_schedules');
}
if (!$db->tableExists('silr_selected_objects')) {
    $db->createTable('silr_selected_objects', [
        'schedule_id' => ['type' => 'integer', 'notnull' => true],
        'object_id' => ['type' => 'integer', 'notnull' => true],
    ]);

    $db->addPrimaryKey('silr_selected_objects', ['schedule_id', 'object_id']);
}
if (!$db->tableExists('silr_selected_users')) {
    $db->createTable('silr_selected_users', [
        'schedule_id' => ['type' => 'integer', 'notnull' => true],
        'user_id' => ['type' => 'integer', 'notnull' => true],
    ]);

    $db->addPrimaryKey('silr_selected_users', ['schedule_id', 'user_id']);
}
if (!$db->tableExists('silr_excluded_users')) {
    $db->createTable('silr_excluded_users', [
        'schedule_id' => ['type' => 'integer', 'notnull' => true],
        'user_id' => ['type' => 'integer', 'notnull' => true],
    ]);

    $db->addPrimaryKey('silr_excluded_users', ['schedule_id', 'user_id']);
}
if (!$db->tableExists('silr_selected_roles')) {
    $db->createTable('silr_selected_roles', [
        'schedule_id' => ['type' => 'integer', 'notnull' => true],
        'role_id' => ['type' => 'integer', 'notnull' => true],
    ]);

    $db->addPrimaryKey('silr_selected_roles', ['schedule_id', 'role_id']);
}
if (!$db->tableExists('silr_history')) {
    $db->createTable('silr_history', [
        'id' => ['type' => 'integer', 'notnull' => true],
        'schedule_id' => ['type' => 'integer', 'notnull' => true],
        'date' => ['type' => 'timestamp', 'notnull' => true],
        'method' => ['type' => 'integer', 'notnull' => true],
        'duration' => ['type' => 'integer', 'notnull' => true]
    ]);

    $db->addPrimaryKey('silr_history', ['id']);
    $db->createSequence('silr_history');
}
if (!$db->tableExists('silr_users_affected')) {
    $db->createTable('silr_users_affected', [
        'execution_id' => ['type' => 'integer', 'notnull' => true],
        'user_id' => ['type' => 'integer', 'notnull' => true],
    ]);

    $db->addPrimaryKey('silr_users_affected', ['execution_id', 'user_id']);
}
if (!$db->tableExists('silr_objects_affected')) {
    $db->createTable('silr_objects_affected', [
        'execution_id' => ['type' => 'integer', 'notnull' => true],
        'object_id' => ['type' => 'integer', 'notnull' => true],
    ]);

    $db->addPrimaryKey('silr_objects_affected', ['execution_id', 'object_id']);
}
?>