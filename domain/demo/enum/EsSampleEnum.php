<?phpnamespace domain\demo\enum;/** * notes: 数据单元常量 * desc: 状态层 - 业务中用到的常量,统一放这里, 一个数据单元 对应 一个常量类 */class EsSampleEnum{    const TABLE = 'es_sample'; //索引表名    const VERSION = 0; //对外接口-索引表版本号    const TASK_VERSION = 0; //数据清洗-索引表版本号}