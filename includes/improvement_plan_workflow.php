<?php
// Shared improvement-plan workflow and immutable history helpers.

if (!defined('IP_STATUS_DRAFT')) define('IP_STATUS_DRAFT', 'draft');
if (!defined('IP_STATUS_SUBMITTED')) define('IP_STATUS_SUBMITTED', 'submitted');
if (!defined('IP_STATUS_RETURNED')) define('IP_STATUS_RETURNED', 'returned_to_school_head');
if (!defined('IP_STATUS_RESUBMITTED')) define('IP_STATUS_RESUBMITTED', 'resubmitted_to_coordinator');
if (!defined('IP_STATUS_APPROVED')) define('IP_STATUS_APPROVED', 'approved');
if (!defined('IP_STATUS_FINALIZED')) define('IP_STATUS_FINALIZED', 'finalized');

function ipRecordHistory(PDO $db, int $planId, int $actorId, string $action, ?string $fromStatus, ?string $toStatus, string $remarks = ''): void
{
    $q = $db->prepare('SELECT * FROM improvement_plans WHERE plan_id = ?');
    $q->execute([$planId]);
    $plan = $q->fetch(PDO::FETCH_ASSOC);
    if (!$plan) return;

    $db->prepare('INSERT INTO improvement_plan_history
        (plan_id, version_no, action, from_status, to_status, actor_id, remarks, snapshot_json)
        VALUES (?, (SELECT COALESCE(MAX(v.version_no), 0) + 1 FROM (SELECT version_no FROM improvement_plan_history WHERE plan_id = ?) v), ?, ?, ?, ?, ?, ?)')
        ->execute([
            $planId,
            $planId,
            $action,
            $fromStatus,
            $toStatus,
            $actorId,
            $remarks ?: null,
            json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
}

function ipFindSchoolHead(PDO $db, int $schoolId): ?int
{
    $q = $db->prepare("SELECT user_id FROM users WHERE school_id = ? AND role = 'school_head' AND status = 'active' ORDER BY user_id LIMIT 1");
    $q->execute([$schoolId]);
    $id = $q->fetchColumn();
    return $id ? (int) $id : null;
}

function ipStatusLabel(string $status): string
{
    return match ($status) {
        IP_STATUS_SUBMITTED => 'With SBM Coordinator',
        IP_STATUS_RETURNED => 'Returned to School Head',
        IP_STATUS_RESUBMITTED => 'With SBM Coordinator for Final Review',
        IP_STATUS_APPROVED => 'Approved - Awaiting Validation',
        IP_STATUS_FINALIZED => 'Finalized',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
}

function ipHistoryActionLabel(string $action): string
{
    return match ($action) {
        'migration_snapshot' => 'Initial submission',
        'created' => 'Draft created',
        'school_head_submitted' => 'Submitted by School Head',
        'coordinator_revision' => 'Coordinator revised and returned',
        'coordinator_returned' => 'Coordinator returned for review',
        'school_head_revision' => 'School Head revised',
        'school_head_resubmitted' => 'School Head resubmitted',
        'coordinator_approved' => 'Coordinator approved',
        'coordinator_validated' => 'Coordinator validated and finalized',
        default => ucfirst(str_replace('_', ' ', $action)),
    };
}

function ipStageIndex(string $status): int
{
    return match ($status) {
        IP_STATUS_SUBMITTED => 1,
        IP_STATUS_RESUBMITTED => 3,
        IP_STATUS_APPROVED => 4,
        IP_STATUS_FINALIZED => 6,
        IP_STATUS_RETURNED => 2,
        default => 0,
    };
}
