### 堆栈调试工具

> 1 打印堆栈,可选 trace_code 参数 "anchor_param", "anchor_args", "anchor_context",

~~~

     bt(); //输出 精简参数-默认
     bt('line'); //输出 行信息
     bt('param'); //输出 精简入参
     bt('args'); //输出 详细入参
     bt('context'); //输出 过程代码
     bt('all'); //输出 全部参数

~~~

> 2 打印堆栈, 输出 trace_code 堆栈 和 trace 原始堆栈

~~~

     bt('',true); //输出 trace_code,trace

~~~