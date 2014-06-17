<?php
namespace hathoora\helper
{
    /**
     * A collection of date helpers
     */
    class dateHelper
    {
        /**
         * Mysql's NOW() equivalent
         */
        public static function now()
        {
            return date('Y-m-d H:i:s');
        }
    }
}