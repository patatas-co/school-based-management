<?php
// ============================================================
// includes/stakeholder_lifecycle.php
// Central service for external-stakeholder account lifecycle:
//   • Email notification on account creation
//   • Auto-deactivation when cycle access window ends
//   • Manual reactivation with optional new end-date
//   • Warning emails N days before deactivation
// ============================================================
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/email_service.php';

// ── Helpers ──────────────────────────────────────────────────

/**
 * Pull a system_config value; return $default when absent.
 */
function scfg(PDO $db, string $key, mixed $default = null): mixed
{
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        $st = $db->prepare("SELECT config_value FROM system_config WHERE config_key=? LIMIT 1");
        $st->execute([$key]);
        $cache[$key] = $st->fetchColumn();
    }
    return $cache[$key] !== false ? $cache[$key] : $default;
}

/**
 * Format a datetime string as a human-readable Manila-timezone date.
 * e.g.  "Monday, June 16, 2025 at 5:00 PM"
 */
function fmtManilaDate(?string $dt, string $format = 'l, F j, Y \a\t g:i A'): string
{
    if (!$dt)
        return '—';
    try {
        $d = new DateTime($dt, new DateTimeZone('Asia/Manila'));
        return $d->format($format);
    } catch (\Exception $e) {
        return $dt;
    }
}

// ── 1. Account-creation email for external stakeholders ──────

/**
 * Send a stakeholder-specific welcome email that includes:
 *  - Login credentials / set-password link
 *  - Temporary-account notice
 *  - Cycle access window (start → end)
 */
function sendStakeholderWelcomeEmail(
    PDO $db,
    array $user,        // must have user_id, full_name, email
    int $cycleId,
    bool $resend = false
): bool {
    // Fetch cycle dates
    $cycleStmt = $db->prepare(
        "SELECT c.cycle_id, c.stakeholder_access_start, c.stakeholder_access_end,
                sy.label AS sy_label, s.school_name
         FROM sbm_cycles c
         JOIN school_years sy ON c.sy_id = sy.sy_id
         JOIN schools       s  ON c.school_id = s.school_id
         WHERE c.cycle_id = ?"
    );
    $cycleStmt->execute([$cycleId]);
    $cycle = $cycleStmt->fetch();

    $syLabel = $cycle['sy_label'] ?? '—';
    $startDate = $cycle['stakeholder_access_start']
        ? fmtManilaDate($cycle['stakeholder_access_start'], 'F j, Y')
        : 'as soon as your account is ready';
    $endDate = $cycle['stakeholder_access_end']
        ? fmtManilaDate($cycle['stakeholder_access_end'], 'F j, Y \a\t g:i A')
        : 'the end of the assessment cycle';
    $schoolName = $cycle['school_name'] ?? 'the school';

    // Generate setup token
    try {
        $token = generateSetupToken($db, $user['user_id']);
    } catch (\Exception $e) {
        error_log('StakeholderWelcome: token generation failed uid=' . $user['user_id'] . ': ' . $e->getMessage());
        return false;
    }

    $setupLink = baseUrl() . '/set_password.php?token=' . urlencode($token);
    $expiry = ($_ENV['SBM_TOKEN_EXPIRY_HOURS'] ?? 48) . ' hours';

    $html = _buildStakeholderWelcomeHtml(
        $user['full_name'],
        $user['email'],
        $setupLink,
        $expiry,
        $syLabel,
        $startDate,
        $endDate,
        $schoolName,
        $resend
    );

    $mail = new PHPMailer(true);
    try {
        _configureMailer($mail);
        $mail->addAddress($user['email'], $user['full_name']);
        $mail->isHTML(true);
        $mail->Subject = ($resend ? '[Reminder] ' : '') .
            "Your Evaluator Access for {$schoolName} SBM Assessment (SY {$syLabel})";
        $mail->Body = $html;
        $mail->AltBody = _stakeholderWelcomePlainText(
            $user['full_name'],
            $setupLink,
            $syLabel,
            $startDate,
            $endDate,
            $schoolName,
            $expiry
        );
        $mail->send();

        $db->prepare("UPDATE users SET email_sent_at = NOW() WHERE user_id = ?")
            ->execute([$user['user_id']]);
        $db->prepare(
            "INSERT INTO email_logs (user_id, email_type, recipient_email, status)
             VALUES (?, 'stakeholder_welcome', ?, 'sent')"
        )->execute([$user['user_id'], $user['email']]);

        return true;

    } catch (\Exception $e) {
        $errMsg = $mail->ErrorInfo ?: $e->getMessage();
        $db->prepare(
            "INSERT INTO email_logs (user_id, email_type, recipient_email, status, error_message)
             VALUES (?, 'stakeholder_welcome', ?, 'failed', ?)"
        )->execute([$user['user_id'], $user['email'], $errMsg]);
        error_log('StakeholderWelcomeEmail Error: ' . $errMsg);
        return false;
    }
}

