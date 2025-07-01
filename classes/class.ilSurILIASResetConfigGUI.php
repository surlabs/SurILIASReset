<?php

declare(strict_types=1);

use classes\objects\Schedule;
use classes\ui\SurILIASResetList;
use Customizing\global\plugins\Services\UIComponent\UserInterfaceHook\SurILIASReset\classes\ui\Component\CustomFactory;
use ILIAS\HTTP\Wrapper\WrapperFactory;
use ILIAS\UI\Component\Input\Field\Group;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ILIAS\UI\URLBuilder;

/**
 * Class ilSurILIASResetConfigGUI
 * @ilCtrl_IsCalledBy  ilSurILIASResetConfigGUI: ilObjComponentSettingsGUI
 */
class ilSurILIASResetConfigGUI extends ilPluginConfigGUI
{
    protected ilTabsGUI $tabs;
    protected ilGlobalTemplateInterface $tpl;
    protected ilCtrl $ctrl;
    protected ilSurILIASResetPlugin $plugin;
    protected CustomFactory $customFactory;
    protected Factory $factory;
    protected Renderer $renderer;
    protected ilLanguage $language;
    protected WrapperFactory $wrapper;
    protected ILIAS\Refinery\Factory $refinery;
    private $request;

    /**
     * @throws ilCtrlException
     */
    public function performCommand(string $cmd): void
    {
        global $DIC;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->tabs = $DIC->tabs();
        $this->ctrl = $DIC->ctrl();
        $this->plugin = ilSurILIASResetPlugin::getInstance();
        $this->factory = $DIC->ui()->factory();
        $this->customFactory = new CustomFactory();
        $this->renderer = $DIC->ui()->renderer();
        $this->language = $DIC->language();
        $this->wrapper = $DIC->http()->wrapper();
        $this->refinery = $DIC->refinery();
        $this->request = $DIC->http()->request();

        $this->tpl->addCss($this->plugin->getDirectory() . '/templates/css/sur_ilias_reset_config.css');
        $this->tpl->addJavaScript($this->plugin->getDirectory() . '/templates/js/email_preview.js');

        $this->setTabs();

        $this->{$cmd}();
    }

    /**
     * @throws ilCtrlException
     */
    protected function setTabs(): void
    {
        $this->tabs->addTab('list', $this->plugin->txt('list'), $this->ctrl->getLinkTarget($this, 'configure'));
        $this->tabs->addTab('new_schedule', $this->plugin->txt('new_schedule'), $this->ctrl->getLinkTarget($this, 'newSchedule'));
    }

    /**
     * @throws ilCtrlException
     */
    protected function setSubTabs(string $active): void
    {
        $this->tabs->clearSubTabs();

        $this->ctrl->setParameterByClass('ilSurILIASResetConfigGUI', 'schedule_id', $this->wrapper->query()->retrieve('schedule_id', $this->refinery->to()->string()));

        $this->tabs->addSubTab('edit', $this->language->txt('edit'), $this->ctrl->getLinkTarget($this, 'editSchedule'));
        $this->tabs->addSubTab('delete', $this->language->txt('delete'), $this->ctrl->getLinkTarget($this, 'deleteSchedule'));

        $this->tabs->activateSubTab($active);
    }

