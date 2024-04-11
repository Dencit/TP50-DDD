<?php

namespace domain\demo\edoc;

use extend\elastic\EsModel;
use extend\elastic\EsSchema;

/**
 * notes: ES文档-模型类
 * Class EsSampleEDoc
 * @package Domain\Demo\EDocs
 */
class EsSampleEDoc extends EsModel
{
    //ES-table
    protected $esTable = 'es_sample';

    //执行模型是否自动维护时间戳
    protected $timestamps = true;
    const CREATED_AT = 'doc_create_time';
    const UPDATED_AT = 'doc_update_time';

    //字段设置类型自动转换 - https://www.kancloud.cn/manual/thinkphp5/138669
    protected $casts = [
        "id"          => "integer",
        "name"        => "text",
        "mobile"      => "text",
        "photo"       => "text",
        "sex"         => "integer",
        "type"        => "integer",
        "status"      => "integer",
        "float_value" => "float",

        "created_at" => "date",
        "updated_at" => "date",
        "deleted_at" => "date",

        "doc_id"          => "integer",
        "doc_source"      => "text",
        "doc_create_time" => "date",
        "doc_update_time" => "date",
    ];

    //创建索引 - 只需要执行一次
    public function schema($version = 0)
    {
        $table = $this->esTable;
        //索引版本号
        if (!empty($version)) {
            $table .= '-' . $version;
        }

        $schema = EsSchema::instance();
        $exists = $schema->exists($table);
        if ($exists) {
            return $exists;
        }

        $schema->table($table, $table)->drop();
        $schema->table($table)->setting(['number_of_shards' => 5])
            ->addColumn('id', ['type' => 'long'])
            ->addColumn('name', ['type' => 'text', 'index' => true])
            //->addColumn('name', ['type' => 'text', 'index' => true, 'analyzer' => 'ik_smart', 'store' => 'true']) //ik分词索引
            ->addColumn('mobile', ['type' => 'text', 'index' => true])
            ->addColumn('photo', ['type' => 'text', 'index' => true])
            ->addColumn('sex', ['type' => 'integer'])
            ->addColumn('type', ['type' => 'integer'])
            ->addColumn('status', ['type' => 'integer'])
            ->addColumn('float_value', ['type' => 'scaled_float', "scaling_factor" => 100])//缩放因子,按整型计算,提高效率

            ->addColumn('created_at', ['type' => 'date'])
            ->addColumn('updated_at', ['type' => 'date'])
            ->addColumn('deleted_at', ['type' => 'date'])
            ->addColumn('doc_id', ['type' => 'long'])
            ->addColumn('doc_source', ['type' => 'text', 'index' => true])
            ->addColumn('doc_create_time', ['type' => 'date'])
            ->addColumn('doc_update_time', ['type' => 'date']);
        $result = $schema->create();

        return $result;
    }

}