// ── 2. Warning email (N days before deactivation) ───────────

/**
 * Send a "your access expires soon" email.
 * Called by the cron job or manually.
 */
function sendStakeholderExpiryWarning(
    PDO $db,
    array $user,
    int $cycleId,
    int $daysLeft
): bool {
    $cycleStmt = $db->prepare(
        "SELECT c.stakeholder_access_end, sy.label, s.school_name
         FROM sbm_cycles c
         JOIN school_years sy ON c.sy_id = sy.sy_id
         JOIN schools       s  ON c.school_id = s.school_id
         WHERE c.cycle_id = ?"
    );
    $cycleStmt->execute([$cycleId]);
    $cycle = $cycleStmt->fetch();

    $endDate = $cycle ? fmtManilaDate($cycle['stakeholder_access_end'], 'F j, Y \a\t g:i A') : '—';
    $syLabel = $cycle['label'] ?? '—';
    $schoolName = $cycle['school_name'] ?? 'the school';

    $portalUrl = baseUrl() . '/stakeholder/self_assessment.php';
    $html = _buildExpiryWarningHtml($user['full_name'], $schoolName, $syLabel, $endDate, $daysLeft, $portalUrl);

    $mail = new PHPMailer(true);
    try {
        _configureMailer($mail);
        $mail->addAddress($user['email'], $user['full_name']);
        $mail->isHTML(true);
        $mail->Subject = "Action needed: Your SBM access expires in {$daysLeft} day(s) — {$schoolName}";
        $mail->Body = $html;
        $mail->AltBody = "Hello {$user['full_name']},\n\n"
            . "Your evaluator access for the {$schoolName} SBM Assessment (SY {$syLabel}) "
            . "will expire on {$endDate}.\n\n"
            . "Please complete your assessment at: {$portalUrl}\n\n"
            . "DIHS SBM Portal";
        $mail->send();

        $db->prepare(
            "INSERT INTO email_logs (user_id, email_type, recipient_email, status)
             VALUES (?, 'stakeholder_expiry_warning', ?, 'sent')"
        )->execute([$user['user_id'], $user['email']]);
        return true;

    } catch (\Exception $e) {
        $db->prepare(
            "INSERT INTO email_logs (user_id, email_type, recipient_email, status, error_message)
             VALUES (?, 'stakeholder_expiry_warning', ?, 'failed', ?)"
        )->execute([$user['user_id'], $user['email'], $mail->ErrorInfo ?: $e->getMessage()]);
        return false;
    }
}

// ── 3. Auto-deactivation ─────────────────────────────────────

/**
 * Deactivate all evaluators whose cycle access window has ended.
 * Safe to call multiple times (idempotent — checks auto_deactivated_at).
 *
 * Returns array of stats: ['deactivated' => n, 'cycles' => [...]]
 */
