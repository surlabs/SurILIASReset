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
        'notification_template' => ['type' => 'text'],
        'last_run' => ['type' => 'timestamp', 'notnull' => false],
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
?>