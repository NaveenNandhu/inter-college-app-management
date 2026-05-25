<?php
$file = 'c:\\xampp\\htdocs\\Inter-College_Meet\\admin\\submit_results.php';
$content = file_get_contents($file);

$old = 'WHERE r.event_id = ?
                     ORDER BY u.full_name ASC';
$new = 'WHERE r.event_id = ? AND r.attendance_marked = 1
                     ORDER BY u.full_name ASC';

// normalize line endings just in case
$content = preg_replace('/\r\n/', "\n", $content);

$content = str_replace($old, $new, $content);
file_put_contents($file, $content);
echo "done replacing admin/submit_results.php";

$file_edit = 'c:\\xampp\\htdocs\\Inter-College_Meet\\admin\\edit_results.php';
$content_edit = file_get_contents($file_edit);
$old_edit = 'WHERE r.event_id = ? ORDER BY u.full_name';
$new_edit = 'WHERE r.event_id = ? AND r.attendance_marked = 1 ORDER BY u.full_name';

$content_edit = str_replace($old_edit, $new_edit, $content_edit);
file_put_contents($file_edit, $content_edit);
echo "\ndone replacing admin/edit_results.php";
?>