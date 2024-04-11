### EsOrm 查询工具使用说明

###### 新增数据

* 单行新增 - 相同id会覆盖
~~~
        //id为 源数据自增主键, 对应es文档id, 需要自己定义, 工具不做处理.
        $requestInput = ["id"=>1,"name"=>'abc'];

        $es = EsOrm::instance();
        $es->table('es_db', 'es_index_table');

        $result = $es->save($requestInput);
~~~

* 单行新增 - 指定id覆盖旧数据
~~~
        //id为 源数据自增主键, 对应es文档id, 需要自己定义, 工具不做处理.
        $requestInput = ["id"=>1,"name"=>'abc'];

        $es = EsOrm::instance();
        $es->table('es_db', 'es_index_table');

        $result = $es->saveById($requestInput['id'], $requestInput);
~~~

* 多行新增 - 相同id会覆盖
~~~
        //id为 源数据自增主键, 对应es文档id, 需要自己定义, 工具不做处理.
        $requestInput = [
            ["id"=>1,"name"=>'abc'],
            ["id"=>2,"name"=>'efg']
        ];

        $es = EsOrm::instance();
        $es->table('es_db', 'es_index_table');

        $result = $es->saveAll($requestInput);
~~~

###### 修改数据

* 单行更新 - 指定id覆盖旧数据
~~~
        //id为 源数据自增主键, 对应es文档id, 需要自己定义, 工具不做处理.
        $id = 1;
        $requestInput = ["id"=>1,"name"=>'abc'];

        $es = EsOrm::instance();
        $es->table('es_db', 'es_index_table');

        $result = $es->update($id, $requestInput);
~~~

* 多行更新 - 相同id会覆盖
~~~
        //id为 源数据自增主键, 对应es文档id, 需要自己定义, 工具不做处理.
        $requestInput = [
            ["id"=>1,"name"=>'abc'],
            ["id"=>2,"name"=>'efg']
        ];

        $es = EsOrm::instance();
        $es->table('es_db', 'es_index_table');

        $result = $es->updateAll($requestInput);
~~~

###### 查询数据

* fields 筛选索引字段
~~~
        $es = EsOrm::instance();
        $es->table('es_db');
    
        $es->fields(['id','name']); //筛选索引字段
        $es->fields(['id','name AS user_name']); //筛选索引字段 - 带AS佚名,只在 ->toArray()函数 触发.
        //$es->fields("id,name");
        //$es->fields(["*"]);
        //$es->fields("*");
    
        $es->select();
        $result = $es->toArray();
~~~


* where 筛选条件
~~~
        $es = EsOrm::instance();
        $es->table('es_db');

        $es->where(id, "=", 1); //运算符支持: = , >, >= , < , <= , like , in , not in 

        $es->select();
        $result = $es->toArray();
~~~

* orWhere 筛选条件
~~~
        $es = EsOrm::instance();
        $es->table('es_db');

        $es->orWhere(id, "=", 1); //运算符支持: = , >, >= , < , <= , like , in , not in 

        $es->select();
        $result = $es->toArray();
~~~

* whereIn 筛选条件
~~~
        $es = EsOrm::instance();
        $es->table('es_db');

        $es->whereIn("id","1,2"); 

        $es->select();
        $result = $es->toArray();
~~~

* orWhereIn 筛选条件
~~~
        $es = EsOrm::instance();
        $es->table('es_db');

        $es->orWhereIn("id","1,2"); 

        $es->select();
        $result = $es->toArray();
~~~

* whereNotIn 筛选条件
~~~
        $es = EsOrm::instance();
        $es->table('es_db');

        $es->whereNotIn(id,"1,2"); 

        $es->select();
        $result = $es->toArray();
~~~

* where(function) 筛选条件
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');

        $es->where('name',444);
        $es->where(function ($childEs){
            $childEs->orWhere('id',4);
            $childEs->orWhere('id',5);
        });

        $es->select();
        $result = $es->toArray();
~~~

* order 排序
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');

        $es->order(id,"desc"); // 升序-asc , 降序-desc 

        $es->select();
        $result = $es->toArray();
~~~

* orderRaw 排序
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');

        //支持脚本排序
        $es->orderRaw(function(){
            return [
                "type"=>"number",
                "script"=>["source"=>"doc['sex'].value * 0.5"],
                "order"=>"desc"
            ];
        });

        $es->select();
        $result = $es->toArray();
~~~

* group by 聚合
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');

        $groupArr=["status"];               // 单层聚合
        //$groupArr=["sex","status"];       // 多层聚合
        //$groupArr=["sex.terms","status.terms"]; // .terms 代表一般聚合,不传一样效果. 非聚合计算.
        
        $es->groupBy($groupArr);
        
        $es->select();
        $result = $es->toArray();
~~~

* group by 聚合计算
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');

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

        $es->groupBy($groupArr);
        
        $es->select();
        $result = $es->toArray();
~~~

* page 翻页
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');

        $es->page(1, 20); //翻页参数[页码,页数] //如果不执行翻页,默认参数[1,100]

        $es->select();
        $result = $es->toArray();
~~~

* count/sum/min/max/avg/stats 聚合计算
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');

        $es->count('id'); //合计个数
        //$es->distinct('id'); //合计个数-去重
        //$es->sum('id'); //加法合计
        //$es->min('id'); //最小值
        //$es->max('id'); //最大值
        //$es->avg('id'); //平均值
        //$es->stats('id'); //以上计算方式 全部执行

        $result = $es->toArray();
~~~

* select 多行查询
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');
    
        $es->page(1, 20); //翻页参数[页码,页数] //如果不执行翻页,默认参数[1,100]
    
        $es->select();
        //可利用闭包 对查询dsl做 二次补充
        //$es->select(function (&$param){
            //dd($param);
        //});
    
        $result = $es->toSource();
        $result = $es->toArray();
~~~

* first 单行查询
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');
    
        $es->first();
        //可利用闭包 对查询dsl做 二次补充
        //$es->first(function (&$param){
            //dd($param);
        //});
    
        $result = $es->toArray();
~~~

* find 单行查询
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');
    
        $id = 1; //指定文档id
    
        $es->find($id);
        //可利用闭包 对查询dsl做 二次补充
        //$es->find($id,function (&$param){
            //dd($param);
        //});
    
        $result = $es->toArray();
~~~

* ES DSL - 打印校验
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');
    
        $es->where(id, "=", 1);
    
        dd( $es->toDSL() ); //ES DSL
~~~

* 获取原始数据 - 打印校验
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');
    
        $es->where(id, "=", 1);
        $es->select();
    
        dd( $es->toSource() ); //原始数据
~~~

* 获取原始数据转化 - 打印校验
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');
    
        $es->where(id, "=", 1);
        $es->select();
    
        $result = $es->toArray(); //把行数据转换到新结构上
        //$result = $es->toArray(true); //把行数据转换到新结构上, 同时附加行数据中其它信息
    
        dd($result);//
~~~

* ORM单例重复调用时,重置查询参数.
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');
    
        $es->where('id', "=", 1);
        $es->select();
        $es->toArray();
    
        $es->fresh(); //上面的条件会被清空
    
        $es->where('name', "=", 'abc');
        $es->select();
        $es->toArray();
~~~

###### 删除数据

* 删除 - 单行
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');
    
        $id = 1; //指定文档id
    
        $es->deleteById($id);
~~~

* 删除 - 多行
~~~
        $es = EsOrm::instance();
        $es->table('es_sample');
    
        $es->whereIn("id","1,2");
    
        $es->delete();
~~~
