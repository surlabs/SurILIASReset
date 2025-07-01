<?php

declare(strict_types=1);

namespace classes\objects;

use DateMalformedStringException;
use DateTime;
use Exception;
use ilChangeEvent;
use ilContainerReference;
use ilLPMarks;
use ilObject;
use ilObjectLP;
use ilObjStudyProgramme;
use ilStudyProgrammeTreeException;

class Schedule
{
    public const USERS_ALL = 1;
    public const USERS_SPECIFIC = 2;
    public const USERS_BY_ROLE = 3;
    public const USERS_ALL_EXCEPT = 4;

    private int $id;
    private string $name;
    private int $users;
    private string $frequency;
    private string $frequency_data = '';
    private string $created_at;
    private bool $email_notifications = false;
    private int $days_in_advance = 0;
    private string $notification_template = '';
    private ?string $last_run;

    public const TEXTS = [
        self::USERS_ALL => "all_users",
        self::USERS_SPECIFIC => "specific_users",
        self::USERS_BY_ROLE => "users_by_role",
        self::USERS_ALL_EXCEPT => "all_users_except",
    ];

    /**
     * @throws Exception
     */
    public function __construct(int $id = 0)
    {
        $this->id = $id;

        if ($this->id > 0) {
            $this->load($id);
        }
    }

    /**
     * @throws Exception
     */
    public function load(int $id): void
    {
        global $DIC;

        $query = /** @lang text */ "SELECT * FROM silr_schedules WHERE id = %s";
        $result = $DIC->database()->queryF($query, ['integer'], [$id]);

        if ($record = $DIC->database()->fetchAssoc($result)) {
            foreach ($record as $key => $value) {
                if ($key === 'email_notifications') {
                    $value = (bool) $value;
                }

                $this->{$key} = $value;
            }
        } else {
            throw new Exception("Schedule with ID $id not found.");
        }
    }

