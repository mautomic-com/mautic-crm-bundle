<?php

declare(strict_types=1);

namespace MauticPlugin\MautomicCrmBundle;

final class MautomicCrmEvents
{
    public const PIPELINE_PRE_SAVE = 'mautomic_crm.pipeline_pre_save';

    public const PIPELINE_POST_SAVE = 'mautomic_crm.pipeline_post_save';

    public const PIPELINE_PRE_DELETE = 'mautomic_crm.pipeline_pre_delete';

    public const PIPELINE_POST_DELETE = 'mautomic_crm.pipeline_post_delete';

    public const DEAL_PRE_SAVE = 'mautomic_crm.deal_pre_save';

    public const DEAL_POST_SAVE = 'mautomic_crm.deal_post_save';

    public const DEAL_PRE_DELETE = 'mautomic_crm.deal_pre_delete';

    public const DEAL_POST_DELETE = 'mautomic_crm.deal_post_delete';

    public const DEAL_STAGE_CHANGED = 'mautomic_crm.deal_stage_changed';

    public const ON_CAMPAIGN_TRIGGER_DECISION = 'mautomic_crm.on_campaign_trigger_decision';

    public const ON_CAMPAIGN_BATCH_ACTION = 'mautomic_crm.on_campaign_batch_action';

    public const TASK_PRE_SAVE = 'mautomic_crm.task_pre_save';

    public const TASK_POST_SAVE = 'mautomic_crm.task_post_save';

    public const TASK_PRE_DELETE = 'mautomic_crm.task_pre_delete';

    public const TASK_POST_DELETE = 'mautomic_crm.task_post_delete';

    public const NOTE_PRE_SAVE = 'mautomic_crm.note_pre_save';

    public const NOTE_POST_SAVE = 'mautomic_crm.note_post_save';

    public const NOTE_PRE_DELETE = 'mautomic_crm.note_pre_delete';

    public const NOTE_POST_DELETE = 'mautomic_crm.note_post_delete';

    public const DEAL_FIELD_PRE_SAVE = 'mautomic_crm.deal_field_pre_save';

    public const DEAL_FIELD_POST_SAVE = 'mautomic_crm.deal_field_post_save';

    public const DEAL_FIELD_PRE_DELETE = 'mautomic_crm.deal_field_pre_delete';

    public const DEAL_FIELD_POST_DELETE = 'mautomic_crm.deal_field_post_delete';
}
