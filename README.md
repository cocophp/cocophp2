cocophp v2
请注意需要使用到 mysqli扩展。

更多介绍可移步至 www.cocophp.com

整个站点目录如下：

```
/---
   |__  applications  // api业务逻辑
   |__  config        // 配置文件
      |___test        // 测试环境配置文件
      |___uat         // 预发布环境配置文件
      |___prod        // 生产环境配置文件
   |__  console       // 控制台脚本业务逻辑
   |__  core          // 框架核心
   |__  lib           // 一些扩展lib
   |__  logs          // 日志信息
   |__  public        // 项目根目录
   |__  request       // 请求拦截器
   |__  response      // 响应拦截器，用于拦截返回数据。
   |__  runtime       // 运行时的一些缓存数据。
```