    public function delete(): bool
    {
        global $DIC;

        $query = /** @lang text */ "DELETE FROM silr_schedules WHERE id = %s";
        $result = $DIC->database()->manipulateF($query, ['integer'], [$this->id]);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function save(): void
    {
        if ($this->id > 0) {
            $this->update();
        } else {
            $this->insert();
        }
    }

    private function insert(): void
    {
        global $DIC;

        $this->id = $DIC->database()->nextId('silr_schedules');

        $DIC->database()->insert('silr_schedules', [
            'id' => ['integer', $this->id],
            'name' => ['text', $this->name],
            'users' => ['integer', $this->users],
            'frequency' => ['text', $this->frequency],
            'frequency_data' => ['text', $this->frequency_data],
            'created_at' => ['timestamp', $this->created_at ?? date('Y-m-d H:i:s')],
            'email_notifications' => ['integer', (int) $this->email_notifications],
            'days_in_advance' => ['integer', $this->days_in_advance],
            'notification_template' => ['text', $this->notification_template],
            'last_run' => ['timestamp', $this->last_run ?? null],
        ]);
    }

    private function update(): void
    {
        global $DIC;

        $DIC->database()->update('silr_schedules', [
            'name' => ['text', $this->name],
            'users' => ['integer', $this->users],
            'frequency' => ['text', $this->frequency],
            'frequency_data' => ['text', $this->frequency_data],
            'email_notifications' => ['integer', (int) $this->email_notifications],
            'days_in_advance' => ['integer', $this->days_in_advance],
            'notification_template' => ['text', $this->notification_template],
            'last_run' => ['timestamp', $this->last_run ?? null],
        ], [
            'id' => ['integer', $this->id]
        ]);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getUsers(): int
    {
        return $this->users;
    }

    public function setUsers(int $users): void
    {
        $this->users = $users;
    }

    public function getFrequency(): string
    {
        return $this->frequency;
    }

    public function getFrequencyData(): array
    {
        return json_decode($this->frequency_data, true);
    }

    public function setFrequency(string $frequency, array $frequency_data = []): void
    {
        $this->frequency = $frequency;
        $this->frequency_data = json_encode($frequency_data);
    }

    public function getCreatedAt(): string
    {
        return $this->created_at ?? date('Y-m-d H:i:s');
    }

    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function isEmailEnabled(): bool
    {
        return $this->email_notifications;
    }

    public function setEmailEnabled(bool $enabled): void
    {
        $this->email_notifications = $enabled;
    }

    public function getDaysInAdvance(): int
    {
        return $this->days_in_advance;
    }

    public function setDaysInAdvance(int $days): void
    {
        $this->days_in_advance = $days;
    }

    public function getNotificationTemplate(): string
    {
        return $this->notification_template;
    }

    public function setNotificationTemplate(string $template): void
    {
        $this->notification_template = $template;
    }

    public function getLastRun(): ?string
    {
        return $this->last_run;
    }

    public function setLastRun(?string $last_run): void
    {
        $this->last_run = $last_run;
    }

    /**
     * @throws Exception
     */
    public function saveObjectsData(array $objects): void
    {
        global $DIC;

        $query = /** @lang text */ "DELETE FROM silr_selected_objects WHERE schedule_id = %s";

        $DIC->database()->manipulateF($query, ['integer'], [$this->id]);

        foreach ($objects as $object) {
            $query = /** @lang text */ "INSERT INTO silr_selected_objects (schedule_id, object_id) VALUES (%s, %s) ON DUPLICATE KEY UPDATE object_id = %s";

            $DIC->database()->manipulateF(
                $query,
                ['integer', 'integer', 'integer'],
                [$this->id, $object['id'], $object['id']]
            );
        }
    }

    public function saveUsersData(array $users): void
    {
        global $DIC;

        if ($this->users === self::USERS_SPECIFIC) {
            $query = /** @lang text */ "DELETE FROM silr_selected_users WHERE schedule_id = %s";

            $DIC->database()->manipulateF($query, ['integer'], [$this->id]);

            foreach ($users["specific_users"] as $user) {
                $query = /** @lang text */ "INSERT INTO silr_selected_users (schedule_id, user_id) VALUES (%s, %s) ON DUPLICATE KEY UPDATE user_id = %s";

                $DIC->database()->manipulateF(
                    $query,
                    ['integer', 'integer', 'integer'],
                    [$this->id, $user['id'], $user['id']]
                );
            }
        } elseif ($this->users === self::USERS_BY_ROLE) {
            $query = /** @lang text */ "DELETE FROM silr_selected_roles WHERE schedule_id = %s";

            $DIC->database()->manipulateF($query, ['integer'], [$this->id]);

            foreach ($users["role"] as $role) {
                $query = /** @lang text */ "INSERT INTO silr_selected_roles (schedule_id, role_id) VALUES (%s, %s) ON DUPLICATE KEY UPDATE role_id = %s";

                $DIC->database()->manipulateF(
                    $query,
                    ['integer', 'integer', 'integer'],
                    [$this->id, $role['id'], $role['id']]
                );
            }
        } elseif ($this->users === self::USERS_ALL_EXCEPT) {
            $query = /** @lang text */ "DELETE FROM silr_excluded_users WHERE schedule_id = %s";

            $DIC->database()->manipulateF($query, ['integer'], [$this->id]);

            foreach ($users["excluded_users"] as $user) {
                $query = /** @lang text */ "INSERT INTO silr_excluded_users (schedule_id, user_id) VALUES (%s, %s) ON DUPLICATE KEY UPDATE user_id = %s";

                $DIC->database()->manipulateF(
                    $query,
                    ['integer', 'integer', 'integer'],
                    [$this->id, $user['id'], $user['id']]
                );
            }
        }
    }

    public function getObjectsData(): array
    {
        global $DIC;

        $query = /** @lang text */ "SELECT object_id FROM silr_selected_objects WHERE schedule_id = %s";
        $result = $DIC->database()->queryF($query, ['integer'], [$this->id]);

        $objects = [];
        while ($record = $DIC->database()->fetchAssoc($result)) {
            $objects[] = ["id" => $record['object_id']];
        }

        return $objects;
    }

    public function getUsersData(): array
    {
        global $DIC;

        if ($this->users === self::USERS_SPECIFIC) {
            $users = [];

            $query = /** @lang text */ "SELECT user_id FROM silr_selected_users WHERE schedule_id = %s";

            $result = $DIC->database()->queryF($query, ['integer'], [$this->id]);

            while ($record = $DIC->database()->fetchAssoc($result)) {
                $users[] = ["id" => $record['user_id']];
            }

            return ["specific_users" => $users];
        } elseif ($this->users === self::USERS_BY_ROLE) {
            $roles = [];

            $query = /** @lang text */ "SELECT role_id FROM silr_selected_roles WHERE schedule_id = %s";

            $result = $DIC->database()->queryF($query, ['integer'], [$this->id]);

            while ($record = $DIC->database()->fetchAssoc($result)) {
                $roles[] = ["id" => $record['role_id']];
            }

            return ["role" => $roles];
        } elseif ($this->users === self::USERS_ALL_EXCEPT) {
            $excluded_users = [];

            $query = /** @lang text */ "SELECT user_id FROM silr_excluded_users WHERE schedule_id = %s";

            $result = $DIC->database()->queryF($query, ['integer'], [$this->id]);

            while ($record = $DIC->database()->fetchAssoc($result)) {
                $excluded_users[] = ["id" => $record['user_id']];
            }

            return ["excluded_users" => $excluded_users];
        }

        return [];
    }

    /**
     * @throws DateMalformedStringException
     */
    public function shouldRun(): bool
    {
        if ($this->frequency == "manual") {
            return false;
        }

        $last_run = new DateTime($this->last_run ?? '1970-01-01 00:00:00');
        $today = new DateTime();
        $frequency_data = json_decode($this->frequency_data, true);

        if (!is_array($frequency_data) || empty($frequency_data)) {
            return false;
        }

        switch ($this->frequency) {
            case 'minutely':
                $interval = (int) $frequency_data['interval'];

                if ($today->diff($last_run)->i >= $interval) {
                    return true;
                }

                break;
            case 'hourly':
                $interval = (int) $frequency_data['interval'];

                if ($today->diff($last_run)->h >= $interval) {
                    return true;
                }

                break;
            case 'daily':
                $interval = (int) $frequency_data['interval'];

                if ($today->diff($last_run)->days >= $interval) {
                    return true;
                }

                break;
            case 'weekly':
                $interval = (int) $frequency_data['interval'];

                if ($today->diff($last_run)->days >= $interval * 7) {
                    return true;
                }

                break;
            case 'monthly':
                $interval = (int) $frequency_data['interval'];

                if ($today->diff($last_run)->m >= $interval) {
                    return true;
                }

                break;
            case 'yearly':
                $interval = (int) $frequency_data['interval'];

                if ($today->diff($last_run)->y >= $interval) {
                    return true;
                }

                break;
            case 'day_of_week':
                if ($today->format('Y-m-d') === $last_run->format('Y-m-d')) {
                    return false;
                }

                $today = (int) $today->format('N');
                $day_of_week = (int) $frequency_data['day'];

                if ($today === $day_of_week) {
                    return true;
                }
                break;
            case 'day_of_year':
                if ($today->format('Y-m-d') === $last_run->format('Y-m-d')) {
                    return false;
                }

                $today_month = (int) $today->format('n');
                $today_day = (int) $today->format('j');

                $month = (int) $frequency_data['month'];
                $day = (int) $frequency_data['day'];

                if ($today_month === $month && $today_day === $day) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * @throws ilStudyProgrammeTreeException
     */
    public function run(): void
    {
        $objects = $this->getObjectsToReset();

        if ($this->users === self::USERS_ALL) {
            foreach ($objects as $object) {
                $lp_obj = ilObjectLP::getInstance(ilObject::_lookupObjectId($object['ref_id']));
                $lp_obj->resetLPDataForCompleteObject();
            }
        } elseif ($this->users === self::USERS_SPECIFIC) {
            foreach ($objects as $object) {
                $lp_obj = ilObjectLP::getInstance(ilObject::_lookupObjectId($object['ref_id']));
                $user_ids = [];

                foreach ($this->getUsersData()['specific_users'] as $user) {
                    $user_ids[] = $user['id'];
                }

                $lp_obj->resetLPDataForUserIds($user_ids);
            }
        } elseif ($this->users === self::USERS_BY_ROLE) {
            global $DIC;

            foreach ($objects as $object) {
                $obj_id = ilObject::_lookupObjectId($object['ref_id']);
                $lp_obj = ilObjectLP::getInstance($obj_id);
                $user_ids = ilLPMarks::_getAllUserIds($obj_id);
                $user_ids =  array_merge($user_ids, ilChangeEvent::_getAllUserIds($obj_id));
                $user_ids_filtered = [];

                foreach ($user_ids as $user_id) {
                    $user_roles = $DIC->rbac()->review()->assignedRoles($user_id);

                    foreach ($this->getUsersData()['roles'] as $role) {
                        if (in_array($role['id'], $user_roles)) {
                            $user_ids_filtered[] = $user_id;
                            break;
                        }
                    }
                }

                $lp_obj->resetLPDataForUserIds($user_ids_filtered);
            }
        } elseif ($this->users === self::USERS_ALL_EXCEPT) {
            foreach ($objects as $object) {
                $obj_id = ilObject::_lookupObjectId($object['ref_id']);
                $lp_obj = ilObjectLP::getInstance($obj_id);
                $user_ids = ilLPMarks::_getAllUserIds($obj_id);
                $user_ids =  array_merge($user_ids, ilChangeEvent::_getAllUserIds($obj_id));

                foreach ($this->getUsersData()['excluded_users'] as $excluded_user) {
                    $user_ids = array_filter($user_ids, function ($id) use ($excluded_user) {
                        return $id !== $excluded_user['id'];
                    });
                }

                $lp_obj->resetLPDataForUserIds($user_ids);
            }
        }
    }

    /**
     * @throws ilStudyProgrammeTreeException
     */
    public function getObjectsToReset(): array
    {
        $objects = [];

        foreach ($this->getObjectsData() as $object) {
            if (!$this->refExist($objects, $object['id'])) {
                $type = ilObject::_lookupType(ilObject::_lookupObjectId($object['id']));

                $objects[] = [
                    "ref_id" => $object['id'],
                    "type" => $type,
                ];

                if ($type == 'prg') {
                    $prg = new ilObjStudyProgramme($object['id'], true);

                    $children = $this->getChildrenFromStudyProgramme($prg);

                    foreach ($children as $child) {
                        if (!$this->refExist($objects, $child['ref_id'])) {
                            $objects[] = [
                                "ref_id" => $child['ref_id'],
                                "type" => $child['type'],
                            ];
                        }
                    }
                }
            }
        }

        return $objects;
    }

    /**
     * @throws ilStudyProgrammeTreeException
     */
    private function getChildrenFromStudyProgramme(ilObjStudyProgramme $prg): array
    {
        $children = [];

        foreach ($prg->getLPChildren() as $child) {
            if (!$child instanceof ilContainerReference) {
                continue;
            }

            $child_obj = ilObject::_lookupObjectId($child->getTargetRefId());
            $type = ilObject::_lookupType($child_obj);

            $children[] = [
                "ref_id" => $child->getTargetRefId(),
                "type" => $type
            ];

            if ($type == 'prg') {
                $sub_prg = new ilObjStudyProgramme($child_obj, true);

                $children = array_merge($children, $this->getChildrenFromStudyProgramme($sub_prg));
            }
        }

        return $children;
    }

    private function refExist(array $objects, int $ref_id): bool
    {
        foreach ($objects as $object) {
            if ($object['ref_id'] === $ref_id) {
                return true;
            }
        }
        return false;
    }
}