    /**
     * @throws ilCtrlException
     */
    public function configure(): void
    {
        global $DIC;

        $this->tabs->activateTab('list');

        $columns = [
            "name" => $this->factory->table()->column()->text($this->plugin->txt("name")),
            "count" => $this->factory->table()->column()->text($this->plugin->txt("count_of_programs_or_courses")),
            "users" => $this->factory->table()->column()->text($this->plugin->txt("users")),
            "frequency" => $this->factory->table()->column()->text($this->plugin->txt("frequency")),
        ];

        $df = new \ILIAS\Data\Factory();
        $here_uri = $df->uri($DIC->http()->request()->getUri()->__toString());
        $url_builder = new URLBuilder($here_uri);

        $query_params_namespace = ['schedule_table'];
        list($url_builder, $id_token, $action_token) = $url_builder->acquireParameters(
            $query_params_namespace,
            "relay_param",
            "action"
        );

        $query = $this->wrapper->query();
        if ($query->has($action_token->getName())) {
            $action = $query->retrieve($action_token->getName(), $this->refinery->to()->string());
            $ids = $query->retrieve($id_token->getName(), $this->refinery->custom()->transformation(fn($v) => $v));
            $id = $ids[0] ?? null;

            switch ($action) {
                case "edit":
                    $this->ctrl->setParameterByClass('ilSurILIASResetConfigGUI', 'schedule_id', $id);
                    $this->ctrl->redirectByClass('ilSurILIASResetConfigGUI', 'editSchedule');
                    break;
                case "delete":
                    $this->ctrl->setParameterByClass('ilSurILIASResetConfigGUI', 'schedule_id', $id);
                    $this->ctrl->redirectByClass('ilSurILIASResetConfigGUI', 'deleteSchedule');
                    break;
                case "run":
                    $this->ctrl->setParameterByClass('ilSurILIASResetConfigGUI', 'schedule_id', $id);
                    $this->ctrl->redirectByClass('ilSurILIASResetConfigGUI', 'runSchedule');
                    break;
            }
        }

        $data_provider = new SurILIASResetList();

        $actions = [
            $this->factory->table()->action()->single(
                $this->language->txt('edit'),
                $url_builder->withParameter($action_token, "edit"),
                $id_token
            ),
            $this->factory->table()->action()->single(
                $this->language->txt('delete'),
                $url_builder->withParameter($action_token, "delete"),
                $id_token
            ),
            $this->factory->table()->action()->single(
                $this->plugin->txt('run'),
                $url_builder->withParameter($action_token, "run"),
                $id_token
            ),
        ];

        $table_component = $this->factory->table()->data('', $columns, $data_provider)->withRequest($this->request)->withActions($actions);

        $this->tpl->setContent($this->renderer->render($table_component));
    }

    /**
     * @throws Exception
     */
    public function newSchedule(): void
    {
        $this->tabs->activateTab('new_schedule');

        $this->tpl->setTitle($this->plugin->txt('new_schedule'));

        $this->tpl->setContent($this->renderScheduleForm());
    }

    /**
     * @throws ilCtrlException
     * @throws Exception
     */
    public function editSchedule(): void
    {
        $this->setSubTabs("edit");

        $this->tabs->activateTab('list');

        $schedule = new Schedule((int) $this->wrapper->query()->retrieve('schedule_id', $this->refinery->to()->string()));

        $this->tpl->setTitle($this->language->txt('edit') . ': ' . $schedule->getName());

        $this->tpl->setContent($this->renderScheduleForm($schedule));
    }

    /**
     * @throws ilCtrlException
     * @throws Exception
     */
    public function deleteSchedule(): void
    {
        $this->setSubTabs("delete");

        $this->tabs->activateTab('list');

        $schedule = new Schedule((int) $this->wrapper->query()->retrieve('schedule_id', $this->refinery->to()->string()));

        $this->tpl->setTitle($this->language->txt('delete') . ': ' . $schedule->getName());

        $this->tpl->setContent($this->buildDeleteConfirmation($schedule));
    }

    /**
     * @throws ilCtrlException
     */
    private function buildDeleteConfirmation(Schedule $schedule): string
    {
        $this->ctrl->setParameterByClass('ilSurILIASResetConfigGUI', 'schedule_id', $schedule->getId());
        $button = $this->factory->button()->standard(
            $this->language->txt("confirm"),
            $this->ctrl->getLinkTarget($this, 'confirmDeleteSchedule')
        );

        $confirmation = $this->factory->messageBox()->confirmation(
            $this->plugin->txt("delete_confirmation")
        )->withButtons([$button]);

        return $this->renderer->render($confirmation);
    }

