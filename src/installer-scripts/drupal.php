<?php
$templatePath = $template['dir'];

// Check if database name is provided
if (file_exists($templatePath . "/db.sql") && !isset($args['values']['db'])) {
    showError("db not specified using db=dbname");
    exit;
}

// Check if target directory is provided
if (!isset($args['values']['dir'])) {
    showError("target dir not specified using dir=foldername");
    exit;
}

$_targetDir = $args['values']['dir'];
$targetDir = realpath($_targetDir);

if (!$targetDir) {
    $targetDir = CWD_TARGET . "/" . $_targetDir;
}


// Check if the target folder already exists
if (file_exists($targetDir)) {
    if (is_dir($targetDir)) {
        if (!dir_is_empty($targetDir)) {
            showError("Target folder is not empty: " . CWD_TARGET . '/' . $targetDir);
            exit;
        } else {
            showInfo("Target folder is empty will extract to it");
        }
    } else {
        showError("Target is not a directory: " . $targetDir);
        exit;
    }
} else {
    if (!mkdir($targetDir, 0777, true)) {
        showError("Unable to create directory " . $targetDir, "Creation failed: ");
        exit;
    }
}


if (file_exists($templatePath . "/db.sql")) {
    // Check if the database exists
    $conn = null;
    try {
        $conn = mysqli_connect("localhost", "root", "", $args['values']['db']);
    } catch (\Exception $e) {
        nl();
        showError($e->getMessage(), "Caught error ");
    }

    if (mysqli_connect_error()) {
        showError("Database " . $args['values']['db'] . " does not exist or something went wrong");
        showError(mysqli_connect_error(), "MYSQLI_CONNECT ERROR");
        nl();
        showInfo("Trying to create new DB");
        nl();
        $conn = mysqli_connect("localhost", "root", "");
        $q = $conn->query("CREATE DATABASE `{$args['values']['db']}`");
        s($q);
        if (!$q) {
            showError("Failed to create DB ");
            exit;
        }
        nl();
        showSuccess("Database created succesfully.", $args['values']['db']);
        nl();
    } else {
        showSuccess("Database exists ", $args['values']['db']);

        $query = $conn->query("SHOW TABLES");
        $count = $query->num_rows;
        if ($count > 0) {
            showError("Database not empty", $args['values']['db']);
            exit;
        }
    }

    showInfo("Importing", "Database");
    // $dbImportCommand = "mysql -u root {$args['values']['db']}";
    $dbImportCommand = "mysql -u root {$args['values']['db']} < $templatePath/db.sql";
    // Execute MySQL import command and capture output and status
    list($out, $status) = myExec($dbImportCommand);
    if ($status !== 0) {
        showError("Error importing database: $out");
        exit;
    }
    echo st("Database import completed\n\n", "green");
} else {
    showInfo("Database skipped because no db.sql present");
}

nl();
showInfo("Going to extract template to $targetDir");
nl();

try {
    $phar = new PharData(TEMPLATES_PATH . "/$tpl/files.tar.gz");
    $phar->extractTo($targetDir);
    showSuccess("succesfully extracted to $targetDir");
} catch (\Exception $e) {
    showError($e->getMessage(), "Failed to extract");
    exit;
}
