<?php
namespace sunnnnn\wechat;

/**
 * @use: 微信错误显示类
 * @date: 2018/7/23 下午2:50
 * @author: sunnnnn [http://www.sunnnnn.com] [mrsunnnnn@qq.com]
 */
class Error{

    /**
     * 显示错误信息
     * @date: 2016-12-28 下午12:42:48
     * @author: sunnnnn
     * @param unknown $msg
     */
    public static function showError($title = '', $message = '', $back = false){
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
            <title><?= empty($title) ? '操作失败' : $title; ?></title>
            <link rel="stylesheet" href="//cdn.bootcss.com/weui/1.1.0/style/weui.min.css"/>
        </head>
        <body>
        <div class="page">
            <div class="weui-msg">
                <div class="weui-msg__icon-area"><i class="weui-icon-warn weui-icon_msg"></i></div>
                <div class="weui-msg__text-area">
                    <h2 class="weui-msg__title"><?= empty($title) ? '操作失败' : $title; ?></h2>
                    <p class="weui-msg__desc"><?= $message; ?></p>
                </div>
                <?php if($back === true){ ?>
                    <div class="weui-msg__opr-area">
                        <p class="weui-btn-area">
                            <a href="javascript:history.back();" class="weui-btn weui-btn_primary">确定</a>
                        </p>
                    </div>
                <?php } ?>
            </div>
        </div>
        </body>
        </html>
        <?php
        exit();
    }
}
