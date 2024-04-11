<?php

namespace domain\demo\srv;

use domain\base\srv\BaseSrv;

/**
 * notes: 领域层-业务类 ( 领域服务聚合根,每个模块只能有一个,用于特别复杂 的 多领域服务调用 )
 * desc: 当不同 应用端/模块 的 应用层-业务类,对同一个表数据(或第三方API)进行操作, 该表的操作代码分散在多个应用端中且冗余, 就需要抽象到这一层.
 * 领域层-业务类 允许 被 跨应用端/模块 调用, 而 各应用层-业务 则保持隔离, 避免应用层业务耦合.
 * 调用原则: 向下调用[仓储类,第三方服务-SDK]
 */
class DemoRootSrv extends BaseSrv
{

}