function runStakeholderAutoDeactivation(PDO $db, string $trigger = 'cron'): array
{
    if (!(int) scfg($db, 'stakeholder_auto_deactivate_enabled', 1)) {
        return ['deactivated' => 0, 'cycles' => [], 'skipped' => 'disabled'];
    }

    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));

    // Find cycles whose access window ended and haven't been deactivated yet
    $cycles = $db->prepare(
        "SELECT c.cycle_id, c.school_id, c.stakeholder_access_end,
                sy.label AS sy_label, s.school_name
         FROM sbm_cycles c
         JOIN school_years sy ON c.sy_id = sy.sy_id
         JOIN schools       s  ON c.school_id = s.school_id
         WHERE c.stakeholder_access_end IS NOT NULL
           AND c.stakeholder_access_end <= NOW()
           AND c.auto_deactivated_at IS NULL"
    );
    $cycles->execute();
    $affectedCycles = $cycles->fetchAll();

    $totalDeactivated = 0;
    $summary = [];

    foreach ($affectedCycles as $cycle) {
        $count = _deactivateEvaluatorsForCycle(
            $db,
            (int) $cycle['cycle_id'],
            $trigger,
            null  // no actor for cron
        );
        $totalDeactivated += $count;
        $summary[] = [
            'cycle_id' => $cycle['cycle_id'],
            'sy_label' => $cycle['sy_label'],
            'school' => $cycle['school_name'],
            'count' => $count,
            'ended_at' => $cycle['stakeholder_access_end'],
        ];

        // Mark cycle as auto-deactivated
        $db->prepare(
            "UPDATE sbm_cycles
             SET auto_deactivated_at = NOW(),
                 auto_deactivated_by = ?
             WHERE cycle_id = ?"
        )->execute([$trigger, $cycle['cycle_id']]);
    }

    return ['deactivated' => $totalDeactivated, 'cycles' => $summary];
}

/**
 * Deactivate all active evaluators for a specific cycle.
 * Returns number of accounts deactivated.
 */
function _deactivateEvaluatorsForCycle(
    PDO $db,
    int $cycleId,
    string $trigger = 'manual',
    ?int $actorId = null
): int {
    // Fetch evaluators that are still active
    $evals = $db->prepare(
        "SELECT ce.evaluator_id, ce.user_id, ce.school_id, u.full_name, u.email, u.status
         FROM cycle_evaluators ce
         JOIN users u ON ce.user_id = u.user_id
         WHERE ce.cycle_id = ?
           AND ce.is_active = 1
           AND u.status = 'active'"
    );
    $evals->execute([$cycleId]);
    $evals = $evals->fetchAll();

    if (empty($evals))
        return 0;

    $count = 0;
    foreach ($evals as $ev) {
        // Deactivate user account
        $db->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?")
            ->execute([$ev['user_id']]);

        // Mark in cycle_evaluators
        $db->prepare(
            "UPDATE cycle_evaluators
             SET is_active = 0, deactivated_at = NOW()
             WHERE evaluator_id = ?"
        )->execute([$ev['evaluator_id']]);

        // Audit log
        $db->prepare(
            "INSERT INTO cycle_evaluator_status_log
                (cycle_id, user_id, school_id, action, triggered_by, actor_id)
             VALUES (?, ?, ?, 'deactivated', ?, ?)"
        )->execute([$cycleId, $ev['user_id'], $ev['school_id'], $trigger, $actorId]);

        $count++;
    }
    return $count;
}

// ── 4. Reactivation ──────────────────────────────────────────

/**
 * Reactivate one or all deactivated evaluators for a cycle.
 * Optionally extend the cycle's access end date.
 *
 * $userIds  = null  → reactivate ALL deactivated evaluators for the cycle
 * $userIds  = [1,2] → reactivate only those user IDs
 * $newEndDt = 'Y-m-d H:i:s' string or null (keep existing)
 *
 * Returns ['reactivated' => n, 'errors' => [...]]
 */
