<?php

declare(strict_types=1);

namespace MautomicCrmTests;

use MautomicCrmTests\Page\Acceptance\TaskPage;
use MautomicCrmTests\Step\Acceptance\TaskStep;

class TaskManagementCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function createTask(AcceptanceTester $I, TaskStep $task): void
    {
        $I->wantTo('Create a standalone task');

        $title = $I->uniqueName('Task');
        $task->createTask($title, 'Task description for E2E');

        $I->waitForText($title, 15);
        $I->takeNamedScreenshot('task_created');
    }

    public function createTaskLinkedToDeal(AcceptanceTester $I, TaskStep $task): void
    {
        $I->wantTo('Create a task linked to a deal');

        $title = $I->uniqueName('TaskLinked');
        $task->createTask($title, 'Linked task description', 'E2E Seed Deal');

        $I->waitForText($title, 15);
        $I->takeNamedScreenshot('task_linked_to_deal');
    }

    public function viewTaskDetail(AcceptanceTester $I, TaskStep $task): void
    {
        $I->wantTo('View task detail page');

        $title = $I->uniqueName('TaskView');
        $task->createTask($title, 'Detail view test');

        $I->waitForText($title, 15);
        $I->seeElement('#toolbar');
        $I->takeNamedScreenshot('task_detail');
    }

    public function editTaskAndChangeStatus(AcceptanceTester $I, TaskStep $task): void
    {
        $I->wantTo('Edit a task and change its status');

        $title = $I->uniqueName('TaskEdit');
        $task->createTask($title, 'Will be edited');

        $I->waitForText($title, 15);
        $I->click(TaskPage::$editButton);
        $I->waitForElementVisible(TaskPage::$titleField, 15);
        $I->hideDebugToolbar();

        $editedTitle = $title.' Done';
        $I->fillField(TaskPage::$titleField, $editedTitle);
        $I->selectChosenOption(TaskPage::$statusField, 'completed');
        $I->clickSaveAndClose('task');
        $I->waitForPageLoad();

        $I->waitForText($editedTitle, 15);
        $I->takeNamedScreenshot('task_edited');
    }

    public function deleteTask(AcceptanceTester $I, TaskStep $task): void
    {
        $I->wantTo('Delete a task');

        $title = $I->uniqueName('TaskDel');
        $task->createTask($title);

        $I->waitForText($title, 15);

        $I->waitForElementClickable(TaskPage::$dropDown, 10);
        $I->click(TaskPage::$dropDown);
        $I->waitForElementClickable(TaskPage::$deleteAction, 5);
        $I->click(TaskPage::$deleteAction);
        $I->confirmDeletion();
        $I->waitForPageLoad();

        $I->waitForText('deleted', 15);
        $I->takeNamedScreenshot('task_deleted');
    }
}
