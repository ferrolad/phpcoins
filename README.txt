PHPCoins开源比特币交易平台系统

----功能特性-----

比特币转入转出

钱包自动生成

人民币充值提现

比特币买卖

实时挂单列表

实时交易列表

实时K线图

Google双重验证

支持SSL


-----安装需要--------

Linux 2.6+，Apache 2.4+，Mysql 5.6+, PHP 5.5+

PDO扩展

GD扩展

Bitcoind 0.8+


-----安装步骤---------

Ubuntu安装bitcoind

$ sudo add-apt-repository ppa:bitcoin/bitcoin

$ sudo apt-get update

$ sudo apt-get install bitcoin

复制 /usr/share/doc/bitcoind/examples/bitcoin.conf 到 Home目录/.bitcoin/bitcoin.conf

设置

testnet = 1  (不连接正式比特币网络，只使用比特币测试网络，生产环境不开启）

server=1    (以服务器方式长期运行，新用户注册，转入比特币都需要使用bitoind服务器来管理钱包)

rpcuser=root   (用户名，设置的用户名要和PHPCoins目录下config.php文件的 btc_user= 相同）

rpcpassword=aaa (密码，设置的密码要和PHPCoins目录下config.php文件的 btc_password= 相同）

rpcport=18332    (端口，测试网络的是18332，正式网络的是8332，设置的值要和PHPCoins目录下config.php文件的 btc_port= 相同）

rpcconnect=127.0.0.1  (主机，设置的值要和PHPCoins目录下config.php文件的 btc_host= 相同）


Apache,PHP,Mysql安装省略，需要注意的是PHP的版本为PHP5.5以上，否则不能正常工作



--------钱包部署-----------

冷存储方式生成足够数量的比特币地址池，把地址导入到bitcoind的数据目录，每次有新注册用户都会从地址池里取一个地址作为用户转入比特币的地址


--------PHPCoins安装--------

下载源码上传到Web主目录，解压，设置主目录权限为允许PHP进程读写，设置config.php文件里的参数，

admin_password 为后台登录密码

db_user 为数据库用户名

db_password 为数据库密码

db_name 为数据库名 

db_host 为数据库所在的主机

btc_protocol 为比特币服务器bitcoind协议可选 http 和 https

btc_user 为bitcoind的用户名

btc_password 为bitcoind的密码

btc_host 为bitcoind所在的主机

btc_port 为bitcoind的端口

text_logo 为文字LOGO

site_name 为网站名

smtp_host 为SMTP邮箱域名

smtp_port 为SMTP邮箱端口

smtp_user 为SMTP邮箱帐号

smtp_password 为SMTP邮箱密码


新建上面填写的 db_name 数据库，在浏览器打开网站会自动安装，后台的地址为 域名/?admin=1

如果需要重新安装先删除根目录下的install.lock文件

------------其他-----------

加入QQ群交流 53023431









