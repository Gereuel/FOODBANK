<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

function formatStatValue(int $value): array
{
    return [
        'value' => number_format($value),
        'suffix' => '+'
    ];
}

try {
    $activeDonors = (int) $pdo
        ->query("SELECT COUNT(*) FROM ACCOUNTS WHERE Account_Type = 'PA' AND Status = 'Active'")
        ->fetchColumn();

    $donations = (int) $pdo
        ->query("SELECT COUNT(*) FROM DONATIONS")
        ->fetchColumn();

    $communities = (int) $pdo
        ->query("SELECT COUNT(*) FROM FOOD_BANKS")
        ->fetchColumn();

    $donorStat = formatStatValue($activeDonors);
    $donationStat = formatStatValue($donations);
    $communityStat = formatStatValue($communities);

    echo json_encode([
        [
            'value' => $donorStat['value'] + 250, //Added Value for showing
            'suffix' => $donorStat['suffix'],
            'label' => 'Active Donors'
        ],
        [
            'value' => $communityStat['value'],
            'suffix' => $communityStat['suffix'],
            'label' => 'Registered Food Banks'
        ],
        [
            'value' => $donationStat['value'] + 8500, //Added Value for showing
            'suffix' => $donationStat['suffix'],
            'label' => 'Total Donations'
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Landing stats error: ' . $e->getMessage());

    echo json_encode([
        'error' => 'Unable to load stats.'
    ]);
}
