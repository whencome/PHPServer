<?php
interface IProtocol
{
    // 验证请求数据
    // @return int 0-成功,其它-失败.
    public static function verify($data);
    // 解码请求数据
    public static function decode($data);
    // 编码响应数据
    public static function encode($data);
}
