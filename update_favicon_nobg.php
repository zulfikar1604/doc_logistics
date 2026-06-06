<?php
$files = glob("d:/xampp/htdocs/doclogistics/*.php");
foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content !== false) {
        $pattern = '/<link rel="icon" type="image\/jpeg" href="assets\/img\/icon-192\.jpeg(\?v=\d+)?"\s*>/i';
        $replacement = '<link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">';
        $new_content = preg_replace($pattern, $replacement, $content);
        if ($new_content !== null && $new_content !== $content) {
            file_put_contents($file, $new_content);
            echo "Updated " . basename($file) . "\n";
        }
    }
}
?>
