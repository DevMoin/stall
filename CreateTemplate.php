<?php
class CreateTemplate
{
    /**
     * @type drupal
     * @description Olala jumama
     */
    function drupal($source_final, $tplName, $tplPath, &$infoJson, $args)
    {

        mkdir(__DIR__."/src/Templates/$tplName");

        file_put_contents(__DIR__."/src/Templates/$tplName/installer.php", "<?php
require_once(ROOT_PATH.'/src/installer-scripts/drupal.php');
");

        $composerPath = realpath($source_final . "/composer.json");
        if (!$composerPath) {
            showError("No composer.json file found in specified directory");
            exit;
        }

        $composerJson = json_decode(file_get_contents($composerPath), true);
        if (!$composerJson) {
            showError("Not valid JSON in composer.json");
            exit;
        }

        if (!isset($composerJson['require']['drupal/core-recommended'])) {
            showError("Invalid Drupal composer in composer.json didn't found any require => drupal/core-recommended");
            exit;
        }

        $settingsPath = realpath($source_final . "/sites/default/settings.php");
        if (!$settingsPath) {
            $settingsPath = realpath($source_final . "/web/sites/default/settings.php");
        }

        nl(3);
        echo st("Creating template folder. Fake waiting lolz", "bg_blue");
        // sleep(1);
        mkdir($tplPath);
        nl(3);
        echo st("Template folder created\n", "green");

        if (!$settingsPath) {
            showError("No settings.php found will only create folder without database");
        } else {
            $dbInfo = $this->drupalReadSettings($settingsPath);
            if (!$dbInfo) {
                showError("In settings.php there is no database avilable so will only create folder without database");
            } else {
                nl(4);
                echo st("Exporting db", "bg_blue");
                nl(3);
                $pass = '';
                if (isset($dbInfo['password']) && $dbInfo['password']) {
                    $pass = '-p';
                }
                exec("mysqldump -u $dbInfo[username] $pass $dbInfo[database] > $tplPath/db.sql", $output, $code);
                if ($code !== 0) {
                    showError("Database export failed with code $code");
                    echo nl(2) . st("MysqlDump response: " . join("\n", $output), 'red');
                }
            }
        }


        $tarExe = realpath(__DIR__ . "/exe/tar.exe");
        $destination = escapeshellarg("$tplPath/files.tar.gz");
        $source = escapeshellarg(getcwd());
        $destination = preg_replace("/^\"(.?)\:/", "\"/$1", $destination);
        $source = preg_replace("/^\"(.?)\:/", "\"/$1", $source);
        $tarCommand = "$tarExe cvzf " . $destination . " " . $source;
        $tarCommand = str_replace("\\", "/", $tarCommand);
        exec($tarCommand, $output, $code);
        if ($code !== 0) {
            showError("Something went wrong while tar gziping");
            exit;
        }
        echo nl() . "Successfully create template in $tplPath";
    }

    private function drupalReadSettings($path)
    {
        @require_once $path;
        return isset($databases['default']['default']) ? $databases['default']['default'] : null;
    }

    /**
     * @type laravel
     * @description This is so fun my dear
     */
    function laravel()
    {
    }
}
