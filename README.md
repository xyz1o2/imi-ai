# imi-ai

<p align="center">
    <a href="https://www.imiphp.com" target="_blank">
        <img src="https://cdn.jsdelivr.net/gh/imiphp/imi-ai@master/res/logo.png" alt="imi" />
    </a>
</p>

[![Server test](https://github.com/imiphp/imi-ai/actions/workflows/server.yml/badge.svg)](https://github.com/imiphp/imi-ai/actions/workflows/server.yml)
[![Web test](https://github.com/imiphp/imi-ai/actions/workflows/web.yml/badge.svg)](https://github.com/imiphp/imi-ai/actions/workflows/web.yml)
[![Php Version](https://img.shields.io/badge/php-%3E=8.1-brightgreen.svg)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=5.0.3-brightgreen.svg)](https://github.com/swoole/swoole-src)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](https://github.com/imiphp/imi-ai/blob/master/LICENSE)

## 介绍

imi-ai 是一个 ChatGPT 开源项目，可以简单快速部署。

项目架构合理，代码编写优雅，不管是学习自用还是商用二开都很适合。

本项目现已支持 ChatGPT 聊天 AI 和 Embedding 模型训练对话。

项目采用 MIT 协议开源，你可以方便地进行二次开发，并且可以用于商业用途。

## 技术栈

后端基于 [imi](https://github.com/imiphp/imi) (PHP+Swoole)

前端基于 [Chanzhaoyu/chatgpt-web](https://github.com/Chanzhaoyu/chatgpt-web) (TypeScript+Vue3+Vite3+NaiveUI)

后台基于 [honghuangdc/soybean-admin](https://github.com/honghuangdc/soybean-admin) (TypeScript+Vue3+Vite3+NaiveUI)

## 示例

演示地址：<https://ai.imiphp.com>

## 功能列表

### 用户

* [x] 用户邮箱注册和登录
* [ ] 用户手机号注册和登录
* [ ] 微信登录（PC/公众号/小程序）

### 聊天 AI

* [x] ChatGPT 聊天 AI（OpenAI）
* [x] 服务端多会话储存和上下文逻辑
* [x] 渲染代码高亮
* [x] 渲染 LaTeX 公式
* [x] 保存消息到本地图片
* [x] 提示词模型商店
* [x] 支持限流

### 模型训练

* [x] OpenAI 多文件（压缩）模型训练
* [x] OpenAI 单文件模型训练
* [x] 聊天 AI 回答问题（可用于问题解答和客服等场景）
* [ ] 搜索引擎，可定位文件
* [x] 支持解压文件（zip、rar、7z、xz、gz、bz、tar.*）
* [x] 支持解析 txt 文件
* [x] 支持解析 md 文件
* [ ] 支持解析 doc/docx 文件
* [ ] 支持解析 pdf 文件
* [ ] 消息队列异步处理训练任务
* [x] 支持对话限流

### AI 生图

* [ ] OpenAI 图片生成
* [ ] Midjourney 图片生成

### 计费系统

* [x] Tokens 计费系统（卡）
* [ ] 在线支付购买卡
* [x] 输入卡号激活

### 其它

* [ ] 接口文档
* [ ] Docker 支持
* [ ] 视频讲解教程

更多功能计划中……

> 项目正在持续迭代中，欢迎所有人来贡献代码

## 安装

### 服务端

**目录：**`server`

**环境要求：**

* Linux / MacOS

* 7-Zip
（可选，使用模型训练必选。[下载](https://7-zip.org/download.html) 并将 `7zz` / `7zzs` 解压到 `/usr/bin/7z` 或 `/usr/local/bin/7z` 目录）

* PHP >= 8.1（扩展：curl、gd、mbstring、pdo_mysql、redis、swoole）

* Swoole >= v5.0.3

* MySQL >= 8.0.17

* Redis

* PostgreSQL + [pgvector](https://github.com/pgvector/pgvector) （可选，使用模型训练必选）

> 建议直接使用 swoole-cli，可在 [Swoole Release 下载](https://github.com/swoole/swoole-src/releases)。

**安装依赖：**

`composer update`

**生成证书：**

jwt 签名需要，必须生成自己的证书！

```shell
cd server/resource/jwt
openssl genrsa -out pri_key.pem 2048
openssl rsa -in pri_key.pem -pubout -out pub_key.pem
openssl genrsa -out admin_pri_key.pem 2048
openssl rsa -in admin_pri_key.pem -pubout -out admin_pub_key.pem
```

**配置文件：**

复制 **.env.tpl** 改名为 **.env** 文件。

根据文件内注释修改对应的配置。

**应用配置：**

后台-系统管理-系统设置

**导入 MySQL：**

首先创建 `db_imi_ai` 数据库，如果使用其它名称，需要在 `.env` 中修改。

执行生成表结构命令：

```shell
vendor/bin/imi-swoole generate/table
```

**导入 PostgreSQL：**

首先创建 `db_imi_ai` 数据库，如果使用其它名称，需要在 `.env` 中修改。

为 `db_imi_ai` 或你使用的数据库启用 `pgvector` 扩展：

```sql
CREATE EXTENSION pgvector;
```

导入 `pgsql.sql` 文件，创建表。

> 不使用模型训练功能，可以不配置 PostgreSQL。

**运行服务：**

```shell
vendor/bin/imi-swoole swoole/start
```

**生产环境：**

编辑 **.env** 文件。

必须的设置：

```env
# 生产环境禁用热更新
@app.beans.hotUpdate.status=0
# 生产环境禁用调试
APP_DEBUG=false
```

其它设置根据自身需要进行配置。

### 用户端H5

**目录：**`web`

**环境要求：**

`node` 需要 `^16 || ^18 || ^19` 版本（`node >= 14` 需要安装 [fetch polyfill](https://github.com/developit/unfetch#usage-as-a-polyfill)），使用 [nvm](https://github.com/nvm-sh/nvm) 可管理本地多个 `node` 版本

```shell
node -v
```

**安装依赖：**

```shell
npm install
```

> 也可以使用 yarn、pnpm 等。

**配置：**

复制 **.env.tpl** 改名为 **.env** 文件。

编辑 **.env** 文件。

* `VITE_GLOB_API_URL`，服务端接口地址，如：`http://127.0.0.1:12333/`

* `VITE_APP_API_BASE_URL` 前端调试访问地址，如：`http://127.0.0.1:1002/`

**开发调试：**

```shell
npm run dev
```

**生产环境：**

#### 编译

```shell
npm run build-only
```

> `npm run build` 也可以，但会执行类型检查，不规范的代码编译不通过。

#### 编译结果

所有文件都在 `dist` 目录，内部文件放到站点根目录。

### 管理后台

**目录：**`admin`

**环境要求：**

`node` 需要 `^16 || ^18 || ^19` 版本（`node >= 14` 需要安装 [fetch polyfill](https://github.com/developit/unfetch#usage-as-a-polyfill)），使用 [nvm](https://github.com/nvm-sh/nvm) 可管理本地多个 `node` 版本

```shell
node -v
```

**安装依赖：**

```shell
npm install
```

> 也可以使用 yarn、pnpm 等。

**配置：**

复制 **.env.tpl** 改名为 **.env** 文件。

编辑 **.env** 文件。

* `VITE_API_URL`，服务端接口地址，如：`http://127.0.0.1:12333`

**开发调试：**

```shell
npm run dev
```

**生产环境：**

#### 编译

```shell
npm run build
```

> `npm run build` 也可以，但会执行类型检查，不规范的代码编译不通过。

#### 编译结果

所有文件都在 `dist` 目录，内部文件放到站点根目录。

## 技术支持

QQ群：17916227

[![微信](https://cdn.jsdelivr.net/gh/imiphp/imi-ai@master/res/wechat.png)]

[![赞助](https://cdn.jsdelivr.net/gh/imiphp/imi-ai@master/res/pay.png)]
