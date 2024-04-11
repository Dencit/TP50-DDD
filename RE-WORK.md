## 项目编码规范

#### 1/Controller控制器内,只用来引用 如"输入验证 / 业务逻辑 / 输出过滤"类型的类函数, 非封装的代码尽量少写在控制器函数里.

~~~
如:
SampleController:
public function sampleUpdate(){
        //输入逻辑控制
        $rules=[];
        $requestInput= request()->except($rules);
        $validate = new SampleValidate();
        $validate->checkSceneValidate('update',$requestInput);
        $id = $this->request->param('id');
        //业务逻辑控制
        $Service = new SampleService();
        $result = $Service->sampleUpdate($id,$requestInput);
        //输出逻辑控制
        return ApiResponse::Updated($result,new SampleTransformer());
}
~~~

#### 2/Service服务类,主要用来集合 业务逻辑的代码, 当输入数据通过验证后,传递给服务类. 在服务类函数内的代码,要先对所有关键数据id进行核查, 即 model::isExit($id) 的查询, 如果不符合预期则主动设置异常,终止业务, 如果符合预期则继续.

~~~
如:
SampleService:
public function sampleUpdate($id,&$requestInput){
        //业务逻辑
        SampleModel::isExit($id);
        $result = SampleModel::update($requestInput,['id'=>$id]);
        return $result;
}
SampleModel:
public static function isExit($id){
        $where=["id"=>$id];
        $field=['id'];  $field= array_merge( $field, array_keys($where) );
        $result=self::where($where)->field($field)->find();
        if( !$result ){
            Exception::app(
   SampleErrorCode::code("ID_NOT_FOUND") ,
   SampleErrorCode::msg("ID_NOT_FOUND"), __METHOD__);
        };
        return $result;
}
~~~

#### 3/Model模型类, 除了负责建立标准数据查询模型, 预留有函数模板:
~~~
model::isHave(); 存在状态 true/false
model::isExit(); 不存在则截停
model::isUnquire(); 重复则截停
~~~

#### 4/Transformer输出转换类, 通过模型查询的结果,输出为函数对象,需要通过转换类,格式化为json输出,
~~~
转换模板里没有设置的值都不会输出, 对关联模型附加的关联数据对象,可通过调用相应的转换器格式化,输出结果附加到当前结果设置的关联key中.

如:
SampleTransformer:
public function transform($result){
//转换模板
        $data = [
            "id"=>(int)$result->id,
            "name"=>(string)$result->name,
            "nick_name"=>(string)$result->nick_name,
            "mobile"=>(string)$result->mobile,
            "photo"=>(string)$result->photo,
            "sex"=>(int)$result->sex,
            "type"=>(int)$result->type,
            "status"=>(int)$result->status,
            "create_time"=>(string)$result->create_time,
            "update_time"=>(string)$result->update_time,
        ];
        $data = $this->arrayFilter( $result->toArray(), $data );
//关联模型转换
        $objName = 'user';
        $objectField = ['id', 'status'];
        $obj = $this->includeBelongsTo($objName, $result, $objectField, new UserTransformer());
        if (isset($obj) && $obj !== false) {
//附加到指定字段的后面
$this->dataAfterPush($data,"status",[$objName=>$obj]);
        }
        return $data;
}
SampleModel:
//关联模型
public function user(){
return $this->belongsTo(UserModel::class, 'id', 'id')->field('id,mobile,status');
}
~~~

#### 5/不直接 使用 .env 文件. env变量设置完, 先用config函数调用,输出带分类层级结构的配置数据, 便于日后,哪些配置要由数据库控制时,方便统一转移.

#### 6/第三方接口要封装成独立的sdk, 不在sdk内些写业务逻辑, 做到可以随时移到新项目中正常使用.
~~~
如果是相似业务的不同第三方接口,视情况而定,有必要通过工厂模式集成各方sdk,统一数据输入输出.
~~~

#### 7/路由名/模块名/控制器名/模型名/函数名..,等, 需要有关键字和 数据表名对应, 便于查找.

#### 8/相应数据表的服务类内, 相关的函数,除了被调用,不要写在别的服务类里, 也是便于查找.

#### 9/数据库表名的设计: 按 [模块名]_[表名+'s'] 设置, 这样数据库管理工具查看时, 相似的表就会排在一起, 与代码模块目录相呼应. 根据业务设计数据时,要注意按模块归类,.
~~~
如:
user模块的user = users
user模块的user_info = user_infos
goods模块的goods = goods //重复s则忽略
goods模块的goods_order = goods_orders
~~~

