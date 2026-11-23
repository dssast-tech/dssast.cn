## dssast.cn

本仓库维护 DSSAST 网站的 WordPress 主题和插件。

### 目录结构

```text
dssast.cn/
├── .github/workflows/       # CI/CD 配置
├── playground/              # 本地测试配置及数据
├── plugins/
│   ├── dssast-cms/
│   └── dssast-core/
└── themes/
    └── dssast/              # 站点主题
        └── src/             # SCSS 和 JavaScript 源码
```

### 本地调试

在 Node.js 22 和 npm 环境下，在仓库根目录运行以下命令：

```sh
npm run start
```

待终端显示 `Ready!` 后，打开 <http://127.0.0.1:9400/> 即可进行测试。

| 修改内容 | 操作 |
| --- | --- |
| PHP | 刷新浏览器 |
| SCSS | Sass 自动重新编译，编译完成后刷新浏览器 |
| JavaScript | 运行 `npm run build:theme` 后刷新浏览器 |
| 新增插件/其他 | 重启 Playground |

### CI/CD

推送到 `main` 分支时，GitHub Actions 会自动构建主题前端代码，并通过 SSH 部署到服务器，无需手动操作生产环境。
