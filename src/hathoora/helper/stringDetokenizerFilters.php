<?php
namespace hathoora\helper
{
    use hathoora\container,
        hathoora\logger\logger;

    /**
     * Class stringDetokenzierFilters
     * Collection of common string detokenizer filters
     *
     * @package hathoora\helper
     */
    class stringDetokenizerFilters
    {
        /**
         * Call custom filters
         *
         * @param $name
         * @param $args
         */
        public static function __callStatic($name, $args)
        {
            $tokenValue = $args[0];
            $foundFilter = null;
            
            // check if is a valid callback to begin with
            if (function_exists($name))
            {
                $tokenValue = call_user_func_array($name, $args);
                $foundFilter = true;            
            }

            // get list of available filters
            if (!$foundFilter && ($arrFilterClasses = container::getConfig('hathoora.detokenizerFilters')) && is_array($arrFilterClasses))
            {
                foreach($arrFilterClasses as $filterClass)
                {
                    if ($foundFilter)
                        break;
                        
                    if (is_callable(array($filterClass, $name)))
                    {
                        $tokenValue = call_user_func_array(array($filterClass, $name), $args);
                        $foundFilter = true;
                    }
                }
            }
            
            // filter not found
            if (!$foundFilter)
                logger::log(logger::LEVEL_WARNING, 'Detokenize: unable to apply filter: <i>' . $name . '</i>');

            return $tokenValue;
        }
    }
}