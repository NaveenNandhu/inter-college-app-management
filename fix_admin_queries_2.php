<?php
$file = 'c:\\xampp\\htdocs\\Inter-College_Meet\\admin\\submit_results.php';
$content = file_get_contents($file);

$content = preg_replace('/WHERE r.event_id = \?[ \t\n\r]*ORDER BY u.full_name ASC/', 'WHERE r.event_id = ? AND r.attendance_marked = 1' . "\n" . '                     ORDER BY u.full_name ASC', $content);

file_put_contents($file, $content);
echo "Replaced submit_results.php";
?>