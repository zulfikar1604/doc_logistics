<?php
require 'd:\xampp\htdocs\doclogistics\koneksi.php';
$res = mysqli_query($conn, 'SHOW TABLES');
while ($r = mysqli_fetch_array($res)) {
    echo $r[0] . "\n";
}
