<?php

if (!is_dir('dist')) {
    mkdir('dist');
}

$output = './dist/koncerto.php';
file_put_contents($output, '<?php');

$files = scandir('./src/');

foreach ($files as $file) {
    if ('.php' === strrchr($file, '.')) {
        $php = file_get_contents('./src/' . $file);
        if ('<?php' === substr($php, 0, 5)) {
            $php = substr($php, 5);
        }
        file_put_contents($output, $php, FILE_APPEND);
    }
}
