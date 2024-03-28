<?php
$templatePath = $template['dir'];
$targetDir = $args['values']['dir'];



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




// Check if the target folder already exists
if (file_exists(CWD_TARGET . '/' . $targetDir)) {
    showError("Target folder already exists: " . CWD_TARGET . '/' . $targetDir);
    exit;
}

$templatePathUnix = windowDriveToUnix($templatePath);
$targetFolder = CWD_TARGET_UNIX . '/' . $targetDir;




if (file_exists($templatePath . "/db.sql")) {
    // Check if the database exists
    $x = exec("mysqlshow -u root {$args['values']['db']}", $output, $result_code);
    if ($result_code !== 0) {
        showError("Database does not exist or something went wrong");
        exit;
    }

    echo "Importing DB\n";
    // Execute MySQL import command and capture output and status
    list($out, $status) = myExec("mysql -u root {$args['values']['db']} < $templatePath/db.sql");
    if ($status !== 0) {
        showError("Error importing database: $out");
        exit;
    }

    echo st("Database import completed\n\n", "green");
} else {
    echo st("Database skipped because no db.sql present\n\n", "green");
}

mkdir(CWD_TARGET . '/' . $targetDir);
echo st("\n\nFolder created $targetFolder\n", "green");
// Create a new ZipArchive instance

$tarExtractCommand = ROOT_PATH . "/exe/tar.exe -xf " . ($templatePathUnix . "/files.tar.gz") . " -C " . $targetFolder;
exec($tarExtractCommand);
echo "  Please edit " . CWD_TARGET . '/' . $targetDir . "/sites/default/settings.php  ";
