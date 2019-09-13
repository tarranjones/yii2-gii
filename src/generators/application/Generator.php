<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tarranjones\yii\gii\generators\application;

use Yii;
use yii\base\Model;
use yii\gii\CodeFile;
use yii\helpers\FileHelper;
use yii\web\JsonParser;

class Generator extends \yii\gii\Generator
{

    public $type = 'console';
    public $rest = false;
    public $dirname = 'console';
    private $environments = [];


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['type', 'dirname'], 'string'],
            [['rest'], 'boolean'],
            [['rest', 'type', 'dirname'], 'required'],
            [['type', 'dirname'], 'filter', 'filter' => 'trim'],
            [['dirname'], 'validateDirName', 'skipOnEmpty' => false],
        ]);
    }

    public function validateDirName()
    {
        if (empty($this->dirname)) {
            $this->addError('dirname', 'Application Name cannot be blank.');
            return;
        }
        if (is_dir(Yii::getAlias('@common/../') . $this->dirname)) {
            $this->addError('dirname', 'Application directory already in use');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Application Generator';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'This generator generates generates an environment file.';
    }

    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $files = [];

        /**
         * for each environment dir add a new directory called $this->dirname containing the application env files
         */
        // update envs with new application
        // add new applications base files
        // php init current environment if it has one.
        // auto gen readme.md with vhost and nginx config
        // craete custom yii files called yii_{appname} or {appname}

        /**
         * add application env files to environment.php
         */
        if ($this->type === 'web') {

            $new_env_data = [
                'setWritable' => [
                    $this->dirname . '/runtime',
                    $this->dirname . '/web/assets',
                ],
                'setCookieValidationKey' => [
                    $this->dirname . '/config/main-local.php',
                ],
            ];

        } else if ($this->type === 'console') {

            $new_env_data = [
                'setWritable' => [
                    $this->dirname . '/runtime',
                ],
                'setExecutable' => [
                    $this->dirname,
                ],
            ];

        } else {
            // unrecognised type
            $new_env_data = [];
        }

        $environments_dir = Yii::getAlias('@common/../environments/');
        $environments_file = $environments_dir . 'index.php';
        $envs = (array)require $environments_file;

        if (file_exists($environments_file)) {

            $envs = array_map(function ($env) use ($new_env_data) {

                if (isset($env['setExecutable']) && in_array('yii_test', $env['setExecutable']) || isset($env['setCookieValidationKey']) && in_array('common/config/codeception-local.php', $env['setCookieValidationKey'])) {
                    // is a dev env
                    $new_env_data['setExecutable'][] = $this->dirname . '_test';
                }
                return array_merge_recursive($env, $new_env_data);
            }, $envs);

            $files[] = new CodeFile($environments_file, $this->render('environments/index.php', [
                'envs' => $envs
            ]));
        }

        /**
         * copy core application files to yii application
         */
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getTemplatePath() . '/' . $this->type,\RecursiveIteratorIterator::SELF_FIRST| \RecursiveDirectoryIterator::SKIP_DOTS )) as $file){

            $template_file = substr($file, strlen($this->getTemplatePath()));
            $dest_file = Yii::getAlias('@common/../' . $this->dirname  . substr($file, strlen($this->getTemplatePath() . '/' . $this->type)));

            $files[] = new CodeFile( $dest_file, $this->render($template_file, [
                'appName' => $this->dirname,
            ]));
        }

        /**
         * add application env files to every environment
         */
        foreach ($envs as $envName => $env){

            $is_dev = isset($env['setExecutable']) && in_array('yii_test', $env['setExecutable']) || isset($env['setCookieValidationKey']) && in_array('common/config/codeception-local.php', $env['setCookieValidationKey']);

            $env_type = $is_dev ? 'dev' : 'prod';

            $template_dir = $this->getTemplatePath() . '/environments/' . $this->type . '/' . $env_type;

            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($template_dir, \RecursiveIteratorIterator::SELF_FIRST| \RecursiveDirectoryIterator::SKIP_DOTS )) as $file){

                $template_file = substr($file, strlen($this->getTemplatePath()));
                $dest_file = $environments_dir . $env['path']  . '/'. $this->dirname . substr($file, strlen($template_dir));

                $dest_filename = basename($dest_file);
                $dest_path = dirname($dest_file);

                if(strpos($dest_file, 'yii') !== false){
                    $dest_filename = str_replace(['yii'],['yii-' . $this->dirname], basename($dest_file));
                     $dest_path = dirname($dest_path);
                }

                $files[] = new CodeFile($dest_path . '/' . $dest_filename , $this->render($template_file, [
                    'appName' => $this->dirname,
                    'envPath' => $env['path']
                ]));
            }
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
        return [];
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
