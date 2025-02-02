<?php

namespace Dcat\Admin\Scaffold;

use Dcat\Admin\Exception\AdminException;
use Dcat\Admin\Support\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModelCreator
{
    /**
     * Table name.
     *
     * @var string
     */
    protected $tableName;

    /**
     * Model name.
     *
     * @var string
     */
    protected $name;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * ModelCreator constructor.
     *
     * @param  string  $tableName
     * @param  string  $name
     * @param  null  $files
     */
    public function __construct($tableName, $name, $files = null)
    {
        $this->tableName = $tableName;

        $this->name = $name;

        $this->files = $files ?: app('files');
    }

    /**
     * Create a new migration file.
     *
     * @param  string  $keyName
     * @param  bool|true  $timestamps
     * @param  bool|false  $softDeletes
     * @return string
     *
     * @throws \Exception
     */
    public function create($keyName = 'id', $timestamps = true, $softDeletes = false)
    {
        $path = $this->getpath($this->name);
        $dir = dirname($path);

        if (! is_dir($dir)) {
            $this->files->makeDirectory($dir, 0755, true);
        }

        if ($this->files->exists($path)) {
            throw new AdminException("Model [$this->name] already exists!");
        }

        $stub = $this->files->get($this->getStub());

        $stub = $this->replaceClass($stub, $this->name)
            ->replaceNamespace($stub, $this->name)
            ->replaceComment($stub)
            ->replaceValueDes($stub)
            ->replaceSoftDeletes($stub, $softDeletes)
            ->replaceDatetimeFormatter($stub)
            ->replaceTable($stub, $this->name)
            ->replaceTimestamp($stub, $timestamps)
            ->replacePrimaryKey($stub, $keyName)
            ->replaceSpace($stub);

        $this->files->put($path, $stub);
        $this->files->chmod($path, 0777);

        return $path;
    }

    /**
     * Get path for migration file.
     *
     * @param  string  $name
     * @return string
     */
    public function getPath($name)
    {
        return Helper::guessClassFileName($name);
    }

    /**
     * Get namespace of giving class full name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Replace class dummy.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceClass(&$stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        $stub = str_replace('DummyClass', $class, $stub);

        return $this;
    }

    /**
     * Replace namespace dummy.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace(
            'DummyNamespace',
            $this->getNamespace($name),
            $stub
        );

        return $this;
    }

    /**
     * Replace comment dummy.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceComment(&$stub)
    {
        $stub = str_replace(
            'DummyComment',
            $this->getTableComment($this->tableName),
            $stub
        );

        return $this;
    }

    protected function getTableComment($tableName)
    {
        $info = jsonDecode(jsonEncode(DB::select("SHOW FULL COLUMNS FROM `{$tableName}`;")));
        //showLog($info);exit();
        $_key = $info[0]; //仅判断第一个字段；非常规数据表，生成后需自行编辑调整。
        if ($_key['Key'] == 'PRI') {
            $this->pkName = $_key['Field'];
            if (strstr($_key['Type'],'int')) {
                $this->pkType = 'int';
            } else {
                $this->pkType = 'string';
            }
            if ($_key['Extra'] == 'auto_increment') {
                $this->pkIncrement = 'true';
            } else {
                $this->pkIncrement = 'false';
            }
        }
        $comments = ['/**'];
        $comments[] = '* table `'.$tableName.'`';
        $comments[] = '*';
        foreach ($info as $attr) {
            $t = '* @property';
            if (strpos($attr['Type'],'int') !== false) {
                $t .= ' int';
            } else {
                $t .= ' string';
            }
            if ($attr['Null'] != 'NO') {
                $t .= '|null';
            }
            $t .= ' $'.$attr['Field'];
            if (!empty($attr['Comment'])) {
                $t .= ' '. $attr['Comment'];
            } else {
                $t .= ' //'.$attr['Type'];
            }
            $comments[] = $t;
            if ($attr['Field'] == 'delete_time') {
                $this->hasDeleteTimeColumn = true;
            }
        }
        $comments[] = '*/';
        return implode("\n ", $comments);
    }

    /**
     * Replace soft-deletes dummy.
     *
     * @param  string  $stub
     * @return $this
     */
    protected function replaceValueDes(&$stub)
    {
        $import = 'use App\\Helpers\\ValueDesTrait;';
        $use = 'use ValueDesTrait;';

        $stub = str_replace(['DummyImportValueDesTrait', 'DummyUseValueDesTrait'], [$import, $use], $stub);

        return $this;
    }

    protected $hasDeleteTimeColumn = false;
    /**
     * Replace soft-deletes dummy.
     *
     * @param  string  $stub
     * @param  bool  $softDeletes
     * @return $this
     */
    protected function replaceSoftDeletes(&$stub, $softDeletes)
    {
        $import = $use = $delColumn = '';

        if ($softDeletes) {
            $import = 'use Illuminate\\Database\\Eloquent\\SoftDeletes;';
            $use = 'use SoftDeletes;';
        }

        if ($this->hasDeleteTimeColumn) {
            $delColumn = 'const DELETED_AT = \'delete_time\';';
        }

        $stub = str_replace(['DummyImportSoftDeletesTrait', 'DummyUseSoftDeletesTrait', 'DummyDeleteColumnName'], [$import, $use, $delColumn], $stub);

        return $this;
    }

    /**
     * Replace datetimeFormatter dummy.
     *
     * @param  string  $stub
     * @param  bool  $softDeletes
     * @return $this
     */
    protected function replaceDatetimeFormatter(&$stub)
    {
        $import = $use = '';

        if (version_compare(app()->version(), '7.0.0') >= 0) {
            $import = 'use Dcat\\Admin\\Traits\\HasDateTimeFormatter;';
            $use = 'use HasDateTimeFormatter;';
        }

        $stub = str_replace(['DummyImportDateTimeFormatterTrait', 'DummyUseDateTimeFormatterTrait'], [$import, $use], $stub);

        return $this;
    }

    /**
     * Replace primarykey dummy.
     *
     * @param  string  $stub
     * @param  string  $keyName
     * @return $this
     */
    protected function replacePrimaryKey(&$stub, $keyName)
    {
        $modelKey = $keyName == 'id' ? '' : "protected \$primaryKey = '$keyName';";

        $stub = str_replace('DummyModelKey', $modelKey, $stub);

        return $this;
    }

    /**
     * Replace Table name dummy.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceTable(&$stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        $table = Str::plural(strtolower($class)) !== $this->tableName ? "protected \$table = '$this->tableName';" : '';

        $stub = str_replace('DummyModelTable', $table, $stub);

        return $this;
    }

    /**
     * Replace timestamps dummy.
     *
     * @param  string  $stub
     * @param  bool  $timestamps
     * @return $this
     */
    protected function replaceTimestamp(&$stub, $timestamps)
    {
        $useTimestamps = $timestamps ? '' : "public \$timestamps = false;\n";

        $stub = str_replace('DummyTimestamp', $useTimestamps, $stub);

        return $this;
    }

    /**
     * Replace spaces.
     *
     * @param  string  $stub
     * @return mixed
     */
    public function replaceSpace($stub)
    {
        return str_replace(["\n\n\n", "\n    \n"], ["\n\n", ''], $stub);
    }

    /**
     * Get stub path of model.
     *
     * @return string
     */
    public function getStub()
    {
        return __DIR__.'/stubs/model.stub';
    }
}
