响应拦截器。
这里 Response 类用于注册响应拦截, Std类包含了api标准的响应拦截。

所有拦截器必须包含 toFilter(), toError() 两个方法，用于处理正常响应及错误时响应。
这里需要注意，异常类code已经被占领一部分：
0 php抛出
1 框架抛出
2 request拦截器抛出
