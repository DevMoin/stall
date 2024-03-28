<?php


class Commands
{
    /**
     * @command help
     * @alias -h
     * @description Get help for all commands ulalala
     */
    public function help()
    {
        $result = getCommandsInfo();
        print_table($result);
    }

    /**
     * @command test
     * @alias ---DUMMY
     * @description your description
     */
    public function DUMMY()
    {
    }

    /**
     * @command install
     * @alias i
     * @description Create a template from current directory based on type of project
     */
    public function install($args)
    {
        $tpl = isset($args['props'][0]) ? strtolower($args['props'][0]) : null;
        $installerFiles = glob(__DIR__ . "/src/Templates/*/installer.php");
        $templates = [];
        foreach ($installerFiles as $file) {
            $dirName = dirname($file);
            $tplName = basename($dirName);
            $infoJsonPath = $dirName . "/info.json";
            $infoJson = [];
            if (file_exists($infoJsonPath)) {
                $infoJson = file_get_contents($infoJsonPath);
                $infoJson = json_decode($infoJson, 1);
            }
            $templates[strtolower($tplName)] = [
                'dir' => $dirName,
                'tplName' => $tplName,
                'installerPath' => $file,
                'infoJson' => print_r($infoJson, 1),
            ];
        }
        if (!$tpl || isset($args['options']['-h'])) {

            print_table($templates);
            return;
        }
        if (!isset($templates[$tpl])) {
            showError("Unkown template $tpl");
            echo nl()."Do you want to show all templates? [ Y ] or ".st("[ N ]", 'bg_white', 'black')." : ";
            $fin = fopen("php://stdin", "r");
            $line = fgets($fin);
            $line = trim($line);
            $line = strtolower($line);
            if(isset($line[0]) && $line[0] == "y"){
                print_table($templates);
            }
            return;
        }

        $template = $templates[$tpl];

        $installerFile = $template['installerPath'];
        require_once $installerFile;

    }


    /**
     * @command createTemplate
     * @alias ctpl
     * @description Create a template from current directory based on type of project
     */
    public function createTemplate($args)
    {

        $help = "STALL ctpl dir type=drupal name=template_name [--no-db] [--override]";
        $helpPad = "\n" . st(str_repeat(" ", strlen($help)), "bg_blue") . "\n";
        $help = st("\nexample: ", 'blue')
            . st('[--no-db]', 'reset')
            . st(" etc are optional \n", 'blue')
            . $helpPad
            . st("$help", 'bg_blue', 'bold', 'white')
            . $helpPad;

        require_once 'CreateTemplate.php';
        $templateCreator = new CreateTemplate();

        if (isset($args['options']['-h'])) {
            $methods = $this->getMethodsOfCreateTemplate($templateCreator);
            echo $help;
            echo "Available types:";
            print_table($methods);
            exit;
        }
        if (!isset($args['props'][0], $args['values']['type'], $args['values']['name'])) {
            showError("Command not complete");
            echo $help . st("\n");
            exit;
        }

        $source = $args['props'][0];
        $type = $args['values']['type'];
        $tplName = $args['values']['name'];
        $tplPath = realpath(__DIR__ . "/src/Templates") . "/" . $tplName;
        if (file_exists($tplPath)) {
            if (isset($args['options']['--override']) && $args['options']['--override']) {
                rrmdir($tplPath);
            } else {
                showError("Template $tplPath already exists add --override to override it");
                exit;
            }
        }

        $tplPath = __DIR__ . "/src/Templates/" . $tplName;


        $source_final = realpath("$source");
        if (!$source_final) {
            showError("Unkown folder " . st(' ', 'reset') . $source);
            exit;
        }


        $methods = $this->getMethodsOfCreateTemplate($templateCreator);

        if (!isset($methods[$type])) {
            showError("Unkown type " . st("$type", 'white', 'bold'));
            echo "Available types are";
            print_table($methods);
            exit;
        }

        $method = $methods[$type];
        $infoJson = [
            'type' => $type,
            'templatePath' => $tplPath,
            'orignalPath' => $source_final,
            'tplName' => $tplName,
        ];
        $methodFn = $method['method'];
        $templateCreator->$methodFn($source_final, $tplName, $tplPath, $infoJson, $args);
        // call_user_func([$templateCreator, $method['method']], $source_final, $tplName, $tplPath, $infoJson, $args);
        file_put_contents($tplPath . "/info.json", json_encode($infoJson));
    }

    private function getMethodsOfCreateTemplate($templateCreator)
    {
        $refClass = new ReflectionClass($templateCreator);
        $refMethods = $refClass->getMethods();
        $methods = [];
        foreach ($refMethods as $refMethod) {
            if ($refMethod->isPublic() && !$refMethod->isStatic()) {
                $methodInfo = getDocInfo($refMethod->getDocComment());
                if (!isset($methodInfo['type'])) {
                    continue;
                }

                $methods[$methodInfo['type']] = $methodInfo;
                $methods[$methodInfo['type']]['method'] = $refMethod->name;
            }
        }
        return $methods;
    }
}
