<?php

namespace Dcat\Admin\Scaffold;

use Illuminate\Support\Str;

trait GridCreator
{
    /**
     * @param  string  $primaryKey
     * @param  array  $fields
     * @return string
     */
    protected function generateGrid(string $primaryKey = null, array $fields = [], $timestamps = null)
    {
        $primaryKey = $primaryKey ?: request('primary_key', 'id');
        $fields = $fields ?: request('fields', []);
        $timestamps = $timestamps === null ? request('timestamps') : $timestamps;
        $modelName = class_basename(request('model_name',''));

        $rows = [
            "\$grid->column('{$primaryKey}')->sortable();",
        ];

        foreach ($fields as $field) {
            if (empty($field['name'])) {
                continue;
            }

            if ($field['name'] == $primaryKey) {
                continue;
            }
            if ($field['name'] == 'sort_order') {
                $rows[] = "            \$grid->column('{$field['name']}')->editable();";
                continue;
            }

            if ($field['name'] == 'remark') {
                $tmpRows = '';
                foreach ($fields as $_f) {
                    if ($modelName && Str::startsWith($_f['name'],'is_')) {
                        $tmpRows .= "                        ['{$_f['comment']}', {$modelName}::value2des('{$_f['name']}',\$this->{$_f['name']})],\n";
                    } else {
                        $tmpRows .= "                        ['{$_f['comment']}', \$this->{$_f['name']}],\n";
                    }
                }
                $rows[] = <<<COLSTR
            \$grid->column('remark','详情')
                ->display('查看')
                ->modal('详细信息',function(\$modal){
                    \$_rows = [
{$tmpRows}
                    ];
                    return \Dcat\Admin\Widgets\Table::make(\$_rows);
                });
COLSTR;
                continue;
            }

            if ($modelName && Str::startsWith($field['name'],'is_')) {
                $rows[] = "            \$grid->column('{$field['name']}')->switch();";
                continue;
            }

            if (Str::endsWith($field['name'],'_image')) {
                $rows[] = "            \$grid->column('{$field['name']}')->image(getImgServer(),100,100);";
                continue;
            }

            if (in_array($field['name'],['delete_time','create_time','update_time'])) {
                $rows[] = "            //\$grid->column('{$field['name']}');";
                continue;
            }

            $rows[] = "            \$grid->column('{$field['name']}');";
        }

        if ($timestamps) {
            $rows[] = '            $grid->column(\'created_at\');';
            $rows[] = '            $grid->column(\'updated_at\')->sortable();';
        }

        $tmpRows = '';
        foreach ($fields as $_f) {
            if (in_array($_f['name'],['remark','sort_order','delete_time','create_time','update_time'])) {
                continue;
            }
            if ($modelName && Str::startsWith($_f['name'],'is_')) {
                $tmpRows .= "                \$filter->equal('{$_f['name']}')->width(3)->select({$modelName}::vd2options('{$_f['name']}'));\n";
            } else {
                $tmpRows .= "                \$filter->equal('{$_f['name']}')->width(3);\n";
            }
        }
        $rows[] = <<<EOF

            \$grid->filter(function (Grid\Filter \$filter) {
                \$filter->panel();
                \$filter->expand();
{$tmpRows}
            });
EOF;

        return implode("\n", $rows);
    }
}