function reactivateEvaluators(
    PDO $db,
    int $cycleId,
    ?array $userIds = null,
    ?string $newEndDt = null,
    int $actorId = 0
): array {
    // Extend end date if requested
    if ($newEndDt !== null) {
        $db->prepare(
            "UPDATE sbm_cycles
             SET stakeholder_access_end = ?,
                 auto_deactivated_at    = NULL,
                 auto_deactivated_by    = NULL
             WHERE cycle_id = ?"
        )->execute([$newEndDt, $cycleId]);
    }

    // Build query
    $sql = "SELECT ce.evaluator_id, ce.user_id, ce.school_id, u.full_name, u.email
            FROM cycle_evaluators ce
            JOIN users u ON ce.user_id = u.user_id
            WHERE ce.cycle_id = ?
              AND ce.is_active = 0";
    $params = [$cycleId];

    if (!empty($userIds)) {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $sql .= " AND ce.user_id IN ($placeholders)";
        $params = array_merge($params, array_map('intval', $userIds));
    }

    $evals = $db->prepare($sql);
    $evals->execute($params);
    $evals = $evals->fetchAll();

    $count = 0;
    $errors = [];

    foreach ($evals as $ev) {
        try {
            $db->prepare("UPDATE users SET status = 'active' WHERE user_id = ?")
                ->execute([$ev['user_id']]);

            $db->prepare(
                "UPDATE cycle_evaluators
                 SET is_active = 1, reactivated_at = NOW()
                 WHERE evaluator_id = ?"
            )->execute([$ev['evaluator_id']]);

            $db->prepare(
                "INSERT INTO cycle_evaluator_status_log
                    (cycle_id, user_id, school_id, action, triggered_by, actor_id)
                 VALUES (?, ?, ?, 'reactivated', 'admin', ?)"
            )->execute([$cycleId, $ev['user_id'], $ev['school_id'], $actorId]);

            $count++;
        } catch (\Throwable $e) {
            $errors[] = "uid {$ev['user_id']}: " . $e->getMessage();
        }
    }

    // Clear auto_deactivated_at so the cron won't skip re-deactivating later
    if ($count > 0 && $newEndDt !== null) {
        $db->prepare(
            "UPDATE sbm_cycles SET auto_deactivated_at = NULL WHERE cycle_id = ?"
        )->execute([$cycleId]);
    }

    return ['reactivated' => $count, 'errors' => $errors];
}

// ── 5. Warning email cron helper ─────────────────────────────

/**
 * Send expiry-warning emails to evaluators whose access ends in ≤ N days.
 * Called from cron.
 */
function runStakeholderExpiryWarnings(PDO $db): array
{
    $warnDays = (int) scfg($db, 'stakeholder_email_notify_days_before', 3);
    if ($warnDays < 1)
        return ['sent' => 0];

    $threshold = (new DateTime('now', new DateTimeZone('Asia/Manila')))
        ->modify("+{$warnDays} days")
        ->format('Y-m-d H:i:s');

    $evals = $db->prepare(
        "SELECT ce.user_id, ce.cycle_id, u.full_name, u.email
         FROM cycle_evaluators ce
         JOIN users u         ON ce.user_id = u.user_id
         JOIN sbm_cycles c    ON ce.cycle_id = c.cycle_id
         WHERE ce.is_active = 1
           AND c.stakeholder_access_end IS NOT NULL
           AND c.stakeholder_access_end <= ?
           AND c.stakeholder_access_end >  NOW()
           AND c.auto_deactivated_at IS NULL
           AND u.status = 'active'
           AND NOT EXISTS (
               SELECT 1 FROM email_logs el
               WHERE el.user_id     = ce.user_id
                 AND el.email_type  = 'stakeholder_expiry_warning'
                 AND el.sent_at    >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 AND el.status      = 'sent'
           )"
    );
    $evals->execute([$threshold]);

    $sent = 0;
    foreach ($evals->fetchAll() as $ev) {
        $cycleEnd = $db->prepare(
            "SELECT stakeholder_access_end FROM sbm_cycles WHERE cycle_id=?"
        );
        $cycleEnd->execute([$ev['cycle_id']]);
        $endDt = $cycleEnd->fetchColumn();

        $diff = (new DateTime('now', new DateTimeZone('Asia/Manila')))
            ->diff(new DateTime($endDt, new DateTimeZone('Asia/Manila')));
        $days = max(0, (int) $diff->days);

        if (sendStakeholderExpiryWarning($db, $ev, (int) $ev['cycle_id'], $days)) {
            $sent++;
        }
    }
    return ['sent' => $sent];
}

