<?php
namespace hathoora\helper
{
    use hathoora\container;

    /**
     * Class stringDetokenzierFilters
     * Collection of common string detokenizer filters
     *
     * @package hathoora\helper
     */
    class stringDetokenizerFilters
    {
        /**
         * Trims value
         *
         * @param $str
         * @return string
         */
        public static function trim($tokenValue)
        {
            return trim($tokenValue);
        }

        /**
         * Call custom filters
         *
         * @param $name
         * @param $args
         */
        public static function __callStatic($name, $args)
        {
            $tokenValue = $args[0];

            // get list of available filters
            if (($arrFilterClasses = container::getConfig('hathoora.detokenizerFilters')) && is_array($arrFilterClasses))
            {
                foreach($arrFilterClasses as $filterClass)
                {
                    if (is_callable(array($filterClass, $name)))
                        $tokenValue = call_user_func_array(array($filterClass, $name), $args);
                }
            }

            return $tokenValue;
        }
    }
}