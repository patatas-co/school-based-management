<?php
$file = 'c:\xampp\htdocs\sbm\school_head\dashboard.php';
$content = file_get_contents($file);

// 1. Move Filter bar
$fbStart = strpos($content, '<!-- Filter bar -->');
$fbEnd = strpos($content, '<!-- KPI insight strip — Primary -->', $fbStart);
if ($fbStart !== false && $fbEnd !== false) {
    $filterBarCode = substr($content, $fbStart, $fbEnd - $fbStart);
    $content = substr_replace($content, '', $fbStart, $fbEnd - $fbStart);
} else {
    echo "Could not find Filter bar boundaries.\n";
    exit(1);
}

// 2. Move Charts row and everything down to the end of viewAnalytics
$chartsStart = strpos($content, '<!-- Charts row -->');
$vaEnd = strpos($content, '</div><!-- /viewAnalytics -->', $chartsStart);

if ($chartsStart !== false && $vaEnd !== false) {
    $chartsCode = substr($content, $chartsStart, $vaEnd - $chartsStart);
    $content = substr_replace($content, '', $chartsStart, $vaEnd - $chartsStart);
} else {
    echo "Could not find Charts row or viewAnalytics boundaries.\n";
    exit(1);
}

// 3. Insert Filter bar into viewProgress right after PIPELINE
$pipelineEnd = strpos($content, '<!-- ━ ━ ━ ━ ━ ━ ━ ━ ━ ━ ━  MAIN GRID ━ ━ ━ ━ ━ ━ ━ ━ ━ ━ ━  -->');
if ($pipelineEnd !== false) {
    $content = substr_replace($content, $filterBarCode . "\n", $pipelineEnd, 0);
} else {
    echo "Could not find PIPELINE boundaries.\n";
    exit(1);
}

// 4. Insert Charts Code at the end of viewProgress
$vpEnd = strpos($content, '</div><!-- /viewProgress -->');
if ($vpEnd !== false) {
    $content = substr_replace($content, "\n" . $chartsCode . "\n", $vpEnd, 0);
} else {
    echo "Could not find viewProgress end.\n";
    exit(1);
}

file_put_contents($file, $content);
echo "Restructure applied successfully.\n";
