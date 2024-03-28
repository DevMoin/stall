<?php
class CreateTemplate
{
    /**
     * @type drupal
     * @description Olala jumama
     */
    function drupal($source_final, $tplName, $tplPath, &$infoJson, $args)
    {
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

        echo nl(3) . st("Creating template folder. Fake waiting lolz", "bg_blue");
        mkdir($tplPath);
        echo st(nl(3) . "Template folder created\n", "green");

        file_put_contents("$tplPath/installer.php", "<?php
require_once(ROOT_PATH.'/src/installer-scripts/drupal.php');
");
        if (isset($args['options']['--no-db'])) {
            showInfo("Skipped database because --no-db is set");
        } else if (!$settingsPath) {
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

        nl();
        showInfo("Archiving/zipping $tplPath", "INFO");

        $phar = new PharData($tplPath . "/files.tar.gz");
        if (!$phar) {
            showError("Failed to create files.tar.gz", "Failure");
            exit;
        }
        $phar->compress(Phar::GZ);
        $phar->buildFromDirectory($source_final);
        
        nl();
        showSuccess("Templae file created succesfully $tplPath");
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
