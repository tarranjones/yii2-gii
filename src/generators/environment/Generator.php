<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tarranjones\yii\gii\generators\environment;

use Yii;
use yii\base\Model;
use yii\gii\CodeFile;
use yii\helpers\Inflector;

class Generator extends \yii\gii\Generator
{

    public $type;
    public $path;

    public $environments =[];
    public $default_environments;
    public $name;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['type', 'path', 'name'], 'string'],
            [['type', 'path'], 'required'],
            [['type', 'path', 'name'], 'filter', 'filter' => 'trim'],
            [['type'], 'validateType', 'skipOnEmpty' => false],
            [['name'], 'validateName', 'skipOnEmpty' => false],
            [['path'], 'validatePath', 'skipOnEmpty' => false],
        ]);
    }

    public function validateName()
    {
        if(empty($this->name)){
            $this->addError('name', 'Environment Name cannot be blank.');
            return;
        }
        if(array_key_exists($this->name, $this->environments)){
            $this->addError('name', 'Environment with the path "' . $this->name . '" already exists.');
        }
    }

    public function validatePath()
    {
        if(empty($this->path)){
            $this->addError('path', 'Environment path cannot be blank.');
            return;
        }
        foreach ($this->environments as $env){
            if(empty($env['path'])){
                continue;
            }
            if($env['path'] === $this->path){
                $this->addError('path', 'Environment with the path "' . $this->path . '" already exists.');
                break;
            }
        }
    }

    public function validateType()
    {
        if(empty($this->type)){
            $this->addError('type', 'Environment type cannot be blank.');
            return;
        }
        if(array_key_exists($this->type, $this->environments) ){

            $env = $this->environments[$this->type];
            if(!is_dir(Yii::getAlias('@common/../environments/') . $env['path'])){
                $this->addError('type', 'Environment Type "' . $this->type . '" has an invalid path, ' . Yii::getAlias('@common/../environments/') . $env['path'] . ' not found');
            }

        } else if(array_key_exists($this->type, $this->default_environments)){
            $env = $this->default_environments[$this->type];
            if(!is_dir($this->defaultTemplate() . '/environments/' . $env['path'] )){
                $this->addError('type', 'Environment Type "' . $this->type . '" has an invalid path, ' . $this->defaultTemplate() . '/environments/' . $env['path'] . ' not found');
            }

        } else {
            $this->addError('type', 'Environment Type "' . $this->type . '" not found, please select an existing environment to extend.');
        }

    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Environment Generator';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'This generator generates generates an environment file.';
    }


    public function init()
    {
        parent::init();
        if(file_exists(Yii::getAlias('@common/../environments/index.php'))){
            $this->environments = (array)require Yii::getAlias('@common/../environments/index.php');
        }
        $this->default_environments = [
            'Development' => [
                'path' => 'dev',
                'setWritable' => [],
                'setExecutable' => [],
                'setCookieValidationKey' => [],
            ],
            'Production' => [
                'path' => 'prod',
                'setWritable' => [],
                'setExecutable' => [],
                'setCookieValidationKey' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {

        $files = [];

        $envs = $this->environments;

        $environments_dir = Yii::getAlias('@common/../environments/');

        if(array_key_exists($this->type, $envs)){
            $template_env = $envs[$this->type];
            $root = $environments_dir;
        } else {
            $template_env = $this->default_environments[$this->type];
            $root = $this->defaultTemplate() . '/environments/';
        }

        $new_env = array_merge($template_env, ['path' => $this->path]);
        $envs[$this->name] = $new_env;

        $files[] = new CodeFile($environments_dir .'index.php', $this->render('environments/index.php', ['envs' => $envs]));

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . $template_env['path'],\RecursiveIteratorIterator::SELF_FIRST| \RecursiveDirectoryIterator::SKIP_DOTS )) as $env_file){

                $files[] = new CodeFile(Yii::getAlias($environments_dir. $new_env['path']  . substr($env_file, strlen($root . $template_env['path']))), str_replace([
                    "'" . $template_env['path']. "'",
                    "'test'"
                ],[
                   "'" . $new_env['path']. "'",
                    "'test-" . $new_env['path']. "'"
                ],file_get_contents($env_file)));
        }
        return $files;
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [

        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function requiredTemplates()
    {
        return array_merge(parent::requiredTemplates(), [
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), [
            ''
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function hints()
    {
        return array_merge(parent::hints(), [

        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function successMessage()
    {
        return parent::successMessage();

    }
}
