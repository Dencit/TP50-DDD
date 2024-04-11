### EsModel 查询工具使用说明

###### ESModel 模型使用

* 模型基本结构
~~~

class EsSampleEDoc extends EsModel
{
    //ES索引名 - 必须
    protected $esTable = 'es_sample';

    //执行模型是否自动维护时间戳
    protected $timestamps = true;
    const CREATED_AT = 'doc_create_time';
    const UPDATED_AT = 'doc_update_time';

    //字段设置类型自动转换
    protected $casts = [
        "id"          => "integer",
        "name"        => "text",
        "float_value" => "float",

        "created_at" => "date",
        "updated_at" => "date",
        "deleted_at" => "date",

        "doc_id"          => "integer",
        "doc_source"      => "text",
        "doc_create_time" => "date",
        "doc_update_time" => "date",
    ];

    //创建索引 - 必须 - 只需要执行一次
    public function schema($version=0)
    {
        $table = $this->esTable;
        //索引版本号
        if(!empty($version)){
            $table.='-'.$version;
        }

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

        return $result;
    }

}

~~~

###### 新增数据

* 单行新增 - 相同id会覆盖
~~~
        //id为 源数据自增主键, 对应es文档id, 需要自己定义, 工具不做处理.
        $requestInput = ["id"=>1,"name"=>'abc'];

        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $result = $esModel->save($requestInput);
~~~

* 单行新增 - 指定id覆盖旧数据
~~~
        //id为 源数据自增主键, 对应es文档id, 需要自己定义, 工具不做处理.
        $requestInput = ["id"=>1,"name"=>'abc'];

        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $result = $esModel->saveById($requestInput['id'], $requestInput);
~~~

* 多行新增 - 相同id会覆盖
~~~
        //id为 源数据自增主键, 对应es文档id, 需要自己定义, 工具不做处理.
        $requestInput = [
            ["id"=>1,"name"=>'abc'],
            ["id"=>2,"name"=>'efg']
        ];

        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $result = $esModel->saveAll($requestInput);
~~~

###### 修改数据

* 单行更新 - 指定id覆盖旧数据
~~~
        //id为 源数据自增主键, 对应es文档id, 需要自己定义, 工具不做处理.
        $id = 1;
        $requestInput = ["id"=>1,"name"=>'abc'];

        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $result = $esModel->update($id, $requestInput);
~~~

* 多行更新 - 相同id会覆盖
~~~
        //id为 源数据自增主键, 对应es文档id, 需要自己定义, 工具不做处理.
        $requestInput = [
            ["id"=>1,"name"=>'abc'],
            ["id"=>2,"name"=>'efg']
        ];

        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $result = $esModel->updateAll($requestInput);
~~~

###### 查询数据

* fields 筛选索引字段
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);
    
        $esModel->fields(['id','name']); //筛选索引字段
        $esModel->fields(['id','name AS user_name']); //筛选索引字段 - 带AS佚名,只在 ->toArray()函数 触发.
        //$esModel->fields("id,name");
        //$esModel->fields(["*"]);
        //$esModel->fields("*");
    
        $esModel->select();
        $result = $esModel->toArray();
~~~


* where 筛选条件
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->where(id, "=", 1); //运算符支持: = , >, >= , < , <= , like , in , not in 

        $esModel->select();
        $result = $esModel->toArray();
~~~

* orWhere 筛选条件
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->orWhere(id, "=", 1); //运算符支持: = , >, >= , < , <= , like , in , not in 

        $esModel->select();
        $result = $esModel->toArray();
~~~

* whereIn 筛选条件
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->whereIn("id","1,2"); 

        $esModel->select();
        $result = $esModel->toArray();
~~~

* orWhereIn 筛选条件
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->orWhereIn("id","1,2"); 

        $esModel->select();
        $result = $esModel->toArray();
~~~

* whereNotIn 筛选条件
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->whereNotIn(id,"1,2"); 

        $esModel->select();
        $result = $esModel->toArray();
~~~

* where(function) 筛选条件
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->where('name',444);
        $esModel->where(function ($childEs){
            $childEs->orWhere('id',4);
            $childEs->orWhere('id',5);
        });

        $esModel->select();
        $result = $esModel->toArray();
~~~

* order 排序
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->order(id,"desc"); // 升序-asc , 降序-desc 

        $esModel->select();
        $result = $esModel->toArray();
~~~

* orderRaw 排序
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        //支持脚本排序
        $esModel->orderRaw(function(){
            return [
                "type"=>"number",
                "script"=>["source"=>"doc['sex'].value * 0.5"],
                "order"=>"desc"
            ];
        });

        $esModel->select();
        $result = $esModel->toArray();
~~~

* group by 聚合
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $groupArr=["status"];               // 单层聚合
        //$groupArr=["sex","status"];       // 多层聚合
        //$groupArr=["sex.terms","status.terms"]; // .terms 代表一般聚合,不传一样效果. 非聚合计算.
        
        $esModel->groupBy($groupArr);
        
        $esModel->select();
        $result = $esModel->toArray();
~~~

