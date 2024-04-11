
# 参考资料

[链路追踪 Tracing 的前世今生](https://baijiahao.baidu.com/s?id=1718414047917857518&wfr=spider&for=pc&searchword=%E9%93%BE%E8%B7%AF%E8%BF%BD%E8%B8%AA%20%E8%AE%BE%E8%AE%A1)


# X-Trace对元数据的内容定义：

~~~


'trace' => [		
	'trace_id' 			=> '11c6b8c3-6864-4019-a7d4-ec366f12aa4d', 	//全局唯一的id,标识初始请求为根请求
	'parent_id'			=> '22c6b8c3-6864-4019-a7d4-ec366f12aa4d', 	//父节点id 调用链内唯一		//请求方trace信息中的 span_id
	'span_id' 			=> '33c6b8c3-6864-4019-a7d4-ec366f12aa4d', 	//当前操作id 调用链内唯一 	//接收方生成uuid
	'type' 				=> 'down', 									//当前操作类型: next表示兄弟关系(并发场景), down表示父子关系(单进程场景)
	'option' 			=> [										//预留字段 用于扩展
		'span_ip' => '172.0.0.1'									//当前服务器IP
		'span_name' 	=> 'backend.php.name'					    //当前项目名称: 前端-frontend.vue.name/前端-frontend.nuxt.name, 后端-backend.php.tp50/backend.java.name, 中间件-middle.node.name
		'span_start' 	=> '1652378932000',							//当前请求起始时间-毫秒时间戳
		'span_end' 		=> '1652378932099'							//当前请求结束时间-毫秒时间戳
		'span_duration' => '0.099'									//当前请求耗时-毫秒时间戳
	],
	//'destination' 		=> '', 									//用于指定上报地址
	//'flags' 			=> [0,1]									//顺序标记 option,destination 是否使用: 0-否,1-是
]

~~~


# 链路传播的操作

~~~

//获取头部或链接参数 trace = {json数据}, 生成请求方信息
//起始请求(如前端)不用传trace 
//接力请求(如后端curl)需要传
$xTrace = xTrace::instance();

$xTrace->pushFrom($option);				//获取请求方的信息

$xTrace->pushDown($option);  			//获取 请求方传递到->接收方的信息 ( trace_id 和 请求方一致 )

$xTrace->pushNext($option);  			//获取并发场景下的请求方信息 (trace_id,parent_id在多进程下一致)

~~~