// ─────────────────────────────────────────────────────────────
// HTML Builders
// ─────────────────────────────────────────────────────────────

function _buildStakeholderWelcomeHtml(
    string $name,
    string $email,
    string $link,
    string $expiry,
    string $syLabel,
    string $startDate,
    string $endDate,
    string $schoolName,
    bool $resend
): string {
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
    $safeSy = htmlspecialchars($syLabel, ENT_QUOTES, 'UTF-8');
    $safeStart = htmlspecialchars($startDate, ENT_QUOTES, 'UTF-8');
    $safeEnd = htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8');
    $safeSchool = htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8');
    $year = date('Y');
    $resendNote = $resend ? '<div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:10px;padding:10px 14px;margin-bottom:14px;color:#92400E;font-size:13px;font-weight:600;">⚠️ Reminder: Your account was created earlier but not yet activated.</div>' : '';
    $base = _emailBaseStyles();

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Your Evaluator Account is Ready</title>
<style>{$base}</style>
</head>
<body style="margin:0;padding:0;background:#eef4ef;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#eef4ef;">
  <tr><td align="center" style="padding:32px 16px;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:640px;">
      <tr><td>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#fff;border-radius:22px;">
          <tr><td style="padding:0;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
              <!-- HEADER -->
              <tr>
                <td style="background:linear-gradient(145deg,#0c2d17 0%,#145127 52%,#1f7a3f 100%);border-radius:22px 22px 0 0;padding:28px 28px 24px;">
                  <div style="color:#fff;font-family:Arial,sans-serif;font-size:17px;font-weight:700;">DIHS SBM Portal</div>
                  <div style="color:rgba(255,255,255,.7);font-size:11px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;margin-top:2px;">{$safeSchool}</div>
                  <div style="margin-top:16px;">
                    <span style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:999px;color:#fff;font-size:10px;font-weight:700;letter-spacing:1.5px;padding:5px 12px;text-transform:uppercase;">Evaluator Invite</span>
                  </div>
                  <div style="margin-top:14px;color:#fff;font-family:Georgia,serif;font-size:30px;font-weight:500;line-height:1.1;">
                    You've been invited to evaluate.
                  </div>
                  <div style="margin-top:8px;color:rgba(255,255,255,.75);font-size:14px;line-height:1.7;">
                    Your temporary evaluator account for SY {$safeSy} is ready. Please read the details below carefully.
                  </div>
                </td>
              </tr>
              <!-- BODY -->
              <tr>
                <td style="padding:28px;">
                  {$resendNote}
                  <p style="margin:0 0 18px 0;color:#5d6f62;font-size:15px;line-height:1.8;">
                    Hello <strong style="color:#102316;">{$safeName}</strong>,
                    you have been assigned as an external evaluator for the SBM Self-Assessment of
                    <strong style="color:#102316;">{$safeSchool}</strong>.
                    Your login email is <strong style="color:#102316;">{$safeEmail}</strong>.
                  </p>

                  <!-- Access window -->
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                         style="margin-bottom:18px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;">
                    <tr><td style="padding:16px 18px;">
                      <div style="font-size:11px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase;color:#166534;margin-bottom:10px;">Your Access Window</div>
                      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                        <tr>
                          <td style="font-size:13px;color:#166534;font-weight:600;padding-bottom:6px;">
                            📅 &nbsp;Starts: <span style="font-weight:700;">{$safeStart}</span>
                          </td>
                        </tr>
                        <tr>
                          <td style="font-size:13px;color:#dc2626;font-weight:600;">
                            ⏰ &nbsp;Access Ends: <span style="font-weight:700;">{$safeEnd}</span>
                          </td>
                        </tr>
                      </table>
                    </td></tr>
                  </table>

                  <!-- Temporary account notice -->
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                         style="margin-bottom:18px;background:#fef3c7;border:1px solid #fde68a;border-radius:14px;">
                    <tr><td style="padding:14px 18px;">
                      <div style="font-size:11px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase;color:#92400e;margin-bottom:6px;">Important — Temporary Account</div>
                      <div style="font-size:13.5px;color:#92400e;line-height:1.7;">
                        This account is <strong>temporary</strong> and will be
                        <strong>automatically deactivated on {$safeEnd}</strong>.
                        After that date you will no longer be able to log in.
                        Please complete and submit your assessment <strong>before</strong> the deadline.
                      </div>
                    </td></tr>
                  </table>

                  <!-- Steps -->
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                         style="margin-bottom:18px;">
                    <tr>
                      <td width="33%" valign="top" style="padding-right:6px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                               style="background:#f8fbf8;border-radius:14px;">
                          <tr><td style="padding:12px 12px;">
                            <div style="width:26px;height:26px;border-radius:999px;background:#d8efdf;color:#1f7a3f;font-size:12px;font-weight:800;line-height:26px;text-align:center;">1</div>
                            <div style="margin-top:8px;color:#5d6f62;font-size:13px;line-height:1.5;">Click "Set My Password" below.</div>
                          </td></tr>
                        </table>
                      </td>
                      <td width="33%" valign="top" style="padding:0 3px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                               style="background:#f8fbf8;border-radius:14px;">
                          <tr><td style="padding:12px 12px;">
                            <div style="width:26px;height:26px;border-radius:999px;background:#d8efdf;color:#1f7a3f;font-size:12px;font-weight:800;line-height:26px;text-align:center;">2</div>
                            <div style="margin-top:8px;color:#5d6f62;font-size:13px;line-height:1.5;">Create a secure password.</div>
                          </td></tr>
                        </table>
                      </td>
                      <td width="33%" valign="top" style="padding-left:6px;">
                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                               style="background:#f8fbf8;border-radius:14px;">
                          <tr><td style="padding:12px 12px;">
                            <div style="width:26px;height:26px;border-radius:999px;background:#d8efdf;color:#1f7a3f;font-size:12px;font-weight:800;line-height:26px;text-align:center;">3</div>
                            <div style="margin-top:8px;color:#5d6f62;font-size:13px;line-height:1.5;">Complete the assessment before {$safeEnd}.</div>
                          </td></tr>
                        </table>
                      </td>
                    </tr>
                  </table>

                  <!-- CTA -->
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                         style="margin-bottom:8px;">
                    <tr><td align="center">
                      <a href="{$safeLink}"
                         style="display:inline-block;min-width:220px;background:linear-gradient(135deg,#1f7a3f,#165c30);border-radius:999px;color:#fff;font-family:Arial,sans-serif;font-size:15px;font-weight:700;padding:16px 28px;text-decoration:none;">
                        Set My Password →
                      </a>
                    </td></tr>
                  </table>
                  <p style="margin:0 0 18px 0;color:#77877b;font-size:12px;line-height:1.6;text-align:center;">
                    This setup link expires in {$expiry}.
                  </p>

                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                    <tr><td style="border-top:1px solid #edf2ed;padding-top:16px;color:#5d6f62;font-size:12px;line-height:1.7;">
                      Button not working? Copy this link into your browser:<br>
                      <a href="{$safeLink}" style="color:#1459c7;word-break:break-all;">{$safeLink}</a>
                    </td></tr>
                  </table>
                </td>
              </tr>
              <!-- FOOTER -->
              <tr>
                <td style="background:#f7faf7;border-top:1px solid #edf2ed;border-radius:0 0 22px 22px;padding:16px 28px;color:#77877b;font-family:Arial,sans-serif;font-size:12px;line-height:1.7;text-align:center;">
                  This is an automated message from DIHS SBM Portal.<br>
                  &copy; {$year} {$safeSchool}. All rights reserved.
                </td>
              </tr>
            </table>
          </td></tr>
        </table>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}

