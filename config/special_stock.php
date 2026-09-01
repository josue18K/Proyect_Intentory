<?php

$codes = fn (array $numbers) => array_map(
    fn ($number) => 'LIU-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
    $numbers,
);

return [
    'chemicals' => array_merge($codes(range(1, 31)), ['TIEN-00223']),
    'quick_purchases' => $codes(array_merge(
        [32, 33, 34, 35, 36, 38, 39, 40],
        range(41, 99),
        [101, 103, 104, 105],
        range(106, 116),
        range(119, 131),
        range(133, 138),
        [141, 143],
    )),
];
