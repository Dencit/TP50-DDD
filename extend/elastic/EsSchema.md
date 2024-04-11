### EsSchema 索引创建工具使用说明

###### 索引库

* 创建索引
~~~
        $table='es_sample';

        $schema = EsSchema::instance();
        $exists = $schema->exists($table);
        if($exists){
            return $exists;
        }

        $schema->table($table)->drop();
        $schema->table($table)->setting(['number_of_shards' => 5])
            ->addColumn('id', ['type' => 'long'])
            //->addColumn('name', ['type' => 'text', 'index' => true])
            ->addColumn('name', ['type' => 'text', 'index' => true, 'analyzer' => 'ik_smart', 'store' => 'true']) //ik分词索引

            ->addColumn('float_value', ['type' => 'scaled_float',"scaling_factor"=>100]) //缩放因子,按整型计算,提高效率

            ->addColumn('created_at', ['type' => 'date'])
            ->addColumn('updated_at', ['type' => 'date'])
            ->addColumn('deleted_at', ['type' => 'date'])

            ->addColumn('doc_id', ['type' => 'long'])
            ->addColumn('doc_source', ['type' => 'text', 'index' => true])
            ->addColumn('doc_create_time', ['type' => 'date'])
            ->addColumn('doc_update_time', ['type' => 'date'])
        ;
        $result = $schema->create();

~~~

* 清空库索引 - 清除index+type层 所有索引 
~~~
        $db='db'; $table='es_sample';

        $schema = EsSchema::instance();
        $schema->table($table)->drop();
~~~

* 删除表索引 - 清除type层的索引
~~~
        $db='db'; $table='es_sample';

        $schema = EsSchema::instance();
        $schema->table($table)->delete();
~~~
