<?php

// Include all include files
require_once "./includes/autoload.php";

$sh->echo("Stopping Dej...");

// Stop if root permissions not granted
if (!rootPermissions())
    return;

// Search for Dej screens
$screenSessionPids = searchScreens();

// Check if there are some screens to stop
if (count($screenSessionPids) !== 0) {
    // Stop screens that started before
    foreach ($screenSessionPids as $screenSessionPid)
        `screen -X -S $screenSessionPid quit`;
    
    $sh->echo("Done!");
} else
    $sh->echo("Not started, nothing to do!");