#### 10/由于有工具,可以根据表生成字段说明md表格, 所以, 表注释和字段注释,需要遵循格式编写.
~~~
如:
" 支付状态：0-未支付,1-已支付,2-已取消,3-已退款 "
"运费: 单位[分]"
"供应商订单数据: json字符串"
~~~

#### 11/数据表字段的顺序, 也要注意按类别顺序添加,以方便查阅.


#### 12/每个数据表,都必须具备以下基本字段,以满足model自动记录数据状态.
~~~
create_time, update_time, delete_time
字符类型 都是 datetime,
create_time字段不能为null, 其它都可以为null.
~~~

#### 13/try catch 记录日志标准用法:
~~~
$GoodsOrderService = new GoodsOrderService();
        try {
            $result = $GoodsOrderService->goodsOrderTransfer();
        }catch (Exception $e){
            Log::channel('task')->error($e->getMessage()); throw $e;
        }
记录异常日志之后, 继续throw抛出原异常,便于调试和前端反馈情况,
异常细节在生产环境已经通过 exception_handel 过滤掉, 只会看到 code 和 message,
开发环境则显示全部信息.
~~~

#### 14/二维数组 foreach 规范参考, 命名要反映对象属性,不调试也清楚数据结构.
~~~
foreach ($result as $ind=>$data){  } //用于 有序列表
foreach ($result as $index=>$data){  }//用于 有序列表
foreach ($result as $num=>$item){  } //用于 有序列表
foreach ($result as $k=>$v){  } //用于 键值对
foreach ($result as $key=>$val){  } //用于 键值对
~~~

#### 15/think-queue队列, 只用来处理需要排队处理的业务逻辑,不充当定时任务.
~~~
当前队列处理成功但不符合预期时,不建议再调用当前队列函数,就是单一业务逻辑,形成队列死循环,充当定时任务.
用定时任务每n秒批量处理业务,要比队列节省系统资源.
~~~

#### 16/think-migrate数据迁移, 使用migrate创建表,使用seed预置数据.
~~~
目前用naticat 做表结构同步, 单人开发时还好控制, 多人多分支开发时,就要求建表sql可迭代,可追溯.
开发自测时,靠migrate建表,需要修改时,不用另起migrate文件,migrate-rollback完再执行就可以.
发布到生产环境之前,都不需要另起migrate文件,之后就要.
~~~

#### 17/考虑到预发布环境, 数据库与生产环境同步的情况, 数据库迭代时,必须遵守以下原则:
~~~
表字段为 not null 时,必须设置默认值;
尽量把数据库层面的逻辑操作,换到服务端层面去模拟(例如外键锁,触发器等sql操作),也减轻数据库负担.
修改表字段类型且新旧冲突时( 例如: var_char变date_time; var_char变integer ), 以添加新字段 代替修改,并写定时器脚本,批量拷贝旧字段数据;
由上一条原则可见, 用migrate数据迁移建表的优点, 可以在代码版本里检查到谁的修改不符合规定. 比各自在测试数据库添加字段可控.
 ~~~

#### 18/数据表字段中,用tinyint的值,做状态区分的情况, 数字含义遵循以下规律:
~~~
"0" 表示未知状态;  "1" 表示否定;  "2" 表示肯定;
" 1,2,3,... " 表示流程状态时, 注意按流程顺序来设置数字.
如:
sex 性别  :  0-未知,1-男, 2-女;
status 状态  :  0-未知, 1-未启用, 2-已启用;
is_realname 实名认证  :  0-未知,1-否, 2-是;
apply_status 申请状态 : 0-未知, 1-申请, 2-驳回, 3-通过, 4-激活;
~~~

#### 19/统一用phpstorm 开发, 谨慎使用编辑工具的代码格式化.
~~~
每行代码长度不超过右边界线;
声明多个变量,尽量 聚集前置 或 放一行;
不复杂的 if else 逻辑,尽量写成一行;
函数内有花括号的地方能不换行就不换行,具体看代码整洁度;
逻辑代码块和块之间可以空一行或加注释,区分关系;
编辑器提示不符合规范的地方,尽量做到规范;
可以局部代码自动格式化,但禁止全部格式化;
~~~
