<?php

// マイページで理解度を設定できる言語一覧
const LANGUAGES = [
    'html' => 'HTML',
    'css' => 'CSS',
    'javascript' => 'JavaScript',
    'mysql' => 'MySQL',
    'php' => 'PHP(フレームワークなし)',
];

// タスクの状態(5段階)
const STATUS_LABELS = [
    1 => '順調',
    2 => 'やや遅れ',
    3 => '遅れ気味',
    4 => 'やばい',
    5 => 'かなりやばい',
];

// この状態以上になったらTopページのヘルプ通知に表示する
const SOS_STATUS_THRESHOLD = 4;
