<?php

function sbmRenderBadgeOrText(
    string $label,
    bool $isException = false,
    bool $showOnlyIfException = true,
    string $tone = 'warning',
    string $textTone = 'neutral',
    bool $textOnly = false
): string {
    $escapedLabel = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $textColors = [
        'neutral' => '#6B7280',
        'success' => '#15803D',
    ];

    if ($textOnly || ($showOnlyIfException && !$isException)) {
        if (!isset($textColors[$textTone])) {
            throw new InvalidArgumentException('Unsupported text tone: ' . $textTone);
        }
        return '<span style="font-family:var(--font-display);font-size:13px;font-weight:600;letter-spacing:0.01em;color:'
            . $textColors[$textTone] . ';">' . $escapedLabel . '</span>';
    }

    $tones = [
        'danger' => 'background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;',
        'warning' => 'background:#FFFBEB;color:#B45309;border:1px solid #FDE68A;',
        'success' => 'background:#F0FDF4;color:#15803D;border:1px solid #BBF7D0;',
    ];
    if (!isset($tones[$tone])) {
        throw new InvalidArgumentException('Unsupported badge tone: ' . $tone);
    }

    return '<span style="display:inline-flex;align-items:center;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:600;'
        . $tones[$tone] . '">' . $escapedLabel . '</span>';
}
