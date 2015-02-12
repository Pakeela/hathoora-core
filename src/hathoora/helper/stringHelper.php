<?php
namespace hathoora\helper
{
    /**
     * A collection of string helpers
     */
    class stringHelper
    {
        /**
         * Returns the value of token
         *
         * @param string $token that we want to get the value for ex: {{name}}
         * @param array $arrTokens that has a key of token ex: 'name' = 'xyz'
         * @return mixed|string
         */
        public static function deTokenize($token, &$arrTokens)
        {
            $value = $token;

            // @todo figure out filters (and its params) in same regex
            if (is_array($arrTokens) && preg_match_all('/\{\{(.+?)\}\}/', $token, $arrMatch))
            {
                foreach ($arrMatch[1] as $token)
                {
                    // has callbacks?
                    $arrFilters = explode('|', $token);
                    $newToken = array_shift($arrFilters);
                    if (count($arrFilters))
                    {
                        $value = str_replace('{{'. $token .'}}', '{{' . $newToken .'}}', $value);
                        $token = $newToken;
                    }

                    if (isset($arrTokens[$token]))
                    {
                        $tokenValue = $arrTokens[$token];

                        // filers to apply?
                        if (count($arrFilters))
                        {
                            foreach($arrFilters as $filter)
                            {
                                // @todo more regex? need to figure out all in one regex...
                                // has filter params
                                if (preg_match('/^(.+?)\(\s{0,}(.+?)\s{0,}\)$/', $filter, $arrMatch))
                                {
                                    $filterParams = trim(array_pop($arrMatch));
                                    $filter = array_pop($arrMatch);
                                    $arrFilterParams = explode(',', $filterParams);
                                    array_unshift($arrFilterParams, $tokenValue);
                                    $tokenValue = call_user_func_array(array(__NAMESPACE__ . '\stringDetokenizerFilters', $filter), $arrFilterParams);
                                }
                                else
                                    $tokenValue = stringDetokenizerFilters::$filter($tokenValue);
                            }
                        }

                        $value = str_replace('{{'. $token .'}}', $tokenValue, $value);
                    }
                }
            }
            else if (isset($arrTokens[$value]))
                $value =& $arrTokens[$value];

            return $value;
        }

        /**
         * XOR Encryption
         *
         * @param string $string to obfuscae
         * @param string $keyPhrase to obfuscae with, use the same key to deobfuscate
         * @return string
         */
        public static function XOREncryption($string, $keyPhrase)
        {
            $string = (string) $string;
            $KeyPhraseLength = strlen($keyPhrase);

            // Loop trough input string
            for ($i = 0; $i < strlen($string); $i++){

                // Get key phrase character position
                $rPos = $i % $KeyPhraseLength;

                // Magic happens here:
                $r = ord($string[$i]) ^ ord($keyPhrase[$rPos]);

                // Replace characters
                $string[$i] = chr($r);
            }

            return $string;
        }

        /**
         * base64 encoding using XOR
         *
         * @param string $string to obfuscae
         * @param string $keyPhrase to obfuscae with, use the same key to deobfuscate
         * @return string
         */
        public static function obfuscate($string, $keyPhrase)
        {
            $string = self::XOREncryption($string, $keyPhrase);
            $string = base64_encode($string);

            return $string;
        }

        /**
         * base64 decoding using XOR
         *
         * @param string $string to obfuscae
         * @param string $keyPhrase to dobfuscae with
         * @return string
         */
        public static function deObfuscate($string, $keyPhrase)
        {
            $string = base64_decode($string);
            $string = self::XOREncryption($string, $keyPhrase);

            return $string;
        }

        /**
         * Slugify a string
         *
         * @param string $string
         * @return string
         */
        public static function slugify($string)
        {
            if (!empty($string))
            {
                // replace non letter or digits by -
                $string = preg_replace('~[^\\pL\d]+~u', '-', $string);

                // trim
                $string = trim($string, '-');

                // transliterate
                $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);

                // lowercase
                $string = mb_strtolower($string);

                // remove unwanted characters
                $string = preg_replace('~[^-\w]+~', '', $string);
            }

            return $string;
        }

        /**
         * Only strip tehse tags
         *
         * @param string $str
         * @param mixed $tags
         *          when not array: <p><h1>
         *          when array: array('p', 'h1')
         * @return mixed|string
         * @url: http://www.php.net/manual/en/function.strip-tags.php#93567
         */
        public static function stripTheseTags($str, $tags)
        {
            if(!is_array($tags))
            {
                $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
                if(end($tags) == '') array_pop($tags);
            }
            foreach($tags as $tag)
                $str = preg_replace('#</?'.$tag.'[^>]*>#is', '', $str);

            return $str;
        }

        /**
         * format bytes
         * @url http://stackoverflow.com/questions/2510434/format-bytes-to-kilobytes-megabytes-gigabytes
         */
        public static function formatBytes($size, $precision = 2)
        {
            $base = log($size) / log(1024);
            $suffixes = array('', 'k', 'M', 'G', 'T');

            return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
        }
    }
}