function _stakeholderWelcomePlainText(
    string $name,
    string $link,
    string $syLabel,
    string $startDate,
    string $endDate,
    string $school,
    string $expiry
): string {
    return "Hello {$name},\n\n"
        . "You have been invited as an external evaluator for the {$school} SBM Assessment (SY {$syLabel}).\n\n"
        . "ACCESS WINDOW\n"
        . "  Start : {$startDate}\n"
        . "  End   : {$endDate}\n\n"
        . "IMPORTANT: This is a TEMPORARY account that will be automatically deactivated on {$endDate}.\n"
        . "Please complete your assessment before the deadline.\n\n"
        . "Set your password here (expires in {$expiry}):\n{$link}\n\n"
        . "DIHS SBM Portal";
}

function _buildExpiryWarningHtml(
    string $name,
    string $school,
    string $syLabel,
    string $endDate,
    int $daysLeft,
    string $portalUrl
): string {
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeSchool = htmlspecialchars($school, ENT_QUOTES, 'UTF-8');
    $safeSy = htmlspecialchars($syLabel, ENT_QUOTES, 'UTF-8');
    $safeEnd = htmlspecialchars($endDate, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($portalUrl, ENT_QUOTES, 'UTF-8');
    $year = date('Y');
    $urgency = $daysLeft <= 1 ? 'TODAY' : "in {$daysLeft} day(s)";
    $base = _emailBaseStyles();

    return <<<HTML
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Access Expiring Soon</title><style>{$base}</style></head>
<body style="margin:0;padding:0;background:#fff8e8;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#fff8e8;">
  <tr><td align="center" style="padding:32px 16px;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:580px;">
      <tr><td>
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#fff;border-radius:18px;overflow:hidden;">
          <tr>
            <td style="background:linear-gradient(135deg,#7c2d12,#c2410c);padding:24px 28px;">
              <div style="color:#fff;font-size:16px;font-weight:700;">⚠️ &nbsp;Access Expiring {$urgency}</div>
              <div style="color:rgba(255,255,255,.8);font-size:13px;margin-top:6px;">DIHS SBM Portal — {$safeSchool}</div>
            </td>
          </tr>
          <tr>
            <td style="padding:26px 28px;">
              <p style="margin:0 0 16px;color:#374151;font-size:15px;line-height:1.8;">
                Hello <strong>{$safeName}</strong>,
              </p>
              <p style="margin:0 0 20px;color:#374151;font-size:14px;line-height:1.8;">
                Your evaluator access for the <strong>{$safeSchool}</strong> SBM Assessment
                (SY <strong>{$safeSy}</strong>) will expire on
                <strong style="color:#dc2626;">{$safeEnd}</strong>.
              </p>
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;margin-bottom:20px;">
                <tr><td style="padding:14px 16px;color:#b91c1c;font-size:14px;font-weight:700;line-height:1.6;">
                  🕐 &nbsp;{$daysLeft} day(s) remaining — please complete and submit your assessment now.
                </td></tr>
              </table>
              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                     style="margin-bottom:10px;">
                <tr><td align="center">
                  <a href="{$safeUrl}"
                     style="display:inline-block;background:#dc2626;border-radius:999px;color:#fff;font-size:14px;font-weight:700;padding:14px 28px;text-decoration:none;">
                    Complete My Assessment →
                  </a>
                </td></tr>
              </table>
              <p style="margin:16px 0 0;color:#9ca3af;font-size:12px;text-align:center;">
                After the deadline your account will be automatically deactivated.
              </p>
            </td>
          </tr>
          <tr>
            <td style="background:#fafafa;padding:14px 28px;color:#9ca3af;font-size:12px;text-align:center;border-top:1px solid #f3f4f6;">
              &copy; {$year} DIHS SBM Portal — Automated message
            </td>
          </tr>
        </table>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;
}