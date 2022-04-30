<?php

if (!empty($_GET['id'])) {
    require_once('includes/lib.php');
    if ($text = getJournalEntryText($_GET['id'])) {
        header('Content-type: application/json');
        echo json_encode($text);
        exit;
    }
}
// send a 404
http_response_code(404);
