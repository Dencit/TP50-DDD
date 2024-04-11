### 可用 ide 提供的 phpunit 
~~~
可用 ide 提供的 phpunit 执行, 不用命令行.
~~~

### 执行方式
~~~
php think unit --filter 'SampleTest' tests/demo/api
或
php think unit tests/demo/api/SampleTest.php
~~~

### 目录

###### 例子
~~~
接口-用例
php think unit tests/demo/api/SampleTest.php
php think unit clean tests/demo/api/SampleTest.php
php think unit stay tests/demo/api/SampleTest.php

php think unit tests/demo/api/SampleUrlTest.php
php think unit clean tests/demo/api/SampleUrlTest.php
php think unit stay tests/demo/api/SampleUrlTest.php

行为-用例
php think unit tests/demo/behavior/SampleBehavior.php //默认 不清除 缓存ID和数据
php think unit clean tests/demo/behavior/SampleBehavior.php //清除 所有 缓存ID和数据
php think unit stay tests/demo/behavior/SampleBehavior.php //清除 缓存ID 但 保留数据
~~~



