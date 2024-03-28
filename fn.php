<?php

require_once 'styles.php';

require_once "Commands.php";

function dir_is_empty($dir)
{
    $handle = opendir($dir);
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            closedir($handle);
            return false;
        }
    }
    closedir($handle);
    return true;
}

function windowDriveToUnix($path)
{
    return "/" . $path[0] . "/" . substr($path, 3);
}

function myExec($command)
{
    exec($command, $output, $code);
    return [$output, $code];
}

function nl($count = 1)
{
    echo str_repeat("\n", $count);
}

function showInfo($msg, $header = "Info")
{
    echo nl() . st(" $header: ", 'bg_blue', 'bold', 'white') . style('reset') . st(" $msg ", 'blue', '', 'bold') . nl();
}

function showSuccess($msg, $header = "Success")
{
    echo nl() . st(" $header: ", 'bg_green', 'bold', 'white') . style('reset') . st(" $msg ", 'green', '', 'bold') . nl();
}

function showError($msg, $header = "Error")
{
    echo nl() . st(" $header: ", 'bg_red', 'bold', 'white') . style('reset') . st(" $msg ", 'red', '', 'bold') . nl();
}

function getArgsProps($argv)
{

    $result = [
        'info' => [
            'file' => array_shift($argv),
            'command' => array_shift($argv),
        ],
        'props' => [],
        'values' => [],
        'options' => [],
    ];

    foreach ($argv as $arg) {
        $valueTarget = "values";
        if (strpos($arg, "-") === 0) {
            $valueTarget = "options";
        }
        if (($pos = strpos($arg, "=")) !== false) {

            $result[$valueTarget][substr($arg, 0, $pos)] = substr($arg, $pos + 1);
        } else {
            if ($valueTarget === "options") {
                $result[$valueTarget][$arg] = true;
            } else {
                $result["props"][] = $arg;
            }
        }
    }

    return $result;
}

function getCommandsKeys($commander)
{
    $commandsInfo = getCommandsInfo();
    $commandKeys = [];
    foreach ($commandsInfo as $key => $command) {
        $commandKeys[$command['command']] = [$commander, $command['command']];
        $commandKeys[$command['alias']] = [$commander, $command['command']];
    }
    return $commandKeys;
}

function getCommandsInfo()
{
    static $data = null;
    if ($data) {
        return $data;
    }

    $refClass = new ReflectionClass(Commands::class);

    $refMehods = $refClass->getMethods();
    foreach ($refMehods as $refMethod) {
        if ($refMethod->isPublic() && !$refMethod->isStatic()) {
            $methodInfo = getDocInfo($refMethod->getDocComment());
            if (!isset($methodInfo['command'])) {
                continue;
            }

            $result[] = [
                "command" => $methodInfo['command'],
                "alias" => $methodInfo['alias'] ?? "<none>",
                "description" => $methodInfo['description'] ?? "<none>"
            ];
        }
    }

    $data = $result;
    return $data;
}


function print_table($result, $hasHeader = false)
{
    echo "\n\n";

    $result = array_values($result);
    if (!$hasHeader) {
        $keys = [];
        foreach ($result as $r) {
            $keys = array_merge($keys, array_keys($r));
        }
        array_unshift($result, array_combine($keys, $keys));
    }

    /* Fix missing values */
    foreach ($result[0] as $key => $value) {
        foreach ($result as $k => $r) {
            if (!key_exists($key, $r)) {
                $result[$k][$key] = "-";
            }
        }
    }

    $maxlenghths = array_map(function ($e) {
        return 0;
    }, $result[0]);

    foreach ($result as $line) {
        // var_dump($line);continue;
        foreach ($maxlenghths as $i => $oldMax) {
            if (!isset($line[$i])) {
                continue;
            }
            $len = strlen($line[$i]);
            if ($len > $oldMax) {
                $maxlenghths[$i] = $len;
            }
        }
    }

    foreach ($result as $lineI => $line) {
        $maxlenghthsSum = 0;
        foreach ($maxlenghths as $key => $len) {
            $column = $line[$key];
            $columnSplit = explode("\n", str_replace("\n\r", "\n", $column));
            $columnFinal = array_shift($columnSplit);
            foreach ($columnSplit as $columnLine) {
                $columnFinal .= "\n";
                $columnFinal .= str_repeat(" ", $maxlenghthsSum) . $columnLine;
            }
            echo str_pad($columnFinal, $len + 1, " ", $lineI == 0 ? STR_PAD_BOTH : STR_PAD_RIGHT);
            echo "    ";
            $maxlenghthsSum += $len + 5;
        }
        if ($lineI == 0) {
            echo "\n";
            foreach ($maxlenghths as $len) {
                echo str_repeat("-", $len + 1);
                echo "    ";
            }
            echo "\n";
        }
        echo "\n";
    }
}


function getDocInfo($doc)
{
    preg_match_all("/\* @(.+?) (.+)/", $doc, $matches, PREG_SET_ORDER);

    $methodInfo = [];
    foreach ($matches as $match) {
        $key = trim($match[1]);
        $value = trim($match[2]);
        $methodInfo[$key] = $value;
    }
    return $methodInfo;
}

function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                    rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                else
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
            }
        }
        rmdir($dir);
    }
}

function getListOFFilesNested($path, $base = null, $ignore = [])
{
    echo nl() . "Scanning for files $path";
    if (!$base) {
        $base = $path;
    }

    $files = glob($path . "/*");
    $result = [];
    foreach ($files as $file) {
        $fileTrimed = "." . substr($file, strlen($base));
        if (in_array($fileTrimed, $ignore)) {
            echo "IGNORE";
            continue;
        }
        if (is_dir($file)) {
            $result = [...$result, ...getListOFFilesNested($file, $base)];
        } else {
            $result[] = $file;
        }
    }
    return $result;
}

function zipDirectory($sourcePath, $outZipPath, $ignore = [])
{
    $files = getListOFFilesNested($sourcePath, $sourcePath, $ignore);
    $zip = new ZipArchive();
    if ($zip->open($outZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($files as $file) {
            if (!is_dir($file)) {
                $relativePath = substr($file, strlen($sourcePath) + 1);
                // Add current file to archive
                echo nl() . "Adding $file";
                $zip->addFile($file, $relativePath);
            } else if (!($file instanceof SplFileInfo)) {
                $zip->addEmptyDir($file);
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();
        echo nl(3) . st("Zip file created successfully.", 'green');
    } else {
        showError("Failed to create zip file.");
    }
}

class FlxZipArchive extends ZipArchive
{
    public function addDir($location, $name, $ignore = [])
    {
        if (in_array($name, $ignore)) {
            return;
        }
        $this->addEmptyDir($name);
        $this->addDirDo($location, $name, $ignore);
    }
    private function addDirDo($location, $name, $ignore)
    {
        $name .= '/';
        $location .= '/';
        $dir = opendir($location);
        while ($file = readdir($dir)) {
            if ($file == '.' || $file == '..') continue;
            if (filetype($location . $file) == 'dir') {
                $this->addDir($location . $file, $name . $file, $ignore);
            } else {
                $this->addFile($location . $file, $name . $file);
            }
        }
    }
}
