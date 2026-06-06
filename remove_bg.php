<?php
$src = "d:/xampp/htdocs/doclogistics/assets/img/icon-192.jpeg";
$dst = "d:/xampp/htdocs/doclogistics/assets/img/icon-192-nobg.png";

$img = @imagecreatefromjpeg($src);
if (!$img) {
    die("Error loading jpeg");
}

$w = imagesx($img);
$h = imagesy($img);

$target = imagecreatetruecolor($w, $h);
imagesavealpha($target, true);
$trans = imagecolorallocatealpha($target, 0, 0, 0, 127);
imagefill($target, 0, 0, $trans);

// Get background color from top-left pixel (assuming it's background)
$bg_rgb = imagecolorat($img, 0, 0);
$bg_r = ($bg_rgb >> 16) & 0xFF;
$bg_g = ($bg_rgb >> 8) & 0xFF;
$bg_b = $bg_rgb & 0xFF;

$tolerance = 45; // Generous tolerance for JPEG compression artifacts

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $rgb = imagecolorat($img, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        $dist = sqrt(pow($r - $bg_r, 2) + pow($g - $bg_g, 2) + pow($b - $bg_b, 2));

        if ($dist <= $tolerance) {
            imagesetpixel($target, $x, $y, $trans);
        } else {
            $color = imagecolorallocatealpha($target, $r, $g, $b, 0);
            imagesetpixel($target, $x, $y, $color);
        }
    }
}

// Save the new PNG
if (imagepng($target, $dst)) {
    echo "SUCCESS";
} else {
    echo "ERROR";
}

imagedestroy($img);
imagedestroy($target);
?>
