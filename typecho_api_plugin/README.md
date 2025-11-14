## Typecho REST API 插件

该插件为 Typecho 提供统一的 RESTful API 服务层，覆盖文章、页面、分类、标签、评论、用户和后台管理等场景，同时内置 Token 认证、签名校验、限流、黑名单、日志、CORS、中间件扩展等能力，可直接作为前后端分离 / 小程序 / APP 的数据接口。

### 功能概览

- **公共接口**：文章列表、详情、归档、页面、分类、标签、评论列表、提交评论、站点配置、作者信息。
- **用户接口**：登录、刷新 token、获取/修改资料、修改密码、作者文章列表。
- **后台接口**：文章 CRUD / 草稿、评论审核 & 删除、附件上传、统计面板、站点配置更新。
- **安全控制**：Bearer Token、签名模式(timestamp + nonce + signature)、IP 黑名单、按 IP 限流、全站 CORS、请求/错误日志。
- **扩展机制**：第三方插件可注册路由、中间件、Before/After Dispatch Hook，统一响应结构。

### 安装与启用

1. 将 `typecho_api_plugin` 放入 Typecho `/usr/plugins` 目录。
2. 登录后台 → 插件 → 启用 “Typecho API Plugin”。
3. 在插件配置中设置 API 前缀、Token 密钥、限流、签名、CORS 等参数。
4. 默认入口为 `https://your-site.com/api/v1`，可在配置里修改。

### 配置说明

| 选项 | 描述 |
| ---- | ---- |
| API 前缀 | REST 路径前缀，默认 `/api/v1` |
| Token 密钥 / 有效期 | 用于生成/验证 JWT 风格 token |
| CORS | 是否自动返回跨域响应头及允许的 Origin |
| 签名模式 | 开启后需在请求头/参数中提供 `timestamp`、`nonce`、`signature` |
| 限流 | IP 级别访问频率控制（次数 + 时间窗口） |
| 日志 | 是否写入 `runtime/logs/api.log` |
| 评论设置 | 是否需登录、敏感词过滤 |
| IP 黑名单 | 命中后直接 403 |

### 认证与安全

- **Token**：`POST /api/v1/auth/login` 获取，后续请求在 Header 中附 `Authorization: Bearer <token>`。
- **刷新**：`POST /api/v1/auth/refresh`（可直接复用 Authorization 头）。
- **签名模式**：当启用时需附加 `timestamp`, `nonce`, `signature=sha256(path + timestamp + nonce + secret)`。
- **限流**：默认 60 秒内 120 次，可按需在配置中调整。
- **Middleware**：内置 `cors`、`request logger`、`signature`、`rate limit`、`token`、`admin`、`ip blacklist`，支持扩展自定义中间件。

### API 速览

| 模块 | 方法 | 路径 | 说明 |
| ---- | ---- | ---- | ---- |
| Posts | GET | `/api/v1/posts` | 支持分页、分类、标签、关键字、排序、includeContent |
| Posts | GET | `/api/v1/post/{id或slug}` | `markdown=true` / `html=true` 返回原文或 HTML |
| Posts | GET | `/api/v1/posts/archive` | 年份分组归档 |
| Pages | GET | `/api/v1/pages` / `/api/v1/page/{slug}` | 页面列表/详情 |
| Taxonomy | GET | `/api/v1/categories` `/api/v1/tags` | 分类 / 标签 |
| Comments | GET | `/api/v1/comments/{postId}` | 嵌套评论树 |
| Comments | POST | `/api/v1/comment` | 提交评论（可配置需登录/敏感词过滤） |
| Site | GET | `/api/v1/site/info` | 标题、描述、备案、主题等 |
| Site | GET | `/api/v1/site/author/{uid}` | 作者信息 |
| Auth | POST | `/api/v1/auth/login` / `/auth/refresh` | 登录 / 刷新 token |
| User | GET | `/api/v1/user/info` | 当前用户信息（需 token） |
| User | POST | `/api/v1/user/update` / `/user/password` | 修改资料 / 密码 |
| User | GET | `/api/v1/user/posts` | 当前用户文章 |
| Admin | POST | `/api/v1/admin/post/add` / `post/update/{id}` | 创建 / 更新文章 |
| Admin | DELETE | `/api/v1/admin/post/{id}` | 删除文章 |
| Admin | POST | `/api/v1/admin/post/draft` / `post/draft/{id}` | 草稿新增/更新（status=draft） |
| Admin | POST | `/api/v1/admin/comment/approve/{id}` | 评论审核 |
| Admin | DELETE | `/api/v1/admin/comment/{id}` | 删除评论及子级 |
| Admin | POST | `/api/v1/admin/upload` | 文件上传：multipart 或 base64 |
| Admin | GET | `/api/v1/admin/statistics` | 文章/分类/标签/评论等统计 |
| Admin | POST | `/api/v1/admin/config` | 更新站点标题、描述、ICP、Logo |

所有接口统一返回：

```json
{
  "code": 0,
  "msg": "ok",
  "data": {},
  "timestamp": 1700000000
}
```

### 扩展能力

```php
use TypechoApiPlugin\Extensions\API;

API::route('GET', '/custom/ping', function ($app, $request) {
    return ['code' => 0, 'msg' => 'pong', 'data' => []];
});

API::middleware('custom', CustomMiddleware::class);
API::beforeDispatch(function ($app, $request) {
    // ...
});
```

- 自定义路由默认沿用统一响应结构，可返回数组或 `Response::success(...)`。
- 自定义中间件实现 `Http\MiddlewareInterface`，在路由 `middleware` 中使用别名。
- 提供 `BeforeDispatch` / `AfterDispatch` Hook 以扩展监控、埋点或统一处理。

### 日志与存储

- 动态文件写入目录：`typecho_api_plugin/runtime`（日志、限流计数、userMeta 等）。
- 若不需要日志，可在配置中关闭“记录日志”开关。

### 开发提示

- 插件通过 `Widget_Archive->begin` 提前接管请求，不影响现有主题/页面。
- 使用 Typecho 原生 `Typecho_Db` 查询，不新增额外数据表；banner 等字段通过 `table.fields` 存储。
- 所有服务 / 控制器 / 中间件都走 `Application` 容器，可在二次开发时复用。
- 若需要新增字段或响应数据，可在对应 Service 中扩展，再在 Controller 中返回即可。

> 建议启用 HTTPS，并妥善保存 Token/签名密钥。生产环境可将限流、日志与 Web 防火墙结合使用。
