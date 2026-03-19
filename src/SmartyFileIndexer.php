<?php

namespace Adilis\SeoOptimizer;

if (!defined('_PS_VERSION_')) {
    exit;
}

class SmartyFileIndexer
{
    private $tplFiles = [];
    private $directoriesToScan = [];

    public function __construct()
    {
        $this->setDefaultDirectories();
    }

    public function setDefaultDirectories()
    {
        $this->addDirectory(_PS_THEME_DIR_);

        if (_PS_PARENT_THEME_DIR_ && is_dir(_PS_PARENT_THEME_DIR_)) {
            $this->addDirectory(_PS_PARENT_THEME_DIR_);
        }

        $modules = \Module::getModulesOnDisk();
        $modules = array_filter($modules, function ($module) {
            return (int)$module->active;
        });

        array_map(function ($module) {
            if ($module->name !== Utils::MODULE_NAME) {
                $this->addDirectory(_PS_MODULE_DIR_ . $module->name);
            }
        }, $modules);
    }

    public function addDirectory($directory)
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("$directory is not a valid directory.");
        }
        $this->directoriesToScan[] = realpath($directory);
    }

    private function scanAllDirectories()
    {
        foreach ($this->directoriesToScan as $directory) {
            $this->scanDirectory($directory);
        }
    }

    private function scanDirectory($directory)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'tpl') {
                $this->tplFiles[] = $file->getPathname();
            }
        }
    }

    public function getTplFiles(): array
    {
        $start_time = microtime(true);
        if (empty($this->tplFiles)) {
            $this->scanAllDirectories();
        }
        $duration = microtime(true) - $start_time;
        //dump('Smarty file indexing duration: ' . $duration);
        return $this->tplFiles;
    }
}