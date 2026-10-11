<?php

function syncActiveSchoolYear(PDO $db): void
{
    $ownsTransaction = !$db->inTransaction();
    if ($ownsTransaction) {
        $db->beginTransaction();
    }

    try {
        $schoolYears = $db->query(
            'SELECT sy_id, date_start, date_end, is_current, is_archived FROM school_years FOR UPDATE'
        )->fetchAll();
        $activeOverride = $db->query(
            "SELECT setting_value FROM system_settings
             WHERE setting_key = 'active_school_year_override'
             LIMIT 1"
        )->fetchColumn();
        if ($activeOverride !== false && ctype_digit((string) $activeOverride)) {
            $overrideId = (int) $activeOverride;
            $overrideYear = null;
            foreach ($schoolYears as $schoolYear) {
                if (
                    (int) $schoolYear['sy_id'] === $overrideId
                    && (int) $schoolYear['is_archived'] === 0
                ) {
                    $overrideYear = $schoolYear;
                    break;
                }
            }
            if ($overrideYear !== null) {
                $activeCount = count(array_filter(
                    $schoolYears,
                    static fn(array $schoolYear): bool => (int) $schoolYear['is_current'] === 1
                ));
                if ((int) $overrideYear['is_current'] !== 1 || $activeCount !== 1) {
                    $db->exec('UPDATE school_years SET is_current = 0 WHERE is_current = 1');
                    $activate = $db->prepare(
                        'UPDATE school_years SET is_current = 1 WHERE sy_id = ?'
                    );
                    $activate->execute([$overrideId]);
                }
                if ($ownsTransaction) {
                    $db->commit();
                }
                return;
            }

            $db->prepare(
                "DELETE FROM system_settings WHERE setting_key = 'active_school_year_override'"
            )->execute();
        } elseif ($activeOverride !== false) {
            $db->prepare(
                "DELETE FROM system_settings WHERE setting_key = 'active_school_year_override'"
            )->execute();
        }

        $today = (new DateTimeImmutable('today'))->format('Y-m-d');
        $matchingYears = array_values(array_filter(
            $schoolYears,
            static fn(array $schoolYear): bool =>
                (int) $schoolYear['is_archived'] === 0
                && !empty($schoolYear['date_start'])
                && !empty($schoolYear['date_end'])
                && $schoolYear['date_start'] <= $today
                && $schoolYear['date_end'] >= $today
        ));

        usort($matchingYears, static function (array $left, array $right): int {
            $startDateOrder = strcmp($right['date_start'], $left['date_start']);
            return $startDateOrder !== 0
                ? $startDateOrder
                : (int) $right['sy_id'] <=> (int) $left['sy_id'];
        });

        if ($matchingYears) {
            $currentYear = $matchingYears[0];
            $activeCount = count(array_filter(
                $schoolYears,
                static fn(array $schoolYear): bool => (int) $schoolYear['is_current'] === 1
            ));

            if ((int) $currentYear['is_current'] !== 1 || $activeCount !== 1) {
                $db->exec('UPDATE school_years SET is_current = 0 WHERE is_current = 1');
                $activate = $db->prepare(
                    'UPDATE school_years SET is_current = 1 WHERE sy_id = ?'
                );
                $activate->execute([(int) $currentYear['sy_id']]);
            }
        }

        if ($ownsTransaction) {
            $db->commit();
        }
    } catch (Throwable $e) {
        if ($ownsTransaction && $db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

function schoolYearStatus(array $schoolYear): string
{
    if ((int) ($schoolYear['is_current'] ?? 0) === 1) {
        return 'Current';
    }

    $startDate = (string) ($schoolYear['date_start'] ?? '');
    $today = (new DateTimeImmutable('today'))->format('Y-m-d');

    return $startDate !== '' && $startDate > $today ? 'Upcoming' : 'Past';
}
