<?php

// Includes
$incPath = "includes";
$filesPath = [
    "directory.php",
    "load.php"
];
foreach ($filesPath as $filePath)
    require "$incPath/$filePath";

// Load configurations
$dataJson = new LoadJSON("data.json");
$dataJson->type_validation();
$configData = $dataJson->data;

// Create (if needed) and change directory to the path of saved files
$dirPath = $configData->save_to->path;
directory($dirPath);
chdir($dirPath);

// Set required variables from data file
$backupDirName = $configData->backup->dir;
$backupTimeout = $configData->backup->timeout;

// Create backup directory (if needed)
directory($backupDirName);
$backupDirName = force_end_slash($backupDirName);

while (true) {
    // Make a list of the whole files
    $filesDir = new DirectoryIterator(".");

    // Create the timestamp file (to see when backup was made)
    fwrite($f = fopen($backupDirName . "update_time", "w"), time());
    fclose($f);

    // Make backup from the files
    foreach ($filesDir as $file)
        if ($file->isFile()) {
            $filename = $file->getFilename();
            `cp $filename $backupDirName/$filename`;
        }

    // Timeout
    sleep($backupTimeout);
}
