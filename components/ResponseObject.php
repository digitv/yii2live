<?php

namespace digitv\yii2live\components;

/**
 * Interface ResponseObject
 */
interface ResponseObject
{
    /**
     * @return array|string
     */
    public function getResponseData();

    /**
     * @return string
     */
    public function getResponseType();
}