<?php
/**
 * RpcException
 */
class RpcException extends \Exception
{
    private $errors;

    /**
     * 构造业务异常类.
     */
    public function __construct($message, $code = 0)
    {
        $args = func_get_args();

        if (is_array($message)) {
            if (empty($message)) {
                throw new \Exception('You won\'t throw RpcException with an empty array.');
            }
            $this->errors = $message;
            $args[0] = 'Business Errors';
        }

        call_user_func_array(array($this, 'parent::__construct'), $args);
    }

    /**
     * 检查是否为错误 key/values 对.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * 返回错误 key/values 对.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