* group by 聚合计算
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        //单层聚合计算
        $groupArr=["status.count"];             // .count 合计个数
        //$groupArr=["status.cardinality"];     // .cardinality 去重合计个数
        //$groupArr=["status.sum"];             // .sum 加法合计
        //$groupArr=["status.min"];             // .min 最小值
        //$groupArr=["status.max"];             // .max 最大值
        //$groupArr=["status.avg"];             // .avg 平均值
        //$groupArr=["status.stats"];           // .stats 以上计算方式 全部执行

        //子层聚合计算 - 计算参数只能在最后一个字段使用, 否则ES端查询会报错
        //$groupArr=["sex","status.count"];         // .count 合计个数
        //$groupArr=["sex","status.cardinality"];   // .cardinality 去重合计个数
        //$groupArr=["sex","status.sum"];           // .sum 加法合计
        //$groupArr=["sex","status.min"];           // .min 最小值
        //$groupArr=["sex","status.max"];           // .max 最大值
        //$groupArr=["sex","status.avg"];           // .avg 平均值
        //$groupArr=["sex","status.stats"];         // .stats 以上计算方式 全部执行

        $esModel->groupBy($groupArr);
        
        $esModel->select();
        $result = $esModel->toArray();
~~~

* page 翻页
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->page(1, 20); //翻页参数[页码,页数] //如果不执行翻页,默认参数[1,100]

        $esModel->select();
        $result = $esModel->toArray();
~~~

* count/sum/min/max/avg/stats 聚合计算
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->count('id'); //合计个数
        //$esModel->distinct('id'); //合计个数-去重
        //$esModel->sum('id'); //加法合计
        //$esModel->min('id'); //最小值
        //$esModel->max('id'); //最大值
        //$esModel->avg('id'); //平均值
        //$esModel->stats('id'); //以上计算方式 全部执行
        
        $result = $esModel->toArray();
~~~

* select 多行查询
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->page(1, 20); //翻页参数[页码,页数] //如果不执行翻页,默认参数[1,100]
    
        $esModel->select();
        //可利用闭包 对查询dsl做 二次补充
        //$esModel->select(function (&$param){
            //dd($param);
        //});
    
        $result = $esModel->toSource();
        $result = $esModel->toArray();
~~~

* first 单行查询
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->first();
        //可利用闭包 对查询dsl做 二次补充
        //$esModel->first(function (&$param){
            //dd($param);
        //});
    
        $result = $esModel->toArray();
~~~

* find 单行查询
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $id = 1; //指定文档id
    
        $esModel->find($id);
        //可利用闭包 对查询dsl做 二次补充
        //$esModel->find($id,function (&$param){
            //dd($param);
        //});
    
        $result = $esModel->toArray();
~~~

* ES DSL - 打印校验
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->where(id, "=", 1);
    
        dd( $esModel->toDSL() ); //ES DSL
~~~

* 获取原始数据 - 打印校验
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->where(id, "=", 1);
        $esModel->select();
    
        dd( $esModel->toSource() ); //原始数据
~~~

* 获取原始数据转化 - 打印校验
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->where(id, "=", 1);
        $esModel->select();
    
        $result = $esModel->toArray(); //把行数据转换到新结构上
        //$result = $esModel->toArray(true); //把行数据转换到新结构上, 同时附加行数据中其它信息
    
        dd($result);//
~~~

* ORM单例重复调用时,重置查询参数.
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->where('id', "=", 1);
        $esModel->select();
        $esModel->toArray();
    
        $esModel->fresh(); //上面的条件会被清空
    
        $esModel->where('name', "=", 'abc');
        $esModel->select();
        $esModel->toArray();
~~~


* 关联预载入模型结构 - 1对1关联模型
~~~
        //模型内部结构
        class EsSampleEDoc extends EsModel
        {
            //ES索引名 - 必须
            protected $esTable = 'es_sample';
        
            //1对1关联模型
            public function with_order()
            {
                return $this->belongsTo(DtOrderEDoc::class, 'order_id', 'order_id')
                    ->fields(['goods_title','order_type_sign','order_id'])
                    ->groupBy(['order_id','order_id.cardinality'])
                    ->limit(0,1000)
                    ;
            }
        
        }

        //使用模型

        $version = 0;
        $esModel = EsSampleEDoc::instance($version);
    
        $esModel->with(['order']); //关联模型内部的1对1函数

        //$esModel->first();
        $esModel->select();

        $result = $esModel->toArray();

~~~


* 关联预载入模型结构 - 1对n关联模型
~~~
        //模型内部结构
        class EsSampleEDoc extends EsModel
        {
            //ES索引名 - 必须
            protected $esTable = 'es_sample';
        
            //1对n关联模型
            public function with_order_many()
            {
                return $this->hasMany(DtOrderEDoc::class, 'order_id', 'order_id')
                    ->fields(['goods_title','order_type_sign','order_id'])
                    ->limit(0,1000)
                    ;
            }
        
        }

        //使用模型

        $version = 0;
        $esModel = EsSampleEDoc::instance($version);
    
        $esModel->with(['order_many']); //关联模型内部的1对n函数

        //$esModel->first();
        $esModel->select();

        $result = $esModel->toArray();
~~~


###### 删除数据

* 删除 - 单行
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $id = 1; //指定文档id
    
        $esModel->deleteById($id);
~~~

* 删除 - 多行
~~~
        $version = 0;
        $esModel = EsSampleEDoc::instance($version);

        $esModel->whereIn("id","1,2");
    
        $esModel->delete();
~~~
