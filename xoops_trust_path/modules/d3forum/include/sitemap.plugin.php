<?php

function b_sitemap_d3forum($mydirname)
{
    $db =& Database::getInstance();
    (method_exists('MyTextSanitizer', 'sGetInstance') and $myts =& MyTextSanitizer::sGetInstance()) || $myts =& (new MyTextSanitizer)->getInstance();
    $ret = [];

    include_once __DIR__ . '/common_functions.php';

    $whr_forum = 'forum_id IN (' . implode(',', d3forum_get_forums_can_read($mydirname)) . ')';

    $sql = 'SELECT forum_id,forum_title FROM ' . $db->prefix($mydirname . '_forums') . " WHERE ($whr_forum)";
    $result = $db->query($sql);

    while (list($forum_id, $forum_title) = $db->fetchRow($result)) {
        $ret['parent'][] = [
            'id' => (int)$forum_id,
            'title' => $myts->makeTboxData4Show($forum_title),
            'url' => 'index.php?forum_id=' . (int)$forum_id,
        ];
    }

    return $ret;
}
