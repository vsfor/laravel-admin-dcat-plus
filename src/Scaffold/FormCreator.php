<?php

namespace Dcat\Admin\Scaffold;

use Illuminate\Support\Str;

trait FormCreator
{
    /**
     * @param  string  $primaryKey
     * @param  array  $fields
     * @param  bool  $timestamps
     * @return string
     */
    protected function generateForm(string $primaryKey = null, array $fields = [], $timestamps = null)
    {
        $primaryKey = $primaryKey ?: request('primary_key', 'id');
        $fields = $fields ?: request('fields', []);
        $timestamps = $timestamps === null ? request('timestamps') : $timestamps;

        $rows = [
            <<<EOF
\$form->display('{$primaryKey}');
EOF

        ];

        foreach ($fields as $field) {
            if (empty($field['name'])) {
                continue;
            }

            if ($field['name'] == $primaryKey) {
                continue;
            }

            if (Str::startsWith($field['name'],'is_')) {
                $rows[] = "            \$form->radio('{$field['name']}')->default(1)->options([1=>'是',0=>'否']);";
                continue;
            }

            if (Str::endsWith($field['name'],'image')) {
                $_str = <<<EOF
            \$form->image('{$field['name']}')
                ->saveAsString()
                ->disk('alioss')
                ->move('image/'.date('Y/m'))
                ->uniqueName();
EOF;
                $rows[] = $_str;
                continue;
            }

            if (Str::endsWith($field['name'],'images')) {
                $_str = <<<EOF
            \$form->multipleImage('{$field['name']}')
                ->sortable()
                ->saveAsString()
                ->disk('alioss')
                ->move('image/'.date('Y/m'))
                ->uniqueName();
EOF;
                $rows[] = $_str;
                continue;
            }

            if (in_array($field['name'],['delete_time','create_time','update_time'])) {
                continue;
            }

            if ($field['name'] == 'remark') {
                $rows[] = "            \$form->text('{$field['name']}')->saveAsString();";
                continue;
            }

            $rows[] = "            \$form->text('{$field['name']}');";
        }
        if ($timestamps) {
            $rows[] = <<<'EOF'

                $form->display('created_at');
                $form->display('updated_at');
EOF;
        }

        return implode("\n", $rows);
    }
}