    /**
     * @throws ilCtrlException
     * @throws Exception
     */
    public function confirmDeleteSchedule(): void
    {
        $schedule = new Schedule((int) $this->wrapper->query()->retrieve('schedule_id', $this->refinery->to()->string()));

        if ($schedule->delete()) {
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("schedule_deleted"), true);
        } else {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("schedule_delete_failed"), true);
        }

        $this->ctrl->redirect($this, 'configure');
    }

    /**
     * @throws ilCtrlException
     * @throws Exception
     */
    private function runSchedule(): void
    {
        $schedule = new Schedule((int) $this->wrapper->query()->retrieve('schedule_id', $this->refinery->to()->string()));

        try {
            $schedule->run();
            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("schedule_run_success"), true);
        } catch (Exception $e) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("schedule_run_failed") . ': ' . $e->getMessage(), true);
        }

        $this->ctrl->redirect($this, 'configure');
    }

    /**
     * @throws Exception
     */
    public function renderScheduleForm(?Schedule $schedule = null): string
    {
        $form = $this->factory->input()->container()->form()->standard(
            "#",
            $this->buildForm($schedule)
        );

        if ($this->request->getMethod() == "POST") {
            $form = $form->withRequest($this->request);
            $result = $form->getData();
            if ($result) {
                $this->saveForm($result);
            }
        }

        return $this->renderer->render($form);
    }

    private function buildForm(?Schedule $schedule): array
    {
        $inputs = [];

        $inputs['id'] = $this->factory->input()->field()->hidden()->withValue($schedule ? $schedule->getId() : '');

        $inputs['name'] = $this->factory->input()->field()->text($this->plugin->txt('name'))
            ->withValue($schedule ? $schedule->getName() : '')
            ->withRequired(true);

        $inputs['objects'] = $this->factory->input()->field()->section([
            "objects" => $this->customFactory->objectSelect(
                $this->plugin->txt('select_objects'),
                $this->plugin->txt('select_objects_info'),
                ['crs', 'prg']
            )->withRequired(true)->withValue($schedule ? $schedule->getObjectsData() : [])
        ], $this->plugin->txt('objects'));

        $users = $this->factory->input()->field()->switchableGroup([
            Schedule::USERS_ALL => $this->factory->input()->field()->group([], $this->plugin->txt('all_users')),
            Schedule::USERS_BY_ROLE => $this->factory->input()->field()->group([
                "role" => $this->customFactory->roleSelect(
                    $this->plugin->txt('select_role')
                )->withRequired(true)
            ], $this->plugin->txt('users_by_role')),
            Schedule::USERS_SPECIFIC => $this->factory->input()->field()->group([
                "specific_users" => $this->customFactory->userSelect(
                    $this->plugin->txt('select_specific_users')
                )->withRequired(true)
            ], $this->plugin->txt('specific_users')),
            Schedule::USERS_ALL_EXCEPT => $this->factory->input()->field()->group([
                "excluded_users" => $this->customFactory->userSelect(
                    $this->plugin->txt('select_excluded_users')
                )->withRequired(true)
            ], $this->plugin->txt('all_users_except')),
        ], $this->plugin->txt('users'))->withRequired(true);

        if ($schedule) {
            $users = $users->withValue([$schedule->getUsers(), $schedule->getUsersData()]);
        }

        $inputs['users'] = $this->factory->input()->field()->section([
            "users" => $users
        ], $this->plugin->txt('users'), $this->plugin->txt('users_info'));

        $frequency = $schedule ? $schedule->getFrequency() : 'manual';
        $frequency_data = [];

        if ($frequency != 'manual') {
            $frequency = "automatic";

            $frequency_data = $schedule ? $schedule->getFrequencyData() : [];
        }

        $frequency_type = $this->factory->input()->field()->switchableGroup([
            "minutely" => $this->buildFrequencyGroup("minutely"),
            "hourly" =>$this->buildFrequencyGroup("hourly"),
            "daily" => $this->buildFrequencyGroup("daily"),
            "weekly" => $this->buildFrequencyGroup("weekly"),
            "monthly" => $this->buildFrequencyGroup("monthly"),
            "yearly" => $this->buildFrequencyGroup("yearly"),
            "day_of_week" => $this->buildFrequencyGroup("day_of_week"),
            "day_of_year" => $this->buildFrequencyGroup("day_of_year"),
        ], $this->plugin->txt('frequency_type'))->withRequired(true);

        if (!empty($frequency_data)) {
            $frequency_data = [$schedule->getFrequency(), $frequency_data];

            $frequency_type = $frequency_type->withValue($frequency_data);
        }

        $inputs['frequency'] = $this->factory->input()->field()->section([
            "frequency" => $this->factory->input()->field()->switchableGroup([
                "manual" => $this->factory->input()->field()->group([], $this->plugin->txt('frequency_manual')),
                "automatic" => $this->factory->input()->field()->group([
                    "frequency_type" => $frequency_type,
                ], $this->plugin->txt('frequency_automatic')),
            ], $this->plugin->txt('frequency'))->withValue($frequency)
        ], $this->plugin->txt('frequency'));

        $inputs['notifications'] = $this->factory->input()->field()->section([
            "email_enabled" => $this->factory->input()->field()->checkbox($this->plugin->txt('email_enabled'))
                ->withValue($schedule && $schedule->isEmailEnabled()),
            "days_in_advance" => $this->factory->input()->field()->numeric($this->plugin->txt('days_in_advance'))
                ->withValue($schedule ? $schedule->getDaysInAdvance() : ""),
            "template" => $this->factory->input()->field()->textarea($this->plugin->txt('template'), $this->plugin->txt('template_info'))
                ->withValue($schedule ? $schedule->getNotificationTemplate() : "")->withAdditionalOnLoadCode(function ($id) {return "initEmailPreview('$id')";}),
        ], $this->plugin->txt('notifications'));


        return $inputs;
    }

    private function buildFrequencyGroup(string $type): Group
    {
        $inputs = [];

        switch ($type) {
            case 'minutely':
            case 'hourly':
            case 'daily':
            case 'weekly':
            case 'monthly':
            case 'yearly':
                $inputs['interval'] = $this->factory->input()->field()->numeric($this->plugin->txt("interval"))
                    ->withValue(1)
                    ->withRequired(true);
                break;
            case 'day_of_week':
                $inputs['day'] = $this->factory->input()->field()->select($this->plugin->txt('day'), [
                    0 => $this->plugin->txt('frequency_day_monday'),
                    1 => $this->plugin->txt('frequency_day_tuesday'),
                    2 => $this->plugin->txt('frequency_day_wednesday'),
                    3 => $this->plugin->txt('frequency_day_thursday'),
                    4 => $this->plugin->txt('frequency_day_friday'),
                    5 => $this->plugin->txt('frequency_day_saturday'),
                    6 => $this->plugin->txt('frequency_day_sunday'),
                ])
                    ->withValue(0)
                    ->withRequired(true);
                break;
            case 'day_of_year':
                $inputs['day'] = $this->factory->input()->field()->numeric($this->plugin->txt('day'))
                    ->withValue(1)
                    ->withRequired(true);
                $inputs['month'] = $this->factory->input()->field()->select($this->plugin->txt('month'), [
                    1 => $this->plugin->txt('frequency_month_january'),
                    2 => $this->plugin->txt('frequency_month_february'),
                    3 => $this->plugin->txt('frequency_month_march'),
                    4 => $this->plugin->txt('frequency_month_april'),
                    5 => $this->plugin->txt('frequency_month_may'),
                    6 => $this->plugin->txt('frequency_month_june'),
                    7 => $this->plugin->txt('frequency_month_july'),
                    8 => $this->plugin->txt('frequency_month_august'),
                    9 => $this->plugin->txt('frequency_month_september'),
                    10 => $this->plugin->txt('frequency_month_october'),
                    11 => $this->plugin->txt('frequency_month_november'),
                    12 => $this->plugin->txt('frequency_month_december'),
                ])
                    ->withValue(1)
                    ->withRequired(true);
                break;
        }

        return $this->factory->input()->field()->group($inputs, $this->plugin->txt($type));
    }

    /**
     * @throws Exception
     */
    private function saveForm(array $data): void
    {
        try {
            $schedule = new Schedule((int) $data['id'] ?? 0);

            $schedule->setName($data['name']);

            $schedule->saveObjectsData($data['objects']['objects']);

            $schedule->setUsers((int) $data['users']['users'][0] ?? Schedule::USERS_ALL);
            $schedule->saveUsersData($data['users']['users'][1] ?? []);

            $frequency = $data['frequency']['frequency'][0] ?? 'manual';
            $frequency_data = [];

            if ($frequency === 'automatic') {
                $frequency = $data['frequency']['frequency'][1]['frequency_type'][0] ?? 'manual';

                switch ($frequency) {
                    case 'minutely':
                    case 'hourly':
                    case 'daily':
                    case 'weekly':
                    case 'monthly':
                    case 'yearly':
                        $frequency_data['interval'] = (int) ($data['frequency']['frequency'][1]['frequency_type'][1]['interval'] ?? 1);
                        break;
                    case 'day_of_week':
                        $frequency_data['day'] = (int) ($data['frequency']['frequency'][1]['frequency_type'][1]['day'] ?? 0);
                        break;
                    case 'day_of_year':
                        $frequency_data['day'] = (int) ($data['frequency']['frequency'][1]['frequency_type'][1]['day'] ?? 1);
                        $frequency_data['month'] = (int) ($data['frequency']['frequency'][1]['frequency_type'][1]['month'] ?? 1);
                        break;
                    default:
                        throw new Exception("Invalid frequency type: $frequency");
                }
            }

            $schedule->setFrequency($frequency, $frequency_data);

            $schedule->setEmailEnabled($data['notifications']['email_enabled'] ?? false);
            $schedule->setDaysInAdvance((int) ($data['notifications']['days_in_advance'] ?? 0));
            $schedule->setNotificationTemplate($data['notifications']['template'] ?? '');

            $schedule->save();

            $this->tpl->setOnScreenMessage("success", $this->plugin->txt("schedule_saved"), true);
        } catch (Exception) {
            $this->tpl->setOnScreenMessage("failure", $this->plugin->txt("schedule_save_failed"), true);
        }
    }
}