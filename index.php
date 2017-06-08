<?php

include_once(__DIR__."/vendor/autoload.php");

use Cocur\Slugify\Slugify;

class CreatePlugin
{
    public function __construct()
    {
        $this->templateDirectory = __DIR__."/template-files";
        $slugify = new Slugify();
        $this->input = $input = $this->promptForDetails();
        $this->projectNiceName = $input['projectName'];
        $this->projectName = $slugify->slugify($input['projectName']);
        $this->description = $input['description'];
        $this->line("Project name slugified to {$this->projectName}");
        $this->userName = $input['userName'];
        $this->camelCaseName = $this->dashesToCamelCase($this->projectName);
        $this->createRepo();
        $this->createFolder();
        $this->getTemplateFiles();
        $this->commitFiles();
    }

    public function line($string)
    {
        echo $string.PHP_EOL;
    }


    public function promptForDetails()
    {
        return [
          'projectName' => readline("Project name: "),
          'description' => readline("Description: "),
          'userName' => readline("Gihub username: ")
        ];
    }

    public function createRepo()
    {
        $config = [
          'name' => $this->projectName,
          'description' => $this->description
        ];

        $configJSON = json_encode($config);
        $createRepo = "curl -u '".$this->userName."' https://api.github.com/orgs/shortlist-digital/repos -d '$configJSON'";
        echo shell_exec($createRepo);
    }

    public function fillTemplate($fields, $string)
    {
        $string = preg_replace_callback('/{{(\w+)}}/', function ($match) use ($fields) {
            return $fields[$match[1]];
        }, $string);
        return $string;
    }


    public function createFolder()
    {
        $createFolder = "mkdir {$this->projectName};";
        echo shell_exec($createFolder);
        chdir($this->projectName);
    }

    public function getTemplateFiles()
    {
        $this->getFile('.gitignore');
        $this->getFile('.editorconfig');
        $this->getFile('LICENSE');
        $this->getComposer();
        $this->getReadme();
        $this->getPluginFile();
    }

    public function getFile($fileName)
    {
        $getEditorConfig = "cp {$this->templateDirectory}/$fileName .";
        echo shell_exec($getEditorConfig);
    }

    public function getComposer()
    {
        $composerString = file_get_contents($this->templateDirectory."/composer.json");
        $fileString = $this->fillTemplate([
          'className' => $this->camelCaseName,
          'description' => $this->description,
          'projectName'=> $this->projectName
        ], $composerString);
        file_put_contents('composer.json', $fileString);
    }

    public function getReadme()
    {
        $readmeString = file_get_contents($this->templateDirectory."/readme.md");
        $fileString = $this->fillTemplate($this->input, $readmeString);
        file_put_contents('readme.md', $fileString);
    }

    public function getPluginFile()
    {
        $composerString = file_get_contents($this->templateDirectory."/plugin-file.php");
        $fileString = $this->fillTemplate([
          'className' => $this->camelCaseName,
          'description' => $this->description,
          'projectName'=> $this->projectName,
          'projectNiceName'=> $this->projectNiceName
        ], $composerString);
        file_put_contents("{$this->projectName}.php", $fileString);
    }

    public function dashesToCamelCase($string)
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }


    public function commitFiles()
    {
        $commitFiles = "git init; git add .; git commit -m 'init'; git remote add origin git@github.com:shortlist-digital/{$this->projectName}.git; git push origin master";
        echo shell_exec($commitFiles);
    }
}

new CreatePlugin();
